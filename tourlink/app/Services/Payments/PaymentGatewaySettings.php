<?php

namespace App\Services\Payments;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewaySettings
{
    /**
     * @var array<string, array<string, string>>
     */
    private const CONFIG_KEYS = [
        'mpesa' => [
            'environment' => 'services.mpesa.environment',
            'consumer_key' => 'services.mpesa.consumer_key',
            'consumer_secret' => 'services.mpesa.consumer_secret',
            'shortcode' => 'services.mpesa.shortcode',
            'passkey' => 'services.mpesa.passkey',
            'callback_url' => 'services.mpesa.callback_url',
        ],
        'pesapal' => [
            'environment' => 'payments.pesapal.environment',
            'consumer_key' => 'payments.pesapal.consumer_key',
            'consumer_secret' => 'payments.pesapal.consumer_secret',
            'ipn_id' => 'payments.pesapal.ipn_id',
        ],
    ];

    public function get(string $provider, string $name): string
    {
        $configKey = self::CONFIG_KEYS[$provider][$name] ?? null;
        abort_unless($configKey !== null, 500, 'Unknown payment gateway setting.');

        $stored = Setting::query()
            ->where('key', "payments.{$provider}.{$name}")
            ->value('value');

        if (is_string($stored)) {
            return Crypt::decryptString($stored);
        }

        return (string) config($configKey, '');
    }

    public function isConfigured(string $provider, string $name): bool
    {
        $configKey = self::CONFIG_KEYS[$provider][$name] ?? null;
        abort_unless($configKey !== null, 500, 'Unknown payment gateway setting.');

        return Setting::query()
            ->where('key', "payments.{$provider}.{$name}")
            ->exists()
            || (is_string(config($configKey)) && config($configKey) !== '');
    }

    public function isStored(string $provider, string $name): bool
    {
        abort_unless(isset(self::CONFIG_KEYS[$provider][$name]), 500, 'Unknown payment gateway setting.');

        return Setting::query()
            ->where('key', "payments.{$provider}.{$name}")
            ->exists();
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, bool>  $clear
     * @return array<int, string>
     */
    public function save(string $provider, array $values, array $clear = []): array
    {
        abort_unless(isset(self::CONFIG_KEYS[$provider]), 500, 'Unknown payment gateway.');

        $changed = [];
        foreach ($values as $name => $value) {
            abort_unless(isset(self::CONFIG_KEYS[$provider][$name]), 500, 'Unknown payment gateway setting.');

            $key = "payments.{$provider}.{$name}";
            if ($value !== '') {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => Crypt::encryptString($value)],
                );
                $changed[] = $name;
            } elseif ($clear[$name] ?? false) {
                Setting::query()->where('key', $key)->delete();
                $changed[] = $name;
            }
        }

        return $changed;
    }

    /**
     * @return array<string, array<string, array{configured: bool, stored: bool}>>
     */
    public function configuredState(): array
    {
        $storedKeys = Setting::query()
            ->where('key', 'like', 'payments.%')
            ->pluck('key')
            ->flip();
        $state = [];
        foreach (self::CONFIG_KEYS as $provider => $settings) {
            foreach (array_keys($settings) as $name) {
                $key = "payments.{$provider}.{$name}";
                $stored = isset($storedKeys[$key]);
                $configKey = self::CONFIG_KEYS[$provider][$name];
                $state[$provider][$name] = [
                    'configured' => $stored || (is_string(config($configKey)) && config($configKey) !== ''),
                    'stored' => $stored,
                ];
            }
        }

        return $state;
    }
}
