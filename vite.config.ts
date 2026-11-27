import { execSync } from 'node:child_process';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

const hasPhp = () => {
    try {
        execSync('php --version', { stdio: 'ignore' });

        return true;
    } catch {
        return false;
    }
};

const shouldGenerateWayfinder = process.env.WAYFINDER_GENERATE !== 'false';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        allowedHosts: true,
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
        ...(shouldGenerateWayfinder && hasPhp()
            ? [
                    wayfinder({
                        formVariants: true,
                    }),
                ]
            : []),
    ],
});
