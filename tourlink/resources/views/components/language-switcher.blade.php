<form class="language-switcher" method="POST" action="{{ route('locale.update') }}" aria-label="{{ __('Change language') }}">
    @csrf
    <input type="hidden" name="return_to" value="{{ '/'.ltrim(request()->path(), '/').(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}">
    <label class="sr-only" for="site-language">{{ __('Choose language') }}</label>
    <select id="site-language" name="locale" aria-label="{{ __('Choose language') }}" onchange="this.form.requestSubmit()">
        @foreach (config('localization.languages') as $locale => $language)
            <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>{{ $language['native'] }}</option>
        @endforeach
    </select>
    <button type="submit" aria-label="{{ __('Change language') }}" title="{{ __('Change language') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0-18"></path>
        </svg>
        <span>{{ __('Apply') }}</span>
    </button>
</form>
