import React, { useState, useEffect } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { adminSections } from '@/Components/admin/navConfig';

/** Strip query strings so two links to the same page compare equal. */
const pathOf = (href) => href.split('?')[0];
const queryOf = (href) => {
    const q = href.split('?')[1];
    return q ? Object.fromEntries(new URLSearchParams(q)) : {};
};

export default function AdminLayout({ title = 'داشبورد', children }) {
    // Inertia keeps `url` at the top level of the page object, not inside props
    const page = usePage();
    const { url } = page;
    const { auth, flash } = page.props;
    const currentPath = (url || '').split('?')[0];
    const currentParams = Object.fromEntries(new URLSearchParams((url || '').split('?')[1] || ''));

    const [expanded, setExpanded] = useState(null);
    const [preview, setPreview] = useState(null);
    const [hoveredItem, setHoveredItem] = useState(null);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [today, setToday] = useState('');

    useEffect(() => {
        setToday(new Date().toLocaleDateString('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' }));
    }, []);

    const isActive = (item) => {
        const p = pathOf(item.href);
        const q = queryOf(item.href);
        if (currentPath !== p) return false;

        const keys = Object.keys(q);
        if (keys.length === 0) {
            // plain link: active only when no conflicting filters are present
            return !currentParams.cat && !currentParams.status && !currentParams.new
                && !currentParams.section && !currentParams.edit;
        }
        return keys.every((k) => currentParams[k] === q[k]);
    };

    // auto-open the section containing the active link
    useEffect(() => {
        for (const s of adminSections) {
            if (s.items.some(isActive)) {
                setExpanded(s.title);
                return;
            }
        }
    }, [url]);

    const pageTitle = (() => {
        for (const s of adminSections) {
            const found = s.items.find(isActive);
            if (found) return found.label;
        }
        return title;
    })();

    const isSectionActive = (s) => s.items.some(isActive);
    const toggle = (t) => setExpanded((p) => (p === t ? null : t));

    const logout = () => {
        router.post('/logout');
    };

    const chevron = (open) => (
        <svg
            className={`w-3 h-3 transition-transform duration-300 ${open ? 'text-white/50 rotate-0' : 'text-white/30 rotate-180'}`}
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
    );

    const eyeBtn = (section) => (
        <button
            onClick={(e) => { e.stopPropagation(); setPreview(preview === section.title ? null : section.title); }}
            className="p-1.5 rounded-lg hover:bg-white/[0.06] transition-colors ml-0.5"
            title="پیش‌نمایش این بخش"
        >
            <svg
                className={`w-3.5 h-3.5 transition-colors ${preview === section.title ? 'text-[#C9A96E]' : 'text-white/30 hover:text-white/50'}`}
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </button>
    );

    const sectionButton = (s, isOpen) => (
        <button
            onClick={() => toggle(s.title)}
            className={`flex-1 flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-all duration-200 group ${
                isOpen ? 'bg-white/[0.06]' : 'hover:bg-white/[0.03]'
            }`}
        >
            <span className={`transition-colors duration-200 ${isOpen ? 'text-[#C9A96E]' : 'text-white/50 group-hover:text-white/60'}`}>
                {s.icon}
            </span>
            <span className={`text-[12px] font-bold transition-colors duration-200 ${isOpen ? 'text-white/90' : 'text-white/65 group-hover:text-white/80'}`}>
                {s.title}
            </span>
            {isSectionActive(s) && !isOpen && (
                <span className="w-1.5 h-1.5 rounded-full bg-[#C9A96E] mr-auto" />
            )}
            {chevron(isOpen)}
        </button>
    );

    const itemLink = (item, i) => {
        const activeItem = isActive(item);
        return (
            <a
                key={item.label + i}
                href={item.href}
                onMouseEnter={() => setHoveredItem(item.label)}
                onMouseLeave={() => setHoveredItem(null)}
                className={`relative flex items-center px-3 py-[7px] rounded-lg text-[11.5px] transition-all duration-150 my-0.5 ${
                    activeItem
                        ? 'bg-[#C9A96E]/[0.12] text-[#C9A96E] font-bold'
                        : 'text-white/70 hover:text-white hover:bg-white/[0.08]'
                }`}
            >
                {activeItem && (
                    <span className="absolute -right-[9px] top-1/2 -translate-y-1/2 w-[3px] h-4 bg-[#C9A96E] rounded-full" />
                )}
                {item.label}
            </a>
        );
    };

    const userName = auth?.user?.name || 'مدیر';
    const isAdmin = auth?.user?.role === 'ADMIN';

    return (
        <div className="min-h-screen bg-[#f8f9fb] flex" dir="rtl">
            {/* ── sidebar ─────────────────────────────────────────── */}
            <aside className="hidden lg:flex fixed inset-y-0 right-0 z-50 w-72 bg-[#0a1220] flex-col shadow-2xl shadow-black/30">
                <div className="h-16 flex items-center px-5 border-b border-white/[0.04] shrink-0">
                    <Link href="/admin/dashboard" className="flex items-center gap-3">
                        <span className="text-[#C9A96E] font-black text-lg tracking-tight">لیان دید</span>
                        <span className="text-white/30 text-[10px]">پنل مدیریت</span>
                    </Link>
                </div>

                <nav className="flex-1 overflow-y-auto py-3 px-3">
                    {adminSections.map((s) => {
                        const isOpen = expanded === s.title;
                        return (
                            <div key={s.title} className="mb-1.5">
                                <div className="flex items-center">
                                    {sectionButton(s, isOpen)}
                                    {eyeBtn(s)}
                                </div>
                                <div className={`overflow-hidden transition-all duration-300 ease-out ${isOpen ? 'max-h-[600px] opacity-100' : 'max-h-0 opacity-0'}`}>
                                    <div className="mr-5 mt-1 mb-2 pl-2 border-r border-white/[0.04]">
                                        {s.items.map(itemLink)}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </nav>

                <div className="px-4 py-3 border-t border-white/[0.04] shrink-0">
                    <div className="flex items-center gap-2.5 px-1 mb-3">
                        <div className="w-9 h-9 rounded-full bg-gradient-to-br from-[#C9A96E] to-[#8a6d3b] flex items-center justify-center text-white font-black text-xs shadow-lg">
                            {userName[0] || 'A'}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-white text-[11px] font-bold truncate">{userName}</p>
                            <p className="text-white/50 text-[10px]">{isAdmin ? 'مدیر ارشد' : 'نویسنده'}</p>
                        </div>
                    </div>
                    <div className="flex gap-1.5">
                        <a href="/" target="_blank" className="flex-1 flex items-center justify-center gap-1.5 text-[10px] text-white/55 hover:text-white/80 py-2 rounded-lg hover:bg-white/[0.06] transition-colors border border-white/[0.08]">
                            مشاهده سایت
                        </a>
                        <button onClick={logout} className="flex-1 flex items-center justify-center gap-1.5 text-[10px] text-white/55 hover:text-red-400 py-2 rounded-lg hover:bg-white/[0.06] transition-colors border border-white/[0.08]">
                            خروج
                        </button>
                    </div>
                </div>
            </aside>

            {/* ── main ───────────────────────────────────────────── */}
            <div className="flex-1 lg:mr-72 min-h-screen min-w-0">
                <header className="sticky top-0 z-30 h-14 bg-white/80 backdrop-blur-xl border-b border-gray-200/60 flex items-center px-6">
                    <button onClick={() => setMobileOpen(!mobileOpen)} className="lg:hidden ml-4 p-2 rounded-lg hover:bg-gray-100">
                        <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h2 className="text-sm font-bold text-gray-800">{pageTitle}</h2>
                    <div className="mr-auto flex items-center gap-3">
                        <Link href="/admin/shutdown" className="flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-red-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors border border-red-100">
                            خاموش کردن
                        </Link>
                        <span className="text-xs text-gray-400">{today}</span>
                    </div>
                </header>
                <main className="p-6">{children}</main>
            </div>

            {/* ── mobile drawer ──────────────────────────────────── */}
            {mobileOpen && (
                <div className="fixed inset-0 z-[60] bg-black/50 lg:hidden" onClick={() => setMobileOpen(false)}>
                    <div className="absolute inset-y-0 right-0 w-72 bg-[#0a1220] shadow-2xl" onClick={(e) => e.stopPropagation()}>
                        <div className="h-16 flex items-center px-5 border-b border-white/[0.04]">
                            <Link href="/admin/dashboard" onClick={() => setMobileOpen(false)}>
                                <span className="text-[#C9A96E] font-black text-lg">لیان دید</span>
                            </Link>
                        </div>
                        <nav className="overflow-y-auto py-3 px-3 h-[calc(100vh-4rem)]">
                            {adminSections.map((s) => {
                                const isOpen = expanded === s.title;
                                return (
                                    <div key={s.title} className="mb-1.5">
                                        <button
                                            onClick={() => toggle(s.title)}
                                            className={`w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-all duration-200 ${isOpen ? 'bg-white/[0.06]' : 'hover:bg-white/[0.03]'}`}
                                        >
                                            <span className={isOpen ? 'text-[#C9A96E]' : 'text-white/50'}>{s.icon}</span>
                                            <span className={`text-[12px] font-bold ${isOpen ? 'text-white/90' : 'text-white/65'}`}>{s.title}</span>
                                            {chevron(isOpen)}
                                        </button>
                                        <div className={`overflow-hidden transition-all duration-300 ${isOpen ? 'max-h-[600px] opacity-100' : 'max-h-0 opacity-0'}`}>
                                            <div className="mr-5 mt-1 mb-2 pl-2 border-r border-white/[0.04]">
                                                {s.items.map((item, i) => (
                                                    <Link
                                                        key={item.label + i}
                                                        href={item.href}
                                                        onClick={() => setMobileOpen(false)}
                                                        className={`relative block px-3 py-[7px] rounded-lg text-[11.5px] my-0.5 ${
                                                            isActive(item) ? 'bg-[#C9A96E]/[0.12] text-[#C9A96E] font-bold' : 'text-white/55 hover:text-white/80'
                                                        }`}
                                                    >
                                                        {item.label}
                                                    </Link>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </nav>
                    </div>
                </div>
            )}

            {/* ── preview modal ──────────────────────────────────── */}
            {preview && (
                <div className="fixed inset-0 z-[100] bg-black/40 flex items-center justify-center p-8" onClick={() => setPreview(null)}>
                    <div
                        className="bg-[#0f1d35] rounded-2xl border border-white/10 w-full max-w-[1000px] max-h-[85vh] shadow-2xl overflow-hidden flex flex-col"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between px-6 py-4 border-b border-white/10 shrink-0">
                            <div className="flex items-center gap-3">
                                <div className="w-3 h-3 rounded-full bg-[#C9A96E]" />
                                <span className="text-white text-base font-bold">{preview}</span>
                            </div>
                            <button onClick={() => setPreview(null)} className="text-white/40 hover:text-white p-2 rounded-lg hover:bg-white/10 transition-colors">
                                ✕
                            </button>
                        </div>
                        <div className="p-8 overflow-y-auto flex-1">
                            <p className="text-white/40 text-sm">برای دیدن پیش‌نمایش، روی یکی از لینک‌های این بخش نگه دارید.</p>
                            <div className="mt-6 space-y-1.5" dir="rtl">
                                {adminSections
                                    .find((s) => s.title === preview)
                                    ?.items.map((item, i) => (
                                        <div
                                            key={i}
                                            onMouseEnter={() => setHoveredItem(item.label)}
                                            className="text-white/70 text-sm px-4 py-2 rounded-lg bg-white/[0.04] border border-white/[0.06] hover:bg-white/[0.08] transition-colors cursor-pointer"
                                            onClick={() => { setPreview(null); router.get(item.href); }}
                                        >
                                            {hoveredItem === item.label ? '▸ ' : ''}{item.label}
                                            <span className="text-white/25 text-[11px] mr-3" dir="ltr">{item.href}</span>
                                        </div>
                                    ))}
                            </div>
                        </div>
                        <div className="px-6 py-3 border-t border-white/10 shrink-0">
                            <p className="text-xs text-white/40 text-center">پیش‌نمایش ساختار این بخش</p>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
