<?php

namespace App\Services\Payments;

use App\Models\Payment;
use InvalidArgumentException;

class MpesaGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly MpesaDarajaGateway $daraja) {}

    public function initiate(Payment $payment, array $context): array
    {
        $phoneNumber = $context['phone_number'] ?? null;
        if (! is_string($phoneNumber) || $phoneNumber === '') {
            throw new InvalidArgumentException('A normalized M-Pesa phone number is required.');
        }

        return $this->daraja->requestStkPush($payment, $phoneNumber);
    }

    public function verify(string $reference): array
    {
        $payment = Payment::query()
            ->where('daraja_checkout_request_id', $reference)
            ->firstOrFail();

        return $this->daraja->queryStkStatus($payment);
    }
}
