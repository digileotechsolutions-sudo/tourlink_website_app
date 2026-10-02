@extends('layouts.admin')

@section('title', 'Messages | Havenedge Tourlink Admin')

@section('content')
    <div class="mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="border-b border-slate-200 pb-6"><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Support</p><h1 class="mt-2 text-3xl font-black text-slate-950">Messages</h1><p class="mt-1 text-sm text-slate-600">Review conversation history and linked bookings.</p></header>
        <form method="GET" class="mt-6 flex flex-wrap gap-3 border-b border-slate-200 pb-5"><label class="sr-only" for="message-search">Search messages</label><input id="message-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Participant, conversation or message text" class="min-h-10 w-full max-w-lg rounded border border-slate-300 px-3 text-sm"><button class="min-h-10 rounded border border-slate-300 px-4 text-sm font-bold">Search</button><a href="{{ route('admin.messages.index') }}" class="grid min-h-10 place-items-center px-2 text-sm text-slate-600">Clear</a></form>
        <div class="mt-2 overflow-x-auto"><table class="w-full min-w-[780px] text-left text-sm"><thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">Conversation</th><th class="py-3 pr-4">Participants</th><th class="py-3 pr-4">Booking</th><th class="py-3 pr-4">Messages</th><th class="py-3 pr-4">Latest activity</th><th class="py-3">Open</th></tr></thead><tbody>
            @forelse($conversations as $conversation)
                <tr class="border-b border-slate-100"><td class="py-4 pr-4 font-semibold">{{ $conversation->title ?: 'Conversation' }}</td><td class="py-4 pr-4">{{ $conversation->participants->pluck('user.name')->filter()->unique()->join(', ') ?: 'No participants' }}</td><td class="py-4 pr-4">{{ $conversation->booking?->reference ?? '—' }}</td><td class="py-4 pr-4">{{ $conversation->messages_count }}</td><td class="py-4 pr-4 text-xs text-slate-600">{{ $conversation->messages_max_created_at ? \Illuminate\Support\Carbon::parse($conversation->messages_max_created_at)->diffForHumans() : 'No messages' }}</td><td class="py-4"><a href="{{ route('admin.messages.show', $conversation) }}" class="font-bold text-emerald-800 hover:underline">Read</a></td></tr>
            @empty<tr><td colspan="6" class="py-10 text-center text-sm text-slate-600">No conversations found.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-6">{{ $conversations->links() }}</div>
    </div>
@endsection