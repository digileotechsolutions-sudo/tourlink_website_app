<?php

namespace App\Services\Verification;

use App\Models\User;
use App\OtpChannel;
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
        return $this->mode() === self::MODE_LOG;
    }

    public function developmentMode(): bool
    {
        return app()->isLocal() && (bool) config('services.otp.dev_mode');
    }

    public function emailConfigured(): bool
    {
        return $this->emailTransport() !== null;
    }

    public function resendConfigured(): bool
    {
        return (bool) config('services.resend.key') && (bool) config('services.resend.from');
    }

    public function smtpConfigured(): bool
    {
        return config('mail.default') === 'smtp'
            && (bool) config('mail.mailers.smtp.host')
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
            default => $this->resendConfigured() ? 'resend' : ($this->smtpConfigured() ? 'smtp' : null),
        };
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
            $this->sendEmail($user->email, 'Your '.config('app.name').' verification code', '<p>'.e($message).'</p>');

            return;
        }

        if (! $user->phone) {
            throw new RuntimeException('A verified phone number is required for SMS delivery.');
        }

        $this->sendSms($user->phone, $message);
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
        $site = e((string) config('app.name'));
        $name = e($user->name);
        $minutes = OtpService::EXPIRY_MINUTES;

        $html = <<<HTML
            <p>Hello {$name},</p>
            <p>Thank you for creating an account with {$site}!</p>
            <p>Your account has been successfully registered. To verify your email address, please use the One-Time Password (OTP) below:</p>
            <p><strong>Your Verification Code: {$code}</strong></p>
            <p>This code is valid for {$minutes} minutes. For your security, please do not share this code with anyone.</p>
            <p>Once your email address has been verified, your account will be submitted for administrator approval, if required.</p>
            <p>Thank you for choosing {$site}!</p>
            <p>Best regards,<br><strong>{$site} Team</strong></p>
            HTML;

        $this->sendEmail($user->email, 'Welcome to '.config('app.name').' - your verification code', $html);
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
        $body = strip_tags($html);

        if ($this->logDelivery()) {
            Log::notice('TourLink email delivery', ['recipient' => $recipient, 'subject' => $subject, 'body' => $body]);

            return;
        }

        $transport = $this->emailTransport();

        if ($transport === 'smtp') {
            Mail::html($html, function (Message $message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            });

            return;
        }

        if ($transport !== 'resend') {
            if ($this->developmentMode()) {
                Log::debug('TourLink development email', ['recipient' => $recipient, 'subject' => $subject, 'body' => $body]);

                return;
            }

            throw new RuntimeException('Email verification delivery is not configured.');
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

    private function sendSms(string $recipient, string $message): void
    {
        if ($this->logDelivery()) {
            Log::notice('TourLink SMS delivery', ['recipient' => $recipient, 'message' => $message]);

            return;
        }

        $apiKey = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');

        if (! $apiKey || ! $username) {
            if ($this->developmentMode()) {
                Log::debug('TourLink development SMS', ['recipient' => $recipient, 'message' => $message]);

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
