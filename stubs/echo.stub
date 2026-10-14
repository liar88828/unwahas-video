import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'>;
    }
}

const isBrowser = typeof window !== 'undefined';

if (isBrowser) {
    window.Pusher = Pusher;
}

const host = isBrowser ? window.location.hostname : 'localhost';
const isSecure = isBrowser ? window.location.protocol === 'https:' : false;

const echo: Echo<'reverb'> = isBrowser
    ? new Echo({
          broadcaster: 'reverb',
          key: import.meta.env.VITE_REVERB_APP_KEY || 'reverb-local-key',
          wsHost: host,
          wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
          wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
          forceTLS: isSecure,
          enabledTransports: ['ws', 'wss'],
      })
    : (new Proxy({}, {
          get: () => () => ({
              here: () => ({ joining: () => ({ leaving: () => ({ error: () => ({ listenForWhisper: () => ({}) }) }) }) }),
              joining: () => ({ leaving: () => ({ error: () => ({}) }), listenForWhisper: () => ({}) }),
              leaving: () => ({ error: () => ({}) }),
              error: () => ({}),
              listen: () => ({}),
              listenForWhisper: () => ({}),
              whisper: () => ({}),
              stopListening: () => ({}),
          }),
      }) as unknown as Echo<'reverb'>);

if (isBrowser) {
    window.Echo = echo;
}

export { echo };
export default echo;
