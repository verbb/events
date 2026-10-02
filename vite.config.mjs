import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/cp');
const jsEntries = new Set(['event-edit', 'event-index', 'session-edit', 'session-index']);

export default defineConfig(({ mode }) => {
    const isCssBuild = mode === 'css';

    if (!isCssBuild && !jsEntries.has(mode)) {
        throw new Error(`Unknown asset build mode: ${mode}`);
    }

    const input = isCssBuild ? {
        'edit-meta': resolve(root, 'src/edit-meta.css'),
        'session-index': resolve(root, 'src/session-index.css'),
    } : resolve(root, `src/${mode}.js`);

    const output = isCssBuild ? {
        assetFileNames: '[name][extname]',
    } : {
        codeSplitting: false,
        entryFileNames: '[name].js',
        format: 'iife',
    };

    return {
        root,
        input,
        build: {
            outDir: resolve(root, 'dist'),
            emptyOutDir: isCssBuild,
            assetsDir: '',
            cssMinify: 'esbuild',
            cssTarget: ['chrome61', 'safari10'],
            minify: 'oxc',
            sourcemap: !isCssBuild,
            target: 'es2015',
            rolldownOptions: {
                output,
            },
        },
    };
});
