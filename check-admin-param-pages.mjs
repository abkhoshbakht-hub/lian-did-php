#!/usr/bin/env node
/**
 * Logs in as admin and requests the parameterised admin pages
 * (/admin/header/{group}, /admin/homepage/{section}) that check-nav-links.mjs
 * wrongly reported as missing.
 * Run: node check-admin-param-pages.mjs
 */
import fs from 'node:fs';
import { execFileSync } from 'node:child_process';

const BASE = process.env.SITE_URL || 'http://127.0.0.1:8000';
const EMAIL = process.env.ADMIN_EMAIL;
const PASS = process.env.ADMIN_PASSWORD;

if (!EMAIL || !PASS) {
    console.error('set ADMIN_EMAIL and ADMIN_PASSWORD first:');
    console.error('  $env:ADMIN_EMAIL="..."; $env:ADMIN_PASSWORD="..."; node check-admin-param-pages.mjs');
    process.exit(1);
}

const COOKIE = process.env.COOKIE_PATH
    || 'C:/Users/LIANDE~1/AppData/Local/Temp/opencode/lian.cookie';

fs.rmSync(COOKIE, { force: true });

const post = (path, fields) => {
    const args = ['-s', '-c', COOKIE, '-b', COOKIE, '-o', 'NUL', '-w', '%{http_code}', '-X', 'POST'];
    for (const [k, v] of Object.entries(fields)) args.push('-d', `${k}=${encodeURIComponent(v)}`);
    args.push(BASE + path);
    return exec(args);
};

function exec(args) {
    return execFileSync('curl.exe', args, { encoding: 'utf8' }).trim();
}

const get = (path) =>
    execFileSync('curl.exe', ['-s', '-c', COOKIE, '-b', COOKIE, BASE + path], {
        encoding: 'utf8',
        maxBuffer: 20 * 1024 * 1024,
    });

// The login screen is an Inertia app: the CSRF token lives in the
// XSRF-TOKEN cookie and must be sent back in the X-XSRF-TOKEN header.
get('/login');
const jar = fs.readFileSync(COOKIE, 'utf8');
const xsrf = (jar.match(/XSRF-TOKEN\s+(\S+)/) || [])[1];
console.log(`xsrf cookie      ${xsrf ? 'found' : 'MISSING'}`);

const login = exec([
    '-s', '-c', COOKIE, '-b', COOKIE, '-o', 'NUL', '-w', '%{http_code}',
    '-X', 'POST',
    '-H', `X-XSRF-TOKEN: ${decodeURIComponent(xsrf || '')}`,
    '-d', `email=${encodeURIComponent(EMAIL)}`,
    '-d', `password=${encodeURIComponent(PASS)}`,
    BASE + '/login',
]);
console.log(`login            ${login}\n`);

const pages = [
    ...['logo', 'license', 'about', 'contact', 'search', 'nav'].map((g) => `/admin/header/${g}`),
    ...['hero', 'latest', 'analysis'].map((s) => `/admin/homepage/${s}`),
    '/admin/social',
    '/admin/gallery',
];

let bad = 0;
for (const p of pages) {
    const code = exec(['-s', '-c', COOKIE, '-b', COOKIE, '-o', 'NUL', '-w', '%{http_code}', BASE + p]);
    if (code !== '200') bad++;
    console.log(`  ${p.padEnd(30)} ${code}`);
}

console.log(`\nnot-200: ${bad}`);
console.log(bad === 0 ? 'ALL PARAMETERISED PAGES OK' : 'SOME PAGES FAILED');