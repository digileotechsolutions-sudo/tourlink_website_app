@extends('layouts.admin')

@section('title', 'Support | Havenedge Tourlink Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div><p class="text-xs font-bold uppercase tracking-wider text-purple-800">Customer care</p><h1 class="mt-1 text-3xl font-black text-slate-950">Support conversations</h1><p class="mt-1 text-sm text-slate-600">Review customer questions and reply from one inbox.</p></div>
            <span class="rounded-full bg-pink-100 px-3 py-1.5 text-sm font-bold text-pink-950">{{ $unreadTotal }} unread message{{ $unreadTotal === 1 ? '' : 's' }}</span>
        </header>

        <form method="GET" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
            <label class="grid gap-1 text-xs font-bold text-slate-700 lg:col-span-2">Search customer or message<input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, phone, booking..." class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label>
            <label class="grid gap-1 text-xs font-bold text-slate-700">Status<select name="status" class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-normal"><option value="">All statuses</option>@foreach(['open', 'pending', 'closed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <label class="grid gap-1 text-xs font-bold text-slate-700">Assignment<select name="assignment" class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-normal"><option value="">Everyone</option><option value="mine" @selected(($filters['assignment'] ?? '') === 'mine')>Assigned to me</option><option value="unassigned" @selected(($filters['assignment'] ?? '') === 'unassigned')>Unassigned</option></select></label>
            <div class="flex items-end gap-2"><label class="grid min-w-0 flex-1 gap-1 text-xs font-bold text-slate-700">Sort<select name="sort" class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-normal"><option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Latest message</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest message</option></select></label><button class="min-h-11 rounded-lg bg-purple-950 px-4 text-sm font-bold text-white">Filter</button></div>
        </form>

        <section class="grid gap-3" aria-label="Support conversation list">
            @forelse ($conversations as $conversation)
                <a href="{{ route('admin.support.show', $conversation) }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-purple-300 hover:shadow-md sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2"><h2 class="font-extrabold text-slate-950">{{ $conversation->user->name }}</h2><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $conversation->status === 'closed' ? 'bg-slate-100 text-slate-700' : ($conversation->status === 'pending' ? 'bg-amber-100 text-amber-900' : 'bg-emerald-50 text-emerald-800') }}">{{ ucfirst($conversation->status) }}</span>@if($conversation->unread_messages_count > 0)<span class="rounded-full bg-pink-100 px-2.5 py-1 text-[11px] font-bold text-pink-950">{{ $conversation->unread_messages_count }} unread</span>@endif</div>
                        <p class="mt-1 truncate text-sm text-slate-600">{{ $conversation->latestMessage?->message ?? 'No messages yet' }}</p>
                        <p class="mt-1 truncate text-xs text-slate-500">{{ $conversation->user->email }}@if($conversation->user->phone) · {{ $conversation->user->phone }}@endif @if($conversation->booking) · Booking {{ $conversation->booking->reference }}@endif</p>
                    </div>
                    <div class="text-left text-xs text-slate-500 sm:text-right"><p>{{ $conversation->last_message_at?->diffForHumans() ?? $conversation->created_at->diffForHumans() }}</p><p class="mt-1">Assigned: {{ $conversation->assignedAdmin?->name ?? 'Unassigned' }}</p></div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center"><p class="font-bold text-slate-800">No support conversations yet.</p><p class="mt-1 text-sm text-slate-500">Customer messages will appear here.</p></div>
            @endforelse
        </section>
        <div class="mt-5">{{ $conversations->links() }}</div>
    </div>
@endsection
