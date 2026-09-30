<?php

namespace App\Console\Commands;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Models\User;
use App\Role;
use App\Services\Verification\PhoneNumberNormalizer;
use App\VerificationLevel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Signature('tourlink:make-admin')]
#[Description('Create the first local administrator account with a hidden password prompt')]
class CreateFirstAdmin extends Command
{
    public function handle(PhoneNumberNormalizer $phoneNumberNormalizer): int
    {
        if (app()->environment('production')) {
            $this->error('This bootstrap command cannot run in production.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Run this command in an interactive terminal so the password can be entered securely.');

            return self::FAILURE;
        }

        if (User::query()->where('role', Role::Admin->value)->exists()) {
            $this->error('An administrator already exists. This command only creates the first administrator.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Admin name'));
        $email = Str::lower(trim((string) $this->ask('Admin email')));
        $phoneInput = trim((string) $this->ask('Kenyan phone number'));

        try {
            $phone = $phoneNumberNormalizer->normalize($phoneInput);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ], [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Admin password (at least 12 characters)');
        $confirmation = (string) $this->secret('Confirm admin password');

        $passwordValidator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! hash_equals($password, $confirmation)) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $phone, $password): void {
            $now = now();
            $admin = new User;
            $admin->forceFill([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'role' => Role::Admin,
                'approval_status' => AccountApprovalStatus::Approved,
                'approval_reviewed_at' => $now,
                'account_status' => AccountStatus::Active,
                'verification_level' => VerificationLevel::Basic,
                'email_verified_at' => $now,
                'phone_verified_at' => $now,
            ]);
            $admin->save();
        });

        $this->info("Administrator login created for {$email}.");
        $this->line('Sign in at '.route('login').'.');

        return self::SUCCESS;
    }
}
