<aside class="pwa-status" data-pwa-status data-pwa-user="{{ auth()->id() ?? '' }}" data-state="idle" role="status" aria-live="polite" hidden>
    <span class="pwa-status__message" data-pwa-message></span>
    <button type="button" data-pwa-install hidden>Install App</button>
    <button type="button" data-pwa-update hidden>Update App</button>
</aside>