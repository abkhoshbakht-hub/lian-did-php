/**
 * Guards against the "white page" bug: every component Inertia renders must
 * have a matching file under resources/js/Pages, otherwise resolvePageComponent
 * throws and React never mounts.
 */
import fs from 'node:fs';
import path from 'node:path';

const root = 'D:/2';
const controllers = [
    'app/Http/Controllers/Admin/SectionController.php',
    'app/Http/Controllers/Admin/ManageController.php',
    'app/Http/Controllers/Admin/BackupController.php',
    'routes/web.php',
];

const rendered = new Set();
for (const rel of controllers) {
    const full = path.join(root, rel);
    if (!fs.existsSync(full)) continue;
    const src = fs.readFileSync(full, 'utf8');
    for (const m of src.matchAll(/Inertia::render\(\s*'([^']+)'/g)) {
        rendered.add(m[1]);
    }
}

const pagesDir = path.join(root, 'resources/js/Pages');
let missing = 0;
const rows = [];

for (const name of [...rendered].sort()) {
    const file = path.join(pagesDir, `${name}.jsx`);
    const exists = fs.existsSync(file);
    if (!exists) missing++;
    rows.push([exists ? 'OK     ' : 'MISSING', name]);
}

console.log('Inertia page -> file mapping');
console.log('-'.repeat(60));
rows.forEach(([s, n]) => console.log(`${s}  ${n}`));
console.log('-'.repeat(60));
console.log(`total: ${rendered.size}   missing: ${missing}`);
console.log(missing === 0 ? 'EVERY RENDERED PAGE HAS A FILE' : `${missing} PAGE(S) WOULD RENDER BLANK`);

process.exit(missing === 0 ? 0 : 1);
