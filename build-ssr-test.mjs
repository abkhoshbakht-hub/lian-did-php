import { build } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

await build({
    root: 'D:/2',
    logLevel: 'error',
    plugins: [react()],
    resolve: {
        alias: [
            { find: /^@inertiajs\/react$/, replacement: path.resolve('D:/2/tests/stubs/inertia-stub.jsx') },
            { find: '@', replacement: path.resolve('D:/2/resources/js') },
        ],
    },
    ssr: { noExternal: true },
    build: {
        ssr: 'D:/2/test-homepage-render.mjs',
        outDir: 'D:/2/.tmp-ssr',
        emptyOutDir: true,
        rollupOptions: { output: { entryFileNames: 'home.mjs', format: 'esm' } },
    },
});
console.log('ssr bundle built');