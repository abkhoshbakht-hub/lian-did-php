import { createElement } from 'react';
import { renderToString } from 'react-dom/server';
import fs from 'node:fs';
import Home from './resources/js/Pages/Home.jsx';
import AnalysisSection from './resources/js/Components/news/AnalysisSection.jsx';

const props = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));

const { home = {}, articles = [], categories = [] } = props;
const analysis = home.analysis || [];
const latest = home.latest || [];
const breaking = home.breaking || [];

const analysisHtml = renderToString(createElement(AnalysisSection, { items: analysis }));
const cards = (analysisHtml.match(/group block bg-white rounded-2xl/g) || []).length;

let fullHtml = '';
let renderError = null;
try {
    fullHtml = renderToString(createElement(Home, { home, articles, categories }));
} catch (e) {
    renderError = e.message;
}

console.log('=== HOMEPAGE SSR TEST ===\n');
console.log('props.analysis :', analysis.length);
console.log('props.latest   :', latest.length);
console.log('props.breaking :', breaking.length);
console.log('hero main      :', home.heroMain ? 'resolved' : 'null');
console.log('');
console.log('--- AnalysisSection ---');
console.log('heading        :', analysisHtml.includes('تحلیل خبر') ? 'YES' : 'NO');
console.log('cards          :', cards);
console.log('anchors        :', (analysisHtml.match(/<a\s/g) || []).length);
console.log('');
console.log('--- Home page ---');
console.log('render error   :', renderError || 'none');
console.log('bytes          :', fullHtml.length);
console.log('has breaking bar:', fullHtml.includes('فوری') ? 'YES' : 'NO');
console.log('has analysis    :', fullHtml.includes('تحلیل خبر') ? 'YES' : 'NO');
console.log('has latest      :', fullHtml.includes('آخرین اخبار') ? 'YES' : 'NO');
console.log('');

const checks = [
    ['props.analysis === 3', analysis.length === 3],
    ['analysis heading rendered', analysisHtml.includes('تحلیل خبر')],
    ['analysis cards === 3', cards === 3],
    ['props.latest === 8', latest.length === 8],
    ['hero resolved', !!home.heroMain],
    ['Home renders without error', !renderError],
    ['breaking bar rendered', fullHtml.includes('فوری')],
    ['analysis section inside page', fullHtml.includes('تحلیل خبر')],
];

let bad = 0;
for (const [name, ok] of checks) {
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}`);
    if (!ok) bad++;
}
console.log('');
console.log(bad === 0 ? 'ALL HOMEPAGE CHECKS PASSED' : `${bad} CHECK(S) FAILED`);
process.exit(bad === 0 ? 0 : 1);