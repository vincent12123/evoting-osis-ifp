import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Aset layar IFP (kiosk). Semua font & skrip disajikan dari server sendiri — tanpa CDN (NFR-25).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/kiosk.css', 'resources/js/kiosk.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
