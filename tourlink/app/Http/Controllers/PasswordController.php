<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\Password\NotCurrentPassword;
use App\Rules\PasswordRules;
use App\Services\Verification\OtpDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PasswordController extends Controller
{
    public function requestForm(): View
    {
        return view('pages.password.request');
    }

    public function changeForm(): View
    {
        return view('pages.password.change');
    }

    public function change(Request $request, OtpDeliveryService $delivery): RedirectResponse
    {
        $passwordRules = PasswordRules::withAccountContext(PasswordRules::rules(), $request->user());
        $passwordRules[] = new NotCurrentPassword($request->user()->password);
        $passwordRules[] = 'confirmed';

        $input = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => $passwordRules,
        ], PasswordRules::messages());

        $request->user()->forceFill([
            'password' => $input['password'],
            'remember_token' => Str::random(60),
        ])->save();
        $request->user()->tokens()->delete();
        $delivery->sendPasswordChanged($request->user());

        return back()->with('status', __('Your password was successfully changed. A confirmation email has been sent.'));
    }

    public function sendResetLink(Request $request): mixed
    {
        $input = $request->validate(['email' => ['required', 'email', 'max:255']]);

        Password::sendResetLink(['email' => Str::lower(trim($input['email']))]);

        return back()->with('status', __('If that email is registered, password reset instructions have been sent.'));
    }

    public function resetForm(string $token, Request $request): View
    {
        return view('pages.password.reset', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request, OtpDeliveryService $delivery): mixed
    {
        $email = $request->input('email');
        $email = is_string($email) && strlen($email) <= 255 ? Str::lower(trim($email)) : '';
        $account = User::query()->where('email', $email)->first();
        $passwordRules = PasswordRules::withAccountContext(PasswordRules::rules(), $account ?? $email);
        if ($account !== null) {
            $passwordRules[] = new NotCurrentPassword($account->password);
        }
        $passwordRules[] = 'confirmed';

        $input = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $passwordRules,
        ], PasswordRules::messages());

        $resetUser = null;
        $status = Password::reset(
            [
                'email' => Str::lower(trim($input['email'])),
                'password' => $input['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $input['token'],
            ],
            function (User $user, string $password) use (&$resetUser): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
                $resetUser = $user;
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __('That password reset link is invalid or expired.')]);
        }

        if (! $resetUser instanceof User) {
            throw new RuntimeException('Password reset succeeded without returning the reset user.');
        }

        $delivery->sendPasswordChanged($resetUser);

        return redirect()->route('login')->with('status', __('Your password was successfully changed. A confirmation email has been sent.'));
    }
}
