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

// A route like admin/header/{group} also serves /admin/header/logo.
// Compare segment by segment: a {placeholder} matches any one segment,
// every other segment has to be identical.
const exists = (p) => {
    const want = p.replace(/\/+$/, '').split('/').filter(Boolean);
    return routes.some((r) => {
        const have = (r.uri || '').replace(/\?L_\d+.*$/, '').split('/').filter(Boolean);
        if (have.length !== want.length) return false;
        return have.every((seg, i) => seg.includes('{') || seg === want[i]);
    });
};

const missing = paths.filter((p) => !exists(p));

console.log('--- MISSING ROUTES ---');
missing.forEach((m) => console.log('  ' + m));
console.log(missing.length === 0 ? '  (none)' : `\n  total missing: ${missing.length}`);
