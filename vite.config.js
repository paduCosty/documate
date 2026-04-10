import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

const isDev = process.env.NODE_ENV !== 'production';

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
    ...(isDev && {
        server: {
            host: true,
            port: 5173,
            strictPort: true,
            origin: 'http://localhost:5174',
            cors: true,
            hmr: {
                host: 'localhost',
                clientPort: 5174,
            },
            watch: { usePolling: true },
        }
    })
    
});