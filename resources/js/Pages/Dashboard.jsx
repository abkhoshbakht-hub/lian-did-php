import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function AdminDashboard({ articles = [], categories = [], stats = {} }) {
    const [title, setTitle] = useState('');
    const [excerpt, setExcerpt] = useState('');
    const [content, setContent] = useState('');
    const [categoryId, setCategoryId] = useState(categories[0]?.id || '1');
    const [isBreaking, setIsBreaking] = useState(false);
    const [isFeatured, setIsFeatured] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post('/admin/articles', {
            title,
            excerpt,
            content,
            categoryId,
            isBreaking,
            isFeatured,
        }, {
            onSuccess: () => {
                setTitle('');
                setExcerpt('');
                setContent('');
                setIsBreaking(false);
                setIsFeatured(false);
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('آیا از حذف این خبر اطمینان دارید؟')) {
            router.delete(`/admin/articles/${id}`);
        }
    };

    return (
        <MainLayout title="پنل مدیریت پیشرفته">
            <Head title="پنل مدیریت | لیان دید" />
            <div className="max-w-7xl mx-auto px-4 py-8">
                {/* هدر پنل ادمین */}
                <div className="bg-[#1B365D] text-white p-6 rounded-2xl mb-8 flex flex-col md:flex-row justify-between items-center shadow-lg gap-4">
                    <div>
                        <span className="bg-[#C9A96E] text-gray-900 font-bold px-3 py-1 rounded-md text-xs uppercase tracking-wider">سیستم مدیریت محتوا (CMS)</span>
                        <h1 className="text-2xl lg:text-3xl font-extrabold mt-2">داشبورد مدیریت خبرگزاری لیان دید</h1>
                        <p className="text-sm text-gray-300 mt-1">مدیریت متمرکز اخبار، انتشار محتوای برخط و نظارت بر دسته‌بندی‌ها</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link href="/" className="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all">
                            مشاهده سایت اصلی
                        </Link>
                    </div>
                </div>

                {/* کارت‌های آماری */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-500 font-bold uppercase tracking-wider">کل اخبار منتشر شده</p>
                            <h3 className="text-2xl font-black text-[#1B365D] mt-1">{stats.totalArticles || 0}</h3>
                        </div>
                        <div className="w-12 h-12 bg-blue-50 text-[#1B365D] rounded-xl flex items-center justify-center font-bold text-lg">📄</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-500 font-bold uppercase tracking-wider">مجموع بازدیدها</p>
                            <h3 className="text-2xl font-black text-[#1B365D] mt-1">{stats.totalViews || 0}</h3>
                        </div>
                        <div className="w-12 h-12 bg-amber-50 text-[#C9A96E] rounded-xl flex items-center justify-center font-bold text-lg">👁️</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-500 font-bold uppercase tracking-wider">نظرات کاربران</p>
                            <h3 className="text-2xl font-black text-[#1B365D] mt-1">{stats.totalComments || 0}</h3>
                        </div>
                        <div className="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center font-bold text-lg">💬</div>
                    </div>
                    <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p className="text-xs text-gray-500 font-bold uppercase tracking-wider">کاربران سیستم</p>
                            <h3 className="text-2xl font-black text-[#1B365D] mt-1">{stats.totalUsers || 1}</h3>
                        </div>
                        <div className="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center font-bold text-lg">👤</div>
                    </div>
                </div>

                {/* فرم ثبت خبر جدید */}
                <div className="bg-white border border-gray-200 rounded-2xl p-6 lg:p-8 mb-8 shadow-sm">
                    <h2 className="text-lg font-bold text-[#1B365D] mb-6 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span>✍️</span> ایجاد و انتشار خبر جدید
                    </h2>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-2">عنوان خبر</label>
                            <input 
                                type="text" 
                                value={title} 
                                onChange={e => setTitle(e.target.value)} 
                                required
                                placeholder="عنوان جامع و خبری را وارد کنید..."
                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none transition-all text-sm"
                            />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-bold text-gray-700 mb-2">دسته‌بندی خبر</label>
                                <select 
                                    value={categoryId} 
                                    onChange={e => setCategoryId(e.target.value)}
                                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none transition-all text-sm"
                                >
                                    {categories.map(cat => (
                                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex items-center gap-6 pt-6">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked={isBreaking} onChange={e => setIsBreaking(e.target.checked)} className="w-4 h-4 text-[#1B365D] rounded border-gray-300" />
                                    <span className="text-sm font-medium text-gray-700">خبر فوری (Breaking)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked={isFeatured} onChange={e => setIsFeatured(e.target.checked)} className="w-4 h-4 text-[#1B365D] rounded border-gray-300" />
                                    <span className="text-sm font-medium text-gray-700">خبر ویژه (Featured)</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-2">خلاصه (چکیده خبر)</label>
                            <textarea 
                                value={excerpt} 
                                onChange={e => setExcerpt(e.target.value)} 
                                rows="2"
                                placeholder="چکیده کوتاهی که در کارت‌های صفحه اصلی نمایش داده می‌شود..."
                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none transition-all text-sm"
                            ></textarea>
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-2">متن کامل خبر</label>
                            <textarea 
                                value={content} 
                                onChange={e => setContent(e.target.value)} 
                                rows="6"
                                placeholder="متن کامل گزارش یا خبر..."
                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] outline-none transition-all text-sm"
                            ></textarea>
                        </div>

                        <button 
                            type="submit" 
                            className="bg-[#1B365D] hover:bg-[#2a4a7a] text-white font-bold px-8 py-3.5 rounded-xl transition-all shadow-md shadow-[#1B365D]/20 text-sm"
                        >
                            انتشار نهایی در سایت
                        </button>
                    </form>
                </div>

                {/* جدول مدیریت اخبار */}
                <div className="bg-white border border-gray-200 rounded-2xl p-6 lg:p-8 shadow-sm">
                    <h2 className="text-lg font-bold text-[#1B365D] mb-6 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span>📋</span> لیست اخبار و مدیریت محتوا
                    </h2>
                    <div className="overflow-x-auto">
                        <table className="w-full text-right text-sm">
                            <thead className="bg-gray-50 text-gray-600 border-b">
                                <tr>
                                    <th className="p-4 font-bold">عنوان خبر</th>
                                    <th className="p-4 font-bold">دسته‌بندی</th>
                                    <th className="p-4 font-bold">وضعیت انتشار</th>
                                    <th className="p-4 font-bold">بازدید</th>
                                    <th className="p-4 font-bold text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {articles.map(article => (
                                    <tr key={article.id} className="hover:bg-gray-50/80 transition-colors">
                                        <td className="p-4 font-bold text-gray-900 max-w-xs truncate">{article.title}</td>
                                        <td className="p-4 text-gray-600">{article.categoryName}</td>
                                        <td className="p-4"><span className="bg-emerald-50 text-emerald-700 text-xs px-3 py-1 rounded-full font-bold">منتشر شده</span></td>
                                        <td className="p-4 text-gray-600 font-mono">{article.viewCount || 0}</td>
                                        <td className="p-4 flex items-center justify-center gap-3">
                                            <Link href={`/news/${article.slug}`} target="_blank" className="text-blue-600 hover:text-blue-800 font-bold">مشاهده</Link>
                                            <button onClick={() => handleDelete(article.id)} className="text-red-600 hover:text-red-800 font-bold">حذف</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}