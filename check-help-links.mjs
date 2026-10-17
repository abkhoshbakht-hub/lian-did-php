/** Verifies every help item resolves to a real admin route. */
import { execSync } from 'node:child_process';

import { pathToFileURL } from 'node:url';
const { default: helpData } = await import(
    pathToFileURL('D:/2/resources/js/data/helpData.js').href
);

const raw = execSync('php artisan route:list --json', { cwd: 'D:/2', encoding: 'utf8', maxBuffer: 1024 * 1024 * 20 });
const uris = new Set(JSON.parse(raw).map((r) => r.uri));

// mirror the lookup in Help.jsx: exact key, then "key-" prefix
const { default: TARGETS, targetFor } = await import(
    pathToFileURL('D:/2/resources/js/data/helpTargets.js').href
);

function routeExists(href) {
    const p = '/' + href.replace(/^\//, '').replace(/\?.*$/, '');
    for (const u of uris) {
        const nu = '/' + u.replace(/^\//, '');
        if (nu === p) return true;
        const re = new RegExp('^' + nu.split('/').map((s) => (s.startsWith('{') ? '[^/]+' : s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))).join('/') + '$');
        if (re.test(p)) return true;
    }
    return false;
}

let bad = 0;
let total = 0;
const fallback = [];

for (const section of helpData) {
    for (const item of section.items) {
        total++;
        const href = targetFor(item.id);
        const fellBack = targetFor(item.id) === '/admin/help';
        if (fellBack) fallback.push(item.id);
        if (!routeExists(href)) {
            bad++;
            console.log(`  BROKEN  ${section.title} > ${item.id}  -> ${href}`);
        }
    }
}

console.log(`\nsections: ${helpData.length}   items: ${total}`);
console.log(`explicit targets: ${Object.keys(TARGETS).length}`);
console.log(`items falling back to dashboard: ${fallback.length}${fallback.length ? ' (' + fallback.slice(0, 8).join(', ') + ')' : ''}`);
console.log(bad === 0 ? 'EVERY HELP ITEM POINTS AT A REAL PAGE' : `${bad} HELP ITEM(S) POINT NOWHERE`);
process.exit(bad === 0 ? 0 : 1);