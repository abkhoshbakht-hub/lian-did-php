import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { COUNTIES, nearestCounty, matchesCounty, isBushehrNews } from './bushehr-counties';
// styles live in resources/css/app.css so Tailwind ships them

const timeAgo = (dateString) => {
    if (!dateString) return '';
    const diff = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000);
    if (diff < 60) return 'همین الان';
    if (diff < 3600) return `${Math.floor(diff / 60)} دقیقه پیش`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} ساعت پیش`;
    return `${Math.floor(diff / 86400)} روز پیش`;
};

const getCategoryStyle = () => ({ bg: 'bg-[#1B365D]' });

/** Placeholder artwork for items that arrive without an image. */
function placeholderUrl(n, w = 600, h = 400) {
    const t = encodeURIComponent((n.title || '').slice(0, 40));
    const c = encodeURIComponent(n.category || '');
    const s = encodeURIComponent(n.sourceName || '');
    return `/images/placeholder?w=${w}&h=${h}&title=${t}&category=${c}&source=${s}`;
}

/** Maps a mouse event to SVG user units. */
function toSvg(svg, e) {
    const ctm = svg.getScreenCTM();
    if (!ctm) return null;
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const p = pt.matrixTransform(ctm.inverse());
    return { x: p.x, y: p.y };
}

export default function BushehrMap({ news = [] }) {
    const [selected, setSelected] = useState(null);
    const [hovered, setHovered] = useState(null);
    const [svgHtml, setSvgHtml] = useState('');
    const svgRef = useRef(null);
    const initRef = useRef(false);

    // Only Bushehr items; the map is about this province.
    const rawNews = useMemo(() => (news || []).filter(isBushehrNews), [news]);

    useEffect(() => {
        fetch('/maps/bushehr-fa.svg')
            .then((r) => r.text())
            .then(setSvgHtml)
            .catch(() => {});
    }, []);

    // Tag every polygon with the county it belongs to, once the SVG is in the DOM.
    useEffect(() => {
        if (!svgHtml || !svgRef.current || initRef.current) return;

        const c = svgRef.current;
        c.innerHTML = svgHtml;

        const svg = c.querySelector('svg');
        if (!svg) return;

        svg.querySelectorAll('polygon').forEach((el) => {
            el.style.cursor = 'pointer';
            const pts = el.getAttribute('points') || '';
            const nums = pts.trim().split(/[\s,]+/).map(Number);
            let sx = 0;
            let sy = 0;
            let n = 0;
            for (let i = 0; i < nums.length - 1; i += 2) {
                if (!Number.isNaN(nums[i]) && !Number.isNaN(nums[i + 1])) {
                    sx += nums[i];
                    sy += nums[i + 1];
                    n++;
                }
            }
            el.setAttribute('data-county', nearestCounty(n > 0 ? sx / n : 0, n > 0 ? sy / n : 0));
        });

        // The SVG has no label for the Kharg island, so add one.
        const ns = 'http://www.w3.org/2000/svg';
        const txt = document.createElementNS(ns, 'text');
        txt.setAttribute('x', '40');
        txt.setAttribute('y', '290');
        txt.setAttribute('text-anchor', 'middle');
        txt.setAttribute('font-size', '10');
        txt.setAttribute('font-weight', '700');
        txt.setAttribute('fill', '#1B365D');
        txt.setAttribute('font-family', 'Tahoma, Arial, sans-serif');
        txt.setAttribute('pointer-events', 'none');
        txt.textContent = 'خارگ';
        svg.appendChild(txt);

        initRef.current = true;
    }, [svgHtml]);

    // Dim everything except the active county.
    useEffect(() => {
        const svg = svgRef.current?.querySelector('svg');
        if (!svg) return;

        const active = selected || hovered;

        svg.querySelectorAll('polygon').forEach((el) => {
            const k = el.getAttribute('data-county');
            if (!k) return;

            if (!active) {
                el.style.opacity = '1';
                el.style.stroke = 'none';
                el.style.strokeWidth = '0';
            } else if (k === active) {
                el.style.opacity = '1';
                el.style.stroke = '#C9A96E';
                el.style.strokeWidth = '2.5';
                el.style.strokeOpacity = '1';
            } else {
                el.style.opacity = '0.22';
                el.style.stroke = 'none';
                el.style.strokeWidth = '0';
            }
        });
    }, [hovered, selected, svgHtml]);

    const selectedCounty = selected ? COUNTIES.find((c) => c.key === selected) : null;

    const shown = useMemo(() => {
        if (!selected) return [];
        return rawNews.filter((n) => matchesCounty(n, selected));
    }, [rawNews, selected]);

    const featured = shown[0];
    const rest = shown.slice(1, 13);

    /** Which county is under the pointer: hit-test first, nearest-centroid as fallback. */
    const getCounty = useCallback((e) => {
        const els = document.elementsFromPoint(e.clientX, e.clientY);
        for (const el of els) {
            if (el.tagName === 'polygon') {
                return el.getAttribute('data-county');
            }
        }
        const svg = svgRef.current?.querySelector('svg');
        if (!svg) return null;
        const p = toSvg(svg, e);
        if (!p) return null;

        return nearestCounty(p.x, p.y);
    }, []);

    return (
        <div>
            <div className="bushehr-map-wrapper">
                <div className="bushehr-map-header">
                    <h2>اخبار شهرستان‌های استان بوشهر</h2>
                    <p>برای مشاهده اخبار هر شهرستان روی نقشه کلیک کنید</p>
                </div>

                <div className="bushehr-map">
                    <div
                        ref={svgRef}
                        className="bushehr-svg-container"
                        onMouseMove={(e) => setHovered(getCounty(e))}
                        onMouseLeave={() => setHovered(null)}
                        onClick={(e) => {
                            const k = getCounty(e);
                            if (k) setSelected((p) => (p === k ? null : k));
                        }}
                    />
                </div>

                <div className="bushehr-map-hint">
                    {selected ? (
                        <div className="flex items-center gap-2 justify-center">
                            <button
                                type="button"
                                onClick={() => setSelected(null)}
                                className="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-white border border-gray-200 hover:bg-gray-50 transition-colors"
                            >
                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                                بازگشت به نقشه
                            </button>
                            <span className="text-xs text-gray-500">
                                اخبار <strong className="text-[#102a43]">{selectedCounty?.name}</strong> — {shown.length} خبر
                            </span>
                        </div>
                    ) : (
                        <span>روی هر شهرستان کلیک کنید تا اخبار آن نمایش داده شود</span>
                    )}
                </div>
            </div>

            {selected && selectedCounty && (
                <div className="bushehr-news-section">
                    <div className="bushehr-news-header">
                        <div className="flex items-center gap-3">
                            <div className="w-1 h-6 bg-[#C9A96E] rounded-full" />
                            <h2 className="text-base font-black text-[#1B365D]">
                                اخبار شهرستان {selectedCounty.name}
                            </h2>
                            <div className="flex-1 h-px bg-gray-200" />
                        </div>
                    </div>

                    {shown.length === 0 ? (
                        <div className="bushehr-news-empty">
                            <h3 className="font-black text-[#1B365D] text-sm mb-1">خبری یافت نشد</h3>
                            <p className="text-gray-400 text-xs">
                                اخبار جدید به محض انتشار نمایش داده می‌شوند
                            </p>
                        </div>
                    ) : (
                        <>
                            {featured && (
                                <a
                                    href={featured.link}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="group block bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-2xl hover:shadow-[#1B365D]/10 transition-all duration-500 mb-4"
                                >
                                    <div className="grid grid-cols-1 lg:grid-cols-5">
                                        <div className="relative lg:col-span-3 h-56 lg:h-72 overflow-hidden bg-gray-100">
                                            <img
                                                src={featured.image || placeholderUrl(featured, 800, 600)}
                                                alt={featured.title}
                                                className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                            />
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent" />
                                            {featured.category && (
                                                <span className={`absolute top-3 right-3 text-[11px] px-3 py-1 rounded-full font-bold text-white shadow-lg ${getCategoryStyle().bg}`}>
                                                    {featured.category}
                                                </span>
                                            )}
                                        </div>
                                        <div className="lg:col-span-2 p-5 lg:p-6 flex flex-col justify-center bg-[#0f2d52] relative overflow-hidden">
                                            <div className="absolute top-0 right-0 w-24 h-24 bg-[#C9A96E]/10 rounded-full blur-2xl" />
                                            <span className="bg-[#C9A96E] text-[#0f2d52] text-[10px] px-2.5 py-0.5 rounded-full font-black w-fit mb-3">
                                                خبر ویژه
                                            </span>
                                            <h2 className="text-base lg:text-lg font-black text-white group-hover:text-[#C9A96E] transition-colors duration-300 leading-[1.9] line-clamp-3 mb-3">
                                                {featured.title}
                                            </h2>
                                            {featured.description && (
                                                <p className="text-white/50 text-xs leading-relaxed line-clamp-2 mb-4">
                                                    {featured.description}
                                                </p>
                                            )}
                                            <div className="flex items-center gap-3 text-white/50 text-[11px]">
                                                <span>{featured.sourceName}</span>
                                                <span className="w-1 h-1 bg-white/20 rounded-full" />
                                                <span>{timeAgo(featured.publishedAt)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            )}

                            {rest.length > 0 && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {rest.map((n) => (
                                        <a
                                            key={n.id}
                                            href={n.link}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="group flex items-center gap-3.5 bg-white rounded-xl p-3 border border-gray-100 hover:border-transparent hover:shadow-lg hover:shadow-[#1B365D]/5 transition-all duration-300"
                                        >
                                            <div className="w-24 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                                <img
                                                    src={n.image || placeholderUrl(n, 240, 200)}
                                                    alt={n.title}
                                                    className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                                />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-1.5 mb-1">
                                                    {n.category && (
                                                        <span className={`text-[9px] px-2 py-0.5 rounded-full font-bold text-white ${getCategoryStyle().bg}`}>
                                                            {n.category}
                                                        </span>
                                                    )}
                                                    <span className="text-[9px] text-gray-400">{n.sourceName}</span>
                                                </div>
                                                <h3 className="font-black text-[#1B365D] group-hover:text-[#C9A96E] transition-colors duration-300 text-[12.5px] leading-[1.8] line-clamp-2">
                                                    {n.title}
                                                </h3>
                                                <span className="text-[10px] text-gray-400 mt-1 block">
                                                    {timeAgo(n.publishedAt)}
                                                </span>
                                            </div>
                                        </a>
                                    ))}
                                </div>
                            )}
                        </>
                    )}
                </div>
            )}
        </div>
    );
}