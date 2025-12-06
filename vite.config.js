import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/voice-panel.css',
                'resources/css/voice-view.css',
                'resources/js/app.js',
                'resources/js/voice-chat.js',
                'resources/js/voice-view.js',
                'resources/js/lobby-page.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
