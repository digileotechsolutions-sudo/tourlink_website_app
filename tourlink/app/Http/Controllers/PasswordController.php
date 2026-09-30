<?php

namespace App\Http\Controllers;

use App\Models\User;
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

    public function sendResetLink(Request $request): mixed
    {
        $input = $request->validate(['email' => ['required', 'email', 'max:255']]);

        Password::sendResetLink(['email' => Str::lower(trim($input['email']))]);

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
        $input = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'email' => Str::lower(trim($input['email'])),
                'password' => $input['password'],
                'password_confirmation' => $input['password_confirmation'],
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
