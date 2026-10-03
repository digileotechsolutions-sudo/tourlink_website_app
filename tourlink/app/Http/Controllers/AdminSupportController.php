<?php

namespace App\Http\Controllers;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Role;
use App\Services\Support\SupportChatService;
use App\Services\Verification\AccountVerificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSupportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['open', 'pending', 'closed'])],
            'assignment' => ['nullable', Rule::in(['mine', 'unassigned'])],
            'sort' => ['nullable', Rule::in(['latest', 'oldest'])],
        ]);

        $conversations = SupportConversation::query()
            ->with(['user:id,name,email,phone,role', 'assignedAdmin:id,name', 'booking:id,reference', 'latestMessage.sender:id,name'])
            ->withCount([
                'messages as unread_messages_count' => fn (Builder $messages): Builder => $messages
                    ->where('sender_type', SupportMessage::SENDER_CUSTOMER)
                    ->whereNull('read_at'),
            ])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->whereHas('user', fn (Builder $user): Builder => $user
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%'))
                    ->orWhereHas('messages', fn (Builder $messages): Builder => $messages->where('message', 'like', '%'.$search.'%'))
                    ->orWhereHas('booking', fn (Builder $booking): Builder => $booking->where('reference', 'like', '%'.$search.'%'));
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(($filters['assignment'] ?? null) === 'mine', fn (Builder $query): Builder => $query->where('assigned_admin_id', $request->user()->id))
            ->when(($filters['assignment'] ?? null) === 'unassigned', fn (Builder $query): Builder => $query->whereNull('assigned_admin_id'))
            ->orderByDesc('unread_messages_count')
            ->orderBy('last_message_at', ($filters['sort'] ?? 'latest') === 'oldest' ? 'asc' : 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.support.index', [
            'conversations' => $conversations,
            'filters' => $filters,
            'unreadTotal' => SupportMessage::query()
                ->where('sender_type', SupportMessage::SENDER_CUSTOMER)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function show(
        Request $request,
        SupportConversation $supportConversation,
        SupportChatService $support,
        AccountVerificationService $verification,
    ): View
    {
        $support->markIncomingRead($supportConversation, SupportMessage::SENDER_ADMIN);
        $messages = $supportConversation->messages()
            ->with('sender:id,name')
            ->latest('created_at')
            ->paginate(40)
            ->withQueryString();
        $messages->setCollection($messages->getCollection()->reverse()->values());

        return view('admin.support.show', [
            'conversation' => $supportConversation->load(['user:id,name,email,phone,role', 'assignedAdmin:id,name', 'booking:id,reference']),
            'messages' => $messages,
            'admins' => User::query()
                ->where('role', Role::Admin->value)
                ->where('account_status', AccountStatus::Active->value)
                ->where('approval_status', AccountApprovalStatus::Approved->value)
                ->whereNotNull('email_verified_at')
                ->orderBy('name')
                ->get(['id', 'name', 'email_verified_at'])
                ->filter(fn (User $admin): bool => $verification->isFullyVerified($admin)),
        ]);
    }

    public function send(Request $request, SupportConversation $supportConversation, SupportChatService $support): RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);
        $support->addMessage(
            $supportConversation,
            $request->user(),
            SupportMessage::SENDER_ADMIN,
            $data['message'],
        );

        return redirect()->route('admin.support.show', $supportConversation)->withFragment('latest-message');
    }

    public function messages(Request $request, SupportConversation $supportConversation, SupportChatService $support): JsonResponse
    {
        $data = $request->validate(['after' => ['nullable', 'string', 'max:36']]);
        $support->markIncomingRead($supportConversation, SupportMessage::SENDER_ADMIN);

        $query = $supportConversation->messages()
            ->with('sender:id,name')
            ->oldest('created_at')
            ->limit(40);

        if ($after = $data['after'] ?? null) {
            $cursor = $supportConversation->messages()->whereKey($after)->firstOrFail();
            $query->where(function (Builder $messages) use ($cursor): void {
                $messages->where('created_at', '>', $cursor->created_at)
                    ->orWhere(function (Builder $messages) use ($cursor): void {
                        $messages->where('created_at', $cursor->created_at)->where('id', '>', $cursor->id);
                    });
            });
        }

        return response()->json([
            'messages' => $query->get()->map(fn (SupportMessage $message): array => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender?->name ?? 'Customer',
                'message' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
            ]),
            'read_message_ids' => $supportConversation->messages()
                ->where('sender_type', SupportMessage::SENDER_ADMIN)
                ->whereNotNull('read_at')
                ->latest('created_at')
                ->limit(40)
                ->pluck('id'),
            'status' => $supportConversation->status,
            'unread_count' => $support->unreadCount($supportConversation, SupportMessage::SENDER_ADMIN),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function updateStatus(Request $request, SupportConversation $supportConversation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'pending', 'closed'])],
        ]);
        $supportConversation->forceFill(['status' => $data['status']])->save();
        $request->user()->adminLogs()->create([
            'action' => 'support_conversation.status_changed',
            'entity' => 'SupportConversation',
            'entity_id' => $supportConversation->id,
            'metadata' => ['status' => $data['status']],
        ]);

        return back()->with('status', 'Conversation status updated.');
    }

    public function assign(Request $request, SupportConversation $supportConversation, AccountVerificationService $verification): RedirectResponse
    {
        $data = $request->validate([
            'assigned_admin_id' => ['nullable', 'string', 'exists:users,id'],
        ]);

        $admin = isset($data['assigned_admin_id'])
            ? User::query()->whereKey($data['assigned_admin_id'])->firstOrFail()
            : null;

        abort_if(
            $admin && (
                $admin->role !== Role::Admin
                || $admin->account_status !== AccountStatus::Active
                || $admin->approval_status !== AccountApprovalStatus::Approved
                || ! $verification->isFullyVerified($admin)
            ),
            422,
            'Choose an active, verified administrator.',
        );

        $supportConversation->forceFill(['assigned_admin_id' => $admin?->id])->save();
        $request->user()->adminLogs()->create([
            'action' => 'support_conversation.assigned',
            'entity' => 'SupportConversation',
            'entity_id' => $supportConversation->id,
            'metadata' => ['assigned_admin_id' => $admin?->id],
        ]);

        return back()->with('status', 'Conversation assignment updated.');
    }
}
