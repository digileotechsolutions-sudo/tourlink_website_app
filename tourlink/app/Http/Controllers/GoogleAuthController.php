<?php

namespace App\Http\Controllers;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Models\OperatorProfile;
use App\Models\TravelerProfile;
use App\Models\User;
use App\Models\VehicleOwnerProfile;
use App\OtpChannel;
use App\Role;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Services\Referral\ReferralService;
use App\Services\Verification\AccountVerificationService;
use App\Services\Verification\OtpService;
use App\Services\Verification\PhoneNumberNormalizer;
use App\VerificationLevel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function authenticate(Request $request, GoogleIdTokenVerifier $verifier, AccountVerificationService $verification): mixed
    {
        $returnRoute = $request->input('mode') === 'register' ? 'register' : 'login';
        $validator = Validator::make($request->only(['credential', 'mode', 'role']), [
            'credential' => ['required', 'string', 'max:16384'],
            'mode' => ['required', Rule::in(['login', 'register'])],
            'role' => ['sometimes', 'required', Rule::in([Role::Traveler->value, Role::Operator->value, Role::VehicleOwner->value])],
        ]);

        if ($validator->fails()) {
            return $this->failure($returnRoute, 'Google could not verify your account. Please try again.');
        }

        $input = $validator->validated();
        $returnRoute = $input['mode'] === 'register' ? 'register' : 'login';

        try {
            $identity = $verifier->verify($input['credential'], (string) config('services.google.client_id'));
        } catch (InvalidArgumentException) {
            return $this->failure($returnRoute, 'Google could not verify your account. Please try again.');
        } catch (Throwable $exception) {
            Log::warning('Google identity verification unavailable.', ['exception' => $exception::class]);

            return $this->failure($returnRoute, 'Google sign-in is temporarily unavailable. Please use email and password.');
        }

        $nonces = $request->session()->get('google.auth_nonces', []);
        $nonceEntry = is_array($nonces) ? ($nonces[$identity['nonce']] ?? null) : null;

        if (! is_array($nonceEntry)
            || (int) ($nonceEntry['issued_at'] ?? 0) < now()->subMinutes(10)->timestamp
            || ($nonceEntry['mode'] ?? null) !== $input['mode']) {
            return $this->failure($returnRoute, 'This Google sign-in expired. Please try again.');
        }

        unset($nonces[$identity['nonce']]);
        $request->session()->put('google.auth_nonces', $nonces);

        $googleUser = User::query()->where('google_id', $identity['sub'])->first();
        $emailUser = User::query()->whereRaw('LOWER(email) = ?', [$identity['email']])->first();

        if ($googleUser && $emailUser && ! $googleUser->is($emailUser)) {
            return $this->failure($returnRoute, 'This Google account cannot be linked to the existing account. Contact support.');
        }

        $user = $googleUser ?? $emailUser;

        if ($user) {
            if ($user->google_id !== null && $user->google_id !== $identity['sub']) {
                return $this->failure($returnRoute, 'This email is already linked to another Google account.');
            }

            if ($user->account_status !== AccountStatus::Active) {
                return $this->failure($returnRoute, 'This account is not currently available. Contact Havenedge Tourlink support.');
            }

            if ($user->approval_status === AccountApprovalStatus::Rejected) {
                return $this->failure($returnRoute, 'This account was not approved. Contact Havenedge Tourlink support.');
            }

            try {
                $user->forceFill([
                    'google_id' => $identity['sub'],
                    'auth_provider' => 'google',
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'avatar_url' => $user->avatar_url ?: $identity['picture'],
                ])->save();
            } catch (QueryException $exception) {
                Log::warning('Google account linking conflict.', ['exception' => $exception::class]);

                return $this->failure($returnRoute, 'This account could not be linked to Google. Please try signing in with your password.');
            }

            if (! $user->phone) {
                $request->session()->regenerate();
                $request->session()->put('google.pending_profile', [
                    ...$identity,
                    'user_id' => $user->id,
                    'mode' => $input['mode'],
                    'role' => $user->role->value,
                    'issued_at' => now()->timestamp,
                ]);

                return redirect()->route('google.complete');
            }

            return $this->finishSignIn($request, $user, $verification);
        }

        $role = Role::tryFrom((string) ($input['role'] ?? $nonceEntry['role'] ?? ''));
        if (! in_array($role, [Role::Traveler, Role::Operator, Role::VehicleOwner], true)) {
            $role = Role::Traveler;
        }

        $request->session()->regenerate();
        $request->session()->put('google.pending_profile', [
            ...$identity,
            'user_id' => null,
            'mode' => $input['mode'],
            'role' => $role->value,
            'issued_at' => now()->timestamp,
        ]);

        return redirect()->route('google.complete');
    }

    public function showCompletion(Request $request): mixed
    {
        $profile = $this->pendingProfile($request);

        if ($profile === null) {
            return redirect()->route('login')->withErrors(['google' => 'Your Google sign-in expired. Please try again.']);
        }

        $needsAccountType = empty($profile['user_id']);
        $initialRole = in_array($profile['role'] ?? null, [Role::Traveler->value, Role::Operator->value, Role::VehicleOwner->value], true)
            ? $profile['role']
            : Role::Traveler->value;

        return view('pages.auth.google-complete', compact('profile', 'needsAccountType', 'initialRole'));
    }

    public function complete(
        Request $request,
        PhoneNumberNormalizer $phoneNormalizer,
        AccountVerificationService $verification,
        OtpService $otpService,
        ReferralService $referrals,
    ): mixed {
        $profile = $this->pendingProfile($request);

        if ($profile === null) {
            return redirect()->route('login')->withErrors(['google' => 'Your Google sign-in expired. Please try again.']);
        }

        $needsAccountType = empty($profile['user_id']);
        $rules = ['phone' => ['required', 'string', 'min:8', 'max:30']];
        if ($needsAccountType) {
            $rules['role'] = ['required', Rule::in([Role::Traveler->value, Role::Operator->value, Role::VehicleOwner->value])];
            $rules['business_name'] = ['nullable', 'string', 'min:2', 'max:255', Rule::requiredIf(in_array($request->input('role'), [Role::Operator->value, Role::VehicleOwner->value], true))];
            $rules['business_description'] = ['nullable', 'string', 'max:10000'];
            $rules['terms_accepted'] = ['accepted'];
        }
        $input = $request->validate($rules);

        try {
            $phone = $phoneNormalizer->normalize($input['phone']);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['phone' => $exception->getMessage()]);
        }

        $phoneQuery = User::query()->where('phone', $phone);
        if (! empty($profile['user_id'])) {
            $phoneQuery->where('id', '!=', $profile['user_id']);
        }
        if ($phoneQuery->exists()) {
            return back()->withInput()->withErrors(['phone' => 'An account with that phone number already exists.']);
        }

        try {
            if (! empty($profile['user_id'])) {
                $user = User::query()->findOrFail($profile['user_id']);
                $user->forceFill(['phone' => $phone])->save();
                $newUser = false;
            } else {
                $role = Role::from($input['role']);
                $user = $this->createGoogleUser(
                    $profile,
                    $phone,
                    $role,
                    $referrals,
                    $input['business_name'] ?? $profile['name'],
                    $input['business_description'] ?? null,
                );
                $newUser = true;
            }
        } catch (QueryException $exception) {
            Log::warning('Google account completion failed.', ['exception' => $exception::class]);

            return back()->withInput()->withErrors(['google' => 'We could not finish creating your account. Please try again.']);
        } catch (Throwable $exception) {
            Log::error('Google account completion failed.', ['exception' => $exception::class]);

            return back()->withInput()->withErrors(['google' => 'We could not finish creating your account. Please try again.']);
        }

        if ($newUser) {
            try {
                $referrals->recordForNewUser($user, $referrals->pendingReferrer($request));
            } catch (Throwable $exception) {
                Log::warning('Google account referral could not be recorded.', ['exception' => $exception::class]);
            }
        }

        $request->session()->forget('google.pending_profile');
        $pendingChannels = $verification->pendingChannels($user);
        $codes = [];
        $deliveryFailed = false;

        try {
            foreach ($pendingChannels as $channel) {
                $code = $otpService->issueForRegistration($user, $channel);
                $codes[$channel === OtpChannel::Email ? 'email' : 'phone'] = $code;
            }
        } catch (Throwable $exception) {
            $deliveryFailed = true;
            Log::warning('Google account verification delivery failed.', ['exception' => $exception::class]);
        }

        $status = $pendingChannels === []
            ? 'Your Google email is verified. Your account is waiting for approval.'
            : ($deliveryFailed
                ? 'Your account was created, but a verification message could not be delivered. Use resend to try again.'
                : 'Verification codes were sent to your '.$verification->describeChannels($pendingChannels).'.');
        $redirect = redirect()->route('verification.notice', ['user' => $user->id])->with('status', $status);

        if ($codes['email'] ?? null) {
            $redirect->with('dev_otp_email', $codes['email']);
        }
        if ($codes['phone'] ?? null) {
            $redirect->with('dev_otp_phone', $codes['phone']);
        }

        return $redirect;
    }

    /**
     * @param  array{sub: string, email: string, name: string, picture: ?string}  $profile
     */
    private function createGoogleUser(array $profile, string $phone, Role $role, ReferralService $referrals, string $businessName, ?string $businessDescription): User
    {
        return DB::transaction(function () use ($profile, $phone, $role, $referrals, $businessName, $businessDescription): User {
            $user = new User;
            $user->fill([
                'name' => $profile['name'],
                'email' => $profile['email'],
                'phone' => $phone,
                'password' => Str::random(64),
            ]);
            $user->forceFill([
                'role' => $role,
                'approval_status' => AccountApprovalStatus::Pending,
                'account_status' => AccountStatus::Active,
                'verification_level' => VerificationLevel::Basic,
                'referral_code' => $referrals->generateUniqueCode(),
                'google_id' => $profile['sub'],
                'auth_provider' => 'google',
                'avatar_url' => $profile['picture'],
                'email_verified_at' => now(),
            ]);
            $user->save();

            match ($role) {
                Role::Traveler => $user->travelerProfile()->save(new TravelerProfile),
                Role::Operator => $user->operatorProfile()->save(new OperatorProfile([
                    'company_name' => $businessName,
                    'slug' => Str::slug($businessName).'-'.Str::lower(Str::random(8)),
                    'description' => $businessDescription,
                ])),
                Role::VehicleOwner => $user->vehicleOwnerProfile()->save(new VehicleOwnerProfile([
                    'business_name' => $businessName,
                    'description' => $businessDescription,
                ])),
                Role::Admin => throw new InvalidArgumentException('Google sign-up cannot create administrators.'),
            };

            return $user;
        });
    }

    private function finishSignIn(Request $request, User $user, AccountVerificationService $verification): mixed
    {
        if (! $verification->isFullyVerified($user)) {
            return redirect()->route('verification.notice', ['user' => $user->id])
                ->with('status', 'Verify your '.$verification->describeChannels($verification->pendingChannels($user)).' before signing in.');
        }

        if ($user->approval_status !== AccountApprovalStatus::Approved) {
            $message = $user->approval_status === AccountApprovalStatus::Pending
                ? 'Your account is waiting for admin approval.'
                : 'Your account was not approved. Please contact Havenedge Tourlink support.';

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $dashboardUrl = match ($user->role) {
            Role::Admin => Route::has('admin.dashboard') ? route('admin.dashboard') : route('home'),
            Role::Operator => Route::has('operator.dashboard') ? route('operator.dashboard') : route('home'),
            Role::VehicleOwner => Route::has('vehicle-owner.dashboard') ? route('vehicle-owner.dashboard') : route('home'),
            default => route('dashboard'),
        };

        return redirect()->to($dashboardUrl);
    }

    /** @return array<string, mixed>|null */
    private function pendingProfile(Request $request): ?array
    {
        $profile = $request->session()->get('google.pending_profile');

        if (! is_array($profile) || (int) ($profile['issued_at'] ?? 0) < now()->subMinutes(15)->timestamp) {
            $request->session()->forget('google.pending_profile');

            return null;
        }

        return $profile;
    }

    private function failure(string $route, string $message): mixed
    {
        return redirect()->route($route)->withErrors(['google' => $message]);
    }
}
