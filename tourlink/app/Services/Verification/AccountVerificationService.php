<?php

namespace App\Services\Verification;

use App\Models\User;
use App\OtpChannel;

class AccountVerificationService
{
    public function __construct(private readonly OtpDeliveryService $delivery) {}

    /**
     * Channels that can actually receive a code right now.
     *
     * @return array<int, OtpChannel>
     */
    public function deliverableChannels(): array
    {
        return $this->delivery->availableChannels();
    }

    public function canDeliver(OtpChannel $channel): bool
    {
        return $this->delivery->canDeliver($channel);
    }

    /**
     * Channels an account must verify before it can use protected areas.
     *
     * @return array<int, OtpChannel>
     */
    public function requiredChannels(): array
    {
        return [OtpChannel::Email];
    }

    public function isChannelVerified(User $user, OtpChannel $channel): bool
    {
        return (bool) ($channel === OtpChannel::Email ? $user->email_verified_at : $user->phone_verified_at);
    }

    public function isFullyVerified(User $user): bool
    {
        return $this->pendingChannels($user) === [];
    }

    /**
     * @return array<int, OtpChannel>
     */
    public function pendingChannels(User $user): array
    {
        return array_values(array_filter(
            $this->requiredChannels(),
            fn (OtpChannel $channel): bool => ! $this->isChannelVerified($user, $channel),
        ));
    }

    /**
     * @return array<int, array{channel: OtpChannel, label: string, recipient: ?string, verified: bool, deliverable: bool}>
     */
    public function methodsFor(User $user): array
    {
        $channels = array_values(array_filter(
            $this->requiredChannels(),
            fn (OtpChannel $channel): bool => $this->isChannelVerified($user, $channel) || $this->canDeliver($channel),
        ));

        return array_map(fn (OtpChannel $channel): array => [
            'channel' => $channel,
            'label' => $this->label($channel),
            'recipient' => $channel === OtpChannel::Email ? $user->email : $user->phone,
            'verified' => $this->isChannelVerified($user, $channel),
            'deliverable' => $this->canDeliver($channel),
        ], $channels);
    }

    public function label(OtpChannel $channel): string
    {
        return $channel === OtpChannel::Email ? 'email address' : 'phone number';
    }

    public function describeChannels(array $channels): string
    {
        $labels = array_map(fn (OtpChannel $channel): string => $this->label($channel), $channels);

        if ($labels === []) {
            return 'contact details';
        }

        return implode(' and ', $labels);
    }
}
