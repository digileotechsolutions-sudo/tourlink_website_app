@extends('layouts.traveler')

@section('title', 'Support | Havenedge Tourlink')

@section('content')
    @php($lastMessage = $messages->getCollection()->last())
    <div class="support-page mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-8">
        <header class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-purple-800">HAVENEDGE TOURLINK SUPPORT</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950 sm:text-3xl">How can we help?</h1>
                <p class="mt-1 text-sm text-slate-600">Send a message and our support team will get back to you.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $conversation->status === 'closed' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-50 text-emerald-800' }}">{{ ucfirst($conversation->status) }}</span>
                @if ($unreadCount > 0)<span class="rounded-full bg-pink-100 px-3 py-1 text-xs font-bold text-pink-900">{{ $unreadCount }} unread</span>@endif
            </div>
        </header>

        @if ($conversation->booking)
            <p class="mb-3 rounded-lg border border-purple-100 bg-purple-50 px-4 py-3 text-sm text-purple-950">Booking reference: <strong>{{ $conversation->booking->reference }}</strong></p>
        @endif
        @if ($conversation->status === 'closed' && $requestedBooking && $conversation->booking_id !== $requestedBooking->id)
            <p class="mb-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-950">You can start a new conversation about booking <strong>{{ $requestedBooking->reference }}</strong>.</p>
        @endif

        <section class="support-chat-panel" aria-label="Support conversation">
            <div class="support-chat-heading">
                <span class="support-online-dot" aria-hidden="true"></span>
                <div><strong>Support team</strong><p>Messages are usually answered as soon as possible.</p></div>
                <a class="support-back-link" href="{{ route('dashboard') }}">Back</a>
            </div>

            <div id="support-messages" class="support-chat-messages" role="log" aria-live="polite" aria-relevant="additions text" data-support-messages>
                @if ($messages->hasPages())
                    <div class="support-pagination">{{ $messages->links() }}</div>
                @endif
                @forelse ($messages as $message)
                    <article id="{{ $loop->last ? 'latest-message' : '' }}" class="support-message {{ $message->sender_type === 'customer' ? 'support-message--customer' : 'support-message--admin' }}" data-support-message data-message-id="{{ $message->id }}">
                        <p class="support-message__text">{{ $message->message }}</p>
                        <div class="support-message__meta">
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, Y g:i A') }}</time>
                            @if ($message->sender_type === 'customer')<span data-read-status>{{ $message->read_at ? 'Read' : 'Sent' }}</span>@endif
                        </div>
                    </article>
                @empty
                    <article class="support-message support-message--admin support-welcome">
                        <p class="support-message__text">Hello! Welcome to Havenedge Tourlink Support. How can we help you today?</p>
                        <div class="support-message__meta"><span>Support team</span><span>Now</span></div>
                    </article>
                    <p class="support-empty-note">Your conversation history will appear here.</p>
                @endforelse
            </div>

            @if ($conversation->status === 'closed')
                <div class="support-chat-closed">
                    <p>This conversation is closed. Start a new conversation if you need more help.</p>
                    <form method="POST" action="{{ route('support.conversations.store') }}">
                        @csrf
                        @if ($requestedBooking || $conversation->booking)<input type="hidden" name="booking" value="{{ ($requestedBooking ?? $conversation->booking)->reference }}">@endif
                        <button type="submit" class="support-send-button">Start a new conversation</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('support.messages.store', $conversation) }}" class="support-composer" data-support-chat data-current-sender="customer" data-poll-url="{{ route('support.messages.index', $conversation) }}" data-last-message-id="{{ $lastMessage?->id ?? '' }}" data-status="{{ $conversation->status }}">
                    @csrf
                    <label class="sr-only" for="support-message">Type your message</label>
                    <input id="support-message" name="message" type="text" maxlength="4000" autocomplete="off" placeholder="Type your message..." required>
                    <button type="submit" class="support-send-button">Send</button>
                    @error('message')<p class="support-form-error" role="alert">{{ $message }}</p>@enderror
                    <p class="support-live-error" role="status" data-support-error hidden>Messages could not refresh. We’ll try again shortly.</p>
                    <p class="support-composer-help">Press Enter to send</p>
                </form>
            @endif
        </section>
    </div>
@endsection
