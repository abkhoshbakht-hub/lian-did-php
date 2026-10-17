/**
 * Walks every admin nav link and reports the real HTTP status as a logged-in admin.
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

const src = fs.readFileSync('D:/2/resources/js/Components/admin/navConfig.jsx', 'utf8');
const hrefs = [...src.matchAll(/href:\s*'([^']+)'/g)].map((m) => m[1]);

// build an authenticated cookie jar with curl, then hit each link
const { execSync } = await import('node:child_process');
const jar = 'C:/Users/LIANDE~1/AppData/Local/Temp/opencode/navjar.txt';

function sh(cmd) {
    return execSync(cmd, { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] });
}

try { fs.unlinkSync(jar); } catch {}

sh(`curl.exe -s -c "${jar}" "http://127.0.0.1:8000/login" -o NUL`);
const cookie = fs.readFileSync(jar, 'utf8').split('\n').find((l) => l.includes('XSRF-TOKEN'));
const xsrf = decodeURIComponent(cookie.split('\t').pop());

sh(`curl.exe -s -o NUL -b "${jar}" -c "${jar}" -X POST "http://127.0.0.1:8000/login" -H "X-XSRF-TOKEN: ${xsrf}" -H "X-Requested-With: XMLHttpRequest" --data-urlencode "email=admin@liandid.ir" --data-urlencode "password=LianDid2026!"`);

let bad = 0;
const seen = new Set();

console.log('path'.padEnd(42) + 'HTTP  verdict');
console.log('-'.repeat(70));

for (const href of hrefs) {
    const path = href.split('?')[0];
    if (seen.has(path)) continue;
    seen.add(path);

    let code = 'ERR';
    try {
        const out = sh(`curl.exe -s -o NUL -w "%{http_code}" -b "${jar}" "http://127.0.0.1:8000${path}"`);
        code = out.trim();
    } catch (e) {
        code = 'ERR';
    }

    const ok = code === '200';
    if (!ok) bad++;
    console.log(path.padEnd(42) + String(code).padEnd(6) + (ok ? 'OK' : '<-- BROKEN'));
}

console.log('-'.repeat(70));
console.log(`unique paths: ${seen.size}   broken: ${bad}`);
console.log(bad === 0 ? 'EVERY NAV LINK WORKS' : `${bad} BROKEN LINKS`);
