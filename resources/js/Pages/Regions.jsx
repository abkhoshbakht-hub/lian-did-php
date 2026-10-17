import React, { useMemo } from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import BushehrMap from '../Components/news/BushehrMap';
import { COUNTIES, countyForNews } from '../Components/news/bushehrMap';

export default function Regions({ news = [] }) {
    const counts = useMemo(() => {
        const map = {};
        for (const item of news) {
            const key = countyForNews(item);
            if (!key) continue;
            map[key] = (map[key] || 0) + 1;
        }
        return map;
    }, [news]);

    const rows = COUNTIES.map((c) => ({ ...c, count: counts[c.key] || 0 }));
    const total = rows.reduce((a, c) => a + c.count, 0);
    const top = [...rows].sort((a, b) => b.count - a.count).slice(0, 5);

    return (
        <MainLayout title="مناطق و نقشه بوشهر">
            <Head title="مناطق بوشهر | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">مناطق و شهرستان‌های بوشهر</h1>
                        <p className="text-white/70 text-sm mt-1">
                            روی نقشه کلیک کنید تا اخبار هر شهرستان را ببینید
                        </p>
                    </div>

                    <BushehrMap news={news} />

                    <section className="mt-8">
                        <div className="flex items-center gap-3 mb-4">
                            <span className="w-1 h-7 bg-gradient-to-b from-[#C9A96E] to-[#1B365D] rounded-full" />
                            <h2 className="font-black text-[#1B365D]">شهرستان‌ها</h2>
                            <span className="text-xs text-gray-400">
                                ({rows.length} شهرستان، {total} خبر)
                            </span>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            {rows.map((c) => (
                                <div
                                    key={c.key}
                                    className="bg-white rounded-xl border border-gray-100 p-4 text-center hover:border-[#C9A96E]/50 hover:shadow-md transition-all"
                                >
                                    <p className="font-black text-[#1B365D]">{c.name}</p>
                                    <p className="text-xs text-gray-400 mt-1">{c.count} خبر</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    {top.some((c) => c.count > 0) && (
                        <section className="mt-8">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="w-1 h-7 bg-gradient-to-b from-[#C9A96E] to-[#1B365D] rounded-full" />
                                <h2 className="font-black text-[#1B365D]">پراخبرترین شهرستان‌ها</h2>
                            </div>
                            <div className="space-y-3">
                                {top.map((c) => (
                                    <div key={c.key} className="bg-white rounded-xl border border-gray-100 p-4">
                                        <div className="flex items-center justify-between mb-2">
                                            <span className="font-bold text-[#1B365D] text-sm">{c.name}</span>
                                            <span className="text-xs text-gray-400">{c.count} خبر</span>
                                        </div>
                                        <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div
                                                className="h-full bg-gradient-to-l from-[#1B365D] to-[#C9A96E] rounded-full"
                                                style={{ width: `${total ? (c.count / top[0].count) * 100 : 0}%` }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            </div>
        </MainLayout>
    );
}