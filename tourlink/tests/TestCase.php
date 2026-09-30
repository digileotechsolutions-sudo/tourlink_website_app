<?php

namespace Tests;

use App\Models\Setting;
use App\Models\User;
use App\Services\Referral\ReferralSettings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Referral settings are cached forever, so tests must start from a known
     * baseline instead of inheriting whatever a previous test wrote.
     *
     * @param  array<string, string>  $values
     */
    protected function setReferralSettings(array $values = []): void
    {
        $settings = app(ReferralSettings::class);
        $settings->flush();

        foreach (array_merge($settings->defaults(), $values) as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => 'referral.'.$key],
                ['value' => $value],
            );
        }

        $settings->flush();
    }

    protected function referrerUser(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['referral_code' => 'TLABC234']);
    }
}
