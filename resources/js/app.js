import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;
const port = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

/** Mesmo host da barra de endereço quando o .env usa localhost — evita WS para 127.0.0.1 errado ao abrir por samu192.local, etc. */
function resolveReverbHost() {
    const fromEnv = import.meta.env.VITE_REVERB_HOST;
    if (!fromEnv || fromEnv === 'localhost' || fromEnv === '127.0.0.1') {
        return window.location.hostname;
    }

    return fromEnv;
}

const host = resolveReverbHost();

let operationalCallIntakeEchoBound = false;

function subscribeOperationalCallIntake() {
    const body = document.body;
    if (!body?.dataset?.broadcastOperations || body.dataset.broadcastOperations !== '1') {
        return;
    }
    if (!window.Echo) {
        return;
    }
    if (operationalCallIntakeEchoBound) {
        return;
    }
    operationalCallIntakeEchoBound = true;

    window.Echo.private('operations.dispatch').listen('.operational.call-intake', (payload) => {
        const Livewire = window.Livewire;
        if (!Livewire || typeof Livewire.dispatch !== 'function') {
            return;
        }

        Livewire.dispatch('operational-call-intake', {
            form_url: payload?.form_url ?? '',
            phone: payload?.phone ?? '',
            expires_at: payload?.expires_at ?? '',
            caller_name: payload?.caller_name ?? null,
            latitude: payload?.latitude ?? null,
            longitude: payload?.longitude ?? null,
            call_received_at: payload?.call_received_at ?? null,
            external_reference: payload?.external_reference ?? null,
        });
    });
}

if (key) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${window.location.origin}/broadcasting/auth`,
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
            },
        },
        withCredentials: true,
    });

    // Inscreve assim que o Echo existe (não depende de livewire:init; o handler só usa Livewire quando o evento chega).
    subscribeOperationalCallIntake();
}
