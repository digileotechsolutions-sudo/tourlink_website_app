<?php

namespace App\Http\Controllers;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Models\User;
use App\OtpChannel;
use App\Role;
use App\Services\Referral\ReferralService;
use App\Services\Verification\AccountVerificationService;
use App\Services\Verification\OtpDeliveryService;
use App\Services\Verification\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class VerificationController extends Controller
{
    public function show(string $user, AccountVerificationService $verification): View
    {
        $user = User::query()->findOrFail($user);
        $verificationMethods = $verification->methodsFor($user);

        return view('pages.auth.verification', compact('user', 'verificationMethods'));
    }

    public function verify(Request $request, OtpService $otpService, OtpDeliveryService $delivery, AccountVerificationService $verification, ReferralService $referrals): mixed
    {
        $input = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
            'channel' => ['required', Rule::in([OtpChannel::Email->value, OtpChannel::Phone->value])],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::query()->findOrFail($input['user_id']);
        $channel = OtpChannel::from($input['channel']);
        $result = $otpService->verify($user, $channel, $input['code']);

        if ($result !== 'verified') {
            $message = match ($result) {
                'missing' => 'Request a new verification code.',
                'expired' => 'That code has expired. Request a new one.',
                'locked' => 'Too many attempts. Request a new code.',
                default => 'That verification code is incorrect.',
            };

            return redirect()->route('verification.notice', ['user' => $user->id])->withErrors(['code' => $message]);
        }

        $user->refresh();

        if ($channel === OtpChannel::Email) {
            // The email is proven, so a referral for this account can progress.
            $referrals->markVerified($user);

            try {
                $delivery->sendEmailVerified(
                    $user,
                    $verification->isFullyVerified($user) && $user->approval_status === AccountApprovalStatus::Pending
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($verification->isFullyVerified($user) && $user->approval_status === AccountApprovalStatus::Approved && $user->account_status === AccountStatus::Active) {
            Auth::login($user);
            $request->session()->regenerate();

            $dashboard = match ($user->role) {
                Role::Admin => Route::has('admin.dashboard') ? route('admin.dashboard') : route('home'),
                Role::Operator => Route::has('operator.dashboard') ? route('operator.dashboard') : route('home'),
                Role::VehicleOwner => Route::has('vehicle-owner.dashboard') ? route('vehicle-owner.dashboard') : route('home'),
                default => route('home'),
            };

            return redirect()->intended($dashboard);
        }

        $pending = $verification->pendingChannels($user);
        $status = $verification->isFullyVerified($user) && $user->approval_status === AccountApprovalStatus::Pending
            ? 'All contact methods verified. Your account is now waiting for admin approval.'
            : 'Contact verified. Verify your remaining '.$verification->describeChannels($pending).'.';

        return redirect()->route('verification.notice', ['user' => $user->id])->with('status', $status);
    }

    public function resend(Request $request, OtpService $otpService, AccountVerificationService $verification): mixed
    {
        $input = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
            'channel' => ['required', Rule::in([OtpChannel::Email->value, OtpChannel::Phone->value])],
        ]);

        $user = User::query()->findOrFail($input['user_id']);
        $channel = OtpChannel::from($input['channel']);
        $verified = $channel === OtpChannel::Email ? $user->email_verified_at : $user->phone_verified_at;

        if ($verified) {
            return redirect()->route('verification.notice', ['user' => $user->id])->withErrors(['code' => 'That contact method is already verified.']);
        }

        if (! $verification->canDeliver($channel)) {
            return redirect()->route('verification.notice', ['user' => $user->id])
                ->withErrors(['code' => 'Verification codes cannot currently be sent to your '.$verification->label($channel).'.']);
        }

        try {
            $code = $otpService->issue($user, $channel);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('verification.notice', ['user' => $user->id])->withErrors(['code' => 'We could not resend that verification code.']);
        }

        $redirect = redirect()->route('verification.notice', ['user' => $user->id])
            ->with('status', 'A new verification code was sent.');

        if ($code) {
            $redirect->with($channel === OtpChannel::Email ? 'dev_otp_email' : 'dev_otp_phone', $code);
        }

        return $redirect;
    }
}
