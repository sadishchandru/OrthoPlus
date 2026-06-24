import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'vue-router', 'pinia', 'axios'],
                    three: ['three'],
                    calendar: [
                        '@fullcalendar/vue3', '@fullcalendar/core', '@fullcalendar/daygrid',
                        '@fullcalendar/timegrid', '@fullcalendar/list', '@fullcalendar/interaction',
                    ],
                },
            },
        },
        chunkSizeWarningLimit: 600,
    },
});
