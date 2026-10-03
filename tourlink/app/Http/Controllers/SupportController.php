<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Role;
use App\Services\Support\SupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request, SupportChatService $support): View|Response
    {
        if (! $this->supportTablesExist()) {
            return response()->view('support.unavailable', [
                'supportEmail' => config('services.tourlink.support_email'),
                'supportPhone' => config('services.tourlink.support_phone'),
                'dashboardRoute' => $this->dashboardRoute($request),
            ], 503);
        }

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
            'dashboardRoute' => $this->dashboardRoute($request),
        ]);
    }

    public function start(Request $request, SupportChatService $support): RedirectResponse
    {
        if (! $this->supportTablesExist()) {
            return redirect()->route('support.index')
                ->withErrors(['support' => 'Live chat is temporarily unavailable. Please contact our support team directly.']);
        }

        $conversation = $support->startNewConversation($request->user(), $this->bookingFromRequest($request));

        return redirect()->route('support.index', ['conversation' => $conversation->id]);
    }

    public function send(Request $request, string $supportConversation, SupportChatService $support): RedirectResponse
    {
        if (! $this->supportTablesExist()) {
            return redirect()->route('support.index')
                ->withErrors(['support' => 'Live chat is temporarily unavailable. Please contact our support team directly.']);
        }

        $conversation = SupportConversation::query()
            ->whereKey($supportConversation)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);
        $support->addMessage(
            $conversation,
            $request->user(),
            SupportMessage::SENDER_CUSTOMER,
            $data['message'],
        );

        return redirect()->route('support.index', ['conversation' => $conversation->id])
            ->withFragment('latest-message');
    }

    public function messages(Request $request, string $supportConversation, SupportChatService $support): JsonResponse
    {
        if (! $this->supportTablesExist()) {
            return response()->json(['message' => 'Live chat is temporarily unavailable.'], 503)
                ->header('Cache-Control', 'private, no-store, max-age=0');
        }

        $conversation = SupportConversation::query()
            ->whereKey($supportConversation)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $data = $request->validate(['after' => ['nullable', 'string', 'max:36']]);
        $support->markIncomingRead($conversation, SupportMessage::SENDER_CUSTOMER);

        $query = $conversation->messages()
            ->with('sender:id,name')
            ->oldest('created_at')
            ->limit(40);

        if ($after = $data['after'] ?? null) {
            $cursor = $conversation->messages()->whereKey($after)->firstOrFail();
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
            'read_message_ids' => $conversation->messages()
                ->where('sender_type', SupportMessage::SENDER_CUSTOMER)
                ->whereNotNull('read_at')
                ->latest('created_at')
                ->limit(40)
                ->pluck('id'),
            'status' => $conversation->status,
            'unread_count' => $support->unreadCount($conversation, SupportMessage::SENDER_CUSTOMER),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function supportTablesExist(): bool
    {
        return Schema::hasTable('support_conversations')
            && Schema::hasTable('support_messages');
    }

    private function dashboardRoute(Request $request): string
    {
        $route = match ($request->user()->role) {
            Role::Admin => 'admin.dashboard',
            Role::Operator => 'operator.dashboard',
            Role::VehicleOwner => 'vehicle-owner.dashboard',
            Role::Traveler => 'dashboard',
        };

        return Route::has($route) ? $route : 'home';
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
