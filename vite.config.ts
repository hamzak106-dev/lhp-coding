import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        cors: {
            origin: ['http://lhp-coding.local', 'http://localhost', 'http://127.0.0.1'],
        },
        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },
    },
    build: {
        rollupOptions: {
            onwarn(warning, defaultHandler) {
                if (
                    warning.code === 'INVALID_ANNOTATION' &&
                    warning.id?.includes('node_modules/reka-ui/node_modules/@vueuse/core/')
                ) {
                    return;
                }

                defaultHandler(warning);
            },
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
});
