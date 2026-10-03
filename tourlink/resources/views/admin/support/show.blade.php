@extends('layouts.admin')

@section('title', 'Support conversation | Havenedge Tourlink Admin')

@section('content')
    @php($lastMessage = $messages->getCollection()->last())
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<p role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900">{{ session('status') }}</p>@endif
        <a href="{{ route('admin.support.index') }}" class="inline-flex min-h-10 items-center text-sm font-bold text-purple-900 hover:underline">← Support inbox</a>
        <header class="mt-3 flex flex-wrap items-start justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <div><p class="text-xs font-bold uppercase tracking-wider text-purple-800">Support conversation · {{ $conversation->user->role->value }}</p><h1 class="mt-1 text-2xl font-black text-slate-950">{{ $conversation->user->name }}</h1><p class="mt-1 text-sm text-slate-600">{{ $conversation->user->email }} @if($conversation->user->phone) · {{ $conversation->user->phone }}@endif</p>@if($conversation->booking)<p class="mt-2 text-sm font-semibold text-slate-800">Booking {{ $conversation->booking->reference }}</p>@endif</div>
            <div class="grid gap-3 sm:min-w-64">
                <form method="POST" action="{{ route('admin.support.assignment', $conversation) }}" class="flex items-end gap-2">@csrf @method('PATCH')<label class="grid min-w-0 flex-1 gap-1 text-xs font-bold text-slate-700">Assign to<select name="assigned_admin_id" class="min-h-10 rounded-lg border border-slate-300 px-2 text-sm font-normal"><option value="">Unassigned</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected($conversation->assigned_admin_id === $admin->id)>{{ $admin->name }}</option>@endforeach</select></label><button class="min-h-10 rounded-lg border border-slate-300 px-3 text-xs font-bold text-slate-800">Save</button></form>
                <form method="POST" action="{{ route('admin.support.status', $conversation) }}" class="flex items-end gap-2">@csrf @method('PATCH')<label class="grid min-w-0 flex-1 gap-1 text-xs font-bold text-slate-700">Status<select name="status" class="min-h-10 rounded-lg border border-slate-300 px-2 text-sm font-normal">@foreach(['open', 'pending', 'closed'] as $status)<option value="{{ $status }}" @selected($conversation->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><button class="min-h-10 rounded-lg bg-purple-950 px-3 text-xs font-bold text-white">Update</button></form>
            </div>
        </header>

        <section class="support-chat-panel mt-5" aria-label="Support messages">
            <div class="support-chat-heading"><span class="support-online-dot" aria-hidden="true"></span><div><strong>{{ $conversation->user->name }}</strong><p>Account support conversation · {{ ucfirst($conversation->status) }}</p></div></div>
            <div id="support-messages" class="support-chat-messages support-chat-messages--admin" role="log" aria-live="polite" aria-relevant="additions text" data-support-messages>
                @if ($messages->hasPages())<div class="support-pagination">{{ $messages->links() }}</div>@endif
                @forelse ($messages as $message)
                    <article id="{{ $loop->last ? 'latest-message' : '' }}" class="support-message {{ $message->sender_type === 'admin' ? 'support-message--customer' : 'support-message--admin' }}" data-support-message data-message-id="{{ $message->id }}">
                        <p class="support-message__author">{{ $message->sender_type === 'admin' ? 'You' : $conversation->user->name }}</p>
                        <p class="support-message__text">{{ $message->message }}</p>
                        <div class="support-message__meta"><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, Y g:i A') }}</time>@if($message->sender_type === 'admin')<span data-read-status>{{ $message->read_at ? 'Read' : 'Sent' }}</span>@endif</div>
                    </article>
                @empty
                    <p class="support-empty-note">No messages yet. Reply when the customer gets in touch.</p>
                @endforelse
            </div>
            @if ($conversation->status === 'closed')
                <div class="support-chat-closed"><p>This conversation is closed. Reopen it to reply.</p></div>
            @else
                <form method="POST" action="{{ route('admin.support.messages.store', $conversation) }}" class="support-composer" data-support-chat data-current-sender="admin" data-poll-url="{{ route('admin.support.messages.index', $conversation) }}" data-last-message-id="{{ $lastMessage?->id ?? '' }}" data-status="{{ $conversation->status }}">
                    @csrf
                    <label class="sr-only" for="support-message">Type your reply</label>
                    <input id="support-message" name="message" type="text" maxlength="4000" autocomplete="off" placeholder="Type your reply..." required>
                    <button type="submit" class="support-send-button">Send reply</button>
                    @error('message')<p class="support-form-error" role="alert">{{ $message }}</p>@enderror
                    <p class="support-live-error" role="status" data-support-error hidden>Messages could not refresh. We’ll try again shortly.</p>
                    <p class="support-composer-help">Press Enter to send</p>
                </form>
            @endif
        </section>
    </div>
@endsection
