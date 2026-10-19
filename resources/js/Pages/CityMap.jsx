import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import BushehrMap from '../Components/news/BushehrMap';

export default function CityMap({ news = [] }) {
    return (
        <MainLayout title="اخبار استان بوشهر">
            <Head title="اخبار استان بوشهر | لیان دید" />

            <main className="min-h-screen">
                <section className="relative bg-gradient-to-l from-[#1B365D] to-[#2E5090] overflow-hidden">
                    <div className="absolute inset-0">
                        <div className="absolute top-0 left-1/4 w-64 h-64 bg-[#C9A96E]/8 rounded-full blur-3xl" />
                        <div className="absolute bottom-0 right-0 w-48 h-48 bg-white/5 rounded-full blur-2xl" />
                    </div>

<div className="site-container relative z-10 py-3 md:py-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 md:w-14 md:h-14 bg-[#C9A96E]/15 backdrop-blur-sm rounded-xl flex items-center justify-center border border-[#C9A96E]/20">
                                    <span className="text-2xl md:text-3xl">🏙️</span>
                                </div>
                                <div>
                                    <h1 className="text-xl md:text-2xl font-black text-white">
                                        اخبار استان <span className="text-[#C9A96E]">بوشهر</span>
                                    </h1>
                                    <p className="text-white/50 text-xs mt-0.5">
                                        آخرین اخبار ۳۰ روز اخیر استان بوشهر
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <span className="inline-flex items-center gap-2 bg-white/10 text-white text-xs px-3 py-1.5 rounded-full border border-white/10">
                                    <span className="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse" />
                                    {news.length} خبر
                                </span>
                                <nav className="hidden md:flex items-center gap-1.5 text-xs text-white/40">
                                    <Link href="/" className="hover:text-white transition-colors">خانه</Link>
                                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <Link href="/category/bushahr" className="hover:text-white transition-colors">بوشهر</Link>
                                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <span className="text-[#C9A96E] font-bold">شهرها</span>
                                </nav>
                            </div>
                        </div>
                    </div>
                </section>

                <div className="site-container py-3">
                    <BushehrMap news={news} />
                </div>
            </main>

            <BackToHome />
        </MainLayout>
    );
}

function BackToHome() {
    return (
        <Link
            href="/"
            className="fixed bottom-6 left-6 z-50 flex items-center gap-2 px-5 py-3 bg-[#1B365D] text-white rounded-full shadow-lg shadow-[#1B365D]/30 hover:bg-[#2E5090] hover:shadow-xl transition-all font-bold text-sm"
        >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            صفحه اصلی
        </Link>
    );
}