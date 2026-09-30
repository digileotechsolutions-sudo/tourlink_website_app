<?php

namespace App\Http\Requests;

use App\ReferralRewardStatus;
use App\ReferralStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReferralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->value === 'ADMIN';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'reward_enabled' => ['nullable', 'boolean'],
            'reward_amount' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'reward_currency' => ['nullable', 'string', 'size:3'],
            // Only these two stages can actually be a reward trigger.
            'reward_trigger' => ['required', Rule::in(self::rewardTriggers())],
            'reward_requires_approval' => ['nullable', 'boolean'],
            'code_prefix' => ['nullable', 'string', 'max:4', 'regex:/^[A-Za-z]*$/'],
            'code_length' => ['nullable', 'integer', 'min:4', 'max:12'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function rewardTriggers(): array
    {
        return [ReferralStatus::Verified->value, ReferralStatus::Approved->value];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reward_trigger.in' => 'Choose when the referral reward should be issued.',
            'code_prefix.regex' => 'The code prefix may only contain letters.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function referralSettingsPayload(): array
    {
        return [
            'enabled' => $this->boolean('enabled') ? '1' : '0',
            'reward_enabled' => $this->boolean('reward_enabled') ? '1' : '0',
            'reward_amount' => (string) max(0, (int) $this->input('reward_amount', 0)),
            'reward_currency' => strtoupper(trim((string) $this->input('reward_currency', 'KES'))),
            'reward_trigger' => ReferralStatus::from($this->string('reward_trigger')->toString())->value,
            'reward_requires_approval' => $this->boolean('reward_requires_approval') ? '1' : '0',
            'code_prefix' => strtoupper(trim((string) $this->input('code_prefix', 'TL'))),
            'code_length' => (string) max(4, min(12, (int) $this->input('code_length', 6))),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function rewardStatuses(): array
    {
        return array_column(ReferralRewardStatus::cases(), 'value');
    }
}

