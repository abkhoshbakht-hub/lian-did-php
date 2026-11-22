import React, { useRef, useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { uploadAdminImage } from '@/Components/admin/uploadAdminImage';
import ImageCropper from '@/Components/admin/ImageCropper';
import { faNum, faDate } from '@/Components/admin/faFormat';

const PLACEMENTS = [
    { value: 'latest', label: 'آخرین اخبار' },
    { value: 'hero', label: 'هدر اصلی سایت' },
    { value: 'analysis', label: 'بخش تحلیل' },
    { value: 'featured', label: 'ویژه' },
];

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

function ArticleActionsMenu({ article, onEdit, onDelete, onPreview, onSuggest, onNotify }) {
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
        router.post(`/admin/articles/${article.id}/duplicate`, {}, { preserveScroll: false });
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

                    <button
                        type="button"
                        onClick={() => { setPos(null); if (onEdit) onEdit(); }}
                        className={`${itemCls} text-gray-700 hover:bg-amber-50 hover:text-amber-800`}
                    >
                        <ActionIcon className="text-amber-500">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </ActionIcon>
                        ویرایش
                    </button>

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

                    <button
                        type="button"
                        onClick={() => { setPos(null); onSuggest(); }}
                        className={`${itemCls} text-gray-700 hover:bg-teal-50 hover:text-teal-800`}
                    >
                        <ActionIcon className="text-teal-500">
                            <path d="m22 2-7 20-4-9-9-4Z" />
                            <path d="M22 2 11 13" />
                        </ActionIcon>
                        پیشنهاد خبر
                    </button>

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

export default function AdminDashboard({ articles = [], categories = [], stats = {} }) {
    const [activeTab, setActiveTab] = useState('articles');
    const [editingId, setEditingId] = useState(null);
    const fileRef = useRef(null);
    const [imgBusy, setImgBusy] = useState(false);
    const [crop, setCrop] = useState(null); // { src, name }
    const onCropDone = (blob) => {
        const f = crop ? crop.name : 'image.webp';
        setCrop(null);
        if (fileRef.current) fileRef.current.value = '';
        const file = new File([blob], f.replace(/\.[^.]+$/, '') + '.webp', { type: blob.type });
        setImgBusy(true);
        uploadAdminImage(file)
            .then((url) => set('featuredImage', url))
            .catch(() => {})
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
    const [form, setForm] = useState({
        title: '',
        subtitle: '',
        excerpt: '',
        content: '',
        categoryId: '',
        featuredImage: '',
        isBreaking: false,
        isFeatured: false,
        isPinned: false,
        status: 'PUBLISHED',
    });
    const [searchFilter, setSearchFilter] = useState('');
    const [flash, setFlash] = useState(null);
    const [preview, setPreview] = useState(null);
    const [toast, setToast] = useState(null);
    const [suggest, setSuggest] = useState(null);

    useEffect(() => {
        if (!toast) return;
        const t = setTimeout(() => setToast(null), 4500);
        return () => clearTimeout(t);
    }, [toast]);

    useEffect(() => {
        if (!preview && !suggest) return;
        const onKey = (e) => {
            if (e.key === 'Escape') { setPreview(null); setSuggest(null); }
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [preview, suggest]);

    const set = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    const resetForm = () => {
        setForm({
            title: '', subtitle: '', excerpt: '', content: '',
            categoryId: categories[0]?.id || '', featuredImage: '',
            isBreaking: false, isFeatured: false, isPinned: false, status: 'PUBLISHED',
        });
        setEditingId(null);
    };

    const startEdit = (article) => {
        setForm({
            title: article.title || '',
            subtitle: article.subtitle || '',
            excerpt: article.excerpt || '',
            content: article.content || '',
            categoryId: article.categoryId || categories[0]?.id || '',
            featuredImage: article.featuredImage || '',
            isBreaking: !!article.isBreaking,
            isFeatured: !!article.isFeatured,
            isPinned: !!article.isPinned,
            status: article.status || 'PUBLISHED',
        });
        setEditingId(article.id);
        setActiveTab('create');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        const payload = { ...form };

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                resetForm();
                setActiveTab('articles');
                setFlash(editingId ? 'تغییرات با موفقیت ذخیره شد.' : 'خبر با موفقیت منتشر شد.');
            },
            onError: () => setFlash({ error: 'ذخیره نشد. ورودی‌ها را بررسی کنید.' }),
        };

        if (editingId) {
            router.put(`/admin/articles/${editingId}`, payload, options);
        } else {
            router.post('/admin/articles', payload, options);
        }
    };

    const handleDelete = (id) => {
        if (!confirm('آیا از حذف این خبر اطمینان دارید؟ این عمل غیرقابل بازگشت است.')) return;
        router.delete(`/admin/articles/${id}`, {
            preserveScroll: true,
            onSuccess: () => setFlash('خبر با موفقیت حذف گردید.'),
        });
    };

    const filteredArticles = articles.filter((a) => {
        const q = searchFilter.trim().toLowerCase();
        if (!q) return true;
        return (a.title || '').toLowerCase().includes(q)
            || (a.categoryName || '').toLowerCase().includes(q);
    });

    const statusLabel = {
        PUBLISHED: 'منتشر شده',
        DRAFT: 'پیش‌نویس',
        ARCHIVED: 'بایگانی',
    };

    const tabButton = (key, label, badge = null) => (
        <button
            key={key}
            onClick={() => { if (key !== 'create' || !editingId) resetForm(); setActiveTab(key); }}
            className={`px-5 py-2.5 rounded-xl text-sm font-bold transition-all ${
                activeTab === key
                    ? 'bg-[#1B365D] text-white shadow-lg'
                    : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
            }`}
        >
            {label}
            {badge !== null && (
                <span className="mr-2 text-xs opacity-80">({faNum(badge)})</span>
            )}
        </button>
    );

    return (
        <AdminLayout title="داشبورد">
            <Head title="پنل مدیریت | لیان دید" />

            <div className="max-w-7xl mx-auto px-4 py-8">
                {/* هدر */}
                <div className="bg-gradient-to-l from-[#1B365D] to-[#2E5090] text-white p-8 rounded-2xl mb-8 shadow-xl flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <div className="flex items-center gap-2 mb-2">
                            <span className="bg-[#C9A96E] text-[#1B365D] font-bold px-3 py-1 rounded-lg text-xs">سامانه مدیریت محتوا</span>
                        </div>
                        <h1 className="text-3xl font-black">داشبورد مدیریت لیان دید</h1>
                        <p className="text-sm text-white/70 mt-1">کنترل انتشار اخبار، دسته‌بندی‌ها و کاربران</p>
                    </div>
                    <Link
                        href="/"
                        className="bg-white/10 hover:bg-white/25 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all border border-white/10"
                    >
                        مشاهده سایت زنده &larr;
                    </Link>
                </div>

                {flash && (
                    <div className={`mb-6 px-5 py-3 rounded-xl text-sm font-bold ${
                        typeof flash === 'string'
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : 'bg-red-50 text-red-700 border border-red-200'
                    }`}>
                        {typeof flash === 'string' ? flash : flash.error}
                    </div>
                )}

                {/* آمار */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    {[
                        { label: 'کل اخبار', value: stats.totalArticles, icon: '📄' },
                        { label: 'مجموع بازدید', value: stats.totalViews, icon: '👁️' },
                        { label: 'دسته‌بندی‌ها', value: stats.totalCategories, icon: '🗂️' },
                        { label: 'اخبار خارجی', value: stats.totalExternal, icon: '📡' },
                    ].map((s) => (
                        <div key={s.label} className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                            <div>
                                <p className="text-xs text-gray-400 font-bold">{s.label}</p>
                                <h3 className="text-3xl font-black text-[#1B365D] mt-1">{faNum(s.value ?? 0)}</h3>
                            </div>
                            <div className="w-14 h-14 bg-blue-50 text-[#1B365D] rounded-2xl flex items-center justify-center text-xl">
                                {s.icon}
                            </div>
                        </div>
                    ))}
                </div>

                {/* تب‌ها */}
                <div className="flex flex-wrap gap-3 mb-6">
                    {tabButton('articles', 'لیست اخبار', articles.length)}
                    {tabButton('create', editingId ? '✏️ ویرایش خبر' : '➕ انتشار خبر جدید')}
                </div>

                {/* لیست اخبار */}
                {activeTab === 'articles' && (
                    <div className="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <div className="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <h2 className="text-lg font-black text-[#1B365D]">اخبار منتشرشده</h2>
                            <input
                                type="text"
                                placeholder="جستجو در عنوان یا دسته خبر..."
                                value={searchFilter}
                                onChange={(e) => setSearchFilter(e.target.value)}
                                className="w-full md:w-80 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]"
                            />
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-sm">
                                <thead className="bg-gray-50 text-gray-600 border-b">
                                    <tr>
                                        <th className="p-4 font-bold">عنوان خبر</th>
                                        <th className="p-4 font-bold">دسته‌بندی</th>
                                        <th className="p-4 font-bold">وضعیت</th>
                                        <th className="p-4 font-bold">بازدید</th>
                                        <th className="p-4 font-bold">تاریخ</th>
                                        <th className="p-4 font-bold text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {filteredArticles.length > 0 ? filteredArticles.map((a) => (
                                        <tr key={a.id} className="hover:bg-gray-50/80 transition-colors">
                                            <td className="p-4 font-bold text-gray-900 max-w-sm">
                                                <div className="truncate">{a.title}</div>
                                                <div className="flex items-center gap-1.5 mt-1">
                                                    {a.isBreaking && <span className="bg-red-100 text-red-700 text-[11px] px-2 rounded-md font-bold">فوری</span>}
                                                    {a.isFeatured && <span className="bg-amber-100 text-amber-800 text-[11px] px-2 rounded-md font-bold">ویژه</span>}
                                                    {a.isPinned && <span className="bg-indigo-100 text-indigo-700 text-[11px] px-2 rounded-md font-bold">سنجاق</span>}
                                                </div>
                                            </td>
                                            <td className="p-4 text-gray-600 font-medium">{a.categoryName}</td>
                                            <td className="p-4 text-gray-600">
                                                <span className={`text-[11px] px-2.5 py-1 rounded-md font-bold ${
                                                    a.status === 'PUBLISHED'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-gray-100 text-gray-600'
                                                }`}>
                                                    {statusLabel[a.status] || a.status}
                                                </span>
                                            </td>
                                            <td className="p-4 text-gray-600 font-mono">{faNum(a.viewCount || 0)}</td>
                                            <td className="p-4 text-gray-500 text-xs" style={{ whiteSpace: 'nowrap' }}>
                                                {a.publishedAt ? faDate(a.publishedAt) : (a.createdAt ? faDate(a.createdAt) : '—')}
                                            </td>
                                            <td className="p-4">
                                                <div className="flex items-center justify-center">
                                                    <ArticleActionsMenu
                                                        article={a}
                                                        onEdit={() => startEdit(a)}
                                                        onDelete={() => handleDelete(a.id)}
                                                        onPreview={() => setPreview(a)}
                                                        onSuggest={() => setSuggest(a)}
                                                        onNotify={setToast}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="6" className="text-center py-12 text-gray-400">
                                                هیچ خبری با این مشخصات یافت نشد.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* فرم ساخت / ویرایش */}
                {activeTab === 'create' && (
                    <div className="bg-white border border-gray-200 rounded-2xl p-6 lg:p-8 shadow-sm">
                        <h2 className="text-lg font-black text-[#1B365D] mb-6 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span>{editingId ? '✏️' : '✍️'}</span>
                            {editingId ? 'ویرایش خبر' : 'ایجاد خبر جدید'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">عنوان خبر *</label>
                                <input
                                    type="text"
                                    value={form.title}
                                    onChange={(e) => set('title', e.target.value)}
                                    required
                                    placeholder="عنوان اصلی خبر..."
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none text-sm"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">زیرعنوان (اختیاری)</label>
                                <input
                                    type="text"
                                    value={form.subtitle}
                                    onChange={(e) => set('subtitle', e.target.value)}
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#1B365D] outline-none text-sm"
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-bold text-gray-700 mb-2">دسته‌بندی *</label>
                                    <select
                                        value={form.categoryId}
                                        onChange={(e) => set('categoryId', e.target.value)}
                                        required
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#1B365D] outline-none text-sm"
                                    >
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-gray-700 mb-2">وضعیت</label>
                                    <select
                                        value={form.status}
                                        onChange={(e) => set('status', e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#1B365D] outline-none text-sm"
                                    >
                                        <option value="PUBLISHED">منتشر شده</option>
                                        <option value="DRAFT">پیش‌نویس</option>
                                        <option value="ARCHIVED">بایگانی</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">تصویر شاخص</label>
                                <input
                                    ref={fileRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    className="hidden"
                                    onChange={pickImage}
                                />
                                <div className="flex gap-2">
                                    <input
                                        type="url"
                                        value={form.featuredImage}
                                        onChange={(e) => set('featuredImage', e.target.value)}
                                        placeholder="https://… یا با دکمهٔ کنار"
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#1B365D] outline-none text-sm"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => fileRef.current && fileRef.current.click()}
                                        disabled={imgBusy}
                                        className="shrink-0 px-4 rounded-xl bg-[#1B365D] text-white text-xs font-bold hover:bg-[#2a4a7a] disabled:opacity-50 whitespace-nowrap"
                                    >
                                        {imgBusy ? '…' : 'انتخاب فایل'}
                                    </button>
                                </div>
                                {form.featuredImage && (
                                    <div className="mt-2 rounded-xl border border-gray-200 overflow-hidden w-48">
                                        <img src={form.featuredImage} alt="پیش‌نمایش تصویر شاخص" className="w-full h-28 object-cover" />
                                    </div>
                                )}
                            </div>

                            <div className="flex flex-wrap items-center gap-8 py-3 bg-gray-50 px-6 rounded-xl border border-gray-100">
                                {[
                                    ['isBreaking', 'خبر فوری', '🚨'],
                                    ['isFeatured', 'خبر ویژه', '⭐'],
                                    ['isPinned', 'سنجاق‌شده', '📌'],
                                ].map(([key, label, icon]) => (
                                    <label key={key} className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={form[key]}
                                            onChange={(e) => set(key, e.target.checked)}
                                            className="w-4 h-4 text-[#1B365D] rounded border-gray-300"
                                        />
                                        <span className="text-sm font-bold text-gray-700">{icon} {label}</span>
                                    </label>
                                ))}
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">خلاصه (چکیده برای کارت‌ها)</label>
                                <textarea
                                    value={form.excerpt}
                                    onChange={(e) => set('excerpt', e.target.value)}
                                    rows="2"
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#1B365D] outline-none text-sm"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">متن کامل خبر *</label>
                                <textarea
                                    value={form.content}
                                    onChange={(e) => set('content', e.target.value)}
                                    rows="8"
                                    required
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#1B365D] outline-none text-sm"
                                />
                            </div>

                            <div className="flex justify-end gap-4 pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    onClick={() => { resetForm(); setActiveTab('articles'); }}
                                    className="px-6 py-3 rounded-xl font-bold text-sm bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all"
                                >
                                    انصراف
                                </button>
                                <button
                                    type="submit"
                                    className="px-8 py-3 rounded-xl font-bold text-sm bg-[#1B365D] hover:bg-[#2a4a7a] text-white transition-all shadow-lg"
                                >
                                    {editingId ? 'ذخیره تغییرات' : 'انتشار در سایت'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            {/* پیشنهاد خبر */}
            {suggest && (
                <div
                    className="fixed inset-0 z-[9998] flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
                    onClick={() => setSuggest(null)}
                >
                    <div
                        className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <div className="min-w-0">
                                <p className="text-[11px] font-bold text-[#C9A96E]">پیشنهاد خبر</p>
                                <h3 className="mt-0.5 text-sm font-black text-[#1B365D]">محل نمایش پیشنهادی را انتخاب کنید</h3>
                            </div>
                            <button
                                type="button"
                                onClick={() => setSuggest(null)}
                                aria-label="بستن پیشنهاد خبر"
                                className="mr-3 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
                                    strokeLinecap="round" className="h-4 w-4" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div className="p-4">
                            <p className="mb-3 line-clamp-2 text-xs font-bold text-gray-500">{suggest.title}</p>
                            <div className="space-y-2">
                                {PLACEMENTS.map((p) => (
                                    <button
                                        key={p.value}
                                        type="button"
                                        onClick={() => {
                                            const id = suggest.id;
                                            setSuggest(null);
                                            router.post(`/admin/articles/${id}/placement`, { placement: p.value }, { preserveScroll: true });
                                        }}
                                        className={`flex w-full items-center justify-between rounded-xl border px-4 py-2.5 text-right text-xs font-bold transition-colors ${suggest.placement === p.value ? 'border-[#1B365D] bg-[#1B365D]/5 text-[#1B365D]' : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-[#C9A96E] hover:bg-amber-50'}`}
                                    >
                                        <span>{p.label}</span>
                                        <span className={`text-[10px] ${suggest.placement === p.value ? 'text-emerald-600' : 'text-gray-400'}`}>
                                            {suggest.placement === p.value ? 'فعلی' : 'انتخاب'}
                                        </span>
                                    </button>
                                ))}
                            </div>
                            <p className="mt-3 text-[10px] leading-5 text-gray-400">
                                با انتخاب هر گزینه، محل نمایش این خبر در صفحه اصلی به‌روزرسانی می‌شود.
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* پیش‌نمایش خبر */}
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
                                    {preview.publishedAt && <span>{faDate(preview.publishedAt)}</span>}
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
            </div>
        </AdminLayout>
    );
}
