/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo;
    }
}

// Set up Echo with Reverb (Laravel's WebSocket server)
if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel: any) => {
            return {
                authorize: (socketId: string, callback: (error: boolean | Error, authInfo?: any) => void) => {
                    fetch('/broadcasting-custom-auth', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            socket_id: socketId,
                            channel_name: channel.name,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        callback(false, data);
                    })
                    .catch(error => {
                        console.error('Echo authorization failed:', error);
                        callback(true);
                    });
                }
            };
        },
    });
    
    console.log('Laravel Echo initialized with Reverb');
} else {
    // Real-time features disabled - this is normal if you haven't set up Reverb
    console.info('Laravel Echo not initialized (real-time features disabled). This is expected if REVERB is not configured.');
}
