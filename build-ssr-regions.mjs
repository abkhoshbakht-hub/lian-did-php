import { createElement } from 'react';
import { renderToString } from 'react-dom/server';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { build } from 'vite';
import react from '@vitejs/plugin-react';

const base = 'D:/2';

// Build an SSR bundle for the two page/component pair under test.
await build({
    root: base,
    logLevel: 'error',
    plugins: [react()],
    resolve: {
        alias: [
            { find: /^@inertiajs\/react$/, replacement: path.resolve(base, 'tests/stubs/inertia-stub.jsx') },
            { find: '@', replacement: path.resolve(base, 'resources/js') },
        ],
    },
    ssr: { noExternal: true },
    build: {
        ssr: path.resolve(base, 'tests/ssr-regions.mjs'),
        outDir: path.resolve(base, '.tmp-ssr2'),
        emptyOutDir: true,
        rollupOptions: { output: { entryFileNames: 'regions.mjs', format: 'esm' } },
    },
});

console.log('ssr bundle built');
console.log('run: node .tmp-ssr2/regions.mjs <props.json>');
void createElement;
void renderToString;
void fs;
void pathToFileURL;