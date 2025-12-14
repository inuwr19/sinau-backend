<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PointsHistory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    /**
     * List payments (admin)
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class); // optional policy
        $payments = Payment::with('order')->latest()->paginate(25);
        return response()->json($payments);
    }

    /**
     * Show payment detail
     */
    public function show($id)
    {
        $payment = Payment::with('order')->findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Local "webhook" / callback dari Midtrans Snap (dipanggil dari frontend)
     * Endpoint: POST /api/payments/midtrans/confirm
     */
    public function midtransConfirm(Request $request)
    {
        $payload = $request->all();

        // Data standar dari Midtrans Snap (onSuccess / onPending)
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        // Untuk local confirm dari frontend:
        // Wajib minimal punya order_id, status_code, gross_amount
        if (!$orderId || !$statusCode || !$grossAmount) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Signature hanya dicek JIKA ada.
        // Di Snap callback yang Anda kirim sekarang, memang tidak ada signature_key,
        // jadi blok ini akan dilewati.
        if ($signatureKey) {
            $serverKey = config('midtrans.server_key');
            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($signatureKey !== $expectedSignature) {
                \Log::warning('Midtrans confirm invalid signature', [
                    'expected' => $expectedSignature,
                    'given' => $signatureKey,
                ]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        // order_id Midtrans = order_number kita
        $order = Order::where('order_number', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Map status Midtrans -> status order lokal
        if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
            // Untuk skripsi, anggap langsung PAID
            $order->status = 'paid';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'], true)) {
            $order->status = 'cancelled';
        } else {
            // pending / lainnya
            $order->status = 'pending';
        }

        // Map payment_type + detail VA jadi method yang lebih jelas
        // Contoh payload Anda:
        //  "payment_type": "bank_transfer",
        //  "va_numbers":[{"bank":"bca","va_number":"..."}]
        $method = $order->payment_method;
        if ($paymentType) {
            if ($paymentType === 'bank_transfer' && !empty($payload['va_numbers'][0]['bank'])) {
                // bca_va, permata_va, dll
                $bank = $payload['va_numbers'][0]['bank'];
                $method = $bank . '_va';
            } else {
                // qris, gopay, shopeepay, dll
                $method = $paymentType;
            }
        }

        $order->payment_method = $method;
        $order->save();

        // Simpan / update Payment record
        Payment::updateOrCreate(
            [
                'order_id' => $order->id,
                'method' => $method,
            ],
            [
                'amount' => $order->total,
                'meta' => $payload, // pastikan kolom meta = JSON/TEXT
            ]
        );

        // Berikan poin member (sekali saja ketika status settle/capture)
        if ($order->member_id && in_array($transactionStatus, ['capture', 'settlement'], true)) {
            $member = Member::find($order->member_id);
            if ($member) {
                // 1) redeem kalau order ini pakai poin
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
                            'reason' => 'Redeemed for Rp 30.000 discount (Midtrans)',
                        ]);
                    }
                }

                // 2) earn poin
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
                            'reason' => 'Earned for purchase (Midtrans local confirm)',
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'ok',
            'order' => $order->load('payments'),
        ]);
    }


    /**
     * (Opsional) Webhook murni dari Midtrans (kalau nanti Anda deploy online)
     */
    public function webhook(Request $request)
    {
        // Implementasi webhook kalau suatu saat dipakai (ngrok / domain publik).
        return response()->json(['ok']);
    }
}
