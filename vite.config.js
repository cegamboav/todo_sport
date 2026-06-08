import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
                'resources/js/app-rings.ts',
                'resources/js/app-judge.ts',
                'resources/js/app-professor.ts',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@shared': fileURLToPath(new URL('./resources/js/shared', import.meta.url)),
            '@layouts': fileURLToPath(new URL('./resources/js/layouts', import.meta.url)),
            '@domains': fileURLToPath(new URL('./resources/js/domains', import.meta.url)),
        },
    },
});
