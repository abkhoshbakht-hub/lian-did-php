import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

const FILTERS = ['همه', 'سیاسی', 'اقتصادی', 'اجتماعی', 'فرهنگی', 'ورزشی'];

export default function Gallery({ albums = [] }) {
    const [active, setActive] = useState('همه');

    // only offer filters that actually have albums
    const categories = FILTERS.filter((f) => f === 'همه' || albums.some((a) => a.category === f));
    const list = active === 'همه' ? albums : albums.filter((a) => a.category === active);

    return (
        <MainLayout title="گالری تصاویر">
            <Head title="گالری عکس | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="flex items-center gap-3 mb-3">
                        <Link href="/" className="text-sm text-[#1B365D] hover:text-[#C9A96E] font-bold transition-colors flex items-center gap-1">
                            صفحه اصلی
                            <svg className="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div className="flex items-center gap-3 mb-3">
                        <span className="w-1 h-8 bg-[#C9A96E] rounded-full" />
                        <h1 className="text-2xl font-black text-[#1B365D]">گالری تصاویر</h1>
                    </div>

                    <div className="flex flex-wrap items-center gap-4 pr-5 mb-8">
                        {categories.map((c) => (
                            <button
                                key={c}
                                type="button"
                                onClick={() => setActive(c)}
                                className={`text-sm font-bold pb-1 border-b-2 transition-colors ${
                                    active === c
                                        ? 'border-[#C9A96E] text-[#1B365D]'
                                        : 'border-transparent text-gray-400 hover:text-[#1B365D]'
                                }`}
                            >
                                {c}
                            </button>
                        ))}
                    </div>

                    {list.length === 0 ? (
                        <p className="text-center text-gray-400 py-16">هنوز آلبومی در این دسته ثبت نشده است.</p>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {list.map((a) => (
                                <Link
                                    key={a.id}
                                    href={`/gallery/${a.slug}`}
                                    className="group bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300"
                                >
                                    <div className={`relative h-56 bg-gradient-to-br ${a.color}`}>
                                        {a.cover && (
                                            <img
                                                src={a.cover}
                                                alt={a.title}
                                                className="absolute inset-0 w-full h-full object-cover"
                                            />
                                        )}
                                        <div className="absolute top-3 right-3 left-3 flex items-center justify-between">
                                            <span className="bg-white/90 text-gray-800 text-xs px-3 py-1.5 rounded-full font-bold shadow-md">
                                                {a.category}
                                            </span>
                                            <span className="bg-black/50 text-white text-xs px-3 py-1.5 rounded-full font-bold backdrop-blur-sm">
                                                📷 {a.itemCount} تصویر
                                            </span>
                                        </div>
                                    </div>
                                    <div className="p-5">
                                        <h2 className="font-black text-[#1B365D] group-hover:text-[#C9A96E] transition-colors text-lg mb-2">
                                            {a.title}
                                        </h2>
                                        {a.date && <span className="text-xs text-gray-400">{a.date}</span>}
                                        {a.description && (
                                            <p className="text-sm text-gray-500 mt-2 leading-6 line-clamp-2">
                                                {a.description}
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