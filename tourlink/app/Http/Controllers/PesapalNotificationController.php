<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PesapalPaymentVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PesapalNotificationController extends Controller
{
    public function __invoke(Request $request, PesapalPaymentVerifier $verifier): JsonResponse
    {
        $trackingId = $request->string('OrderTrackingId')->toString();
        if ($trackingId === '') {
            return response()->json(['status' => 'missing tracking id'], 400);
        }

        $payment = Payment::query()
            ->where('provider', 'PESAPAL')
            ->where(function ($query) use ($trackingId): void {
                $query->where('transaction_reference', $trackingId)
                    ->orWhere('metadata->order_tracking_id', $trackingId);
            })
            ->firstOrFail();
        $status = $verifier->verify($payment, $trackingId);
        if ($status === 'mismatch') {
            return response()->json(['status' => 'verification mismatch'], 409);
        }

        return response()->json(['status' => 'OK', 'payment_status' => $status]);
    }
}
