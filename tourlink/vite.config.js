import { cpSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const appDirectory = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        {
            name: 'mirror-build-to-document-root',
            apply: 'build',
            closeBundle() {
                const publicBuild = resolve(appDirectory, 'public/build');
                const documentRootBuild = resolve(appDirectory, '../build');
                const documentRoot = resolve(appDirectory, '..');

                mkdirSync(documentRootBuild, { recursive: true });
                cpSync(publicBuild, documentRootBuild, { recursive: true, force: true });
                cpSync(resolve(appDirectory, 'public/icons'), resolve(documentRoot, 'icons'), { recursive: true, force: true });

                for (const file of ['manifest.json', 'manifest.webmanifest', 'sw.js', 'offline.html']) {
                    cpSync(resolve(appDirectory, 'public', file), resolve(documentRoot, file));
                }
            },
        },
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
