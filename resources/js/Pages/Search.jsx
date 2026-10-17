import React, { useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

export default function Search({ articles = [], categories = [], q = '' }) {
    const { url } = usePage();
    const [term, setTerm] = useState(q);

    useEffect(() => {
        const params = new URLSearchParams(url.split('?')[1] || '');
        setTerm(params.get('q') || '');
    }, [url]);

    const submit = (e) => {
        e.preventDefault();
        router.get('/search', { q: term }, { preserveState: true });
    };

    return (
        <MainLayout title="جستجو">
            <Head title="جستجو | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">جستجو در اخبار</h1>
                        <form onSubmit={submit} className="mt-4 flex gap-2">
                            <input
                                value={term}
                                onChange={(e) => setTerm(e.target.value)}
                                placeholder="عبارت مورد نظر را بنویسید..."
                                className="flex-1 px-4 py-3 rounded-xl bg-white/95 text-sm text-gray-800 outline-none"
                            />
                            <button
                                type="submit"
                                className="px-6 py-3 rounded-xl bg-[#C9A96E] text-[#1B365D] text-sm font-black hover:bg-[#d8bd88] transition-colors"
                            >
                                جستجو
                            </button>
                        </form>
                    </div>

                    {q ? (
                        <>
                            <p className="text-sm text-gray-500 mb-4">
                                {articles.length} نتیجه برای «{q}»
                            </p>
                            {articles.length === 0 ? (
                                <p className="text-center text-gray-400 py-16">نتیجه‌ای یافت نشد.</p>
                            ) : (
                                <div className="space-y-3">
                                    {articles.map((a) => (
                                        <Link
                                            key={a.id}
                                            href={`/news/${a.slug}`}
                                            className="block bg-white rounded-xl border border-gray-100 hover:border-[#C9A96E]/50 hover:shadow-md transition-all p-5"
                                        >
                                            <div className="flex items-center gap-2 mb-2">
                                                <span className="text-[10px] font-bold text-white bg-[#1B365D] px-2 py-0.5 rounded">
                                                    {a.category?.name || 'خبر'}
                                                </span>
                                                {a.publishedAt && (
                                                    <span className="text-[11px] text-gray-400">
                                                        {new Date(
                                                            a.publishedAt
                                                        ).toLocaleDateString('fa-IR')}
                                                    </span>
                                                )}
                                            </div>
                                            <h2 className="font-bold text-[#1B365D] leading-7">{a.title}</h2>
                                            {a.excerpt && (
                                                <p className="text-sm text-gray-500 mt-1.5 leading-6 line-clamp-2">
                                                    {a.excerpt}
                                                </p>
                                            )}
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {categories.map((c) => (
                                <Link
                                    key={c.id}
                                    href={`/category/${c.slug}`}
                                    className="block bg-white rounded-xl border border-gray-100 hover:border-[#C9A96E]/50 hover:shadow-md transition-all p-5 text-center font-bold text-[#1B365D]"
                                >
                                    {c.name}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </MainLayout>
    );
}