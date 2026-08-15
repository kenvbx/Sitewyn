import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const appInputs = ['resources/css/app.css', 'resources/js/app.js'];
const moduleResourceRoots = [
    'platform/core',
    'platform/packages',
    'platform/plugins',
    'platform/themes',
];

function collectModuleInputs() {
    return moduleResourceRoots.flatMap((moduleRoot) => {
        const absoluteRoot = path.join(root, moduleRoot);

        if (!fs.existsSync(absoluteRoot)) {
            return [];
        }

        return fs.readdirSync(absoluteRoot, { withFileTypes: true })
            .filter((entry) => entry.isDirectory())
            .flatMap((entry) => {
                const resourceRoot = path.join(absoluteRoot, entry.name, 'resources');
                const candidates = [
                    path.join(resourceRoot, 'css', 'app.css'),
                    path.join(resourceRoot, 'css', 'admin.css'),
                    path.join(resourceRoot, 'js', 'app.js'),
                    path.join(resourceRoot, 'js', 'admin.js'),
                ];

                return candidates
                    .filter((candidate) => fs.existsSync(candidate))
                    .map((candidate) => path.relative(root, candidate));
            });
    });
}

export default defineConfig({
    plugins: [
        laravel({
            input: [...appInputs, ...collectModuleInputs()],
            refresh: [
                'app/**/*.php',
                'resources/views/**/*.blade.php',
                'routes/**/*.php',
                'platform/**/resources/views/**/*.blade.php',
                'platform/**/resources/js/**/*.js',
                'platform/**/resources/css/**/*.css',
            ],
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
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
