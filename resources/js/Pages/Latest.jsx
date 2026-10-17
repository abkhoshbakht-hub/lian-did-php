import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

export default function Latest({ articles = [] }) {
    return (
        <MainLayout title="آخرین اخبار">
            <Head title="آخرین اخبار | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">آخرین اخبار</h1>
                        <p className="text-white/70 text-sm mt-1">
                            تازه‌ترین خبرهای لیان دید به ترتیب انتشار
                        </p>
                    </div>

                    {articles.length === 0 ? (
                        <p className="text-center text-gray-400 py-16">هنوز خبری منتشر نشده است.</p>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            {articles.map((a) => (
                                <Link
                                    key={a.id}
                                    href={`/news/${a.slug}`}
                                    className="group block bg-white rounded-2xl overflow-hidden border border-gray-100 hover:border-[#C9A96E]/50 hover:shadow-xl transition-all"
                                >
                                    {a.featuredImage && (
                                        <div className="h-44 overflow-hidden bg-gray-100">
                                            <img
                                                src={a.featuredImage}
                                                alt={a.title}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                            />
                                        </div>
                                    )}
                                    <div className="p-5">
                                        <div className="flex items-center gap-2 mb-2">
                                            <span className="text-[10px] font-bold text-white bg-[#1B365D] px-2 py-0.5 rounded">
                                                {a.category?.name || 'خبر'}
                                            </span>
                                            {a.publishedAt && (
                                                <span className="text-[11px] text-gray-400">
                                                    {new Date(a.publishedAt).toLocaleDateString('fa-IR')}
                                                </span>
                                            )}
                                        </div>
                                        <h2 className="font-bold text-[#1B365D] text-base leading-7 mb-2 line-clamp-2">
                                            {a.title}
                                        </h2>
                                        {a.excerpt && (
                                            <p className="text-sm text-gray-500 leading-6 line-clamp-3">
                                                {a.excerpt}
                                            </p>
                                        )}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </MainLayout>
    );
}