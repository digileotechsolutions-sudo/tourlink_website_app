<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentAudit;
use App\PaymentStatus;
use App\Services\Verification\PhoneNumberNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

class MpesaCallbackService
{
    public function __construct(
        private readonly PhoneNumberNormalizer $phoneNormalizer,
        private readonly MpesaDarajaGateway $gateway,
        private readonly PaymentService $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    public function handle(array $payload): string
    {
        $callback = data_get($payload, 'Body.stkCallback');
        $checkoutRequestId = is_array($callback) ? ($callback['CheckoutRequestID'] ?? null) : null;
        $callbackHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        if (PaymentAudit::query()->where('callback_hash', $callbackHash)->exists()) {
            return 'duplicate';
        }

        $payment = is_string($checkoutRequestId)
            ? Payment::query()->where('daraja_checkout_request_id', $checkoutRequestId)->first()
            : null;
        $providerVerification = null;

        if ($payment && is_array($callback)) {
            $providerVerification = $this->gateway->queryStkStatus($payment);
            $providerResultCode = $providerVerification['ResultCode'] ?? null;
            $callbackResultCode = $callback['ResultCode'] ?? null;
            $providerCheckoutId = $providerVerification['CheckoutRequestID'] ?? null;

            if (! is_numeric($providerResultCode)
                || ! is_numeric($callbackResultCode)
                || (int) $providerResultCode !== (int) $callbackResultCode
                || (is_string($providerCheckoutId)
                    && (! is_string($checkoutRequestId) || ! hash_equals($checkoutRequestId, $providerCheckoutId)))) {
                return 'verification_pending';
            }
        }

        try {
            return DB::transaction(function () use ($payload, $callback, $checkoutRequestId, $callbackHash, $providerVerification): string {
                if (PaymentAudit::query()->where('callback_hash', $callbackHash)->exists()) {
                    return 'duplicate';
                }

                $payment = is_string($checkoutRequestId)
                    ? Payment::query()->where('daraja_checkout_request_id', $checkoutRequestId)->lockForUpdate()->first()
                    : null;

                $audit = PaymentAudit::query()->create([
                    'payment_id' => $payment?->id,
                    'event_type' => 'CALLBACK_RECEIVED',
                    'checkout_request_id' => is_string($checkoutRequestId) ? $checkoutRequestId : null,
                    'callback_hash' => $callbackHash,
                    'payload' => ['callback' => $payload, 'provider_verification' => $providerVerification],
                ]);

                if (! $payment || ! is_array($callback)) {
                    $audit->forceFill(['event_type' => 'CALLBACK_UNMATCHED'])->save();

                    return 'unmatched';
                }

                if (in_array($payment->status, [PaymentStatus::Successful, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
                    $audit->forceFill(['event_type' => 'CALLBACK_DUPLICATE_TERMINAL'])->save();

                    return 'duplicate';
                }

                $storedResponse = $payment->provider_response ?? [];
                $storedMerchantRequestId = is_array($storedResponse) ? ($storedResponse['merchantRequestId'] ?? null) : null;
                $callbackMerchantRequestId = $callback['MerchantRequestID'] ?? null;

                if (is_string($storedMerchantRequestId)
                    && (! is_string($callbackMerchantRequestId)
                        || ! hash_equals($storedMerchantRequestId, $callbackMerchantRequestId))) {
                    $audit->forceFill(['event_type' => 'CALLBACK_MERCHANT_MISMATCH'])->save();

                    return 'mismatch';
                }

                if ((int) ($callback['ResultCode'] ?? -1) !== 0) {
                    $this->payments->markFailed(
                        $payment,
                        (string) ($callback['ResultDesc'] ?? 'M-Pesa payment failed.'),
                        ['callback' => $payload, 'daraja_verification' => $providerVerification],
                    );
                    $audit->forceFill(['event_type' => 'CALLBACK_FAILED'])->save();

                    return 'failed';
                }

                $metadata = collect(data_get($callback, 'CallbackMetadata.Item', []))
                    ->filter(fn (mixed $item): bool => is_array($item) && isset($item['Name']))
                    ->mapWithKeys(fn (array $item): array => [(string) $item['Name'] => $item['Value'] ?? null]);
                $amount = $metadata->get('Amount');
                $receipt = $metadata->get('MpesaReceiptNumber');
                $phone = $metadata->get('PhoneNumber');

                if (! is_numeric($amount) || (int) $amount !== $payment->amount || ! is_string($receipt) || $receipt === '') {
                    $audit->forceFill(['event_type' => 'CALLBACK_AMOUNT_OR_RECEIPT_MISMATCH'])->save();

                    return 'mismatch';
                }

                if ($payment->phone_number && ! is_numeric($phone)) {
                    $audit->forceFill(['event_type' => 'CALLBACK_PHONE_MISSING'])->save();

                    return 'mismatch';
                }

                if ($payment->phone_number && is_numeric($phone)) {
                    try {
                        if (! hash_equals($payment->phone_number, $this->phoneNormalizer->normalize((string) $phone))) {
                            $audit->forceFill(['event_type' => 'CALLBACK_PHONE_MISMATCH'])->save();

                            return 'mismatch';
                        }
                    } catch (Throwable) {
                        $audit->forceFill(['event_type' => 'CALLBACK_PHONE_INVALID'])->save();

                        return 'mismatch';
                    }
                }

                $this->payments->markSuccessful(
                    $payment,
                    null,
                    $receipt,
                    ['callback' => $payload, 'daraja_verification' => $providerVerification],
                );
                $audit->forceFill(['event_type' => 'CALLBACK_PAYMENT_CONFIRMED'])->save();

                return 'successful';
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (PaymentAudit::query()->where('callback_hash', $callbackHash)->exists()) {
                return 'duplicate';
            }

            throw $exception;
        }
    }
}
