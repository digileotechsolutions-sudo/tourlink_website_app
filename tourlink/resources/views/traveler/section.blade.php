@extends('layouts.traveler')
@section('title', $title.' | TourLink')
@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-8">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">Traveler workspace</p><h1 class="mt-2 text-3xl font-black">{{ $title }}</h1><p class="mt-2 text-slate-600">{{ $description }}</p></div>@if($type === 'notifications')<form method="POST" action="{{ route('traveler.notifications.read') }}">@csrf<button class="rounded border border-slate-300 px-4 py-2 text-sm font-bold">Mark all read</button></form>@endif</div>
    <div class="overflow-x-auto rounded border border-slate-200 bg-white"><table class="w-full min-w-[720px] text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Record</th><th class="p-4">Details</th><th class="p-4">Status</th><th class="p-4">Date</th></tr></thead><tbody class="divide-y divide-slate-100">
    @forelse($items as $item)
        <tr><td class="p-4 font-bold">{{ $item->title ?? $item->reference ?? 'Review' }}</td><td class="p-4 text-slate-600">
            @if($type === 'notifications') {{ $item->body }}
            @elseif(in_array($type, ['payments', 'receipts', 'upcoming', 'past'], true)) {{ $item->trip?->name ?? $item->vehicle?->name ?? 'Journey' }} · KES {{ number_format($item->total_amount) }}
            @elseif($type === 'reviews') {{ $item->body }}
            @else Traveler record
            @endif
        </td><td class="p-4"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold">{{ $item->status?->value ?? ($item->read_at ? 'READ' : 'NEW') }}</span></td><td class="p-4 text-slate-500">{{ $item->created_at?->format('d M Y') }}</td></tr>
    @empty
        <tr><td colspan="4" class="p-10 text-center text-slate-500">Nothing to show yet.</td></tr>
    @endforelse
    </tbody></table></div>
    @if(method_exists($items, 'links')) {{ $items->links() }} @endif
</div>
@endsection
