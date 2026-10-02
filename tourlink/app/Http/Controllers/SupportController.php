<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\Support\SupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request, SupportChatService $support): View
    {
        $booking = $this->bookingFromRequest($request);
        $conversation = $support->activeConversation($request->user(), $booking);
        $support->markIncomingRead($conversation, SupportMessage::SENDER_CUSTOMER);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest('created_at')
            ->paginate(40)
            ->withQueryString();
        $messages->setCollection($messages->getCollection()->reverse()->values());

        return view('support.index', [
            'conversation' => $conversation->loadMissing('booking:id,reference'),
            'messages' => $messages,
            'unreadCount' => $support->unreadCount($conversation, SupportMessage::SENDER_CUSTOMER),
            'requestedBooking' => $booking,
        ]);
    }

    public function start(Request $request, SupportChatService $support): RedirectResponse
    {
        $conversation = $support->startNewConversation($request->user(), $this->bookingFromRequest($request));

        return redirect()->route('support.index', ['conversation' => $conversation->id]);
    }

    public function send(Request $request, SupportConversation $supportConversation, SupportChatService $support): RedirectResponse
    {
        abort_unless((string) $supportConversation->user_id === (string) $request->user()->id, 404);

        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);
        $support->addMessage(
            $supportConversation,
            $request->user(),
            SupportMessage::SENDER_CUSTOMER,
            $data['message'],
        );

        return redirect()->route('support.index', ['conversation' => $supportConversation->id])
            ->withFragment('latest-message');
    }

    public function messages(Request $request, SupportConversation $supportConversation, SupportChatService $support): JsonResponse
    {
        abort_unless((string) $supportConversation->user_id === (string) $request->user()->id, 404);

        $data = $request->validate(['after' => ['nullable', 'string', 'max:36']]);
        $support->markIncomingRead($supportConversation, SupportMessage::SENDER_CUSTOMER);

        $query = $supportConversation->messages()
            ->with('sender:id,name')
            ->oldest('created_at')
            ->limit(40);

        if ($after = $data['after'] ?? null) {
            $cursor = $supportConversation->messages()->whereKey($after)->firstOrFail();
            $query->where(function ($messages) use ($cursor): void {
                $messages->where('created_at', '>', $cursor->created_at)
                    ->orWhere(function ($messages) use ($cursor): void {
                        $messages->where('created_at', $cursor->created_at)->where('id', '>', $cursor->id);
                    });
            });
        }

        return response()->json([
            'messages' => $query->get()->map(fn (SupportMessage $message): array => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender?->name ?? 'Support',
                'message' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
            ]),
            'read_message_ids' => $supportConversation->messages()
                ->where('sender_type', SupportMessage::SENDER_CUSTOMER)
                ->whereNotNull('read_at')
                ->latest('created_at')
                ->limit(40)
                ->pluck('id'),
            'status' => $supportConversation->status,
            'unread_count' => $support->unreadCount($supportConversation, SupportMessage::SENDER_CUSTOMER),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function bookingFromRequest(Request $request): ?Booking
    {
        $data = Validator::make([
            'booking' => $request->query('booking', $request->input('booking')),
        ], ['booking' => ['nullable', 'string', 'max:80']])->validate();
        if (blank($data['booking'] ?? null)) {
            return null;
        }

        return Booking::query()
            ->where('reference', $data['booking'])
            ->where('traveler_id', $request->user()->id)
            ->firstOrFail();
    }
}
