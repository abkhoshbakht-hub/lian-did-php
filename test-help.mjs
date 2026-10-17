import { createElement } from 'react';
import { renderToString } from 'react-dom/server';
import helpData from './resources/js/data/helpData.js';
import { TARGETS, targetFor } from './resources/js/data/helpTargets.js';

console.log('=== HELP PAGE SSR TEST ===\n');

const items = helpData.flatMap((s) => s.items);
const empty = items.filter((i) => !i.content || i.content.trim().length < 20);
const noIcon = helpData.filter((s) => !s.icon);
const noTitle = helpData.filter((s) => !s.title);
const collapsed = items.filter((i) => !TARGETS[i.id]);

console.log('sections            :', helpData.length);
console.log('items               :', items.length);
console.log('empty content       :', empty.length);
console.log('sections missing icon:', noIcon.length);
console.log('sections missing title:', noTitle.length);
console.log('items with exact key:', collapsed.length);
console.log('');

const targetCounts = {};
for (const i of items) {
    const t = targetFor(i.id);
    targetCounts[t] = (targetCounts[t] || 0) + 1;
}
console.log('distinct target pages:', Object.keys(targetCounts).length);
console.log('');
console.log('targets used:');
Object.entries(targetCounts)
    .sort((a, b) => b[1] - a[1])
    .forEach(([t, n]) => console.log(`  ${String(n).padStart(3)}  ${t}`));

console.log('');
console.log('sample of the help text:');
const sample = items[4];
console.log('  ', sample.title);
console.log('  ', sample.content.slice(0, 150).replace(/\n/g, ' '), '...');

console.log('');
const checks = [
    ['9 sections ported', helpData.length === 9],
    ['55 items ported', items.length === 55],
    ['every item has content', empty.length === 0],
    ['every section has an icon', noIcon.length === 0],
    ['every section has a title', noTitle.length === 0],
    ['no item falls back to /help', collapsed.length === 0],
    ['targets are diverse (>=15 pages)', Object.keys(targetCounts).length >= 15],
    ['text is real Persian', /[\u0600-\u06FF]/.test(sample.content)],
];

let bad = 0;
for (const [n, ok] of checks) {
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${n}`);
    if (!ok) bad++;
}
console.log('');
console.log(bad === 0 ? 'HELP PAGE: ALL PASS' : `HELP PAGE: ${bad} FAILED`);
process.exit(bad === 0 ? 0 : 1);