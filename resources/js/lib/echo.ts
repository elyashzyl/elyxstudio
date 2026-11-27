import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { ref } from 'vue';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'> | Echo<'pusher'> | null;
        EduPulseBroadcasting?: {
            driver?: string;
            reverb?: {
                key?: string;
                host?: string;
                port?: number | string;
                scheme?: string;
            };
        };
    }
}

window.Pusher = Pusher;

let echoInstance: Echo<'reverb'> | null = null;
export const isWsConnected = ref(false);

export function getEcho(configOverride?: any): Echo<'reverb'> | null {
    if (echoInstance) {
        return echoInstance;
    }

    if (typeof window === 'undefined') {
        return null;
    }

    const broadcastConfig =
        configOverride ||
        window.EduPulseBroadcasting?.reverb ||
        (window as any).__PAGE__?.props?.broadcasting?.reverb ||
        {};

    const key =
        broadcastConfig.key ||
        import.meta.env.VITE_REVERB_APP_KEY ||
        'edupulse-key';

    const host =
        broadcastConfig.host ||
        import.meta.env.VITE_REVERB_HOST ||
        window.location.hostname ||
        'localhost';

    const port = Number(
        broadcastConfig.port ||
            import.meta.env.VITE_REVERB_PORT ||
            (window.location.protocol === 'https:' ? 443 : 8080),
    );

    const scheme =
        broadcastConfig.scheme ||
        import.meta.env.VITE_REVERB_SCHEME ||
        (window.location.protocol === 'https:' ? 'https' : 'http');

    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';

    try {
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: host,
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            },
        });

        const connector = (echoInstance as any).connector;

        if (connector?.pusher?.connection) {
            connector.pusher.connection.bind('connected', () => {
                isWsConnected.value = true;
            });
            connector.pusher.connection.bind('disconnected', () => {
                isWsConnected.value = false;
            });
            connector.pusher.connection.bind('error', () => {
                isWsConnected.value = false;
            });
        }

        window.Echo = echoInstance;

        return echoInstance;
    } catch {
        isWsConnected.value = false;

        return null;
    }
}

export function disconnectEcho() {
    if (echoInstance) {
        try {
            echoInstance.disconnect();
        } catch {
            // Ignore disconnect errors during teardown.
        }

        echoInstance = null;
        window.Echo = null;
        isWsConnected.value = false;
    }
}
