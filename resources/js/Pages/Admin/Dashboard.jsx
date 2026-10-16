import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function AdminDashboard({ articles = [], categories = [], stats = {} }) {
    const [activeTab, setActiveTab] = useState('articles'); // 'articles', 'create', 'settings'
    const [title, setTitle] = useState('');
    const [subtitle, setSubtitle] = useState('');
    const [excerpt, setExcerpt] = useState('');
    const [content, setContent] = useState('');
    const [categoryId, setCategoryId] = useState(categories[0]?.id || '1');
    const [featuredImage, setFeaturedImage] = useState('');
    const [isBreaking, setIsBreaking] = useState(false);
    const [isFeatured, setIsFeatured] = useState(false);
    const [searchFilter, setSearchFilter] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post('/admin/articles', {
            title,
            subtitle,
            excerpt,
            content,
            categoryId,
            featuredImage: featuredImage || 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=1200&q=80',
            isBreaking,
            isFeatured,
        }, {
            onSuccess: () => {
                setTitle('');
                setSubtitle('');
                setExcerpt('');
                setContent('');
                setFeaturedImage('');
                setIsBreaking(false);
                setIsFeatured(false);
                setActiveTab('articles');
                alert('خبر جدید با موفقیت در سامانه منتشر شد.');
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('آیا از حذف این خبر اطمینان دارید؟ این عمل غیرقابل بازگشت است.')) {
            router.delete(`/admin/articles/${id}`);
        }
    };

    const filteredArticles = articles.filter(a => 
        a.title.toLowerCase().includes(searchFilter.toLowerCase()) ||
        a.categoryName.toLowerCase().includes(searchFilter.toLowerCase())
    );

    return (
        <MainLayout title="مدیریت سیستم خبری لیان دید">
            <Head title="پنل مدیریت پیشرفته | لیان دید" />
            
            <div className="max-w-7xl mx-auto px-4 py-8">
                {/* هدر بالایی پنل ادمین */}
                <div className="bg-gradient-to-r from-[#1B365D] to-[#2a4a7a] text-white p-8 rounded-2xl mb-8 shadow-xl flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <div className="flex items-center gap-2 mb-2">
                            <span className="bg-[#C9A96E] text-gray-900 font-bold px-3 py-1 rounded-lg text-xs uppercase tracking-wider">سامانه مدیریت محتوای سازمانی (CMS)</span>
                            <span className="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-3 py-1 rounded-lg text-xs font-mono">نسخه عملیاتی 2.0</span>
                        </div>
                        <h1 className="text-3xl font-black">داشبورد مدیریت خبرگزاری لیان دید</h1>
                        <p className="text-sm text-gray-300 mt-1">کنترل کامل انتشار اخبار، مدیریت محتوا، تحلیل بازدیدها و تنظیمات پایگاه خبری</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link href="/" className="bg-white/10 hover:bg-white/25 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all border border-white/10">
                            مشاهده سایت زنده &larr;
                        </Link>
                    </div>
                </div>

                {/* آمار کلان سیستم */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-400 font-bold uppercase tracking-wider">کل اخبار آرشیو شده</p>
                            <h3 className="text-3xl font-black text-[#1B365D] mt-1">{stats.totalArticles || 0}</h3>
                        </div>
                        <div className="w-14 h-14 bg-blue-50 text-[#1B365D] rounded-2xl flex items-center justify-center text-xl font-bold">📄</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-400 font-bold uppercase tracking-wider">مجموع بازدید کاربران</p>
                            <h3 className="text-3xl font-black text-[#1B365D] mt-1">{stats.totalViews || 0}</h3>
                        </div>
                        <div className="w-14 h-14 bg-amber-50 text-[#C9A96E] rounded-2xl flex items-center justify-center text-xl font-bold">👁️</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-400 font-bold uppercase tracking-wider">دسته‌بندی‌های فعال</p>
                            <h3 className="text-3xl font-black text-[#1B365D] mt-1">{stats.totalCategories || 0}</h3>
                        </div>
                        <div className="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl font-bold">🗂️</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-400 font-bold uppercase tracking-wider">مدیران سیستم</p>
                            <h3 className="text-3xl font-black text-[#1B365D] mt-1">{stats.totalUsers || 1}</h3>
                        </div>
                        <div className="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl font-bold">🛡️</div>
                    </div>
                </div>

                {/* نوار تب‌های مدیریت */}
                <div className="flex items-center gap-3 mb-6 border-b border-gray-200 pb-4">
                    <button 
                        onClick={() => setActiveTab('articles')}
                        className={`px-6 py-2.5 rounded-xl font-bold text-sm transition-all ${activeTab === 'articles' ? 'bg-[#1B365D] text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                    >
                        📋 مدیریت اخبار و مقالات ({articles.length})
                    </button>
                    <button 
                        onClick={() => setActiveTab('create')}
                        className={`px-6 py-2.5 rounded-xl font-bold text-sm transition-all ${activeTab === 'create' ? 'bg-[#1B365D] text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                    >
                        ➕ انتشار خبر جدید
                    </button>
                </div>

                {/* تب اول: لیست و مدیریت اخبار */}
                {activeTab === 'articles' && (
                    <div className="bg-white border border-gray-200 rounded-2xl p-6 lg:p-8 shadow-sm">
                        <div className="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <h2 className="text-lg font-black text-[#1B365D]">لیست کل اخبار منتشر شده در سامانه</h2>
                            <input 
                                type="text"
                                placeholder="جستجو در عنوان یا دسته خبر..."
                                value={searchFilter}
                                onChange={e => setSearchFilter(e.target.value)}
                                className="w-full md:w-80 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]"
                            />
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-sm">
                                <thead className="bg-gray-50 text-gray-600 border-b">
                                    <tr>
                                        <th className="p-4 font-bold">عنوان خبر</th>
                                        <th className="p-4 font-bold">دسته‌بندی</th>
                                        <th className="p-4 font-bold">نشان‌ها</th>
                                        <th className="p-4 font-bold">بازدید</th>
                                        <th className="p-4 font-bold">تاریخ انتشار</th>
                                        <th className="p-4 font-bold text-center">عملیات مدیریت</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {filteredArticles.length > 0 ? filteredArticles.map(article => (
                                        <tr key={article.id} className="hover:bg-gray-50/80 transition-colors">
                                            <td className="p-4 font-bold text-gray-900 max-w-sm truncate">{article.title}</td>
                                            <td className="p-4 text-gray-600 font-medium">{article.categoryName}</td>
                                            <td className="p-4 flex items-center gap-1.5">
                                                {article.isBreaking && <span className="bg-red-100 text-red-700 text-[11px] px-2.5 py-0.5 rounded-md font-bold">فوری</span>}
                                                {article.isFeatured && <span className="bg-amber-100 text-amber-800 text-[11px] px-2.5 py-0.5 rounded-md font-bold">ویژه</span>}
                                                {!article.isBreaking && !article.isFeatured && <span className="text-gray-400 text-xs">—</span>}
                                            </td>
                                            <td className="p-4 text-gray-600 font-mono">{article.viewCount || 0}</td>
                                            <td className="p-4 text-gray-500 text-xs">{new Date(article.publishedAt).toLocaleDateString('fa-IR')}</td>
                                            <td className="p-4 flex items-center justify-center gap-3">
                                                <Link href={`/news/${article.slug}`} target="_blank" className="text-blue-600 hover:text-blue-800 font-bold bg-blue-50 px-3 py-1.5 rounded-lg text-xs">نمایش</Link>
                                                <button onClick={() => handleDelete(article.id)} className="text-red-600 hover:text-red-800 font-bold bg-red-50 px-3 py-1.5 rounded-lg text-xs">حذف</button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="6" className="text-center py-12 text-gray-400">هیچ خبری با این مشخصات یافت نشد.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* تب دوم: فرم حرفه‌ای انتشار خبر */}
                {activeTab === 'create' && (
                    <div className="bg-white border border-gray-200 rounded-2xl p-6 lg:p-8 shadow-sm">
                        <h2 className="text-lg font-black text-[#1B365D] mb-6 pb-3 border-b border-gray-100 flex items-center gap-2">
                            <span>✍️</span> ایجاد خبر جدید در پایگاه خبری لیان‌دید
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">عنوان خبر *</label>
                                <input 
                                    type="text" 
                                    value={title} 
                                    onChange={e => setTitle(e.target.value)} 
                                    required
                                    placeholder="عنوان اصلی خبر..."
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none text-sm"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">روتیتر / زیرعنوان (اختیاری)</label>
                                <input 
                                    type="text" 
                                    value={subtitle} 
                                    onChange={e => setSubtitle(e.target.value)} 
                                    placeholder="زیرعنوان یا روتیتر خبری..."
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none text-sm"
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-bold text-gray-700 mb-2">دسته‌بندی خبر *</label>
                                    <select 
                                        value={categoryId} 
                                        onChange={e => setCategoryId(e.target.value)}
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none text-sm"
                                    >
                                        {categories.map(cat => (
                                            <option key={cat.id} value={cat.id}>{cat.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-gray-700 mb-2">لینک تصویر شاخص (URL)</label>
                                    <input 
                                        type="url" 
                                        value={featuredImage} 
                                        onChange={e => setFeaturedImage(e.target.value)} 
                                        placeholder="https://example.com/image.jpg"
                                        className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none text-sm"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-8 py-2 bg-gray-50 px-6 rounded-xl border border-gray-100">
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked={isBreaking} onChange={e => setIsBreaking(e.target.checked)} className="w-4 h-4 text-[#1B365D] rounded border-gray-300" />
                                    <span className="text-sm font-bold text-gray-700">علامت‌گذاری به عنوان «خبر فوری» 🚨</span>
                                </label>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked={isFeatured} onChange={e => setIsFeatured(e.target.checked)} className="w-4 h-4 text-[#1B365D] rounded border-gray-300" />
                                    <span className="text-sm font-bold text-gray-700">علامت‌گذاری به عنوان «خبر ویژه» ⭐</span>
                                </label>
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">خلاصه (چکیده خبر برای کارت‌ها)</label>
                                <textarea 
                                    value={excerpt} 
                                    onChange={e => setExcerpt(e.target.value)} 
                                    rows="2"
                                    placeholder="مختصری از متن خبر که در صفحه اصلی نمایش داده می‌شود..."
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none text-sm"
                                ></textarea>
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">متن کامل خبر *</label>
                                <textarea 
                                    value={content} 
                                    onChange={e => setContent(e.target.value)} 
                                    rows="7"
                                    required
                                    placeholder="متن کامل خبر یا گزارش..."
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none text-sm"
                                ></textarea>
                            </div>

                            <div className="flex justify-end gap-4 pt-4 border-t border-gray-100">
                                <button 
                                    type="button" 
                                    onClick={() => setActiveTab('articles')}
                                    className="px-6 py-3 rounded-xl font-bold text-sm bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all"
                                >
                                    انصراف
                                </button>
                                <button 
                                    type="submit" 
                                    className="px-8 py-3 rounded-xl font-bold text-sm bg-[#1B365D] hover:bg-[#2a4a7a] text-white transition-all shadow-lg shadow-[#1B365D]/20"
                                >
                                    انتشار نهایی در سایت
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        </MainLayout>
    );
}