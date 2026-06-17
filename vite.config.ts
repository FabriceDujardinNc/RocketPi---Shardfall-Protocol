import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@components': path.resolve(__dirname, 'resources/js/Components'),
            '@ui': path.resolve(__dirname, 'resources/js/Components/UI'),
            '@game': path.resolve(__dirname, 'resources/js/Components/Game'),
            '@pages': path.resolve(__dirname, 'resources/js/Pages'),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        // When Vite runs inside Docker, HMR must point to the host-accessible address
        // so the browser can open the WebSocket connection directly.
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            usePolling: true,          // required for inotify inside WSL2 / Docker
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
