<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\PaymentStatus;
use App\Services\Payments\MpesaDarajaGateway;
use App\Services\Verification\PhoneNumberNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class MpesaPaymentController extends Controller
{
    public function stk(
        Request $request,
        PhoneNumberNormalizer $phoneNormalizer,
        MpesaDarajaGateway $gateway,
    ): JsonResponse {
        $input = $request->validate([
            'booking_id' => ['required', 'string', 'exists:bookings,id'],
            'phone_number' => ['required', 'string', 'min:9', 'max:30'],
        ]);

        try {
            $phoneNumber = $phoneNormalizer->normalize($input['phone_number']);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $user = $request->user();
        $payment = DB::transaction(function () use ($input, $user, $phoneNumber) {
            $booking = Booking::query()
                ->whereKey($input['booking_id'])
                ->where('traveler_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                abort(404);
            }

            $payment = $booking->payments()->lockForUpdate()->latest('created_at')->first();
            if (! $payment || ! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed], true)) {
                abort(409, 'Payment request is no longer available.');
            }

            $payment->forceFill([
                'status' => PaymentStatus::Processing,
                'phone_number' => $phoneNumber,
                'failure_reason' => null,
            ])->save();

            return $payment;
        });

        try {
            $result = $gateway->requestStkPush($payment, $phoneNumber);
            $payment->forceFill([
                'daraja_checkout_request_id' => $result['checkoutRequestId'],
                'provider_response' => $result,
            ])->save();

            return response()->json(['ok' => true, 'checkoutRequestId' => $result['checkoutRequestId']]);
        } catch (Throwable $exception) {
            report($exception);
            $payment->forceFill([
                'status' => PaymentStatus::Failed,
                'failure_reason' => 'We could not start the M-Pesa request.',
            ])->save();

            return response()->json(['error' => 'We could not start the M-Pesa request.'], 502);
        }
    }
}
