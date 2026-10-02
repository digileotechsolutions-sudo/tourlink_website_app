<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\PaymentStatus;
use App\Role;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    public function show(Request $request, Payment $payment, PaymentService $payments): View
    {
        $this->authorizeReceipt($request, $payment);
        abort_unless(in_array($payment->status, [
            PaymentStatus::Successful,
            PaymentStatus::Refunded,
            PaymentStatus::PartiallyRefunded,
        ], true), 404);

        $payment = $payments->ensureReceipt($payment);
        $payment->load(['booking.traveler', 'booking.trip.destination', 'booking.vehicle.destination', 'receiver']);
        $summary = $payments->summary($payment->booking);
        $previousPaid = max(0, $summary['paid'] - $payment->amount);

        return view('payments.receipt', compact('payment', 'summary', 'previousPaid'));
    }

    public function download(Request $request, Payment $payment, PaymentService $payments): StreamedResponse
    {
        $this->authorizeReceipt($request, $payment);
        abort_unless(in_array($payment->status, [
            PaymentStatus::Successful,
            PaymentStatus::Refunded,
            PaymentStatus::PartiallyRefunded,
        ], true), 404);

        $payment = $payments->ensureReceipt($payment);
        $payment->load(['booking.traveler', 'booking.trip.destination', 'booking.vehicle.destination', 'receiver']);
        $summary = $payments->summary($payment->booking);
        $previousPaid = max(0, $summary['paid'] - $payment->amount);
        $html = view('payments.receipt', compact('payment', 'summary', 'previousPaid'))->render();
        $filename = 'receipt-'.($payment->receipt_number ?: $payment->merchant_reference).'.html';

        return response()->streamDownload(
            static function () use ($html): void {
                echo $html;
            },
            $filename,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    private function authorizeReceipt(Request $request, Payment $payment): void
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->role === Role::Admin || $payment->booking->traveler_id === $user->id),
            404,
        );
    }
}
