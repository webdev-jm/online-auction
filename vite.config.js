import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/sass/auth.scss',
                'resources/js/app.js',
                'resources/js/login-scene.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // three.js alone is ~500 kB minified; it only loads on the sign-in and welcome pages.
        chunkSizeWarningLimit: 600,
    },
});
