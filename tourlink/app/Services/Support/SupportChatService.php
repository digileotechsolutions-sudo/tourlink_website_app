<?php

namespace App\Services\Support;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Mail\SupportMessageReceivedMail;
use App\Models\Booking;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Role;
use App\Services\Verification\AccountVerificationService;
use App\Services\Verification\OtpDeliveryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SupportChatService
{
    public function activeConversation(User $customer, ?Booking $booking = null): SupportConversation
    {
        return DB::transaction(function () use ($customer, $booking): SupportConversation {
            User::query()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();

            $conversation = $customer->supportConversations()
                ->active()
                ->latest('updated_at')
                ->first();

            if ($conversation) {
                if ($booking && ! $conversation->booking_id) {
                    $conversation->forceFill(['booking_id' => $booking->id])->save();
                }

                return $conversation;
            }

            return $customer->supportConversations()->create([
                'booking_id' => $booking?->id,
                'status' => SupportConversation::STATUS_OPEN,
            ]);
        });
    }

    public function startNewConversation(User $customer, ?Booking $booking = null): SupportConversation
    {
        return DB::transaction(function () use ($customer, $booking): SupportConversation {
            User::query()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();

            $active = $customer->supportConversations()->active()->latest('updated_at')->first();
            if ($active) {
                return $active;
            }

            return $customer->supportConversations()->create([
                'booking_id' => $booking?->id,
                'status' => SupportConversation::STATUS_OPEN,
            ]);
        });
    }

    public function addMessage(
        SupportConversation $conversation,
        User $sender,
        string $senderType,
        string $message,
    ): SupportMessage {
        $supportMessage = DB::transaction(function () use ($conversation, $sender, $senderType, $message): SupportMessage {
            $lockedConversation = SupportConversation::query()->whereKey($conversation->getKey())->lockForUpdate()->firstOrFail();
            abort_if($lockedConversation->status === SupportConversation::STATUS_CLOSED, 409, 'This support conversation is closed.');

            $supportMessage = $lockedConversation->messages()->create([
                'sender_id' => $sender->id,
                'sender_type' => $senderType,
                'message' => trim($message),
                'created_at' => now(),
            ]);

            $lockedConversation->forceFill([
                'last_message_at' => $supportMessage->created_at,
                'status' => $senderType === SupportMessage::SENDER_CUSTOMER
                    ? SupportConversation::STATUS_OPEN
                    : SupportConversation::STATUS_PENDING,
            ])->save();

            if ($senderType === SupportMessage::SENDER_CUSTOMER) {
                $this->notifyAuthorizedAdmins($lockedConversation, $supportMessage);
            } else {
                $lockedConversation->user()->firstOrFail()->appNotifications()->create([
                    'title' => 'New reply from Havenedge Tourlink Support',
                    'body' => mb_substr($supportMessage->message, 0, 250),
                    'type' => 'support_message:'.$lockedConversation->id,
                ]);
            }

            return $supportMessage;
        });

        if ($senderType === SupportMessage::SENDER_CUSTOMER) {
            $this->emailAuthorizedAdmins($conversation, $sender, $supportMessage);
        }

        return $supportMessage;
    }

    public function markIncomingRead(SupportConversation $conversation, string $readerType): void
    {
        $incomingSenderType = $readerType === SupportMessage::SENDER_ADMIN
            ? SupportMessage::SENDER_CUSTOMER
            : SupportMessage::SENDER_ADMIN;
        $now = now();

        $conversation->messages()
            ->where('sender_type', $incomingSenderType)
            ->whereNull('read_at')
            ->update(['read_at' => $now]);

        $readAtColumn = $readerType === SupportMessage::SENDER_ADMIN
            ? 'last_read_by_admin_at'
            : 'last_read_by_customer_at';
        $conversation->forceFill([$readAtColumn => $now])->save();

        if ($readerType === SupportMessage::SENDER_CUSTOMER) {
            $conversation->user->appNotifications()
                ->where('type', 'support_message:'.$conversation->id)
                ->whereNull('read_at')
                ->update(['read_at' => $now]);
        } else {
            User::query()->where('role', Role::Admin->value)->get()
                ->each(fn (User $admin) => $admin->appNotifications()
                    ->where('type', 'support_message:'.$conversation->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => $now]));
        }
    }

    public function unreadCount(SupportConversation $conversation, string $readerType): int
    {
        $incomingSenderType = $readerType === SupportMessage::SENDER_ADMIN
            ? SupportMessage::SENDER_CUSTOMER
            : SupportMessage::SENDER_ADMIN;

        return $conversation->messages()
            ->where('sender_type', $incomingSenderType)
            ->whereNull('read_at')
            ->count();
    }

    private function notifyAuthorizedAdmins(SupportConversation $conversation, SupportMessage $message): void
    {
        $verification = app(AccountVerificationService::class);

        User::query()
            ->where('role', Role::Admin->value)
            ->where('account_status', AccountStatus::Active->value)
            ->where('approval_status', AccountApprovalStatus::Approved->value)
            ->get()
            ->filter(fn (User $admin): bool => $verification->isFullyVerified($admin))
            ->each(fn (User $admin) => $admin->appNotifications()->create([
                'title' => 'New support message',
                'body' => mb_substr($message->message, 0, 250),
                'type' => 'support_message:'.$conversation->id,
            ]));
    }

    private function emailAuthorizedAdmins(
        SupportConversation $conversation,
        User $customer,
        SupportMessage $message,
    ): void {
        $verification = app(AccountVerificationService::class);

        User::query()
            ->where('role', Role::Admin->value)
            ->where('account_status', AccountStatus::Active->value)
            ->where('approval_status', AccountApprovalStatus::Approved->value)
            ->get()
            ->filter(fn (User $admin): bool => $verification->isFullyVerified($admin))
            ->each(fn (User $admin) => app(OtpDeliveryService::class)->sendMailable($admin->email, new SupportMessageReceivedMail(
                customerName: $customer->name,
                customerEmail: $customer->email,
                conversationId: (string) $conversation->getKey(),
                supportMessage: $message->message,
                conversationUrl: route('admin.support.show', $conversation),
            )));
    }
}
