@foreach ($groups as $group)
    <details class="portal-nav-group" open>
        <summary class="portal-nav-group__heading"><span class="portal-nav-label">{{ __($group['label']) }}</span><svg viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 7 5 5 5-5"/></svg></summary>
        <div class="portal-nav-group__items">
            @foreach ($group['items'] as $item)
                @if (Route::has($item['route']))
                    @php($active = request()->routeIs($item['active'] ?? $item['route']) && (! isset($item['section']) || request()->route('section') === $item['section']) && (! isset($item['except']) || ! request()->routeIs($item['except'])))
                    <a href="{{ route($item['route'], $item['parameters'] ?? []) }}" class="portal-nav-link {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif title="{{ __($item['label']) }}">
                        <span class="portal-icon" aria-hidden="true">@include('components.portal-icon', ['name' => $item['icon']])</span>
                        <span class="portal-nav-label">{{ __($item['label']) }}</span>
                        @if ($item['label'] === 'Support' && $supportUnreadCount > 0)<span class="portal-nav-badge">{{ min(99, $supportUnreadCount) }}</span>@endif
                    </a>
                @endif
            @endforeach
        </div>
    </details>
@endforeach