import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { bushehrCounties } from '@/Components/admin/regions';
import { uploadAdminImage } from '@/Components/admin/uploadAdminImage';
import ImageCropper from '@/Components/admin/ImageCropper';

const STATUS = {
    PUBLISHED: { label: 'منتشر شده', cls: 'bg-emerald-100 text-emerald-700' },
    DRAFT: { label: 'پیش‌نویس', cls: 'bg-gray-100 text-gray-600' },
    ARCHIVED: { label: 'بایگانی', cls: 'bg-amber-100 text-amber-800' },
};

const PLACEMENTS = [
    { value: 'latest', label: 'آخرین اخبار' },
    { value: 'hero', label: 'هیرو صفحه اصلی' },
    { value: 'analysis', label: 'تحلیل خبر' },
    { value: 'featured', label: 'ویژه' },
];

/* ── منوی سه‌نقطهٔ عملیات هر خبر ── */
function ActionIcon({ children, className = '' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"
            strokeLinecap="round" strokeLinejoin="round" className={`w-4 h-4 shrink-0 ${className}`} aria-hidden="true">
            {children}
        </svg>
    );
}

async function copyToClipboard(text) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch (e) { /* روش جایگزین */ }

    try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(ta);
        return ok;
    } catch (e) {
        return false;
    }
}

function ArticleActionsMenu({ article, onDelete, onPreview, onNotify }) {
    const [pos, setPos] = useState(null);
    const btnRef = useRef(null);
    const menuRef = useRef(null);

    useEffect(() => {
        if (!pos) return;
        const close = () => setPos(null);
        const onDown = (e) => {
            if (menuRef.current?.contains(e.target)) return;
            if (btnRef.current?.contains(e.target)) return;
            close();
        };
        const onKey = (e) => { if (e.key === 'Escape') close(); };
        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);
        window.addEventListener('scroll', close, true);
        window.addEventListener('resize', close);
        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
            window.removeEventListener('scroll', close, true);
            window.removeEventListener('resize', close);
        };
    }, [pos]);

    // اگر منو از پایین صفحه بیرون بزند، به بالای دکمه منتقل می‌شود
    useEffect(() => {
        if (!pos || !menuRef.current) return;
        const h = menuRef.current.offsetHeight;
        if (pos.top + h > window.innerHeight - 8) {
            setPos((p) => (p ? { ...p, top: Math.max(8, p.anchorTop - h - 6) } : p));
        }
    }, [pos]);

    const toggle = () => {
        if (pos) { setPos(null); return; }
        const r = btnRef.current.getBoundingClientRect();
        const width = 240;
        let left = r.left;
        if (left + width > window.innerWidth - 8) left = Math.max(8, window.innerWidth - width - 8);
        setPos({ top: r.bottom + 6, left, anchorTop: r.top });
    };

    const duplicate = () => {
        setPos(null);
        router.post(`/admin/articles/${article.id}/duplicate`, {}, { preserveScroll: true });
    };

    const shortlink = async () => {
        setPos(null);
        try {
            const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
            const token = m ? decodeURIComponent(m[1]) : '';
            const res = await fetch(`/admin/articles/${article.id}/shortlink`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-XSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) throw new Error('http');
            const data = await res.json();
            const ok = await copyToClipboard(data.url);
            onNotify(ok ? `لینک کوتاه کپی شد: ${data.url}` : `لینک کوتاه: ${data.url}`);
        } catch (e) {
            onNotify('ساخت لینک کوتاه ناموفق بود.');
        }
    };

    const unapprove = () => {
        setPos(null);
        if (confirm('این خبر از حالت انتشار خارج و به پیش‌نویس منتقل شود؟')) {
            router.post(`/admin/articles/${article.id}/status`, { status: 'DRAFT' }, { preserveScroll: true });
        }
    };

    const itemCls = 'flex w-full items-center gap-2.5 px-3.5 py-2 text-right text-[12.5px] font-bold transition-colors';

    return (
        <>
            <button
                ref={btnRef}
                type="button"
                onClick={toggle}
                aria-haspopup="menu"
                aria-expanded={!!pos}
                aria-label="عملیات خبر"
                className={`inline-flex h-8 w-8 items-center justify-center rounded-lg border transition-colors ${
                    pos
                        ? 'border-[#1B365D] bg-[#1B365D] text-white'
                        : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-[#1B365D]'
                }`}
            >
                <svg viewBox="0 0 24 24" className="h-4 w-4" fill="currentColor" aria-hidden="true">
                    <circle cx="5" cy="12" r="1.7" />
                    <circle cx="12" cy="12" r="1.7" />
                    <circle cx="19" cy="12" r="1.7" />
                </svg>
            </button>

            {pos && (
                <div
                    ref={menuRef}
                    role="menu"
                    style={{ top: pos.top, left: pos.left }}
                    className="fixed z-[9999] w-60 overflow-hidden rounded-xl border border-gray-200 bg-white py-1.5 text-right shadow-2xl shadow-gray-900/20"
                >
                    <div className="px-3.5 pb-1 pt-1 text-[10px] font-black tracking-wide text-gray-400">عملیات خبر</div>

                    <a
                        href={`/news/${article.slug}`}
                        target="_blank"
                        rel="noreferrer"
                        onClick={() => setPos(null)}
                        className={`${itemCls} text-gray-700 hover:bg-blue-50 hover:text-blue-800`}
                    >
                        <ActionIcon className="text-blue-500">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </ActionIcon>
                        نما
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"
                            strokeLinecap="round" strokeLinejoin="round" className="mr-auto h-3 w-3 text-gray-300" aria-hidden="true">
                            <path d="M7 17 17 7" />
                            <path d="M8 7h9v9" />
                        </svg>
                    </a>

                    <Link
                        href={`/admin/articles?edit=${article.id}`}
                        onClick={() => setPos(null)}
                        className={`${itemCls} text-gray-700 hover:bg-amber-50 hover:text-amber-800`}
                    >
                        <ActionIcon className="text-amber-500">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </ActionIcon>
                        ویرایش
                    </Link>

                    <Link
                        href="/admin/gallery"
                        onClick={() => setPos(null)}
                        className={`${itemCls} text-gray-700 hover:bg-violet-50 hover:text-violet-800`}
                    >
                        <ActionIcon className="text-violet-500">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <circle cx="8.5" cy="8.5" r="1.5" />
                            <path d="m21 15-5-5L5 21" />
                        </ActionIcon>
                        گالری
                    </Link>

                    <button
                        type="button"
                        onClick={() => { setPos(null); onPreview(); }}
                        className={`${itemCls} text-gray-700 hover:bg-sky-50 hover:text-sky-800`}
                    >
                        <ActionIcon className="text-sky-500">
                            <rect x="2" y="3" width="20" height="14" rx="2" />
                            <path d="M8 21h8M12 17v4" />
                        </ActionIcon>
                        پیش‌نمایش
                    </button>

                    <Link
                        href="/admin/submissions"
                        onClick={() => setPos(null)}
                        className={`${itemCls} text-gray-700 hover:bg-teal-50 hover:text-teal-800`}
                    >
                        <ActionIcon className="text-teal-500">
                            <path d="m22 2-7 20-4-9-9-4Z" />
                            <path d="M22 2 11 13" />
                        </ActionIcon>
                        پیشنهاد خبر
                    </Link>

                    <button
                        type="button"
                        onClick={shortlink}
                        className={`${itemCls} text-gray-700 hover:bg-indigo-50 hover:text-indigo-800`}
                    >
                        <ActionIcon className="text-indigo-500">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                        </ActionIcon>
                        لینک کوتاه
                    </button>

                    <button
                        type="button"
                        onClick={duplicate}
                        className={`${itemCls} text-gray-700 hover:bg-emerald-50 hover:text-emerald-800`}
                    >
                        <ActionIcon className="text-emerald-500">
                            <rect x="9" y="9" width="12" height="12" rx="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                        </ActionIcon>
                        کپی کردن خبر
                    </button>

                    <div className="my-1 border-t border-gray-100" />

                    <button
                        type="button"
                        onClick={unapprove}
                        className={`${itemCls} text-rose-600 hover:bg-rose-50`}
                    >
                        <ActionIcon className="text-rose-500">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m15 9-6 6M9 9l6 6" />
                        </ActionIcon>
                        عدم تایید
                    </button>

                    <button
                        type="button"
                        onClick={() => { setPos(null); onDelete(); }}
                        className={`${itemCls} text-red-600 hover:bg-red-50`}
                    >
                        <ActionIcon className="text-red-500">
                            <path d="M3 6h18" />
                            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                            <path d="M10 11v6M14 11v6" />
                        </ActionIcon>
                        حذف خبر
                    </button>
                </div>
            )}
        </>
    );
}

export default function AdminArticles({ articles = [], categories = [], filters = {}, editing = null, new: isNew = false }) {
    const { flash } = usePage().props;
    const [q, setQ] = useState(filters.search || '');
    const [form, setForm] = useState({
        title: '', subtitle: '', excerpt: '', content: '',
        categoryId: '', featuredImage: '', placement: 'latest',
        status: 'PUBLISHED', source: '', sourceUrl: '', region: '',
        metaTitle: '', metaDesc: '', metaKeywords: '',
        isBreaking: false, isFeatured: false, isPinned: false,
    });
    const [errors, setErrors] = useState({});
    const [imgBusy, setImgBusy] = useState(false);
    const fileRef = useRef(null);
    const [crop, setCrop] = useState(null); // { src, name }
    const [preview, setPreview] = useState(null);
    const [toast, setToast] = useState(null);

    useEffect(() => {
        if (!toast) return;
        const t = setTimeout(() => setToast(null), 4500);
        return () => clearTimeout(t);
    }, [toast]);

    useEffect(() => {
        if (!preview) return;
        const onKey = (e) => { if (e.key === 'Escape') setPreview(null); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [preview]);

    const onCropDone = (blob) => {
        const f = crop ? crop.name : 'image.webp';
        setCrop(null);
        if (fileRef.current) fileRef.current.value = '';
        const file = new File([blob], f.replace(/\.[^.]+$/, '') + '.webp', { type: blob.type });
        setImgBusy(true);
        uploadAdminImage(file)
            .then((url) => set('featuredImage', url))
            .catch((err) => setErrors((o) => ({ ...o, featuredImage: [err.message] })))
            .finally(() => setImgBusy(false));
    };

    const pickImage = (e) => {
        const f = e.target.files[0];
        if (!f) return;
        setCrop({ src: URL.createObjectURL(f), name: f.name });
    };

    const cancelCrop = () => {
        setCrop(null);
        if (fileRef.current) fileRef.current.value = '';
    };

    useEffect(() => {
        if (editing) {
            setForm({
                title: editing.title || '', subtitle: editing.subtitle || '',
                excerpt: editing.excerpt || '', content: editing.content || '',
                categoryId: editing.categoryId || (categories[0]?.id ?? ''),
                featuredImage: editing.featuredImage || '',
                placement: editing.placement || 'latest',
                status: editing.status || 'PUBLISHED',
                source: editing.source || '', sourceUrl: editing.sourceUrl || '',
                region: editing.region || '',
                metaTitle: editing.metaTitle || '', metaDesc: editing.metaDesc || '',
                metaKeywords: editing.metaKeywords || '',
                isBreaking: !!editing.isBreaking, isFeatured: !!editing.isFeatured,
                isPinned: !!editing.isPinned,
            });
        }
    }, [editing?.id]);

    const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

    const submit = (e) => {
        e.preventDefault();
        setErrors({});
        const onError = (errs) => setErrors(errs);
        if (editing) {
            router.put(`/admin/articles/${editing.id}`, form, { preserveScroll: true, onError });
        } else {
            router.post('/admin/articles', form, { preserveScroll: true, onError });
        }
    };

    const reset = () => {
        setForm({
            title: '', subtitle: '', excerpt: '', content: '',
            categoryId: categories[0]?.id ?? '', featuredImage: '', placement: 'latest',
            status: 'PUBLISHED', source: '', sourceUrl: '', region: '',
            metaTitle: '', metaDesc: '', metaKeywords: '',
            isBreaking: false, isFeatured: false, isPinned: false,
        });
        setErrors({});
    };

    const del = (id, title) => {
        if (confirm(`حذف «${title}»؟ این عمل بازگشت‌پذیر نیست.`)) {
            router.delete(`/admin/articles/${id}`, { preserveScroll: true });
        }
    };

    const field = 'w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]';

    return (
        <AdminLayout title="اخبار سایت">
            <Head title="اخبار سایت | پنل لیان دید" />

            {flash?.success && (
                <div className="mb-5 px-5 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm font-bold">
                    {flash.success}
                </div>
            )}

            {/* ── نوار فیلتر ── */}
            <div className="bg-white rounded-2xl border border-gray-200 p-4 mb-6 flex flex-wrap items-center gap-3">
                <form onSubmit={(e) => { e.preventDefault(); router.get('/admin/articles', { status: filters.status, cat: filters.cat, search: q }, { preserveState: true }); }} className="flex items-center gap-2">
                    <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="جستجوی عنوان یا شناسه..." className={field + ' w-64'} />
                    <button className="px-4 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">جستجو</button>
                </form>

                <div className="flex items-center gap-2">
                    <span className="text-xs text-gray-400 font-bold">دسته:</span>
                    {[{ slug: '', name: 'همه' }, ...categories.map((c) => ({ slug: c.slug, name: c.name }))].map((c) => (
                        <Link
                            key={c.slug || 'all'}
                            href={c.slug ? `/admin/articles?cat=${c.slug}` : '/admin/articles'}
                            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                                filters.cat === c.slug ? 'bg-[#C9A96E] text-[#1B365D]' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            }`}
                        >
                            {c.name}
                        </Link>
                    ))}
                </div>

                <div className="mr-auto flex items-center gap-2">
                    <Link href="/admin/articles" className={`px-3 py-1.5 rounded-lg text-xs font-bold ${!filters.status ? 'bg-[#1B365D] text-white' : 'bg-gray-100 text-gray-600'}`}>فعال</Link>
                    <Link href="/admin/articles?status=ARCHIVED" className={`px-3 py-1.5 rounded-lg text-xs font-bold ${filters.status === 'ARCHIVED' ? 'bg-[#1B365D] text-white' : 'bg-gray-100 text-gray-600'}`}>آرشیو</Link>
                    <Link href="/admin/articles?status=DRAFT" className={`px-3 py-1.5 rounded-lg text-xs font-bold ${filters.status === 'DRAFT' ? 'bg-[#1B365D] text-white' : 'bg-gray-100 text-gray-600'}`}>پیش‌نویس</Link>
                </div>
            </div>

            <div className="flex flex-col">
            {/* ── جدول ── */}
            <div className={`${isNew ? 'order-2' : ''} bg-white rounded-2xl border border-gray-200 overflow-hidden`}>
                <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 className="text-sm font-black text-[#1B365D]">{articles.length} خبر</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-sm">
                        <thead className="bg-gray-50 text-gray-500 text-xs">
                            <tr>
                                {['عنوان', 'دسته', 'محل نمایش', 'وضعیت', 'نشان‌ها', 'بازدید', 'عملیات'].map((h) => (
                                    <th key={h} className="p-3 font-bold whitespace-nowrap">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {articles.length === 0 && (
                                <tr><td colSpan="7" className="text-center py-12 text-gray-400">خبری یافت نشد.</td></tr>
                            )}
                            {articles.map((a) => (
                                <tr key={a.id} className="hover:bg-gray-50/70">
                                    <td className="p-3 max-w-xs">
                                        <Link href={`/admin/articles?edit=${a.id}`} className="font-bold text-gray-900 hover:text-[#C9A96E] line-clamp-2">
                                            {a.title}
                                        </Link>
                                        <div className="text-[11px] text-gray-400 mt-0.5" dir="ltr">{a.slug}</div>
                                    </td>
                                    <td className="p-3 text-gray-600 whitespace-nowrap">{a.categoryName}</td>
                                    <td className="p-3 whitespace-nowrap">
                                        <span className="text-[11px] px-2 py-1 rounded-md bg-indigo-50 text-indigo-700 font-bold">
                                            {PLACEMENTS.find((p) => p.value === a.placement)?.label || a.placement}
                                        </span>
                                    </td>
                                    <td className="p-3 whitespace-nowrap">
                                        <span className={`text-[11px] px-2 py-1 rounded-md font-bold ${STATUS[a.status]?.cls || 'bg-gray-100 text-gray-600'}`}>
                                            {STATUS[a.status]?.label || a.status}
                                        </span>
                                    </td>
                                    <td className="p-3 whitespace-nowrap">
                                        <div className="flex gap-1">
                                            {a.isBreaking && <span className="bg-red-100 text-red-700 text-[10px] px-1.5 rounded font-bold">فوری</span>}
                                            {a.isFeatured && <span className="bg-amber-100 text-amber-800 text-[10px] px-1.5 rounded font-bold">ویژه</span>}
                                            {a.isPinned && <span className="bg-blue-100 text-blue-700 text-[10px] px-1.5 rounded font-bold">سنجاق</span>}
                                        </div>
                                    </td>
                                    <td className="p-3 text-gray-600 font-mono">{a.viewCount}</td>
                                    <td className="p-3 whitespace-nowrap">
                                        <ArticleActionsMenu
                                            article={a}
                                            onDelete={() => del(a.id, a.title)}
                                            onPreview={() => setPreview(a)}
                                            onNotify={setToast}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* ── فرم ── */}
            <div className={isNew ? 'order-1 mb-6 bg-white rounded-2xl border border-gray-200 p-6' : 'mt-6 bg-white rounded-2xl border border-gray-200 p-6'}>
                <div className="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
                    <h2 className="text-base font-black text-[#1B365D]">
                        {editing ? `ویرایش: ${editing.title.slice(0, 40)}` : 'افزودن خبر جدید'}
                    </h2>
                    {editing && (
                        <Link href="/admin/articles" className="text-xs font-bold text-gray-500 hover:text-gray-800">بستن</Link>
                    )}
                </div>

                <form onSubmit={submit} className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    {/* ستون اصلی */}
                    <div className="lg:col-span-2 space-y-4">
                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-1.5">عنوان</label>
                            <input value={form.title} onChange={(e) => set('title', e.target.value)} className={field} />
                            {errors.title && <p className="text-red-600 text-xs mt-1">{errors.title}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-1.5">زیرعنوان (روتیتر)</label>
                            <input value={form.subtitle} onChange={(e) => set('subtitle', e.target.value)} className={field} />
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-1.5">خلاصه (چکیده کارت‌ها)</label>
                            <textarea value={form.excerpt} onChange={(e) => set('excerpt', e.target.value)} rows="2" className={field} />
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-1.5">متن کامل</label>
                            <textarea value={form.content} onChange={(e) => set('content', e.target.value)} rows="10" className={field} />
                            {errors.content && <p className="text-red-600 text-xs mt-1">{errors.content}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-1.5">عکس شاخص خبر</label>
                            <input
                                ref={fileRef}
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                className="hidden"
                                onChange={pickImage}
                            />
                            <div className="flex gap-2 items-start">
                                <input value={form.featuredImage} onChange={(e) => set('featuredImage', e.target.value)} placeholder="https://… یا از دکمهٔ «انتخاب فایل»" dir="ltr" className={field} />
                                <button
                                    type="button"
                                    onClick={() => fileRef.current && fileRef.current.click()}
                                    disabled={imgBusy}
                                    className="shrink-0 px-3 py-2 rounded-lg bg-[#1B365D] text-white text-xs font-bold hover:bg-[#2a4a7a] disabled:opacity-50 whitespace-nowrap"
                                >
                                    {imgBusy ? 'در حال بارگذاری…' : 'انتخاب فایل عکس'}
                                </button>
                            </div>
                            {errors.featuredImage && <p className="text-red-600 text-xs mt-1">{errors.featuredImage[0]}</p>}
                            {form.featuredImage && (
                                <div className="mt-2 rounded-lg border border-gray-200 overflow-hidden w-40">
                                    <img src={form.featuredImage} alt="پیش‌نمایش عکس شاخص" className="w-full h-24 object-cover" />
                                </div>
                            )}
                        </div>
                    </div>

                    {/* ستون تنظیمات */}
                    <div className="space-y-4">
                        <div className="bg-gray-50 rounded-xl p-4 space-y-3">
                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1.5">دسته‌بندی</label>
                                <select value={form.categoryId} onChange={(e) => set('categoryId', e.target.value)} className={field}>
                                    {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </select>
                                {errors.categoryId && <p className="text-red-600 text-xs mt-1">{errors.categoryId}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1.5">محل نمایش در سایت</label>
                                <select value={form.placement} onChange={(e) => set('placement', e.target.value)} className={field}>
                                    {PLACEMENTS.map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1.5">وضعیت</label>
                                <select value={form.status} onChange={(e) => set('status', e.target.value)} className={field}>
                                    {Object.entries(STATUS).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-600 mb-1.5">استان / شهرستان</label>
                                <select value={form.region} onChange={(e) => set('region', e.target.value)} className={field}>
                                    <option value="">— ندارد —</option>
                                    {bushehrCounties.map((c) => (
                                        <option key={c.key} value={`${c.key}:city`}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="bg-gray-50 rounded-xl p-4 space-y-3">
                            <p className="text-xs font-bold text-gray-600">نشان‌ها</p>
                            {[
                                ['isBreaking', '🚨 خبر فوری'],
                                ['isFeatured', '⭐ خبر ویژه'],
                                ['isPinned', '📌 سنجاق در بالا'],
                            ].map(([k, label]) => (
                                <label key={k} className="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" checked={form[k]} onChange={(e) => set(k, e.target.checked)} className="w-4 h-4 rounded border-gray-300 text-[#1B365D]" />
                                    <span className="text-sm text-gray-700">{label}</span>
                                </label>
                            ))}
                        </div>

                        <div className="bg-gray-50 rounded-xl p-4 space-y-3">
                            <p className="text-xs font-bold text-gray-600">منبع</p>
                            <input value={form.source} onChange={(e) => set('source', e.target.value)} placeholder="نام منبع" className={field} />
                            <input value={form.sourceUrl} onChange={(e) => set('sourceUrl', e.target.value)} placeholder="https://..." dir="ltr" className={field} />
                        </div>

                        <details className="bg-gray-50 rounded-xl p-4">
                            <summary className="text-xs font-bold text-gray-600 cursor-pointer">سئو (پیشرفته)</summary>
                            <div className="space-y-3 mt-3">
                                <input value={form.metaTitle} onChange={(e) => set('metaTitle', e.target.value)} placeholder="عنوان سئو" className={field} />
                                <textarea value={form.metaDesc} onChange={(e) => set('metaDesc', e.target.value)} rows="2" placeholder="توضیحات سئو" className={field} />
                                <input value={form.metaKeywords} onChange={(e) => set('metaKeywords', e.target.value)} placeholder="کلیدواژه‌ها" className={field} />
                            </div>
                        </details>
                    </div>

                    <div className="lg:col-span-3 flex justify-end gap-3 pt-2">
                        {editing && (
                            <button type="button" onClick={() => router.get('/admin/articles')} className="px-6 py-2.5 rounded-xl bg-gray-100 text-gray-600 text-sm font-bold hover:bg-gray-200">
                                انصراف
                            </button>
                        )}
                        <button type="submit" className="px-8 py-2.5 rounded-xl bg-[#1B365D] hover:bg-[#2a4a7a] text-white text-sm font-bold shadow-lg">
                            {editing ? 'ذخیره تغییرات' : 'انتشار خبر'}
                        </button>
                    </div>
                    {crop && (
                        <ImageCropper
                            imageSrc={crop.src}
                            fileName={crop.name}
                            defaultAspect="16:9"
                            outputWidth={1280}
                            quality={82}
                            format="webp"
                            onDone={onCropDone}
                            onCancel={cancelCrop}
                        />
                    )}
                </form>
            </div>
            </div>

            {/* ── پیش‌نمایش خبر ── */}
            {preview && (
                <div
                    className="fixed inset-0 z-[9998] flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
                    onClick={() => setPreview(null)}
                >
                    <div
                        className="flex max-h-[88vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <div className="min-w-0">
                                <p className="text-[11px] font-bold text-[#C9A96E]">پیش‌نمایش خبر</p>
                                <h3 className="mt-0.5 truncate text-sm font-black text-[#1B365D]">{preview.title || 'بدون عنوان'}</h3>
                            </div>
                            <button
                                type="button"
                                onClick={() => setPreview(null)}
                                aria-label="بستن پیش‌نمایش"
                                className="mr-3 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
                                    strokeLinecap="round" className="h-4 w-4" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div className="overflow-y-auto">
                            {preview.featuredImage && (
                                <img src={preview.featuredImage} alt={preview.title || ''} className="h-64 w-full object-cover" />
                            )}
                            <div className="p-5">
                                <div className="mb-3 flex flex-wrap items-center gap-2 text-[11px] text-gray-400">
                                    <span className="rounded-md bg-indigo-50 px-2 py-1 font-bold text-indigo-700">
                                        {preview.categoryName || 'عمومی'}
                                    </span>
                                    {preview.publishedAt && (
                                        <span>{new Date(preview.publishedAt).toLocaleDateString('fa-IR')}</span>
                                    )}
                                </div>
                                <h2 className="text-xl font-black leading-8 text-gray-900">{preview.title || 'بدون عنوان'}</h2>
                                {preview.subtitle && <p className="mt-2 text-sm font-bold text-gray-500">{preview.subtitle}</p>}
                                {preview.excerpt && (
                                    <p className="mt-4 rounded-xl bg-gray-50 p-4 text-sm leading-7 text-gray-600">{preview.excerpt}</p>
                                )}
                                {preview.content && (
                                    <div
                                        className="prose prose-sm mt-5 max-w-none text-justify text-sm leading-8 text-gray-700"
                                        dangerouslySetInnerHTML={{ __html: preview.content }}
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {toast && (
                <div className="fixed bottom-6 left-1/2 z-[10000] max-w-[92vw] -translate-x-1/2 rounded-xl bg-[#1B365D] px-5 py-3 text-center text-xs font-bold text-white shadow-2xl" dir="rtl">
                    {toast}
                </div>
            )}
        </AdminLayout>
    );
}
