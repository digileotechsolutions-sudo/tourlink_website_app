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
use App\Rules\PasswordRules;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        $googleClientId = (string) config('services.google.client_id');
        $googleNonce = $this->issueGoogleNonce($request, 'login', Role::Traveler->value);

        return view('pages.auth.login', compact('googleClientId', 'googleNonce'));
    }

    public function showRegistration(Request $request, ReferralService $referrals, AccountVerificationService $verification): View
    {
        $role = $request->query('role');
        $initialRole = in_array($role, [Role::Traveler->value, Role::Operator->value, Role::VehicleOwner->value], true)
            ? $role
            : Role::Traveler->value;

        $referrer = $referrals->captureReferrer($request);
        $verificationAvailable = $verification->canDeliver(OtpChannel::Email);
        $googleClientId = (string) config('services.google.client_id');
        $googleNonce = $this->issueGoogleNonce($request, 'register', $initialRole);

        return view('pages.auth.register', compact('initialRole', 'referrer', 'verificationAvailable', 'googleClientId', 'googleNonce'));
    }

    public function login(Request $request, AccountVerificationService $verification): mixed
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
        }

        $user = $request->user();

        if ($user->account_status !== AccountStatus::Active) {
            $message = $user->account_status === AccountStatus::Suspended
                ? 'Your account has been suspended. Please contact TourLink support.'
                : 'Your account is inactive. Please contact TourLink support.';

            $this->discardSession($request);

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        if (! $verification->isFullyVerified($user)) {
            $this->discardSession($request);

            return redirect()->route('verification.notice', ['user' => $user->id])
                ->with('status', 'Verify your '.$verification->describeChannels($verification->pendingChannels($user)).' before signing in.');
        }

        if ($user->approval_status !== AccountApprovalStatus::Approved) {
            $message = $user->approval_status === AccountApprovalStatus::Pending
                ? 'Your account is waiting for admin approval.'
                : 'Your account was not approved. Please contact TourLink support.';

            $this->discardSession($request);

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        $request->session()->regenerate();
        $dashboardUrl = $this->dashboardUrl($user);

        if ($this->canVisitIntendedPath($user, $request->session()->get('url.intended'))) {
            return redirect()->intended($dashboardUrl);
        }

        $request->session()->forget('url.intended');

        return redirect()->to($dashboardUrl);
    }

    public function register(
        Request $request,
        PhoneNumberNormalizer $phoneNormalizer,
        OtpService $otpService,
        AccountVerificationService $verification,
        ReferralService $referrals,
    ): mixed {
        Log::info('Registration pipeline: request received.');

        $input = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'business_name' => ['nullable', 'string', 'min:2', 'max:255', Rule::requiredIf(in_array($request->input('role'), [Role::Operator->value, Role::VehicleOwner->value], true))],
            'business_description' => ['nullable', 'string', 'max:10000'],
            'password' => [...PasswordRules::withAccountContext(PasswordRules::rules(), $request), 'confirmed'],
            'role' => ['required', Rule::in([Role::Traveler->value, Role::Operator->value, Role::VehicleOwner->value])],
        ], PasswordRules::messages());
        Log::info('Registration pipeline: validation completed.');

        $email = Str::lower(trim($input['email']));

        try {
            $phone = $phoneNormalizer->normalize($input['phone']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['phone' => $exception->getMessage()]);
        }
        Log::info('Registration pipeline: phone normalized.');

        if (! $verification->canDeliver(OtpChannel::Email)) {
            report(new RuntimeException('Email verification delivery is not configured.'));

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'Email verification delivery is not configured. Please contact TourLink support.']);
        }

        $existingAccount = User::query()
            ->where('email', $email)
            ->orWhere('phone', $phone)
            ->exists();
        Log::info('Registration pipeline: duplicate-account lookup completed.');

        if ($existingAccount) {
            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'An account with that email or phone number already exists.']);
        }

        $user = $this->createAccountWithReferralCode($input, $email, $phone, $referrals);
        Log::info('Registration pipeline: account and profile created.');

        // Consume the captured referrer once, then associate the relationship.
        // Reading it from the session rather than the request body is what stops
        // the referrer being chosen or swapped at submission time.
        $referral = $referrals->recordForNewUser($user, $referrals->pendingReferrer($request));
        Log::info('Registration pipeline: referral processing completed.');

        $codes = [];

        try {
            Log::info('Registration pipeline: OTP delivery started.');
            foreach ($verification->requiredChannels() as $channel) {
                $code = $otpService->issueForRegistration($user, $channel);
                $codes['email'] = $code;
            }

            $status = 'A verification code was sent to your email address.';
            Log::info('Registration pipeline: OTP delivery completed.');
        } catch (Throwable $exception) {
            report($exception);
            $status = 'Your account was created, but a verification message could not be delivered. Use resend to try again.';
        }

        $redirect = redirect()->route('verification.notice', ['user' => $user->id])
            ->with('status', $status)
            ->with('referred_by', $referral?->referrer?->name);

        if ($codes['email'] ?? null) {
            $redirect->with('dev_otp_email', $codes['email']);
        }

        if ($codes['phone'] ?? null) {
            $redirect->with('dev_otp_phone', $codes['phone']);
        }

        return $redirect;
    }

    public function logout(Request $request): mixed
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function issueGoogleNonce(Request $request, string $mode, string $role): ?string
    {
        if ((string) config('services.google.client_id') === '') {
            return null;
        }

        $now = now()->timestamp;
        $nonces = $request->session()->get('google.auth_nonces', []);
        if (! is_array($nonces)) {
            $nonces = [];
        }
        $nonces = array_filter($nonces, fn (mixed $entry): bool => is_array($entry)
            && (int) ($entry['issued_at'] ?? 0) >= $now - 600);

        $nonce = Str::random(64);
        $nonces[$nonce] = [
            'issued_at' => $now,
            'mode' => $mode,
            'role' => $role,
        ];

        $request->session()->put('google.auth_nonces', array_slice($nonces, -5, null, true));

        return $nonce;
    }

    private function discardSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Creates the account, and retries with a fresh referral code if two
     * simultaneous registrations happen to generate the same one.
     *
     * @param  array<string, mixed>  $input
     */
    private function createAccountWithReferralCode(array $input, string $email, string $phone, ReferralService $referrals): User
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($input, $email, $phone, $referrals): User {
                    $user = new User;
                    $user->fill([
                        'name' => $input['name'],
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $input['password'],
                    ]);
                    $user->forceFill([
                        'role' => Role::from($input['role']),
                        'approval_status' => AccountApprovalStatus::Pending,
                        'account_status' => AccountStatus::Active,
                        'verification_level' => VerificationLevel::Basic,
                        // Assigned here so nobody can end up without a
                        // shareable referral link after registering.
                        'referral_code' => $referrals->generateUniqueCode(),
                    ]);
                    $user->save();

                    match (Role::from($input['role'])) {
                        Role::Traveler => $user->travelerProfile()->save(new TravelerProfile),
                        Role::Operator => $user->operatorProfile()->save(new OperatorProfile([
                            'company_name' => $input['business_name'] ?? $input['name'],
                            'slug' => Str::slug($input['business_name'] ?? $input['name']).'-'.Str::lower(Str::random(8)),
                            'description' => $input['business_description'] ?? null,
                        ])),
                        Role::VehicleOwner => $user->vehicleOwnerProfile()->save(new VehicleOwnerProfile([
                            'business_name' => $input['business_name'] ?? $input['name'],
                            'description' => $input['business_description'] ?? null,
                        ])),
                        Role::Admin => null,
                    };

                    return $user;
                });
            } catch (QueryException $exception) {
                if (! $this->isReferralCodeCollision($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to create the account.');
    }

    private function isReferralCodeCollision(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'referral_code')
            && (str_contains($message, 'Duplicate') || str_contains($message, 'duplicate') || str_contains($message, 'UNIQUE'));
    }

    private function dashboardUrl(User $user): string
    {
        if ($user->role === Role::Admin && Route::has('admin.dashboard')) {
            return route('admin.dashboard');
        }

        if ($user->role === Role::Operator && Route::has('operator.dashboard')) {
            return route('operator.dashboard');
        }

        if ($user->role === Role::VehicleOwner && Route::has('vehicle-owner.dashboard')) {
            return route('vehicle-owner.dashboard');
        }

        return $user->role === Role::Traveler || ! Route::has('dashboard') ? route('home') : route('dashboard');
    }

    private function canVisitIntendedPath(User $user, mixed $intendedUrl): bool
    {
        if (! is_string($intendedUrl)) {
            return false;
        }

        $path = parse_url($intendedUrl, PHP_URL_PATH);

        if (! is_string($path)) {
            return false;
        }

        $rolePaths = match ($user->role) {
            Role::Admin => ['/admin'],
            Role::Operator => ['/operator'],
            Role::VehicleOwner => ['/vehicle-owner'],
            Role::Traveler => ['/dashboard', '/bookings', '/traveler'],
        };

        foreach ([...$rolePaths, '/account/password', '/referrals'] as $allowedPath) {
            if ($path === $allowedPath || str_starts_with($path, rtrim($allowedPath, '/').'/')) {
                return true;
            }
        }

        return false;
    }
}
