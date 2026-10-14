<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountSessionService
{
    public const SESSION_VERSION_KEY = 'auth.account_session_version';
    public const SESSION_STARTED_AT_KEY = 'auth.account_session_started_at';

    public function begin(User $user, Request $request): void
    {
        $version = DB::transaction(function () use ($user): int {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $lockedUser->session_version++;
            $lockedUser->save();

            return (int) $lockedUser->session_version;
        });

        $user->setAttribute('session_version', $version);
        $request->session()->regenerate();
        $request->session()->put([
            self::SESSION_VERSION_KEY => $version,
            self::SESSION_STARTED_AT_KEY => now()->timestamp,
        ]);
    }
}
