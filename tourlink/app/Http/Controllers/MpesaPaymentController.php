<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\PaymentMethod;
use App\Services\Payments\MpesaGateway;
use App\Services\Payments\PaymentService;
use App\Services\Verification\PhoneNumberNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MpesaPaymentController extends Controller
{
    public function stk(
        Request $request,
        PhoneNumberNormalizer $phoneNormalizer,
        MpesaGateway $gateway,
        PaymentService $payments,
    ): JsonResponse {
        $input = $request->validate([
            'booking_id' => ['required', 'string', 'exists:bookings,id'],
            'phone_number' => ['required', 'string', 'min:9', 'max:30'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $phoneNumber = $phoneNormalizer->normalize($input['phone_number']);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $user = $request->user();
        $booking = Booking::query()
            ->whereKey($input['booking_id'])
            ->where('traveler_id', $user->id)
            ->firstOrFail();
        $amount = (int) ($input['amount'] ?? $payments->summary($booking)['balance']);

        $payment = $payments->createAttempt(
            $booking,
            PaymentMethod::Mpesa,
            $amount,
            'MPESA',
            $user,
            $phoneNumber,
        );
        $payment = $payments->markProcessing($payment);

        try {
            $result = $gateway->initiate($payment, ['phone_number' => $phoneNumber]);
            $payment->forceFill([
                'daraja_checkout_request_id' => $result['checkoutRequestId'],
                'provider_response' => $result,
            ])->save();
            $payments->recordProviderEvent($payment, 'MPESA_STK_INITIATED', [
                'checkout_request_id' => $result['checkoutRequestId'],
            ]);

            return response()->json(['ok' => true, 'checkoutRequestId' => $result['checkoutRequestId']]);
        } catch (Throwable $exception) {
            report($exception);
            $payments->markFailed($payment, 'We could not start the M-Pesa request.');

            return response()->json(['error' => 'We could not start the M-Pesa request.'], 502);
        }
    }
}
