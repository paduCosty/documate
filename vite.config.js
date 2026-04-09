// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    build: {
        cssMinify: false,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    // vite.config.js
    server: {
        host: true,
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5174',
        cors: true,                    // temporar, ca să simplificăm
        hmr: {
            host: 'localhost',
            clientPort: 5174,
        },
        watch: { usePolling: true },
    },
});