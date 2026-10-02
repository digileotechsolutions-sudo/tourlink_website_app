<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\User;
use App\PaymentMethod;

class PesapalPaymentVerifier
{
    public function __construct(
        private readonly PesapalGateway $gateway,
        private readonly PaymentService $payments,
    ) {}

    public function verify(Payment $payment, string $trackingId, ?User $actor = null, ?string $ipAddress = null): string
    {
        abort_unless($payment->provider === 'PESAPAL' && $payment->payment_method === PaymentMethod::Card, 404);
        $knownTrackingId = data_get($payment->metadata, 'order_tracking_id');
        abort_unless(
            is_string($knownTrackingId) && hash_equals($knownTrackingId, $trackingId),
            404,
        );

        $result = $this->gateway->verify($trackingId);
        $description = strtoupper((string) ($result['payment_status_description'] ?? ''));
        $amount = $result['amount'] ?? null;
        $currency = strtoupper((string) ($result['currency'] ?? ''));
        $merchantReference = $result['merchant_reference'] ?? null;
        $referenceMatches = is_string($merchantReference)
            && hash_equals($payment->merchant_reference, $merchantReference);

        if (! is_numeric($amount)
            || (float) $amount !== (float) $payment->amount
            || $currency !== strtoupper($payment->booking->currency)
            || ! $referenceMatches) {
            $this->payments->recordProviderEvent($payment, 'PESAPAL_STATUS_MISMATCH', [
                'status' => $description,
                'amount' => $amount,
                'currency' => $currency,
                'merchant_reference_matches' => $referenceMatches,
                'actor_id' => $actor?->id,
                'ip_address' => $ipAddress,
            ]);

            return 'mismatch';
        }

        if (in_array($description, ['COMPLETED', 'PAID'], true)) {
            $this->payments->markSuccessful(
                $payment,
                $actor,
                is_string($result['confirmation_code'] ?? null) ? $result['confirmation_code'] : $trackingId,
                ['pesapal_status' => $result],
            );

            return 'successful';
        }

        if (in_array($description, ['FAILED', 'INVALID', 'REVERSED', 'CANCELLED'], true)) {
            $this->payments->recordProviderEvent($payment, 'PESAPAL_PAYMENT_FAILED_VERIFIED', [
                'status' => $description,
                'actor_id' => $actor?->id,
                'ip_address' => $ipAddress,
            ]);
            $this->payments->markFailed($payment, 'Pesapal reported '.$description.'.', ['pesapal_status' => $result]);

            return 'failed';
        }

        $this->payments->recordProviderEvent($payment, 'PESAPAL_STATUS_PENDING', [
            'status' => $description,
            'actor_id' => $actor?->id,
            'ip_address' => $ipAddress,
        ]);

        return 'pending';
    }
}
