@props(['title', 'labels', 'values', 'format' => 'number'])

@php
    $maximum = max($values ?: [0]);
@endphp

<section class="min-w-0 border-t border-slate-200 pt-5" aria-label="{{ $title }}">
    <h3 class="text-base font-extrabold text-slate-950">{{ $title }}</h3>
    @if (count($labels) === 0)
        <p class="py-12 text-sm text-slate-600">No booking data yet.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <div class="grid h-48 min-w-[520px] grid-flow-col auto-cols-fr items-end gap-2" role="img" aria-label="{{ $title }} chart">
                @foreach ($labels as $index => $label)
                    @php
                        $value = (int) ($values[$index] ?? 0);
                        $height = $maximum === 0 ? 0 : max(3, ($value / $maximum) * 100);
                        $valueLabel = $format === 'currency' ? 'KES '.number_format($value) : number_format($value);
                    @endphp
                    <div class="flex h-full min-w-0 flex-col items-center justify-end gap-1">
                        <span class="max-w-full truncate text-[10px] font-semibold text-slate-700" title="{{ $valueLabel }}">{{ $valueLabel }}</span>
                        <div class="w-full rounded-t-sm bg-emerald-800" style="height: {{ $height }}%" aria-hidden="true"></div>
                        <span class="max-w-full truncate text-[10px] text-slate-500" title="{{ $label }}">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
