import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';

export default function Category({ categoryName, articles = [], categories = [] }) {
    return (
        <MainLayout title={categoryName}>
            <div className="max-w-7xl mx-auto px-4 py-6">
                {/* هدر دسته‌بندی */}
                <div className="bg-[#1B365D] text-white p-6 rounded-xl mb-8 shadow-md">
                    <h1 className="text-2xl font-bold">اخبار دسته: {categoryName}</h1>
                    <p className="text-sm text-gray-300 mt-2">آخرین اخبار و گزارش‌های مربوط به {categoryName}</p>
                </div>

                {/* لیست اخبار */}
                {articles.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {articles.map((article) => (
                            <div key={article.id} className="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col">
                                <img src={article.featuredImage} alt={article.title} className="w-full h-48 object-cover" />
                                <div className="p-4 flex flex-col flex-grow">
                                    <h2 className="font-bold text-gray-900 text-base mb-2 line-clamp-2">{article.title}</h2>
                                    <p className="text-gray-600 text-sm mb-4 line-clamp-3">{article.excerpt}</p>
                                    <div className="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                                        <span className="text-xs text-gray-400">{article.publishedAt ? new Date(article.publishedAt).toLocaleDateString('fa-IR') : ''}</span>
                                        <Link href={`/news/${article.slug}`} className="text-sm font-bold text-[#1B365D] hover:text-[#C9A96E]">
                                            مشاهده خبر &larr;
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-16 bg-gray-50 rounded-xl border border-gray-200">
                        <p className="text-gray-500 font-medium">هیچ خبری در این دسته‌بندی موجود نیست.</p>
                    </div>
                )}
            </div>
        </MainLayout>
    );
}