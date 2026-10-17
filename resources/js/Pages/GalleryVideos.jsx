import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

export default function GalleryVideos({ videos = [] }) {
    return (
        <MainLayout title="گالری ویدیو">
            <Head title="گالری ویدیو | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="flex items-center gap-3 mb-3">
                        <Link href="/" className="text-sm text-[#1B365D] hover:text-[#C9A96E] font-bold flex items-center gap-1">
                            صفحه اصلی
                            <svg className="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div className="flex items-center gap-3 mb-3">
                        <span className="w-1 h-8 bg-[#C9A96E] rounded-full" />
                        <h1 className="text-2xl font-black text-[#1B365D]">گالری ویدیو</h1>
                    </div>

                    <div className="flex items-center gap-4 pr-5 mb-8">
                        <Link href="/gallery" className="text-sm font-bold text-[#1B365D] hover:text-[#C9A96E]">
                            گالری تصاویر
                        </Link>
                        <span className="text-sm font-bold text-[#C9A96E] border-b-2 border-[#C9A96E] pb-1">
                            ویدیوها
                        </span>
                    </div>

                    {videos.length === 0 ? (
                        <p className="text-center text-gray-400 py-16">هنوز ویدیویی بارگذاری نشده است.</p>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {videos.map((v) => (
                                <a
                                    key={v.id}
                                    href={v.videoUrl || v.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="group bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all"
                                >
                                    <div className="relative h-44 bg-gradient-to-br from-[#1e3a64] to-[#0f2440] overflow-hidden">
                                        {v.thumbnail && (
                                            <img src={v.thumbnail} alt={v.title || ''} className="w-full h-full object-cover" />
                                        )}
                                        <span className="absolute inset-0 grid place-items-center">
                                            <span className="w-14 h-14 rounded-full bg-white/25 backdrop-blur-sm border border-white/40 grid place-items-center text-white text-2xl group-hover:bg-white/40 transition-colors">
                                                ▶
                                            </span>
                                        </span>
                                    </div>
                                    <div className="p-5">
                                        <h2 className="font-black text-[#1B365D] group-hover:text-[#C9A96E] transition-colors leading-7">
                                            {v.title}
                                        </h2>
                                        {v.albumTitle && (
                                            <p className="text-xs text-gray-400 mt-1">{v.albumTitle}</p>
                                        )}
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </MainLayout>
    );
}