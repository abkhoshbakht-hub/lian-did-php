import { readFileSync } from 'node:fs';
import {
    COUNTIES, BUSHEHR_KEYS, nearestCounty, isBushehrNews, matchesCounty, countyForNews,
} from './resources/js/Components/news/bushehr-counties.js';

let fail = 0;
const check = (name, ok) => {
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${name}`);
    if (!ok) fail++;
};

console.log('=== BUSHEHR MAP TEST ===\n');

console.log('--- county data (must match the SVG tuning) ---');
console.log(`  counties: ${COUNTIES.map((c) => `${c.name}(${c.key}@${c.cx},${c.cy})`).join(' ')}`);
check('11 counties', COUNTIES.length === 11);
check('keys unique', new Set(BUSHEHR_KEYS).size === 11);
check('every county has a centroid', COUNTIES.every((c) => Number.isFinite(c.cx) && Number.isFinite(c.cy)));
check('every county has keywords', COUNTIES.every((c) => c.keywords?.length > 0));
// the reference order matters because COUNTIES[0] is the fallback default
check('first county is deylam', COUNTIES[0].key === 'deylam');
check('exact centroids match the reference', COUNTIES.map((c) => `${c.cx},${c.cy}`).join('|')
    === '62,96|135,208|64,270|182,339|239,248|251,424|355,502|346,610|436,586|484,672|573,753');

console.log('\n--- SVG centroids must land on the right county ---');
const svg = readFileSync('D:/2/public/maps/bushehr-fa.svg', 'utf8');
const polys = [...svg.matchAll(/<polygon[^>]*points="([^"]+)"/g)].map((m) => m[1]);
console.log(`  polygons: ${polys.length}`);

const assignments = polys.map((pts) => {
    const n = pts.trim().split(/[\s,]+/).map(Number);
    let sx = 0, sy = 0, c = 0;
    for (let i = 0; i < n.length - 1; i += 2) { sx += n[i]; sy += n[i + 1]; c++; }
    return nearestCounty(sx / c, sy / c);
});
const covered = new Set(assignments);
console.log(`  counties covered: ${covered.size} -> ${[...covered].join(', ')}`);
check('SVG polygons cover at least 9 counties', covered.size >= 9);
check('all coordinates inside the 650x800 viewBox', polys.every((pts) => {
    const n = pts.trim().split(/[\s,]+/).map(Number);
    const xs = n.filter((_, i) => i % 2 === 0);
    const ys = n.filter((_, i) => i % 2 === 1);
    return Math.min(...xs) >= 0 && Math.max(...xs) <= 650
        && Math.min(...ys) >= 0 && Math.max(...ys) <= 800;
}));

console.log('\n--- hit-test sanity: a point in each county resolves to itself ---');
let ok = true;
for (const c of COUNTIES) {
    if (nearestCounty(c.cx, c.cy) !== c.key) {
        ok = false;
        console.log(`      ${c.key}: got ${nearestCounty(c.cx, c.cy)}`);
    }
}
check('every centroid resolves to its own county', ok);

console.log('\n--- news matching (mirrors the reference rules) ---');
check('item with region booshehr:city matches booshehr', matchesCounty({ region: 'booshehr:city' }, 'booshehr'));
check('region suffix is ignored', matchesCounty({ region: 'jam:village' }, 'jam'));
check('falls back to a title keyword', matchesCounty({ title: 'اهرم مراسم برگزار کرد' }, 'tangestan'));
check('a different county does not match', !matchesCounty({ title: 'اهرم' }, 'jam'));
check('no region means province level', isBushehrNews({ title: 'خبر' }));
check('a known region passes the filter', isBushehrNews({ region: 'dashti:city' }));
check('an unknown region is filtered out', !isBushehrNews({ region: 'tehran:city' }));
check('countyForNews uses the region', countyForNews({ region: 'kangan:city' }) === 'kangan');
check('countyForNews falls back to keywords', countyForNews({ title: 'بنک بندرگاه' }) === 'kangan');
check('countyForNews gives null when unrelated', countyForNews({ title: 'قیمت دلار' }) === null);

console.log('\n--- the tuning CSS is in the shipped stylesheet ---');
const css = readFileSync('D:/2/resources/css/app.css', 'utf8');
for (const rule of [
    'aspect-ratio: 650 / 800',
    '.bushehr-svg-container circle',
    'pointer-events: none',
    '.bushehr-map-wrapper',
    'max-width: 560px',
    '.bushehr-map-hint',
]) {
    check(`css has "${rule}"`, css.includes(rule));
}

console.log('\n' + (fail === 0 ? 'BUSHEHR MAP: ALL PASS' : `BUSHEHR MAP: ${fail} FAILED`));
process.exit(fail === 0 ? 0 : 1);