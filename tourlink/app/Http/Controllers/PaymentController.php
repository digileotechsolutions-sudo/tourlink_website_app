<?php

namespace App\Http\Controllers;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\PaymentMethod;
use App\Services\Payments\MpesaGateway;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PesapalGateway;
use App\Services\Verification\PhoneNumberNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class PaymentController extends Controller
{
    public function show(Request $request, Booking $booking, PaymentService $payments): View
    {
        $this->authorizeTraveler($request, $booking);
        $booking->load(['trip.destination', 'vehicle.destination', 'payments.refunds', 'payments.receiver']);

        return view('payments.show', [
            'booking' => $booking,
            'summary' => $payments->summary($booking),
            'canPay' => ! in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Refunded], true),
        ]);
    }

    public function startMpesa(
        Request $request,
        Booking $booking,
        PaymentService $payments,
        PhoneNumberNormalizer $phoneNormalizer,
        MpesaGateway $gateway,
    ): RedirectResponse {
        $this->authorizeTraveler($request, $booking);
        $input = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'phone_number' => ['required', 'string', 'min:9', 'max:30'],
        ]);

        try {
            $phone = $phoneNormalizer->normalize($input['phone_number']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['phone_number' => $exception->getMessage()]);
        }

        $payment = $payments->createAttempt(
            $booking,
            PaymentMethod::Mpesa,
            (int) $input['amount'],
            'MPESA',
            $request->user(),
            $phone,
        );
        $payment = $payments->markProcessing($payment);

        try {
            $result = $gateway->initiate($payment, ['phone_number' => $phone]);
            $payment->forceFill([
                'daraja_checkout_request_id' => $result['checkoutRequestId'],
                'provider_response' => $result,
            ])->save();
            $payments->recordProviderEvent($payment, 'MPESA_STK_INITIATED', [
                'checkout_request_id' => $result['checkoutRequestId'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $payments->markFailed($payment, 'We could not start the M-Pesa request.');

            return back()->withErrors(['payment' => 'We could not start the M-Pesa request. Please try again.']);
        }

        return redirect()->route('payments.show', $booking)
            ->with('status', 'Check your phone and approve the M-Pesa prompt. The booking balance will update after confirmation.');
    }

    public function startCard(
        Request $request,
        Booking $booking,
        PaymentService $payments,
        PesapalGateway $gateway,
    ): RedirectResponse {
        $this->authorizeTraveler($request, $booking);
        $input = $request->validate(['amount' => ['required', 'integer', 'min:1']]);
        $payment = $payments->createAttempt(
            $booking,
            PaymentMethod::Card,
            (int) $input['amount'],
            'PESAPAL',
            $request->user(),
        );
        $payment = $payments->markProcessing($payment);

        try {
            $name = preg_split('/\s+/', trim($request->user()->name), 2) ?: [];
            $checkout = $gateway->initiate($payment, [
                'email' => $request->user()->email,
                'phone_number' => $request->user()->phone,
                'first_name' => $name[0] ?? 'Traveler',
                'last_name' => $name[1] ?? 'Traveler',
            ]);
            $payment->forceFill([
                'transaction_reference' => $checkout['order_tracking_id'],
                'provider_response' => $checkout,
                'metadata' => [
                    'order_tracking_id' => $checkout['order_tracking_id'],
                    'merchant_reference' => $payment->merchant_reference,
                ],
            ])->save();
            $payments->recordProviderEvent($payment, 'PESAPAL_CHECKOUT_CREATED', [
                'order_tracking_id' => $checkout['order_tracking_id'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $payments->markFailed($payment, 'We could not start the secure card checkout.');

            return back()->withErrors(['payment' => 'We could not start the secure card checkout. Please try again later.']);
        }

        return redirect()->away($checkout['redirect_url']);
    }

    public function pesapalReturn(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeTraveler($request, $payment->booking);

        return redirect()->route('payments.show', $payment->booking)
            ->with('status', 'Card payment is being verified by Pesapal. Refresh this page in a moment to see the result.');
    }

    private function authorizeTraveler(Request $request, Booking $booking): void
    {
        abort_unless($request->user() && $booking->traveler_id === $request->user()->id, 404);
    }
}
