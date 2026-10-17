import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminDashboard({ articles = [], categories = [], stats = {} }) {
    const [activeTab, setActiveTab] = useState('articles');
    const [editingId, setEditingId] = useState(null);
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
                <span className="mr-2 text-xs opacity-80">({badge})</span>
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
                                <h3 className="text-3xl font-black text-[#1B365D] mt-1">{s.value ?? 0}</h3>
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
                                            <td className="p-4 text-gray-600 font-mono">{a.viewCount || 0}</td>
                                            <td className="p-4 text-gray-500 text-xs">
                                                {a.publishedAt ? String(a.publishedAt).slice(0, 10) : '—'}
                                            </td>
                                            <td className="p-4">
                                                <div className="flex items-center justify-center gap-2">
                                                    <button
                                                        onClick={() => startEdit(a)}
                                                        className="text-amber-700 hover:text-amber-900 font-bold bg-amber-50 px-3 py-1.5 rounded-lg text-xs"
                                                    >
                                                        ویرایش
                                                    </button>
                                                    <Link
                                                        href={`/news/${a.slug}`}
                                                        target="_blank"
                                                        className="text-blue-600 hover:text-blue-800 font-bold bg-blue-50 px-3 py-1.5 rounded-lg text-xs"
                                                    >
                                                        نمایش
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(a.id)}
                                                        className="text-red-600 hover:text-red-800 font-bold bg-red-50 px-3 py-1.5 rounded-lg text-xs"
                                                    >
                                                        حذف
                                                    </button>
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
                                <label className="block text-sm font-bold text-gray-700 mb-2">لینک تصویر شاخص (URL)</label>
                                <input
                                    type="url"
                                    value={form.featuredImage}
                                    onChange={(e) => set('featuredImage', e.target.value)}
                                    placeholder="https://example.com/image.jpg"
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#1B365D] outline-none text-sm"
                                />
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
            </div>
        </AdminLayout>
    );
}
