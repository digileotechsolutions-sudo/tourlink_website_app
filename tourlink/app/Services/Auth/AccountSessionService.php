<?php

namespace App\Services\Auth;

use App\Models\AccountSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AccountSessionService
{
    public const LOGIN_CONFLICT_MESSAGE = 'This account is already logged in on another device. Please log out from your active session before logging in here.';

    public function registerWebLogin(User $user, Request $request, string $previousSessionId): bool
    {
        $fingerprint = $this->fingerprint('web', $request->session()->getId());
        $claimed = $this->claim(
            $user,
            $fingerprint,
            'web',
            $request,
            null,
            $this->fingerprint('web', $previousSessionId),
        );

        if ($claimed) {
            $request->session()->put('_account_session_fingerprint', $fingerprint);
        }

        return $claimed;
    }

    public function registerToken(User $user, PersonalAccessToken $token, Request $request): bool
    {
        return $this->claim(
            $user,
            $this->fingerprint('token', (string) $token->getKey()),
            'token',
            $request,
            (int) $token->getKey(),
        );
    }

    public function discardUnregisteredWebLogin(Request $request): void
    {
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function validateWebSession(User $user, Request $request): bool
    {
        return $this->refresh(
            $user,
            $this->fingerprint('web', $request->session()->getId()),
            'web',
        );
    }

    public function validateToken(User $user, PersonalAccessToken $token, Request $request): bool
    {
        $fingerprint = $this->fingerprint('token', (string) $token->getKey());

        if ($this->refresh($user, $fingerprint, 'token')) {
            return true;
        }

        $lifetime = max(1, (int) config('session.lifetime', 120));
        if ($token->created_at === null || $token->created_at->lessThan(now()->subMinutes($lifetime))) {
            return false;
        }

        return $this->registerToken($user, $token, $request);
    }

    public function revokeCurrent(User $user, Request $request): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            DB::transaction(function () use ($user, $token): void {
                User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                AccountSession::query()
                    ->where('user_id', $user->getKey())
                    ->where('personal_access_token_id', $token->getKey())
                    ->delete();
                $token->delete();
            }, 3);

            return;
        }

        if (! $request->hasSession()) {
            return;
        }

        AccountSession::query()
            ->where('user_id', $user->getKey())
            ->where('session_fingerprint', $this->fingerprint('web', $request->session()->getId()))
            ->where('session_type', 'web')
            ->delete();
    }

    public function revokeUser(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $session = AccountSession::query()->where('user_id', $user->getKey())->lockForUpdate()->first();

            if (! $session) {
                return false;
            }

            $session->delete();

            if ($session->personal_access_token_id !== null) {
                PersonalAccessToken::query()->whereKey($session->personal_access_token_id)->delete();
            }

            if ($session->session_type === 'web') {
                $lockedUser->forceFill(['remember_token' => Str::random(60)])->save();
            }

            return true;
        }, 3);
    }

    public function hasActiveSession(User $user): bool
    {
        return AccountSession::query()
            ->where('user_id', $user->getKey())
            ->where('expires_at', '>', now())
            ->exists();
    }

    private function claim(
        User $user,
        string $fingerprint,
        string $type,
        Request $request,
        ?int $tokenId,
        ?string $previousFingerprint = null,
    ): bool {
        return DB::transaction(function () use ($user, $fingerprint, $type, $request, $tokenId, $previousFingerprint): bool {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $session = AccountSession::query()->where('user_id', $user->getKey())->lockForUpdate()->first();
            $now = now();

            if ($session && $session->expires_at->isFuture()) {
                $isSameClient = hash_equals($session->session_fingerprint, $fingerprint)
                    || ($previousFingerprint !== null && hash_equals($session->session_fingerprint, $previousFingerprint));

                if (! $isSameClient) {
                    return false;
                }

                $session->forceFill($this->sessionAttributes($fingerprint, $type, $request, $tokenId, $now))->save();

                return true;
            }

            if ($session?->personal_access_token_id !== null && $session->personal_access_token_id !== $tokenId) {
                PersonalAccessToken::query()->whereKey($session->personal_access_token_id)->delete();
            }

            AccountSession::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                $this->sessionAttributes($fingerprint, $type, $request, $tokenId, $now),
            );

            return true;
        }, 3);
    }

    private function refresh(User $user, string $fingerprint, string $type): bool
    {
        $now = now();

        return AccountSession::query()
            ->where('user_id', $user->getKey())
            ->where('session_fingerprint', $fingerprint)
            ->where('session_type', $type)
            ->where('expires_at', '>', $now)
            ->update([
                'last_activity_at' => $now,
                'expires_at' => $now->copy()->addMinutes(max(1, (int) config('session.lifetime', 120))),
                'updated_at' => $now,
            ]) === 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionAttributes(string $fingerprint, string $type, Request $request, ?int $tokenId, mixed $now): array
    {
        return [
            'session_fingerprint' => $fingerprint,
            'session_type' => $type,
            'personal_access_token_id' => $tokenId,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'last_activity_at' => $now,
            'expires_at' => $now->copy()->addMinutes(max(1, (int) config('session.lifetime', 120))),
        ];
    }

    private function fingerprint(string $type, string $identifier): string
    {
        return hash('sha256', $type.':'.$identifier);
    }
}
