<?php

namespace App\Services\Verification;

use App\Models\OtpChallenge;
use App\Models\User;
use App\OtpChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OtpService
{
    public const ACCOUNT_VERIFICATION_PURPOSE = 'account_verification';

    public const EXPIRY_MINUTES = 10;

    public function __construct(private readonly OtpDeliveryService $delivery) {}

    public function issue(User $user, OtpChannel $channel): ?string
    {
        $code = $this->challenge($user, $channel);

        $this->delivery->sendVerificationCode($user, $channel, $code);

        return $this->delivery->developmentMode() ? $code : null;
    }

    /**
     * First send for a brand-new account: the email channel gets the welcome
     * copy that carries the code, so a new member is not greeted twice.
     */
    public function issueForRegistration(User $user, OtpChannel $channel): ?string
    {
        $code = $this->challenge($user, $channel);

        if ($channel === OtpChannel::Email) {
            $this->delivery->sendRegistrationConfirmation($user, $code);
        } else {
            $this->delivery->sendVerificationCode($user, $channel, $code);
        }

        return $this->delivery->developmentMode() ? $code : null;
    }

    private function challenge(User $user, OtpChannel $channel): string
    {
        if ($channel === OtpChannel::Phone && ! $user->phone) {
            throw new RuntimeException('A phone number is required for phone verification.');
        }

        $code = DB::transaction(function () use ($user, $channel): string {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $previousChallenge = $user->otpChallenges()
                ->where('purpose', self::ACCOUNT_VERIFICATION_PURPOSE)
                ->where('channel', $channel->value)
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->first();

            do {
                $code = (string) random_int(100000, 999999);
            } while ($previousChallenge && hash_equals($previousChallenge->code_hash, $this->hashCode($code)));

            $user->otpChallenges()
                ->where('purpose', self::ACCOUNT_VERIFICATION_PURPOSE)
                ->where('channel', $channel->value)
                ->whereNull('consumed_at')
                ->delete();

            OtpChallenge::query()->create([
                'user_id' => $user->id,
                'channel' => $channel,
                'purpose' => self::ACCOUNT_VERIFICATION_PURPOSE,
                'code_hash' => $this->hashCode($code),
                'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
                'attempts' => 0,
            ]);

            return $code;
        });

        return $code;
    }

    public function verify(User $user, OtpChannel $channel, string $code): string
    {
        return DB::transaction(function () use ($user, $channel, $code): string {
            $challenge = $user->otpChallenges()
                ->where('purpose', self::ACCOUNT_VERIFICATION_PURPOSE)
                ->where('channel', $channel->value)
                ->whereNull('consumed_at')
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (! $challenge) {
                return 'missing';
            }

            if ($challenge->expires_at->isPast()) {
                return 'expired';
            }

            if ($challenge->attempts >= 5) {
                return 'locked';
            }

            if (! hash_equals($challenge->code_hash, $this->hashCode($code))) {
                $challenge->increment('attempts');

                return 'invalid';
            }

            $now = now();
            $challenge->forceFill(['consumed_at' => $now])->save();
            $user->forceFill([$channel === OtpChannel::Email ? 'email_verified_at' : 'phone_verified_at' => $now])->save();

            return 'verified';
        });
    }

    public function newProfileSlug(string $name): string
    {
        return Str::slug($name).'-'.Str::lower(Str::random(8));
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
