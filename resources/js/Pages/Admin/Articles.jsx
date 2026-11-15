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
                                        <div className="flex gap-1.5">
                                            <Link href={`/admin/articles?edit=${a.id}`} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-700 font-bold hover:bg-amber-100">ویرایش</Link>
                                            <a href={`/news/${a.slug}`} target="_blank" rel="noreferrer" className="text-[11px] px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 font-bold hover:bg-blue-100">نمایش</a>
                                            <button onClick={() => del(a.id, a.title)} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold hover:bg-red-100">حذف</button>
                                        </div>
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
        </AdminLayout>
    );
}
