<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PesapalGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentGatewaySettings $settings) {}

    public function initiate(Payment $payment, array $context): array
    {
        $this->assertConfigured();

        $response = $this->client()
            ->withToken($this->accessToken())
            ->post($this->baseUrl().'/api/Transactions/SubmitOrderRequest', [
                'id' => $payment->merchant_reference,
                'currency' => $payment->booking->currency,
                'amount' => $payment->amount,
                'description' => 'Havenedge Tourlink booking '.$payment->booking->reference,
                'callback_url' => route('payments.pesapal.return', $payment),
                'notification_id' => $this->settings->get('pesapal', 'ipn_id'),
                'billing_address' => [
                    'email_address' => $context['email'],
                    'phone_number' => $context['phone_number'],
                    'first_name' => $context['first_name'],
                    'last_name' => $context['last_name'],
                    'country_code' => 'KE',
                ],
            ])->throw()->json();

        if (! is_array($response)
            || ! is_string($response['order_tracking_id'] ?? null)
            || ! $this->isSafeRedirect($response['redirect_url'] ?? null)) {
            throw new RuntimeException('Pesapal returned an invalid checkout response.');
        }

        return $response;
    }

    public function verify(string $reference): array
    {
        $this->assertConfigured();

        $response = $this->client()
            ->withToken($this->accessToken())
            ->get($this->baseUrl().'/api/Transactions/GetTransactionStatus', [
                'orderTrackingId' => $reference,
            ])->throw()->json();

        if (! is_array($response)) {
            throw new RuntimeException('Pesapal returned an invalid transaction status.');
        }

        return $response;
    }

    private function accessToken(): string
    {
        $response = $this->client()
            ->post($this->baseUrl().'/api/Auth/RequestToken', [
                'consumer_key' => $this->settings->get('pesapal', 'consumer_key'),
                'consumer_secret' => $this->settings->get('pesapal', 'consumer_secret'),
            ])->throw()->json();

        $token = is_array($response) ? ($response['token'] ?? null) : null;
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Pesapal did not return an access token.');
        }

        return $token;
    }

    private function assertConfigured(): void
    {
        foreach (['consumer_key', 'consumer_secret', 'ipn_id'] as $key) {
            if (! $this->settings->isConfigured('pesapal', $key)) {
                throw new RuntimeException("Pesapal configuration is missing: {$key}.");
            }
        }
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(20);
    }

    private function baseUrl(): string
    {
        return $this->settings->get('pesapal', 'environment') === 'production'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';
    }

    private function isSafeRedirect(mixed $url): bool
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        $allowedHost = $this->settings->get('pesapal', 'environment') === 'production'
            ? 'pay.pesapal.com'
            : 'cybqa.pesapal.com';

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === $allowedHost;
    }
}
