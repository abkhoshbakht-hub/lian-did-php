import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

const CATEGORIES = {
    national: { label: 'کشوری', color: 'bg-[#1B365D]' },
    local: { label: 'محلی', color: 'bg-[#2a4a7a]' },
    economic: { label: 'اقتصادی', color: 'bg-[#C9A96E]' },
    sports: { label: 'ورزشی', color: 'bg-[#8B5E3C]' },
};

export default function Newspapers({ papers = [], issues = [] }) {
    const grouped = papers.reduce((acc, p) => {
        (acc[p.category || 'national'] ||= []).push(p);

        return acc;
    }, {});

    return (
        <MainLayout title="روزنامه‌ها">
            <Head title="روزنامه‌ها | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">روزنامه‌ها</h1>
                        <p className="text-white/70 text-sm mt-1">
                            صفحهٔ اول روزنامه‌های کشور و استان
                        </p>
                    </div>

                    {issues.length > 0 && (
                        <section className="mb-8">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="w-1 h-7 bg-gradient-to-b from-[#C9A96E] to-[#1B365D] rounded-full" />
                                <h2 className="font-black text-[#1B365D]">تازه‌ترین شماره‌ها</h2>
                            </div>
                            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                {issues.map((i) => (
                                    <Link
                                        key={i.id}
                                        href={i.link}
                                        className="group bg-white rounded-xl overflow-hidden border border-gray-100 hover:border-[#C9A96E]/50 hover:shadow-lg transition-all"
                                    >
                                        {i.image ? (
                                            <div className="h-32 overflow-hidden bg-gray-100">
                                                <img
                                                    src={i.image}
                                                    alt={i.title || i.paperName}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                />
                                            </div>
                                        ) : (
                                            <div className="h-32 bg-gradient-to-br from-[#1e3a64] to-[#0f2440]" />
                                        )}
                                        <div className="p-3">
                                            <p className="text-[11px] font-bold text-[#1B365D] truncate">
                                                {i.paperName}
                                            </p>
                                            <p className="text-[10px] text-gray-400 mt-0.5">{i.persianDate}</p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    )}

                    {Object.keys(grouped).length === 0 ? (
                        <p className="text-center text-gray-400 py-16">روزنامه‌ای ثبت نشده است.</p>
                    ) : (
                        Object.entries(grouped).map(([key, list]) => (
                            <section key={key} className="mb-8">
                                <div className="flex items-center gap-3 mb-4">
                                    <span className="w-1 h-7 bg-gradient-to-b from-[#C9A96E] to-[#1B365D] rounded-full" />
                                    <h2 className="font-black text-[#1B365D]">
                                        {CATEGORIES[key]?.label || key}
                                    </h2>
                                    <span className="text-xs text-gray-400">({list.length})</span>
                                </div>
                                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    {list.map((p) => (
                                        <Link
                                            key={p.id}
                                            href={p.link}
                                            className="group bg-white rounded-xl border border-gray-100 hover:border-[#C9A96E]/50 hover:shadow-lg transition-all p-4 flex items-center gap-3"
                                        >
                                            {p.logo ? (
                                                <img
                                                    src={p.logo}
                                                    alt={p.name}
                                                    className="w-12 h-12 object-contain rounded-lg shrink-0"
                                                />
                                            ) : (
                                                <div className="w-12 h-12 rounded-lg bg-[#1B365D] text-white grid place-items-center font-black shrink-0">
                                                    {p.name?.trim()?.charAt(0)}
                                                </div>
                                            )}
                                            <div className="min-w-0">
                                                <p className="font-bold text-sm text-[#1B365D] truncate">{p.name}</p>
                                                {p.description && (
                                                    <p className="text-[11px] text-gray-400 truncate">
                                                        {p.description}
                                                    </p>
                                                )}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </section>
                        ))
                    )}
                </div>
            </div>
        </MainLayout>
    );
}