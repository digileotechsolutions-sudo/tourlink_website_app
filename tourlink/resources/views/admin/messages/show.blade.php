@extends('layouts.admin')

@section('title', 'Conversation | TourLink Admin')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="border-b border-slate-200 pb-6"><a href="{{ route('admin.messages.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Back to messages</a><p class="mt-4 text-xs font-bold uppercase tracking-wider text-emerald-800">Conversation</p><h1 class="mt-1 text-3xl font-black text-slate-950">{{ $conversation->title ?: 'Conversation' }}</h1><p class="mt-2 text-sm text-slate-600">Booking {{ $conversation->booking?->reference ?? 'not linked' }} · {{ $conversation->participants->pluck('user.name')->filter()->unique()->join(', ') }}</p></header>
        <section class="mt-6 divide-y divide-slate-200 border-y border-slate-200" aria-label="Message history">
            @forelse($conversation->messages as $message)
                <article class="py-5"><div class="flex flex-wrap items-baseline justify-between gap-3"><p class="font-bold text-slate-950">{{ $message->sender?->name ?? 'Unknown sender' }}<span class="ml-2 text-xs font-normal text-slate-500">{{ $message->sender?->email }}</span></p><time class="text-xs text-slate-500" datetime="{{ $message->created_at?->toIso8601String() }}">{{ $message->created_at?->format('M j, Y H:i') }}</time></div><p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $message->body }}</p>@if($message->attachment_url)<p class="mt-2 text-xs text-slate-500">Attachment: {{ $message->attachment_url }}</p>@endif</article>
            @empty<p class="py-8 text-sm text-slate-600">No messages in this conversation.</p>@endforelse
        </section>
    </div>
@endsection