<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PesapalPaymentVerifier;
use App\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPaymentController extends Controller
{
    public function recordCash(Request $request, Booking $booking, PaymentService $payments): RedirectResponse
    {
        $input = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $payments->recordManualPayment(
            $booking,
            $request->user(),
            PaymentMethod::Cash,
            (int) $input['amount'],
            null,
            $input['notes'] ?? null,
            $request->ip(),
        );

        return redirect()->route('admin.bookings.show', $booking)
            ->with('status', "Cash payment recorded. Receipt {$payment->receipt_number}.");
    }

    public function recordBankTransfer(Request $request, Booking $booking, PaymentService $payments): RedirectResponse
    {
        $input = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['required', 'string', 'max:120', Rule::unique('payments', 'transaction_reference')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payments->recordManualPayment(
            $booking,
            $request->user(),
            PaymentMethod::BankTransfer,
            (int) $input['amount'],
            $input['reference'],
            $input['notes'] ?? null,
            $request->ip(),
        );

        return redirect()->route('admin.bookings.show', $booking)
            ->with('status', 'Bank transfer recorded as pending verification.');
    }

    public function verifyBankTransfer(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $payments->verifyBankTransfer($payment, $request->user(), $request->ip());

        return redirect()->route('admin.bookings.show', $payment->booking)
            ->with('status', 'Bank transfer verified and applied to the booking balance.');
    }

    public function verifyPesapal(Request $request, Payment $payment, PesapalPaymentVerifier $verifier): RedirectResponse
    {
        $trackingId = data_get($payment->metadata, 'order_tracking_id');
        abort_unless(is_string($trackingId) && $trackingId !== '', 404);

        $status = $verifier->verify($payment, $trackingId, $request->user(), $request->ip());
        $message = match ($status) {
            'successful' => 'Pesapal confirmed the payment.',
            'failed' => 'Pesapal confirmed that the payment failed.',
            'mismatch' => 'Pesapal returned payment details that do not match this order; the payment was not applied.',
            default => 'Pesapal has not confirmed a final payment status yet.',
        };

        return redirect()->route('admin.bookings.show', $payment->booking)->with('status', $message);
    }

    public function requestRefund(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $input = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $refund = $payments->createRefund($payment, $request->user(), (int) $input['amount'], $input['reason'], $request->ip());

        return redirect()->route('admin.bookings.show', $payment->booking)
            ->with('status', "Refund {$refund->reference} recorded as pending. Complete the refund with the provider before marking it completed.");
    }

    public function completeRefund(Request $request, Refund $refund, PaymentService $payments): RedirectResponse
    {
        $input = $request->validate([
            'provider_reference' => ['required', 'string', 'max:120'],
        ]);

        $payments->completeRefund($refund, $request->user(), $input['provider_reference'], $request->ip());

        return redirect()->route('admin.bookings.show', $refund->payment->booking)
            ->with('status', 'Refund marked completed and included in the booking balance.');
    }
}
