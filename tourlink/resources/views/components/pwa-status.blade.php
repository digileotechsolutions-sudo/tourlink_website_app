<aside class="pwa-status" data-pwa-status data-pwa-user="{{ auth()->id() ?? '' }}" data-state="online" role="status" aria-live="polite">
    <span class="pwa-status__dot" aria-hidden="true"></span>
    <span class="pwa-status__message" data-pwa-message>Connected</span>
    <button type="button" data-pwa-install hidden>Install App</button>
    <button type="button" data-pwa-update hidden>Update App</button>
</aside>