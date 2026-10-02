<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function initiate(Payment $payment, array $context): array;

    /**
     * @return array<string, mixed>
     */
    public function verify(string $reference): array;
}
