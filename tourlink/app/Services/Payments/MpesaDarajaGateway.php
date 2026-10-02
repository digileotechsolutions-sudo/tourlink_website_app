<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaDarajaGateway
{
    /** @return array{checkoutRequestId: string, merchantRequestId: string, responseDescription: string} */
    public function requestStkPush(Payment $payment, string $phoneNumber): array
    {
        if ($payment->amount < 1) {
            throw new RuntimeException('M-Pesa requires a positive payment amount.');
        }

        $shortCode = (string) config('services.mpesa.shortcode');
        $passkey = (string) config('services.mpesa.passkey');
        if ($shortCode === '' || $passkey === '') {
            throw new RuntimeException('M-Pesa Daraja credentials are not configured.');
        }

        $timestamp = now('Africa/Nairobi')->format('YmdHis');
        $password = base64_encode($shortCode.$passkey.$timestamp);
        $callbackUrl = $this->callbackUrl();
        $response = $this->authorizedClient()->post($this->apiBase().'/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($payment->amount),
            'PartyA' => $phoneNumber,
            'PartyB' => $shortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $payment->merchant_reference,
            'TransactionDesc' => 'Havenedge Tourlink booking '.$payment->merchant_reference,
        ])->throw()->json();

        if (($response['ResponseCode'] ?? null) !== '0'
            || empty($response['CheckoutRequestID'])
            || empty($response['MerchantRequestID'])) {
            throw new RuntimeException('Safaricom did not accept the STK Push request.');
        }

        return [
            'checkoutRequestId' => (string) $response['CheckoutRequestID'],
            'merchantRequestId' => (string) $response['MerchantRequestID'],
            'responseDescription' => (string) ($response['ResponseDescription'] ?? 'STK Push request accepted.'),
        ];
    }

    /** @return array<string, mixed> */
    public function queryStkStatus(Payment $payment): array
    {
        if (! $payment->daraja_checkout_request_id) {
            throw new RuntimeException('The payment has no Daraja CheckoutRequestID.');
        }

        $shortCode = (string) config('services.mpesa.shortcode');
        $passkey = (string) config('services.mpesa.passkey');
        if ($shortCode === '' || $passkey === '') {
            throw new RuntimeException('M-Pesa Daraja credentials are not configured.');
        }

        $timestamp = now('Africa/Nairobi')->format('YmdHis');
        $response = $this->authorizedClient()->post($this->apiBase().'/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $shortCode,
            'Password' => base64_encode($shortCode.$passkey.$timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $payment->daraja_checkout_request_id,
        ])->throw()->json();

        return is_array($response) ? $response : [];
    }

    private function authorizedClient(): PendingRequest
    {
        $consumerKey = (string) config('services.mpesa.consumer_key');
        $consumerSecret = (string) config('services.mpesa.consumer_secret');
        if ($consumerKey === '' || $consumerSecret === '') {
            throw new RuntimeException('M-Pesa Daraja credentials are not configured.');
        }

        $token = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->connectTimeout(3)
            ->timeout(8)
            ->get($this->apiBase().'/oauth/v1/generate', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Safaricom did not return an access token.');
        }

        return Http::asJson()
            ->withToken($token)
            ->connectTimeout(3)
            ->timeout(12);
    }

    private function apiBase(): string
    {
        return config('services.mpesa.environment') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    private function callbackUrl(): string
    {
        $url = (string) config('services.mpesa.callback_url');
        if ($url === '') {
            $url = rtrim((string) config('app.url'), '/').'/api/mpesa/callback';
        }

        if (config('services.mpesa.environment') === 'production'
            && parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('The production M-Pesa callback URL must use HTTPS.');
        }

        return $url;
    }
}
