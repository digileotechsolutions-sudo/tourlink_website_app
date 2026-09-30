<?php

namespace App\Services\Referral;

use App\Models\Setting;
use App\ReferralStatus;
use Illuminate\Support\Facades\Cache;

class ReferralSettings
{
    private const CACHE_KEY = 'referral.settings';

    /**
     * @var array<string, string>|null
     */
    private ?array $resolved = null;

    /**
     * Every setting has a default so the referral system works on a fresh
     * install, before an administrator has saved the settings form.
     *
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'enabled' => '1',
            'reward_enabled' => '0',
            'reward_amount' => '100',
            'reward_currency' => 'KES',
            'reward_trigger' => ReferralStatus::Approved->value,
            'reward_requires_approval' => '1',
            'code_prefix' => 'TL',
            'code_length' => '6',
        ];
    }

    public function get(string $key): string
    {
        return $this->all()[$key] ?? $this->defaults()[$key] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        return $this->resolved = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $stored = Setting::query()
                ->whereIn('key', array_map(
                    fn (string $key): string => 'referral.'.$key,
                    array_keys($this->defaults()),
                ))
                ->pluck('value', 'key')
                ->map(fn (string $value): string => trim($value))
                ->all();

            $settings = $this->defaults();
            foreach ($this->defaults() as $key => $default) {
                $value = $stored['referral.'.$key] ?? null;
                if ($value !== null && $value !== '') {
                    $settings[$key] = $value;
                }
            }

            return $settings;
        });
    }

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }

    public function rewardEnabled(): bool
    {
        return $this->enabled() && $this->boolean('reward_enabled');
    }

    public function rewardRequiresApproval(): bool
    {
        return $this->boolean('reward_requires_approval');
    }

    public function rewardAmount(): int
    {
        return max(0, (int) $this->get('reward_amount'));
    }

    public function rewardCurrency(): string
    {
        $currency = strtoupper(trim($this->get('reward_currency')));

        return $currency !== '' ? $currency : 'KES';
    }

    public function rewardTrigger(): ReferralStatus
    {
        $value = strtoupper(trim($this->get('reward_trigger')));

        return ReferralStatus::tryFrom($value) === null
            ? ReferralStatus::Approved
            : ReferralStatus::from($value);
    }

    public function codePrefix(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $this->get('code_prefix')) ?? '');

        return $prefix !== '' ? substr($prefix, 0, 4) : 'TL';
    }

    public function codeLength(): int
    {
        return max(4, min(12, (int) $this->get('code_length')));
    }

    public function flush(): void
    {
        $this->resolved = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function boolean(string $key): bool
    {
        return in_array(strtolower($this->get($key)), ['1', 'true', 'on', 'yes'], true);
    }
}
