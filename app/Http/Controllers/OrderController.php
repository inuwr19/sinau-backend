<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PointsHistory;
use App\Services\OrderNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // ✅ tambah ini
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

class OrderController extends Controller
{
    /**
     * Midtrans init
     */
    private function initMidtrans(): void
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized = config('midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = config('midtrans.is_3ds', true);
    }

    /**
     * Generate order_id yang unik untuk Midtrans (lintas device / DB)
     * Contoh: CS-20260203-PST-006-9F3K1Z2A0B
     */
    private function ensureMidtransOrderId(Order $order): void
    {
        if (!empty($order->midtrans_order_id)) {
            return;
        }

        $suffix = substr((string) Str::ulid(), -10); // pendek, tetap unik
        $order->midtrans_order_id = "{$order->order_number}-{$suffix}";
        $order->save();
    }

    /**
     * Buat Snap token Midtrans untuk order ini
     */
    private function createMidtransSnapToken(Order $order, ?Member $member, string $cashierName, string $channel): string
    {
        $this->initMidtrans();

        $enabledPayments = match ($channel) {
            'va' => ['bca_va'],
            'qris' => ['qris', 'gopay', 'shopeepay', 'other_qris'],
            default => ['bca_va', 'qris', 'gopay', 'shopeepay', 'other_qris'],
        };

        $params = [
            'transaction_details' => [
                // ✅ WAJIB unik di Midtrans (JANGAN pakai order_number langsung)
                'order_id' => $order->midtrans_order_id,
                'gross_amount' => (int) $order->total,
            ],
            'customer_details' => [
                'first_name' => $member?->name ?? $cashierName,
                'email' => $member?->email ?? null,
                'phone' => $member?->phone ?? null,
            ],
            'enabled_payments' => $enabledPayments,
        ];

        return Snap::getSnapToken($params);
    }

    /**
     * Create order (dan optional: trigger Midtrans)
     * POST /api/orders
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'member_phone' => 'nullable|string',
            'payment_method' => 'nullable|in:cash,va,qris',
            'cash_received' => 'nullable|numeric|min:0',
            'redeem_points' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Fetch member jika ada
        $member = null;
        if ($request->filled('member_phone')) {
            $phone = preg_replace('/\D+/', '', $request->member_phone);
            $member = Member::where('phone', $phone)->first();
        }

        // Hitung subtotal dan build data item
        $subtotal = 0;
        $itemsData = [];

        foreach ($request->items as $it) {
            $menu = MenuItem::findOrFail($it['menu_item_id']);
            $unit = $menu->price;
            $qty = (int) $it['qty'];
            $rowTotal = $unit * $qty;

            $subtotal += $rowTotal;

            $itemsData[] = [
                'menu_item_id' => $menu->id,
                'qty' => $qty,
                'unit_price' => $unit,
                'total_price' => $rowTotal,
                'notes' => $it['notes'] ?? null,
            ];
        }

        $tax = 0;

        // Diskon member 10%
        $memberDiscount = 0;
        if ($member) {
            $memberDiscount = round($subtotal * 0.10, 2);
        }

        // Diskon tukar poin
        $redeemedPoints = 0;
        $redeemDiscount = 0;
        $totalBeforeRedeem = $subtotal - $memberDiscount + $tax;

        if (
            $member &&
            $request->boolean('redeem_points') &&
            $member->points >= 100 &&
            $totalBeforeRedeem >= 30000
        ) {
            $redeemedPoints = 100;
            $redeemDiscount = 30000.00;
        }

        $discount = $memberDiscount + $redeemDiscount;
        $total = $subtotal - $discount + $tax;

        DB::beginTransaction();

        try {
            // generate order number
            $orderNumber = OrderNumberGenerator::generateForBranch($user->branch_id);

            // Status awal: cash -> paid, selain itu -> pending
            $status = $request->payment_method === 'cash' ? 'paid' : 'pending';

            $order = Order::create([
                'order_number' => $orderNumber,
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'member_id' => $member ? $member->id : null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => $status,
                'payment_method' => $request->payment_method ?? null,
                'cash_received' => $request->payment_method === 'cash' ? ($request->cash_received ?? null) : null,
                'change' => null,
                'redeemed_points' => $redeemedPoints,
                'redeem_discount' => $redeemDiscount,
                // ✅ midtrans_order_id diisi saat VA/QRIS
                'midtrans_order_id' => null,
            ]);

            foreach ($itemsData as $d) {
                $order->items()->create($d);
            }

            $snapToken = null;

            // Jika cash: langsung create Payment & hitung kembalian
            if ($request->payment_method === 'cash') {
                $paymentAmount = $total;
                $cashReceived = $request->cash_received ?? 0;
                $change = max(0, $cashReceived - $total);

                $order->update([
                    'cash_received' => $cashReceived,
                    'change' => $change,
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'method' => 'cash',
                    'amount' => $paymentAmount,
                    'meta' => null,
                ]);

                // Poin member (tetap seperti punyamu)
                if ($member) {
                    if ($redeemedPoints > 0 && $redeemDiscount > 0) {
                        $member->decrement('points', $redeemedPoints);

                        PointsHistory::create([
                            'member_id' => $member->id,
                            'order_id' => $order->id,
                            'points_change' => -$redeemedPoints,
                            'reason' => 'Redeemed for Rp 30.000 discount',
                        ]);
                    }

                    $threshold = 100000;
                    $pointsPerThreshold = 10;
                    $pointsEarned = intdiv((int) $total, $threshold) * $pointsPerThreshold;

                    if ($pointsEarned > 0) {
                        $member->increment('points', $pointsEarned);

                        PointsHistory::create([
                            'member_id' => $member->id,
                            'order_id' => $order->id,
                            'points_change' => $pointsEarned,
                            'reason' => 'Earned for purchase',
                        ]);
                    }
                }
            }

            // ✅ Jika VA / QRIS: buat order_id unik untuk Midtrans + Snap token
            if (in_array($request->payment_method, ['va', 'qris'], true)) {
                $this->ensureMidtransOrderId($order);
                $snapToken = $this->createMidtransSnapToken($order, $member, $user->name, $request->payment_method);

                $order->snap_token = $snapToken;
                $order->save();
            }

            DB::commit();

            return response()->json([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'snap_token' => $snapToken,
                'midtrans_order_id' => $order->midtrans_order_id,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $order = Order::with(['items.menuItem', 'member', 'payments', 'user'])->findOrFail($id);
        return response()->json($order);
    }

    public function pay(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,va,qris',
            'cash_received' => 'nullable|numeric|min:0',
        ]);

        $order = Order::findOrFail($id);

        if ($order->status === 'paid') {
            return response()->json(['message' => 'Order already paid'], 400);
        }

        DB::beginTransaction();

        try {
            $order->payment_method = $request->payment_method;

            if ($request->payment_method === 'cash') {
                $cashReceived = $request->cash_received ?? 0;
                $order->cash_received = $cashReceived;
                $order->change = max(0, $cashReceived - $order->total);
            }

            $order->status = 'paid';
            $order->save();

            Payment::create([
                'order_id' => $order->id,
                'method' => $request->payment_method,
                'amount' => $order->total,
                'meta' => null,
            ]);

            // points logic tetap seperti punyamu
            if ($order->member_id) {
                $member = Member::find($order->member_id);
                if ($member) {
                    if ($order->redeemed_points > 0 && $order->redeem_discount > 0) {
                        $alreadyRedeemed = PointsHistory::where('order_id', $order->id)
                            ->where('points_change', '<', 0)
                            ->exists();

                        if (!$alreadyRedeemed) {
                            $member->decrement('points', $order->redeemed_points);

                            PointsHistory::create([
                                'member_id' => $member->id,
                                'order_id' => $order->id,
                                'points_change' => -$order->redeemed_points,
                                'reason' => 'Redeemed for Rp 30.000 discount',
                            ]);
                        }
                    }

                    $alreadyEarned = PointsHistory::where('order_id', $order->id)
                        ->where('points_change', '>', 0)
                        ->exists();

                    if (!$alreadyEarned) {
                        $threshold = 100000;
                        $pointsPerThreshold = 10;

                        $pointsEarned = intdiv((int) $order->total, $threshold) * $pointsPerThreshold;

                        if ($pointsEarned > 0) {
                            $member->increment('points', $pointsEarned);

                            PointsHistory::create([
                                'member_id' => $member->id,
                                'order_id' => $order->id,
                                'points_change' => $pointsEarned,
                                'reason' => 'Earned for purchase',
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json($order->load('items', 'member', 'payments'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }
}
