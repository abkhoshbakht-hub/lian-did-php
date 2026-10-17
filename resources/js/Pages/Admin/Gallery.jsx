import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const COLORS = [
    ['from-blue-500 to-blue-700', 'آبی'],
    ['from-amber-500 to-orange-600', 'کهربایی'],
    ['from-green-500 to-emerald-600', 'سبز'],
    ['from-purple-500 to-indigo-600', 'بنفش'],
    ['from-pink-500 to-rose-500', 'صورتی'],
    ['from-cyan-500 to-teal-500', 'فیروزه‌ای'],
];

const INPUT = 'w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]';
const BTN = 'px-5 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold hover:bg-[#2a4a7a] transition-colors disabled:opacity-50';

const src = (p) => (!p ? null : /^https?:\/\//i.test(p) ? p : `/storage/${p}`);

function Flash({ flash }) {
    if (!flash) return null;
    const isObj = typeof flash === 'object';
    return (
        <div
            className={`mb-5 px-5 py-3 rounded-xl text-sm font-bold border ${
                isObj ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'
            }`}
        >
            {isObj ? flash.error : flash.success}
        </div>
    );
}

export default function AdminGallery({ albums = [], stats = {} }) {
    const { flash } = { flash: null };
    const [open, setOpen] = useState(null);

    const album = useForm({
        title: '', slug: '', category: '', description: '', color: 'from-blue-500 to-blue-700',
        displayOrder: '', isActive: true,
    });

    const item = useForm({ kind: 'image', title: '', url: '', file: null, videoFile: null });

    const createAlbum = () => album.post('/admin/gallery/albums', { preserveScroll: true, onSuccess: () => album.reset() });

    const saveAlbum = (a) => {
        const key = a._key;
        a._method = 'PUT';
        delete a._key;
        router.post(`/admin/gallery/albums/${key}`, {
            ...a,
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['albums'] }),
        });
        a._key = key;
    };

    const removeAlbum = (id) => {
        if (!confirm('این آلبوم و همهٔ تصاویرش حذف شود؟')) return;
        router.delete(`/admin/gallery/albums/${id}`, { preserveScroll: true });
    };

    const removeItem = (albumId, itemId) => {
        if (!confirm('این آیتم حذف شود؟')) return;
        router.delete(`/admin/gallery/items/${itemId}`, { preserveScroll: true });
    };

    const pickFile = (e) => {
        const f = e.target.files?.[0] || null;
        const fd = new FormData();
        fd.append('kind', item.data.kind);
        if (item.data.title) fd.append('title', item.data.title);
        if (item.data.url) fd.append('url', item.data.url);
        if (f) fd.append(item.data.kind === 'video' ? 'videoFile' : 'file', f);

        fetch(`/admin/gallery/albums/${open}/items`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: fd,
            credentials: 'include',
        })
            .then((r) => r.json())
            .then(() => {
                item.reset();
                router.reload({ only: ['albums'] });
            })
            .then(() => e.target.value = '');
    };

    return (
        <AdminLayout title="گالری تصاویر">
            <Head title="گالری | پنل لیان دید" />
            <Flash flash={album.errors && Object.keys(album.errors).length ? { error: Object.values(album.errors)[0] } : null} />

            <div className="grid grid-cols-4 gap-4 mb-5">
                {[['albums', 'آلبوم'], ['items', 'آیتم'], ['images', 'تصویر'], ['videos', 'ویدیو']].map(([k, l]) => (
                    <div key={k} className="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <p className="text-xs text-gray-400 font-bold">{l}</p>
                        <p className="text-2xl font-black text-[#1B365D] mt-1">{stats[k] ?? 0}</p>
                    </div>
                ))}
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6">
                <h2 className="font-black text-[#1B365D] mb-4">آلبوم جدید</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <input className={INPUT} placeholder="عنوان آلبوم" value={album.data.title}
                        onChange={(e) => album.setData('title', e.target.value)} />
                    <input className={INPUT} placeholder="دسته (مثلاً سیاسی)" value={album.data.category}
                        onChange={(e) => album.setData('category', e.target.value)} />
                    <input className={INPUT} placeholder="نشانی (خالی = خودکار)" value={album.data.slug}
                        onChange={(e) => album.setData('slug', e.target.value)} />
                    <select className={INPUT} value={album.data.color}
                        onChange={(e) => album.setData('color', e.target.value)}>
                        {COLORS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                    </select>
                </div>
                <textarea className={`${INPUT} mt-3`} rows={2} placeholder="توضیح کوتاه"
                    value={album.data.description} onChange={(e) => album.setData('description', e.target.value)} />
                <button className={`${BTN} mt-3`} onClick={createAlbum} disabled={album.processing || !album.data.title}>
                    {album.processing ? 'در حال ذخیره...' : 'ساخت آلبوم'}
                </button>
            </div>

            <div className="space-y-4">
                {albums.length === 0 && (
                    <p className="text-center text-gray-400 py-12">هنوز آلبومی ساخته نشده است.</p>
                )}

                {albums.map((a) => (
                    <div key={a.id} className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                        <div className="flex items-center gap-4 p-4">
                            <div className={`w-16 h-16 rounded-xl bg-gradient-to-br ${a.color} shrink-0 relative overflow-hidden`}>
                                {a.coverImage && <img src={src(a.coverImage)} alt="" className="absolute inset-0 w-full h-full object-cover" />}
                            </div>
                            <div className="flex-1 min-w-0">
                                <input
                                    className="font-bold text-[#1B365D] bg-transparent border-b border-transparent hover:border-gray-200 focus:border-[#1B365D] outline-none w-full"
                                    defaultValue={a.title}
                                    onBlur={(e) => e.target.value !== a.title && saveAlbum({ _key: a.id, title: e.target.value })}
                                />
                                <p className="text-xs text-gray-400 mt-1">
                                    {a.category || 'بدون دسته'} · {a.itemCount} آیتم · /gallery/{a.slug}
                                </p>
                            </div>
                            <div className="flex gap-2 shrink-0">
                                <button
                                    className="px-4 py-2 rounded-lg bg-[#1B365D] text-white text-xs font-bold"
                                    onClick={() => setOpen(open === a.id ? null : a.id)}
                                >
                                    {open === a.id ? 'بستن' : 'مدیریت تصاویر'}
                                </button>
                                <button
                                    className="px-4 py-2 rounded-lg bg-red-50 text-red-600 text-xs font-bold hover:bg-red-100"
                                    onClick={() => removeAlbum(a.id)}
                                >
                                    حذف
                                </button>
                            </div>
                        </div>

                        {open === a.id && (
                            <div className="border-t border-gray-100 p-4 bg-gray-50">
                                <div className="flex flex-wrap gap-3 mb-4 items-end">
                                    <select className={`${INPUT} w-40`} value={item.data.kind}
                                        onChange={(e) => item.setData('kind', e.target.value)}>
                                        <option value="image">تصویر</option>
                                        <option value="video">ویدیو</option>
                                    </select>
                                    <input className={`${INPUT} w-56`} placeholder="عنوان (اختیاری)" value={item.data.title}
                                        onChange={(e) => item.setData('title', e.target.value)} />
                                    <label className="px-5 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold cursor-pointer hover:bg-[#2a4a7a]">
                                        انتخاب فایل
                                        <input type="file" className="hidden"
                                            accept={item.data.kind === 'video' ? 'video/*' : 'image/*'}
                                            onChange={pickFile} />
                                    </label>
                                    <span className="text-xs text-gray-400">
                                        تصاویر به‌صورت خودکار فشرده و به webp تبدیل می‌شوند.
                                    </span>
                                </div>

                                {a.items.length === 0 ? (
                                    <p className="text-center text-gray-400 py-8 text-sm">آیتمی در این آلبوم نیست.</p>
                                ) : (
                                    <div className="grid grid-cols-3 md:grid-cols-6 gap-3">
                                        {a.items.map((it) => (
                                            <div key={it.id} className="relative group bg-white rounded-lg overflow-hidden border border-gray-200">
                                                {it.kind === 'video' ? (
                                                    <div className="aspect-square bg-gray-900 grid place-items-center text-white text-2xl">▶</div>
                                                ) : it.thumbnail || it.url ? (
                                                    <img src={src(it.thumbnail || it.url)} alt="" className="w-full aspect-square object-cover" />
                                                ) : (
                                                    <div className="aspect-square bg-gray-100" />
                                                )}
                                                <button
                                                    onClick={() => removeItem(a.id, it.id)}
                                                    className="absolute top-1 left-1 w-6 h-6 rounded-lg bg-red-600 text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                                >
                                                    ×
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}