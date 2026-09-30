import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

const key = import.meta.env.VITE_REVERB_APP_KEY;
const host = import.meta.env.VITE_REVERB_HOST;
const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

/**
 * Broadcasting is optional and no view subscribes to a channel yet, so a missing
 * Reverb configuration must stay inert. Echo throws when it is constructed without
 * a key or a host, and because app.js imported this file statically that throw ran
 * before the module body and left the password toggles, OTP inputs and resend
 * countdown unbound.
 */
if (key && host) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
