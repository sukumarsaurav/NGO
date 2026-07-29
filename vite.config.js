import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts per docs/08-DESIGN-SYSTEM.md §3. Fetched from Bunny Fonts at BUILD time
// and served from our own origin — no runtime CDN request, no critical-path
// dependency on a third party. Inter is the body font everywhere; Noto Sans
// Devanagari backs Hindi CMS content and is loaded with `unicode-range` so it
// only costs bytes on pages that actually render Devanagari text.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Inter', { weights: [400, 500, 600, 700] }),
                bunny('Noto Sans Devanagari', { weights: [400, 500, 600] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
