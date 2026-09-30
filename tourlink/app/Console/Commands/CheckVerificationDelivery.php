<?php

namespace App\Console\Commands;

use App\Models\User;
use App\OtpChannel;
use App\Services\Verification\AccountVerificationService;
use App\Services\Verification\OtpDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CheckVerificationDelivery extends Command
{
    protected $signature = 'tourlink:check-verification {--email= : Send a live test code to this address}';

    protected $description = 'Report the configured account verification channels and optionally send a test code';

    private const REPORT_PATH = 'logs/verification-check.txt';

    /** @var list<string> */
    private array $report = [];

    public function handle(OtpDeliveryService $delivery, AccountVerificationService $verification): int
    {
        $this->write('Environment:  '.app()->environment().'  ('.config('app.url').')');

        $healthy = $this->reportDatabase();
        $healthy = $this->reportVerificationChannels($delivery, $verification) && $healthy;

        $this->writeReport();

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function write(string $message): void
    {
        $this->report[] = $message;
        $this->line($message);
    }

    private function problem(string $message): void
    {
        $this->report[] = $message;
        $this->error($message);
    }

    private function reportDatabase(): bool
    {
        $name = (string) config('database.connections.'.config('database.default').'.database');

        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            $this->problem('Database: FAILED for '.$name.' - '.$this->firstLine($exception->getMessage()));
            $this->write('Check DB_DATABASE, DB_USERNAME and DB_PASSWORD in the .env file, and that the');
            $this->write('cPanel user is granted access to the database in the MySQL Databases page.');

            return false;
        }

        $this->write('Database:    connected to '.$name);

        $pending = $this->pendingMigrations();

        if ($pending > 0) {
            $this->problem('Migrations:  '.$pending.' pending - run "php artisan migrate --force" from the application folder.');

            return false;
        }

        $this->write('Migrations:  all applied');

        return true;
    }

    private function reportVerificationChannels(OtpDeliveryService $delivery, AccountVerificationService $verification): bool
    {
        $this->write('OTP mode:     '.$delivery->mode());
        $this->write('Email via:    '.($delivery->emailTransport() ?? 'not configured'));
        $this->write('SMS via:      '.($delivery->smsConfigured() ? "Africa's Talking" : 'not configured'));
        $this->write('Required:     '.implode(' + ', array_map(
            fn (OtpChannel $channel): string => $channel->value,
            $verification->requiredChannels(),
        )));

        if ($delivery->availableChannels() === []) {
            $this->problem('No verification channel is configured, so registration is blocked.');
            $this->write('Set Resend credentials, authenticated SMTP (MAIL_MAILER=smtp with host, username and password),');
            $this->write("Africa's Talking credentials, or OTP_DELIVERY=log while testing.");

            return false;
        }

        $this->write('Verification delivery is available for new registrations.');

        $address = $this->option('email');

        if (! $address) {
            return true;
        }

        $code = (string) random_int(100000, 999999);
        $user = new User;
        $user->forceFill(['name' => 'TourLink Verification Test', 'email' => $address, 'phone' => null]);

        try {
            $delivery->sendVerificationCode($user, OtpChannel::Email, $code);
        } catch (Throwable $exception) {
            $this->problem('Test code to '.$address.' failed: '.$this->firstLine($exception->getMessage()));

            return false;
        }

        $this->write('Test code sent to '.$address.'. Code: '.$code);

        return true;
    }

    /**
     * Mirrors the console output to a plain text file so the result can be read
     * through a file manager on hosts without terminal access, together with
     * the most recent mail failure reported by the application log.
     */
    private function writeReport(): void
    {
        $path = storage_path(self::REPORT_PATH);

        $sections = [
            'Run at:  '.date('Y-m-d H:i:s T'),
            'Report:  '.Str::after($path, base_path().DIRECTORY_SEPARATOR),
            '',
            ...$this->report,
        ];

        $recent = $this->recentMailFailure();

        if ($recent !== null) {
            $sections[] = '';
            $sections[] = 'Last mail failure in storage/logs/laravel.log:';
            $sections[] = $recent;
        }

        $sections[] = '';

        if (is_dir($directory = dirname($path)) && is_writable($directory)) {
            file_put_contents($path, implode(PHP_EOL, $sections));
            $this->line('Full report written to storage/'.self::REPORT_PATH);
        }
    }

    private function recentMailFailure(): ?string
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];

        foreach (array_reverse($lines) as $line) {
            if (str_contains($line, 'ERROR') && preg_match('/(mailer|smtp|Mail|mail)/i', $line) === 1) {
                return $this->truncate($line, 500);
            }
        }

        return null;
    }

    private function truncate(string $value, int $length): string
    {
        return strlen($value) > $length ? substr($value, 0, $length).'...' : $value;
    }

    private function pendingMigrations(): int
    {
        $migrator = $this->laravel->make('migrator');
        $ran = $migrator->getRepository()->getRan();

        return count(array_diff(array_keys($migrator->getMigrationFiles($migrator->paths())), $ran));
    }

    private function firstLine(string $message): string
    {
        return trim(strtok($message, "\r\n") ?: $message);
    }
}
