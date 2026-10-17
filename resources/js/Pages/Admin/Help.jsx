import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminHelp({ sections = [] }) {
    const [open, setOpen] = useState(sections[0]?.key || null);

    return (
        <AdminLayout title="آموزش و راهنما">
            <Head title="راهنما | پنل لیان دید" />

            <div className="max-w-4xl">
                <div className="bg-gradient-to-l from-[#1B365D] to-[#2E5090] text-white rounded-2xl p-7 mb-6">
                    <h1 className="text-2xl font-black">آموزش و راهنمای پنل مدیریت</h1>
                    <p className="text-sm text-white/70 mt-2 leading-7">
                        هر بخش را باز کنید؛ در هر مرحله نشان داده شده که دکمه یا فیلد موردنظر کجاست.
                        این راهنما داخل خود پنل است و همیشه به‌روز می‌ماند.
                    </p>
                </div>

                <div className="space-y-3">
                    {sections.map((s) => {
                        const isOpen = open === s.key;
                        return (
                            <div key={s.key} className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                                <button onClick={() => setOpen(isOpen ? null : s.key)}
                                    className="w-full flex items-center gap-3 p-5 text-right hover:bg-gray-50 transition-colors">
                                    <span className="text-2xl">{s.icon}</span>
                                    <span className="font-black text-[#1B365D] flex-1">{s.title}</span>
                                    <span className="text-xs text-gray-400">{s.steps.length} مرحله</span>
                                    <svg className={`w-4 h-4 text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`}
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                {isOpen && (
                                    <div className="border-t border-gray-100 p-5 bg-gray-50/50">
                                        <ol className="space-y-4">
                                            {s.steps.map((step, i) => (
                                                <li key={i} className="flex gap-4">
                                                    <span className="shrink-0 w-8 h-8 rounded-full bg-[#C9A96E] text-[#1B365D] font-black text-sm flex items-center justify-center">
                                                        {i + 1}
                                                    </span>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="font-bold text-gray-900 text-sm">{step.t}</p>
                                                        <p className="text-sm text-gray-600 mt-1 leading-7">{step.d}</p>
                                                        <span className="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-lg bg-white border border-gray-200 text-[11px] text-gray-500 font-bold">
                                                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            {step.where}
                                                        </span>
                                                    </div>
                                                </li>
                                            ))}
                                        </ol>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                <div className="mt-6 bg-white rounded-2xl border border-gray-200 p-5 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p className="font-black text-[#1B365D] text-sm">تغییر رمز عبور</p>
                        <p className="text-xs text-gray-400 mt-1">برای امنیت، رمز مدیر را هر چند ماه عوض کنید.</p>
                    </div>
                    <Link href="/admin/change-password" className="px-5 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">
                        رفتن به تغییر رمز
                    </Link>
                </div>
            </div>
        </AdminLayout>
    );
}
