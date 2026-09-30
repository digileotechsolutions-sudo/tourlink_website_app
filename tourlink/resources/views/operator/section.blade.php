@extends('layouts.operator')
@section('title', $title.' | TourLink')
@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-8">
    <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">Operator workspace</p><h1 class="mt-2 text-3xl font-black">{{ $title }}</h1><p class="mt-2 text-slate-600">{{ $description }}</p></div>
    <div class="overflow-x-auto rounded border border-slate-200 bg-white">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Record</th><th class="p-4">Details</th><th class="p-4">Status</th><th class="p-4">Date</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($items as $item)
                <tr>
                    <td class="p-4 font-bold">{{ $item->name ?? $item->reference ?? $item->title ?? 'Record' }}</td>
                    <td class="p-4 text-slate-600">
                        @if($type === 'bookings') {{ $item->traveler->name ?? 'Traveler' }} · {{ $item->trip->name ?? 'Trip' }}
                        @elseif($type === 'reviews') {{ $item->author->name ?? 'Traveler' }} · Rating {{ $item->rating }}/5
                        @elseif($type === 'customers') {{ $item->email }} · {{ $item->trip_bookings }} trip bookings
                        @elseif($type === 'earnings') KES {{ number_format($item->gross_earnings ?? 0) }}
                        @elseif($type === 'analytics') {{ $item->bookings_count }} bookings · KES {{ number_format($item->bookings_sum_total_amount ?? 0) }}
                        @else Active operator record
                        @endif
                    </td>
                    <td class="p-4">
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold">{{ $item->status?->value ?? 'ACTIVE' }}</span>
                        @if($type === 'bookings')
                            <form method="POST" action="{{ route('operator.bookings.update', $item) }}" class="mt-2 flex gap-2">
                                @csrf @method('PATCH')
                                <select name="status" class="rounded border border-slate-300 text-xs">
                                    @foreach(\App\BookingStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($item->status === $status)>{{ $status->value }}</option>
                                    @endforeach
                                </select>
                                <button class="text-xs font-bold text-emerald-800">Save</button>
                            </form>
                        @endif
                    </td>
                    <td class="p-4 text-slate-500">{{ $item->created_at?->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-10 text-center text-slate-500">Nothing to show yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($items, 'links')) {{ $items->links() }} @endif
</div>
@endsection
