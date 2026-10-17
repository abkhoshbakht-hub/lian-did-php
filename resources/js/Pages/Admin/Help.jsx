import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import helpData from '@/data/helpData';
import { targetFor } from '@/data/helpTargets';

export default function HelpPage() {
    const [openSection, setOpenSection] = useState(helpData[0]?.title ?? null);
    const [openItem, setOpenItem] = useState(null);

    return (
        <AdminLayout title="راهنمای مدیریت">
            <Head title="راهنما | پنل لیان دید" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-[#1B365D]">راهنمای مدیریت سایت</h1>
                <p className="text-gray-500 text-sm mt-1">
                    روی هر بخش کلیک کنید تا آموزش آن نمایش داده شود
                </p>
            </div>

            <div className="space-y-3">
                {helpData.map((section) => {
                    const isOpen = openSection === section.title;

                    return (
                        <div key={section.title} className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                            <button
                                type="button"
                                onClick={() => {
                                    setOpenSection(isOpen ? null : section.title);
                                    setOpenItem(null);
                                }}
                                className="w-full flex items-center gap-3 px-5 py-4 text-right hover:bg-gray-50 transition-colors"
                            >
                                <span className="text-xl">{section.icon}</span>
                                <span className="font-bold text-[#1B365D] flex-1">{section.title}</span>
                                <span className="text-xs text-gray-400">{section.items.length} مورد</span>
                                <svg
                                    className={`w-4 h-4 text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`}
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {isOpen && (
                                <div className="border-t border-gray-100 divide-y divide-gray-50">
                                    {section.items.map((item) => {
                                        const key = `${section.title}::${item.id}`;
                                        const expanded = openItem === key;
                                        const href = targetFor(item.id);

                                        return (
                                            <div key={item.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => setOpenItem(expanded ? null : key)}
                                                    className="w-full flex items-center gap-3 px-6 py-3.5 text-right hover:bg-[#1B365D]/[0.03] transition-colors"
                                                >
                                                    <span className="text-base">{item.icon}</span>
                                                    <span className="text-sm font-bold text-gray-700 flex-1">{item.title}</span>
                                                    <svg
                                                        className={`w-3.5 h-3.5 text-gray-400 transition-transform ${expanded ? 'rotate-180' : ''}`}
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    >
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>

                                                {expanded && (
                                                    <div className="px-6 pb-4 pr-14">
                                                        <div className="bg-[#1B365D]/[0.03] rounded-xl border-r-4 border-[#C9A96E] px-5 py-4">
                                                            <p className="text-sm text-gray-700 leading-8 whitespace-pre-line">
                                                                {item.content}
                                                            </p>
                                                            <button
                                                                type="button"
                                                                onClick={() => router.get(href)}
                                                                className="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#1B365D] text-white text-xs font-bold hover:bg-[#2a4a7a] transition-colors"
                                                            >
                                                                برو به این صفحه
                                                                <svg className="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </AdminLayout>
    );
}