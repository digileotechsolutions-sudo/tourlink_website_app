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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.auth.login');
    }

    public function showRegistration(Request $request, ReferralService $referrals): View
    {
        $role = $request->query('role');
        $initialRole = in_array($role, [Role::Traveler->value, Role::Operator->value, Role::VehicleOwner->value], true)
            ? $role
            : Role::Traveler->value;

        $referrer = $referrals->captureReferrer($request);

        return view('pages.auth.register', compact('initialRole', 'referrer'));
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

        return redirect()->intended($this->dashboardUrl($user));
    }

    public function register(
        Request $request,
        PhoneNumberNormalizer $phoneNormalizer,
        OtpService $otpService,
        AccountVerificationService $verification,
        ReferralService $referrals,
    ): mixed {
        $input = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'password' => [...PasswordRules::withAccountContext(PasswordRules::rules(), $request), 'confirmed'],
            'role' => ['required', Rule::in([Role::Traveler->value, Role::Operator->value, Role::VehicleOwner->value])],
        ], PasswordRules::messages());

        $email = Str::lower(trim($input['email']));

        try {
            $phone = $phoneNormalizer->normalize($input['phone']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['phone' => $exception->getMessage()]);
        }

        if ($verification->deliverableChannels() === []) {
            report(new RuntimeException('No OTP delivery channel is configured. Set Resend, Africa\'s Talking, or OTP_DELIVERY=log.'));

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'Account verification delivery is not configured. Please contact TourLink support.']);
        }

        $existingAccount = User::query()
            ->where('email', $email)
            ->orWhere('phone', $phone)
            ->exists();

        if ($existingAccount) {
            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'An account with that email or phone number already exists.']);
        }

        $user = $this->createAccountWithReferralCode($input, $email, $phone, $referrals);

        // Consume the captured referrer once, then associate the relationship.
        // Reading it from the session rather than the request body is what stops
        // the referrer being chosen or swapped at submission time.
        $referral = $referrals->recordForNewUser($user, $referrals->pendingReferrer($request));

        $codes = [];

        try {
            foreach ($verification->deliverableChannels() as $channel) {
                $code = $otpService->issueForRegistration($user, $channel);
                $codes[$channel === OtpChannel::Email ? 'email' : 'phone'] = $code;
            }

            $status = 'Verification codes sent to your '.$verification->describeChannels($verification->deliverableChannels()).'.';
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
                            'company_name' => $input['name'],
                            'slug' => Str::slug($input['name']).'-'.Str::lower(Str::random(8)),
                        ])),
                        Role::VehicleOwner => $user->vehicleOwnerProfile()->save(new VehicleOwnerProfile),
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
}
