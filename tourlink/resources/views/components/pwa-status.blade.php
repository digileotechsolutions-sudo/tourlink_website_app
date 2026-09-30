<aside class="pwa-status" data-pwa-status data-pwa-user="{{ auth()->id() ?? '' }}" data-state="idle" role="status" aria-live="polite" hidden>
    <span class="pwa-status__message" data-pwa-message></span>
    <button type="button" data-pwa-install aria-label="Install app" title="Install app" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v11m0 0 4-4m-4 4-4-4"/><path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>
    </button>
    <button type="button" data-pwa-update hidden>Update App</button>
</aside>