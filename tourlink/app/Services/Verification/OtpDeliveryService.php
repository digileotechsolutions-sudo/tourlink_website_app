<?php

namespace App\Services\Verification;

use App\Mail\PasswordResetMail;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\OtpChannel;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class OtpDeliveryService
{
    public const MODE_AUTO = 'auto';

    public const MODE_LOG = 'log';

    public const MODE_OFF = 'off';

    public function mode(): string
    {
        $mode = strtolower(trim((string) config('services.otp.delivery', self::MODE_AUTO)));

        return in_array($mode, [self::MODE_AUTO, self::MODE_LOG, self::MODE_OFF], true) ? $mode : self::MODE_AUTO;
    }

    public function logDelivery(): bool
    {
        return $this->mode() === self::MODE_LOG
            && (app()->isLocal() || app()->environment('testing'));
    }

    public function developmentMode(): bool
    {
        return app()->isLocal() && (bool) config('services.otp.dev_mode');
    }

    public function emailConfigured(): bool
    {
        return $this->emailTransport() !== null;
    }

    public function sendMailable(string $recipient, \Illuminate\Mail\Mailable $mail): void
    {
        $this->sendResolvedMailable($recipient, $mail, fn (): string => $mail->render());
    }

    public function resendConfigured(): bool
    {
        return (bool) config('services.resend.key') && (bool) config('services.resend.from');
    }

    public function smtpConfigured(): bool
    {
        $url = config('mail.mailers.smtp.url');
        if (is_string($url) && trim($url) !== '') {
            $parts = parse_url($url);

            return is_array($parts)
                && in_array(strtolower($parts['scheme'] ?? ''), ['smtp', 'smtps'], true)
                && ! empty($parts['host'])
                && ! empty($parts['user'])
                && ! empty($parts['pass']);
        }

        return (bool) config('mail.mailers.smtp.host')
            && (bool) config('mail.mailers.smtp.username')
            && (bool) config('mail.mailers.smtp.password');
    }

    /**
     * Resolves the transport used for email delivery.
     *
     * Resend is preferred, then authenticated SMTP, which lets a host deliver
     * verification codes with only its own mailbox credentials.
     */
    public function emailTransport(): ?string
    {
        $preferred = strtolower(trim((string) config('services.otp.email_transport', 'auto')));

        return match ($preferred) {
            'resend' => $this->resendConfigured() ? 'resend' : null,
            'smtp' => $this->smtpConfigured() ? 'smtp' : null,
            default => $this->resendConfigured()
                ? 'resend'
                : ($this->smtpConfigured() ? 'smtp' : $this->configuredDefaultMailer()),
        };
    }

    private function configuredDefaultMailer(): ?string
    {
        $mailer = strtolower(trim((string) config('mail.default')));
        $configuration = config('mail.mailers.'.$mailer);

        $configuredMailer = $this->usableMailer($mailer, $configuration);
        if ($configuredMailer !== null) {
            return $configuredMailer;
        }

        $sendmail = config('mail.mailers.sendmail');
        $path = is_array($sendmail) ? trim((string) ($sendmail['path'] ?? '')) : '';
        $binary = $path !== '' ? (preg_split('/\s+/', $path, 2)[0] ?? '') : '';

        return $binary !== '' && is_executable($binary) && $this->usableMailer('sendmail', $sendmail) !== null
            ? 'sendmail'
            : null;
    }

    private function usableMailer(string $mailer, mixed $configuration): ?string
    {
        if ($mailer === '' || in_array($mailer, ['log', 'array', 'failover', 'roundrobin'], true) || ! is_array($configuration)) {
            return null;
        }

        $transport = strtolower((string) ($configuration['transport'] ?? ''));

        if ($transport === 'smtp') {
            return $this->smtpConfigured() ? $mailer : null;
        }

        if ($transport === 'sendmail') {
            $path = trim((string) ($configuration['path'] ?? ''));

            return $path !== '' ? $mailer : null;
        }

        if ($transport === 'ses') {
            return config('services.ses.key') && config('services.ses.secret') && config('services.ses.region')
                ? $mailer
                : null;
        }

        if ($transport === 'postmark') {
            return config('services.postmark.key') ? $mailer : null;
        }

        if ($transport === 'mailgun') {
            return config('services.mailgun.secret') && config('services.mailgun.domain')
                ? $mailer
                : null;
        }

        return null;
    }

    public function smsConfigured(): bool
    {
        return (bool) config('services.africastalking.username') && (bool) config('services.africastalking.api_key');
    }

    /**
     * @return array<int, OtpChannel>
     */
    public function availableChannels(): array
    {
        if ($this->mode() === self::MODE_OFF) {
            return [];
        }

        if ($this->developmentMode() || $this->logDelivery()) {
            return OtpChannel::cases();
        }

        return array_values(array_filter(
            OtpChannel::cases(),
            fn (OtpChannel $channel): bool => $channel === OtpChannel::Email
                ? $this->emailConfigured()
                : $this->smsConfigured(),
        ));
    }

    public function canDeliver(OtpChannel $channel): bool
    {
        return in_array($channel, $this->availableChannels(), true);
    }

    public function isConfigured(): bool
    {
        return $this->availableChannels() !== [];
    }

    public function sendVerificationCode(User $user, OtpChannel $channel, string $code): void
    {
        $message = 'Your '.config('app.name').' verification code is '.$code.'. It expires in '.OtpService::EXPIRY_MINUTES.' minutes.';

        if ($channel === OtpChannel::Email) {
            $this->sendAuthEmail(
                $user->email,
                new VerificationCodeMail($user->name, $code, OtpService::EXPIRY_MINUTES),
                'emails.auth.verification-code',
                [
                    'name' => $user->name,
                    'code' => $code,
                    'expiresInMinutes' => OtpService::EXPIRY_MINUTES,
                ],
            );

            return;
        }

        if (! $user->phone) {
            throw new RuntimeException('A verified phone number is required for SMS delivery.');
        }

        $this->sendSms($user->phone, $message);
    }

    public function sendPasswordResetLink(User $user, string $token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $this->sendAuthEmail(
            $user->email,
            new PasswordResetMail($user->name, $resetUrl, $minutes),
            'emails.auth.password-reset',
            [
                'name' => $user->name,
                'resetUrl' => $resetUrl,
                'expiresInMinutes' => $minutes,
            ],
        );
    }

    /**
     * Confirms a verified email address. Once no verification steps remain the
     * message also announces the wait for admin approval; while another channel
     * is still outstanding that claim would be false, so it is left out.
     */
    public function sendEmailVerified(User $user, bool $awaitingApproval): void
    {
        $site = e((string) config('app.name'));

        $html = '<p>Hello '.e($user->name).',</p>'
            .'<p>Congratulations! Your email address has been successfully verified.</p>';

        if ($awaitingApproval) {
            $html .= '<p>Your account with '.$site.' is now awaiting administrator approval. You will receive another email notification once your account has been approved.</p>'
                .'<p>Thank you for your patience. We look forward to welcoming you!</p>';
        }

        $html .= '<p>Best regards,<br><strong>'.$site.' Team</strong></p>';

        $this->sendEmail($user->email, 'Your '.config('app.name').' email address is verified', $html);
    }

    /**
     * Greets a new member and carries their verification code in one message, so
     * registration never arrives as a welcome email followed by a bare code.
     */
    public function sendRegistrationConfirmation(User $user, string $code): void
    {
        $this->sendAuthEmail(
            $user->email,
            new VerificationCodeMail($user->name, $code, OtpService::EXPIRY_MINUTES, true),
            'emails.auth.verification-code',
            [
                'name' => $user->name,
                'code' => $code,
                'expiresInMinutes' => OtpService::EXPIRY_MINUTES,
                'registration' => true,
            ],
        );
    }

    public function sendAccountApproval(User $user, bool $approved, ?string $note = null): void
    {
        $site = e((string) config('app.name'));

        if ($approved) {
            $html = '<p>Hello '.e($user->name).',</p>'
                .'<p>Great news! Your account with '.$site.' has been successfully approved.</p>'
                .'<p>You can now log in using your registered email address and password to access your account and enjoy our services.</p>'
                .($note ? '<p>Admin note: '.e($note).'</p>' : '')
                .'<p>Thank you for joining '.$site.'. We are delighted to have you with us!</p>'
                .'<p>Best regards,<br><strong>'.$site.' Team</strong></p>';
            $subject = 'Your '.config('app.name').' account has been approved';
        } else {
            $html = '<p>Hello '.e($user->name).',</p>'
                .'<p>Your '.$site.' account has <strong>not been approved</strong>.</p>'
                .($note ? '<p>Admin note: '.e($note).'</p>' : '')
                .'<p>Best regards,<br><strong>'.$site.' Team</strong></p>';
            $subject = 'Your '.config('app.name').' account was not approved';
        }

        if ($this->canDeliver(OtpChannel::Email)) {
            $this->sendEmail($user->email, $subject, $html);
        }

        if ($user->phone && $user->phone_verified_at && $this->canDeliver(OtpChannel::Phone)) {
            $this->sendSms($user->phone, 'Hello '.$user->name.', your '.config('app.name').' account was '.($approved ? 'approved. You can now log in.' : 'not approved.'));
        }
    }

    private function sendEmail(string $recipient, string $subject, string $html): void
    {
        if ($this->logDelivery()) {
            Log::notice('Havenedge Tourlink email delivery', ['recipient' => $recipient, 'subject' => $subject]);

            return;
        }

        $transport = $this->emailTransport();

        if ($transport === null) {
            if ($this->developmentMode()) {
                Log::debug('Havenedge Tourlink development email', ['recipient' => $recipient, 'subject' => $subject]);

                return;
            }

            throw new RuntimeException('Email verification delivery is not configured.');
        }

        if ($transport !== 'resend') {
            try {
                Mail::mailer($transport)->html($html, function (Message $message) use ($recipient, $subject): void {
                    $message->to($recipient)->subject($subject);
                });
            } catch (\Throwable $exception) {
                report($exception);

                throw new RuntimeException('Email could not be sent using the configured '.$transport.' mailer.', previous: $exception);
            }

            return;
        }

        $this->resendClient((string) config('services.resend.key'))
            ->post('https://api.resend.com/emails', [
                'from' => config('services.resend.from'),
                'to' => [$recipient],
                'subject' => $subject,
                'html' => $html,
            ])
            ->throw();
    }

    /**
     * Sends security-sensitive messages without ever logging their contents.
     *
     * @param  array<string, mixed>  $viewData
     */
    private function sendAuthEmail(string $recipient, \Illuminate\Mail\Mailable $mail, string $view, array $viewData): void
    {
        $this->sendResolvedMailable($recipient, $mail, fn (): string => view($view, $viewData)->render());
    }

    private function sendResolvedMailable(string $recipient, \Illuminate\Mail\Mailable $mail, Closure $render): void
    {
        $subject = $mail->envelope()->subject;

        if ($this->logDelivery()) {
            Log::notice('Havenedge Tourlink email delivery', ['recipient' => $recipient, 'subject' => $subject]);

            return;
        }

        $transport = $this->emailTransport();

        if ($transport === null) {
            if (app()->environment('testing')) {
                Mail::to($recipient)->send($mail);

                return;
            }

            if ($this->developmentMode()) {
                Log::debug('Havenedge Tourlink development email', ['recipient' => $recipient, 'subject' => $subject]);

                return;
            }

            throw new RuntimeException('Email delivery is not configured. Configure authenticated SMTP or another mail transport.');
        }

        if ($transport === 'resend') {
            $this->resendClient((string) config('services.resend.key'))
                ->post('https://api.resend.com/emails', [
                    'from' => config('services.resend.from'),
                    'to' => [$recipient],
                    'subject' => $subject,
                    'html' => $render(),
                ])
                ->throw();

            return;
        }

        try {
            Mail::mailer($transport)->to($recipient)->send($mail);
        } catch (\Throwable $exception) {
            report($exception);

            throw new RuntimeException('Email could not be sent using the configured '.$transport.' mailer.', previous: $exception);
        }
    }

    private function sendSms(string $recipient, string $message): void
    {
        if ($this->logDelivery()) {
            Log::notice('Havenedge Tourlink SMS delivery', ['recipient' => $recipient]);

            return;
        }

        $apiKey = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');

        if (! $apiKey || ! $username) {
            if ($this->developmentMode()) {
                Log::debug('Havenedge Tourlink development SMS', ['recipient' => $recipient]);

                return;
            }

            throw new RuntimeException('SMS verification delivery is not configured.');
        }

        $body = ['username' => $username, 'to' => $recipient, 'message' => $message];
        if ($senderId = config('services.africastalking.sender_id')) {
            $body['from'] = $senderId;
        }

        Http::asForm()
            ->withHeaders(['apiKey' => $apiKey, 'Accept' => 'application/json'])
            ->connectTimeout(3)
            ->timeout(8)
            ->post('https://api.africastalking.com/version1/messaging', $body)
            ->throw();
    }

    private function resendClient(string $apiKey): PendingRequest
    {
        return Http::asJson()
            ->withToken($apiKey)
            ->connectTimeout(3)
            ->timeout(8);
    }
}
