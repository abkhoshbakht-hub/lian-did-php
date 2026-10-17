/**
 * Finds public-facing links in JSX that have no matching route.
 * Catches 404s before the user clicks them.
 */
import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';

const root = 'D:/2';

// Collect every route uri from artisan.
const raw = execSync('php artisan route:list --json', { cwd: root, encoding: 'utf8', maxBuffer: 1024 * 1024 * 20 });
const routes = JSON.parse(raw);
const uris = new Set(routes.map((r) => r.uri));

function normalise(u) {
    let s = u.split('?')[0].split('#')[0];
    s = s.replace(/\/$/, '');
    if (s === '') s = '/';
    if (!s.startsWith('/')) s = '/' + s;
    return s;
}

function routeExists(href) {
    const p = normalise(href);
    if (uris.has(p) || uris.has(p.replace(/^\//, ''))) return true;
    // placeholder segments
    const pattern = new RegExp(
        '^' +
            (uris.has(p.replace(/^\//, '')) ? '' : '') +
            p
                .split('/')
                .map((seg) => {
                    if (seg.startsWith('{')) return '[^/]+';
                    return seg.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                })
                .join('/') +
            '$'
    );
    for (const u of uris) {
        const nu = normalise(u);
        if (nu === p) return true;
        const rp = new RegExp(
            '^' +
                nu
                    .split('/')
                    .map((seg) => (seg.startsWith('{') ? '[^/]+' : seg.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')))
                    .join('/') +
                '$'
        );
        if (rp.test(p)) return true;
    }
    return false;
}

// Walk public-facing components/pages.
const targets = [];
const walk = (dir) => {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, e.name);
        if (e.isDirectory()) walk(full);
        else if (/\.jsx$/.test(e.name)) targets.push(full);
    }
};
walk(path.join(root, 'resources/js/Components'));
walk(path.join(root, 'resources/js/Pages'));
walk(path.join(root, 'resources/js/Layouts'));

const linkRe = /(?:href|to)\s*=\s*["'`]([^"'`$]+)["'`]|router\.(?:get|post)\(\s*["'`]([^"'`$]+)["'`]/g;

const problems = new Map();

for (const file of targets) {
    // only public surface (skip the admin panel)
    if (file.replace(/\\/g, '/').includes('/Pages/Admin/')) continue;
    if (file.includes('AdminLayout')) continue;

    const src = fs.readFileSync(file, 'utf8');
    let m;
    while ((m = linkRe.exec(src))) {
        const href = m[1] || m[2];
        if (!href || href.startsWith('#')) continue;
        if (href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('tel:')) continue;
        if (href.startsWith('/api/')) continue;
        if (href.includes('${')) continue; // template literal, skip
        if (href.startsWith('/storage/') || href.startsWith('/images/') || href.startsWith('/uploads/')) continue;

        if (!routeExists(href)) {
            const key = href;
            if (!problems.has(key)) problems.set(key, new Set());
            problems.get(key).add(path.relative(root, file).replace(/\\/g, '/'));
        }
    }
}

console.log('=== BROKEN PUBLIC LINKS ===');
if (problems.size === 0) {
    console.log('none - every static link matches a route');
} else {
    const sorted = [...problems.entries()].sort();
    for (const [href, files] of sorted) {
        console.log(`\n  ${href}`);
        for (const f of files) console.log(`      ${f}`);
    }
    console.log(`\ntotal broken: ${problems.size}`);
}
process.exit(problems.size === 0 ? 0 : 1);