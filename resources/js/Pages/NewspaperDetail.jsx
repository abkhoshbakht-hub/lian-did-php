import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

export default function NewspaperDetail({ paper = null, issues = [] }) {
    if (!paper) return null;

    return (
        <MainLayout title={paper.name}>
            <Head title={`${paper.name} | لیان دید`} />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="flex items-start gap-4 bg-white rounded-2xl border border-gray-100 p-6 mb-6 shadow-sm">
                        {paper.logo ? (
                            <img src={paper.logo} alt={paper.name} className="w-20 h-20 object-contain" />
                        ) : (
                            <div className="w-20 h-20 rounded-xl bg-[#1B365D] text-white grid place-items-center text-2xl font-black">
                                {paper.name?.trim()?.charAt(0)}
                            </div>
                        )}
                        <div className="flex-1 min-w-0">
                            <h1 className="text-xl font-black text-[#1B365D]">{paper.name}</h1>
                            {paper.description && (
                                <p className="text-sm text-gray-500 mt-1 leading-6">{paper.description}</p>
                            )}
                            <div className="flex gap-2 mt-3 flex-wrap">
                                {paper.website && (
                                    <a
                                        href={paper.website}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-xs font-bold text-white bg-[#1B365D] px-3 py-1.5 rounded-lg"
                                    >
                                        سایت رسمی
                                    </a>
                                )}
                                {paper.telegram && (
                                    <a
                                        href={paper.telegram}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-xs font-bold text-white bg-[#2a4a7a] px-3 py-1.5 rounded-lg"
                                    >
                                        تلگرام
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-3 mb-4">
                        <span className="w-1 h-7 bg-gradient-to-b from-[#C9A96E] to-[#1B365D] rounded-full" />
                        <h2 className="font-black text-[#1B365D]">شماره‌های موجود</h2>
                        <span className="text-xs text-gray-400">({issues.length})</span>
                    </div>

                    {issues.length === 0 ? (
                        <p className="text-center text-gray-400 py-16">شماره‌ای برای این روزنامه ثبت نشده است.</p>
                    ) : (
                        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            {issues.map((i) => (
                                <a
                                    key={i.id}
                                    href={i.originalUrl || '#'}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="group bg-white rounded-xl overflow-hidden border border-gray-100 hover:border-[#C9A96E]/50 hover:shadow-lg transition-all"
                                >
                                    {i.image ? (
                                        <div className="h-40 overflow-hidden bg-gray-100">
                                            <img
                                                src={i.image}
                                                alt={i.title || paper.name}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                            />
                                        </div>
                                    ) : (
                                        <div className="h-40 bg-gradient-to-br from-[#1e3a64] to-[#0f2440]" />
                                    )}
                                    <div className="p-3">
                                        <p className="text-[11px] font-bold text-[#1B365D]">{i.persianDate}</p>
                                        {i.issueNumber && (
                                            <p className="text-[10px] text-gray-400 mt-0.5">شماره {i.issueNumber}</p>
                                        )}
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}

                    <div className="mt-8">
                        <Link href="/newspapers" className="text-sm font-bold text-[#1B365D] hover:text-[#C9A96E]">
                            ← بازگشت به فهرست روزنامه‌ها
                        </Link>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}