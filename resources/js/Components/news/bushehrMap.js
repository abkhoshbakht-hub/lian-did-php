/**
 * Bushehr county map data and matching rules.
 * Kept identical to the Next.js implementation so the SVG hit-testing,
 * which is tuned around these exact coordinates, keeps working.
 */

export const COUNTIES = [
    { key: 'deylam', name: 'دیلم', cx: 62, cy: 96, keywords: ['دیلم', 'امام حسن'] },
    { key: 'genaveh', name: 'گناوه', cx: 135, cy: 208, keywords: ['گناوه', 'ریگ'] },
    { key: 'kharg', name: 'خارگ', cx: 64, cy: 270, keywords: ['خارگ', 'جزیره خارگ'] },
    { key: 'booshehr', name: 'بوشهر', cx: 182, cy: 339, keywords: ['بوشهر'] },
    {
        key: 'dashtestan', name: 'دشتستان', cx: 239, cy: 248,
        keywords: ['برازجان', 'آب‌پخش', 'بوشکان', 'تنگ ارم', 'سعدآباد', 'شبانکاره', 'دلوار', 'کلمه'],
    },
    { key: 'tangestan', name: 'تنگستان', cx: 251, cy: 424, keywords: ['اهرم'] },
    { key: 'dashti', name: 'دشتی', cx: 355, cy: 502, keywords: ['خورموج', 'کاکی', 'شنبه', 'بوالخیر', 'طسوج'] },
    { key: 'dayer', name: 'دیر', cx: 346, cy: 610, keywords: ['دیر', 'بردخون', 'آبدان'] },
    { key: 'jam', name: 'جم', cx: 436, cy: 586, keywords: ['جم', 'انارستان', 'ریز'] },
    { key: 'kangan', name: 'کنگان', cx: 484, cy: 672, keywords: ['کنگان', 'سیراف', 'بنک'] },
    { key: 'asaluyeh', name: 'عسلویه', cx: 573, cy: 753, keywords: ['عسلویه', 'نخل تقی'] },
];

export const BUSHEHR_KEYS = COUNTIES.map((c) => c.key);

/** Closest county to a point in SVG user units. */
export function nearestCounty(x, y) {
    let best = COUNTIES[0].key;
    let bestD = Infinity;
    for (const c of COUNTIES) {
        const d = (c.cx - x) ** 2 + (c.cy - y) ** 2;
        if (d < bestD) {
            bestD = d;
            best = c.key;
        }
    }

    return best;
}

/** Items with no region are treated as province-level, so they belong on the map. */
export function isBushehrNews(n) {
    if (!n?.region) return true;
    return BUSHEHR_KEYS.includes(n.region.split(':')[0]);
}

/** An item belongs to a county by its region field first, then by title keyword. */
export function matchesCounty(n, countyKey) {
    const rk = n?.region ? n.region.split(':')[0] : null;
    if (rk === countyKey) return true;
    const county = COUNTIES.find((c) => c.key === countyKey);

    return county ? county.keywords.some((k) => (n.title || '').includes(k)) : false;
}

/** Which county a single item belongs to, used to build counts per county. */
export function countyForNews(item) {
    const rk = item?.region ? item.region.split(':')[0] : null;
    if (BUSHEHR_KEYS.includes(rk)) return rk;

    const hay = item?.title || '';
    for (const c of COUNTIES) {
        if (c.keywords.some((k) => hay.includes(k))) return c.key;
    }

    return null;
}

export function countyName(key) {
    return COUNTIES.find((c) => c.key === key)?.name || null;
}

export default COUNTIES;