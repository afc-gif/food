import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? undefined;
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
// Prefer server port if provided to keep client/server in sync
const reverbPort = Number(import.meta.env.VITE_REVERB_SERVER_PORT ?? import.meta.env.VITE_REVERB_PORT ?? 8080);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? (reverbPort === 443 ? 'https' : 'http');

// Expose config for diagnostics in the browser console
window.reverbConfig = {
    hasKey: !!reverbKey,
    key: reverbKey,
    host: reverbHost,
    port: reverbPort,
    scheme: reverbScheme,
};

if (reverbKey) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },
    });
} else {
    console.error('Reverb/Echo disabled: VITE_REVERB_APP_KEY is missing at build time. Current config:', window.reverbConfig);
}
