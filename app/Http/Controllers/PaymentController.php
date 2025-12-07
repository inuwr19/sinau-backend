<?php

namespace App\Http\Controllers;

use App\Models\Payment;
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
     * Show payment
     */
    public function show($id)
    {
        $payment = Payment::with('order')->findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Example webhook handler (for card/qris providers)
     */
    public function webhook(Request $request)
    {
        // Implement provider-specific handling here.
        // Validate signature, find order, create payment record, update order status.
        return response()->json(['ok']);
    }
}
