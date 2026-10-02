<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

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

    public function change(Request $request): RedirectResponse
    {
        $passwordRules = PasswordRules::withAccountContext(PasswordRules::rules(), $request->user());
        $passwordRules[] = 'confirmed';

        $input = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => $passwordRules,
        ], PasswordRules::messages());

        $request->user()->forceFill(['password' => $input['password']])->save();

        return back()->with('status', 'Your password has been changed.');
    }

    public function sendResetLink(Request $request): mixed
    {
        $input = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $status = Password::sendResetLink(['email' => Str::lower(trim($input['email']))]);

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Please wait before requesting another password reset link.']);
        }

        return back()->with('status', 'If that email is registered, password reset instructions have been sent.');
    }

    public function resetForm(string $token, Request $request): View
    {
        return view('pages.password.reset', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): mixed
    {
        $email = $request->input('email');
        $email = is_string($email) && strlen($email) <= 255 ? Str::lower(trim($email)) : '';
        $account = User::query()->where('email', $email)->first();
        $passwordRules = PasswordRules::withAccountContext(PasswordRules::rules(), $account ?? $email);
        $passwordRules[] = 'confirmed';

        $input = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $passwordRules,
        ], PasswordRules::messages());

        $status = Password::reset(
            [
                'email' => Str::lower(trim($input['email'])),
                'password' => $input['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $input['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'That password reset link is invalid or expired.']);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in.');
    }
}
