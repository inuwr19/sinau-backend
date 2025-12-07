<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PointsHistory;
use App\Services\OrderNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create order (and optionally pay)
     * POST /api/orders
     *
     * Payload example:
     * {
     *   "items": [
     *     {"menu_item_id":1,"qty":2,"notes":"less sugar"},
     *     {"menu_item_id":3,"qty":1}
     *   ],
     *   "member_phone":"0811000001", // optional
     *   "payment_method":"cash|card|qris", // optional
     *   "cash_received":150000 // optional if cash payment
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'member_phone' => 'nullable|string',
            'payment_method' => 'nullable|in:cash,card,qris',
            'cash_received' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();

        // Fetch member if provided
        $member = null;
        if ($request->filled('member_phone')) {
            $phone = preg_replace('/\D+/', '', $request->member_phone);
            $member = Member::where('phone', $phone)->first();
        }

        // compute totals and build order items
        $subtotal = 0;
        $itemsData = [];

        foreach ($request->items as $it) {
            $menu = MenuItem::findOrFail($it['menu_item_id']);
            $unit = $menu->price;
            $qty = (int) $it['qty'];
            $total = $unit * $qty;
            $subtotal += $total;

            $itemsData[] = [
                'menu_item_id' => $menu->id,
                'qty' => $qty,
                'unit_price' => $unit,
                'total_price' => $total,
                'notes' => $it['notes'] ?? null,
            ];
        }

        $discount = 0;
        if ($member) {
            $discount = round($subtotal * 0.10, 2); // 10% member discount
        }

        $tax = 0; // set if needed
        $total = $subtotal - $discount + $tax;

        DB::beginTransaction();
        try {
            // generate order number (race-safe)
            $orderNumber = OrderNumberGenerator::generateForBranch($user->branch_id);

            $order = Order::create([
                'order_number' => $orderNumber,
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'member_id' => $member ? $member->id : null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => $request->filled('payment_method') ? 'paid' : 'pending',
                'payment_method' => $request->payment_method ?? null,
                'cash_received' => $request->payment_method === 'cash' ? ($request->cash_received ?? null) : null,
                'change' => null,
            ]);

            foreach ($itemsData as $d) {
                $order->items()->create($d);
            }

            // If payment provided, record payment & handle cash change
            if ($request->filled('payment_method')) {
                $paymentAmount = $total;
                $meta = null;
                if ($request->payment_method === 'cash') {
                    $cashReceived = $request->cash_received ?? 0;
                    $change = max(0, $cashReceived - $total);
                    $order->update(['change' => $change, 'cash_received' => $cashReceived]);
                }

                $payment = Payment::create([
                    'order_id' => $order->id,
                    'method' => $request->payment_method,
                    'amount' => $paymentAmount,
                    'meta' => $meta,
                ]);

                // member points awarding
                if ($member) {
                    $pointsEarned = floor($total / 75000) * 10; // 10 points per 75k
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

            DB::commit();

            return response()->json($order->load('items', 'member'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show single order (RLS will apply automatically)
     * GET /api/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $order = Order::with(['items.menuItem', 'member', 'payments', 'user'])->findOrFail($id);
        return response()->json($order);
    }

    /**
     * Pay an existing order
     * POST /api/orders/{id}/pay
     * body: payment_method, cash_received (if cash)
     */
    public function pay(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,qris',
            'cash_received' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
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

            // award member points if exists
            if ($order->member_id) {
                $member = Member::find($order->member_id);
                if ($member) {
                    $pointsEarned = floor($order->total / 75000) * 10;
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

            DB::commit();

            return response()->json($order->load('items', 'member', 'payments'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }
}
