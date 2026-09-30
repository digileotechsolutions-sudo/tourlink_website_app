<?php

namespace App\Services\Referral;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\ReferralRewardStatus;
use App\ReferralStatus;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ReferralService
{
    public const SESSION_KEY = 'referral.pending_referrer_id';

    /**
     * Ambiguous glyphs are left out so a code read aloud or retyped from an
     * image survives the trip.
     */
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function __construct(private readonly ReferralSettings $settings) {}

    /**
     * Records the referrer a visitor arrived with, before any account exists.
     *
     * Only the resolved user id is kept in the session, never the code, so a
     * visitor cannot swap the referrer after this point by editing the form.
     */
    public function captureReferrer(Request $request): ?User
    {
        $code = strtoupper(trim((string) $request->query('ref', '')));

        if ($code === '' || ! $this->settings->enabled()) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        $referrer = $this->findByCode($code);

        if (! $referrer || ! $this->isEligibleReferrer($referrer)) {
            // An unknown code must never block registration.
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        $request->session()->put(self::SESSION_KEY, $referrer->id);

        return $referrer;
    }

    public function pendingReferrer(Request $request): ?User
    {
        $referrerId = $request->session()->pull(self::SESSION_KEY);

        if (! is_string($referrerId) || $referrerId === '') {
            return null;
        }

        $referrer = User::query()->find($referrerId);

        return $referrer !== null && $this->isEligibleReferrer($referrer) ? $referrer : null;
    }

    /**
     * Only a live, approved account may earn from referrals, so a suspended
     * referrer is not credited even if someone still holds their link.
     */
    private function isEligibleReferrer(User $user): bool
    {
        return $user->referral_code !== null
            && $user->account_status === AccountStatus::Active
            && $user->approval_status === AccountApprovalStatus::Approved;
    }

    /**
     * Links a brand-new account to the referrer captured in the session.
     *
     * The referred account can never be re-pointed later: the referrals table
     * has a unique index on referred_user_id and this never updates a row.
     */
    public function recordForNewUser(User $user, ?User $referrer): ?Referral
    {
        if (! $referrer || ! $this->settings->enabled()) {
            return null;
        }

        if ($referrer->is($user) || $this->isSelfReferral($referrer, $user)) {
            return null;
        }

        if (! $this->isEligibleReferrer($referrer)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($referrer, $user): Referral {
                return Referral::query()->create([
                    'referrer_id' => $referrer->id,
                    'referred_user_id' => $user->id,
                    'referral_code' => $referrer->referral_code,
                    'status' => ReferralStatus::Pending,
                    'reward_status' => ReferralRewardStatus::None,
                    'reward_amount' => 0,
                ]);
            });
        } catch (QueryException) {
            // Unique index on referred_user_id already guards this; treat a
            // duplicate as "no referral recorded" rather than failing signup.
            return null;
        }
    }

    /**
     * The referred account proved control of its email address.
     */
    public function markVerified(User $referred): ?Referral
    {
        return $this->advance($referred, ReferralStatus::Verified, 'verified_at');
    }

    /**
     * An administrator approved the referred account, which is the point at
     * which a referral becomes successful.
     */
    public function markApproved(User $referred): ?Referral
    {
        $referral = $this->advance($referred, ReferralStatus::Approved, 'approved_at');

        if ($referral) {
            $this->maybeIssueReward($referral);
        }

        return $referral;
    }

    public function markRejected(User $referred): ?Referral
    {
        $referral = Referral::query()->where('referred_user_id', $referred->id)->first();

        if (! $referral || $referral->isSettled()) {
            return $referral;
        }

        $referral->forceFill([
            'status' => ReferralStatus::Rejected,
            'reward_status' => ReferralRewardStatus::Rejected,
            'rewarded_at' => null,
        ])->save();

        return $referral->refresh();
    }

    /**
     * Awards the configured reward once the trigger stage is reached.
     *
     * Guarded so a reward can only ever be issued a single time per referral,
     * regardless of how many times approval or verification is replayed.
     */
    public function maybeIssueReward(Referral $referral): ?Referral
    {
        if (! $this->settings->rewardEnabled()) {
            return null;
        }

        $trigger = $this->settings->rewardTrigger();
        $reached = match ($trigger) {
            ReferralStatus::Verified => in_array($referral->status, [ReferralStatus::Verified, ReferralStatus::Approved, ReferralStatus::Rewarded], true),
            ReferralStatus::Approved => in_array($referral->status, [ReferralStatus::Approved, ReferralStatus::Rewarded], true),
            default => $referral->status === ReferralStatus::Rewarded,
        };

        if (! $reached || $referral->isSettled() || $referral->reward_status !== ReferralRewardStatus::None) {
            return null;
        }

        return DB::transaction(function () use ($referral): Referral {
            $locked = Referral::query()->lockForUpdate()->find($referral->id);

            if (! $locked || $locked->reward_status !== ReferralRewardStatus::None || $locked->isSettled()) {
                return $locked ?? $referral;
            }

            $amount = $this->settings->rewardAmount();

            // Manual approval keeps the reward queued so money only leaves after
            // an administrator reviews it.
            $manual = $this->settings->rewardRequiresApproval();
            $locked->forceFill([
                'reward_amount' => $amount,
                'reward_status' => $manual ? ReferralRewardStatus::Pending : ReferralRewardStatus::Paid,
                'status' => $manual ? $locked->status : ReferralStatus::Rewarded,
                'rewarded_at' => $manual ? null : now(),
            ])->save();

            return $locked->refresh();
        });
    }

    public function ensureCodeFor(User $user): string
    {
        if (is_string($user->referral_code) && $user->referral_code !== '') {
            return $user->referral_code;
        }

        $user->forceFill(['referral_code' => $this->generateUniqueCode()])->save();

        return $user->referral_code;
    }

    public function generateUniqueCode(): string
    {
        $prefix = $this->settings->codePrefix();
        $length = $this->settings->codeLength();
        $alphabetLength = strlen(self::ALPHABET);

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $body = '';
            for ($index = 0; $index < $length; $index++) {
                $body .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
            }

            $code = $prefix.$body;

            if (! User::query()->where('referral_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique referral code.');
    }

    public function findByCode(string $code): ?User
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        return User::query()
            ->where('referral_code', $code)
            ->whereNotNull('referral_code')
            ->first();
    }

    /**
     * @return array<string, int>
     */
    public function statisticsFor(User $referrer): array
    {
        $base = Referral::query()->where('referrer_id', $referrer->id);

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', ReferralStatus::Pending)->count(),
            'verified' => (clone $base)->whereIn('status', [ReferralStatus::Verified, ReferralStatus::Approved, ReferralStatus::Rewarded])->count(),
            'approved' => (clone $base)->whereIn('status', [ReferralStatus::Approved, ReferralStatus::Rewarded])->count(),
            'successful' => (clone $base)->where('status', ReferralStatus::Rewarded)->count(),
            'rewards' => (int) (clone $base)->where('reward_status', ReferralRewardStatus::Paid)->sum('reward_amount'),
            'pending_rewards' => (int) (clone $base)->where('reward_status', ReferralRewardStatus::Pending)->sum('reward_amount'),
        ];
    }

    public function shareUrlFor(User $user): string
    {
        return rtrim((string) config('app.url'), '/').'/register?ref='.$this->ensureCodeFor($user);
    }

    /**
     * A referrer can never be the account being created: same person means the
     * same verified email or phone number.
     */
    private function isSelfReferral(User $referrer, User $user): bool
    {
        if (Str::lower($referrer->email) === Str::lower($user->email)) {
            return true;
        }

        return $referrer->phone !== null && $referrer->phone === $user->phone;
    }

    /**
     * Moves a referral to the next stage without ever going backwards.
     *
     * @return Referral|null
     */
    private function advance(User $referred, ReferralStatus $status, string $timestamp): ?Referral
    {
        $referral = Referral::query()->where('referred_user_id', $referred->id)->first();

        if (! $referral || $referral->isSettled() || $referral->status === $status) {
            return null;
        }

        return DB::transaction(function () use ($referral, $status, $timestamp): Referral {
            $locked = Referral::query()->lockForUpdate()->find($referral->id);

            if (! $locked || $locked->isSettled() || $locked->status === $status) {
                return $locked ?? $referral;
            }

            $locked->forceFill([$timestamp => now()]);

            if ($status === ReferralStatus::Verified && $locked->status === ReferralStatus::Pending) {
                $locked->status = ReferralStatus::Verified;
            }

            if ($status === ReferralStatus::Approved) {
                $locked->status = ReferralStatus::Approved;
            }

            $locked->save();

            return $locked->refresh();
        });
    }
}
