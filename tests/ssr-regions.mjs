import React from 'react';
import { renderToString } from 'react-dom/server';
import fs from 'node:fs';
import Regions from '../resources/js/Pages/CityMap.jsx';
import BushehrMap from '../resources/js/Components/news/BushehrMap.jsx';
import MainLayout from '../resources/js/Layouts/MainLayout.jsx';

const props = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const news = props.news || [];

let fail = 0;
const check = (n, ok) => {
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${n}`);
    if (!ok) fail++;
};

console.log('=== /regions SSR TEST ===\n');
console.log(`news items: ${news.length}\n`);

// 1. the map component on its own
console.log('--- BushehrMap ---');
let mapHtml = '';
let mapErr = null;
try {
    mapHtml = renderToString(React.createElement(BushehrMap, { news }));
} catch (e) {
    mapErr = e.message + '\n' + (e.stack || '').split('\n').slice(0, 4).join('\n');
}
check('map renders without throwing', !mapErr);
if (mapErr) {
    console.log('\nERROR:\n' + mapErr + '\n');
} else {
    check('map wrapper present', mapHtml.includes('bushehr-map-wrapper'));
    check('map header present', mapHtml.includes('bushehr-map-header'));
    check('svg container present', mapHtml.includes('bushehr-svg-container'));
    check('hint bar present', mapHtml.includes('bushehr-map-hint'));
    check('no county is selected yet', !mapHtml.includes('bushehr-news-section'));
    console.log(`  rendered bytes: ${mapHtml.length}`);
}

// 2. the whole page
console.log('\n--- Regions page ---');
let pageHtml = '';
let pageErr = null;
try {
    pageHtml = renderToString(React.createElement(Regions, { news }));
} catch (e) {
    pageErr = e.message + '\n' + (e.stack || '').split('\n').slice(0, 6).join('\n');
}
check('page renders without throwing', !pageErr);
if (pageErr) {
    console.log('\nERROR:\n' + pageErr + '\n');
} else {
    check('page includes the map', pageHtml.includes('bushehr-svg-container'));
    check('page lists every county', pageHtml.includes('شهرستان‌ها'));
    check('header shows the correct title', !pageHtml.includes('اخبار شهرستان '));
    console.log(`  rendered bytes: ${pageHtml.length}`);
}

// 3. the layout must not break the page
console.log('\n--- MainLayout ---');
let layoutErr = null;
try {
    renderToString(
        React.createElement(MainLayout, {
            title: 'مناطق',
            children: React.createElement('p', null, 'x'),
        })
    );
} catch (e) {
    layoutErr = e.message;
}
check('MainLayout renders', !layoutErr);
if (layoutErr) console.log('  ' + layoutErr);

console.log('\n' + (fail === 0 ? 'REGIONS SSR: ALL PASS' : `REGIONS SSR: ${fail} FAILED`));
process.exit(fail === 0 ? 0 : 1);