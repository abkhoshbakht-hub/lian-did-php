/**
 * Extracts every href from the admin nav config and checks each route exists.
 * Run: node check-nav-links.mjs
 */
import fs from 'node:fs';

const src = fs.readFileSync('D:/2/resources/js/Components/admin/navConfig.jsx', 'utf8');

const hrefs = [...src.matchAll(/href:\s*'([^']+)'/g)].map((m) => m[1]);

// resolve /admin/articles?cat=x -> /admin/articles
const paths = [...new Set(hrefs.map((h) => h.split('?')[0]))];

console.log(`nav links: ${hrefs.length}`);
console.log(`unique paths: ${paths.length}\n`);

// ask Laravel which routes exist
const { execFileSync } = await import('node:child_process');
const list = execFileSync('php', ['artisan', 'route:list', '--json'], {
    cwd: 'D:/2',
    encoding: 'utf8',
    maxBuffer: 10 * 1024 * 1024,
});
const routes = JSON.parse(list);

const exists = (p) => routes.some((r) => {
    const uri = '/' + (r.uri || '').replace(/\?L_\d+.*$/, '').replace(/\{[^}]+\}/g, '');
    return uri === p.replace(/\/+$/, '') || uri + '/' === p;
});

const missing = paths.filter((p) => !exists(p));

console.log('--- MISSING ROUTES ---');
missing.forEach((m) => console.log('  ' + m));
console.log(missing.length === 0 ? '  (none)' : `\n  total missing: ${missing.length}`);
