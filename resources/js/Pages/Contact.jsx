import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

const INPUT =
    'w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D] transition-all';

export default function Contact({ settings = {} }) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });

    const socials = [
        ['اینستاگرام', settings.instagram],
        ['تلگرام', settings.telegram],
    ].filter(([, v]) => v);

    return (
        <MainLayout title="تماس با ما">
            <Head title="تماس با ما | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">تماس با ما</h1>
                        <p className="text-white/70 text-sm mt-1">
                            پیام، نقد و پیشنهاد خود را با ما در میان بگذارید
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                            {recentlySuccessful && (
                                <div className="mb-4 px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm font-bold">
                                    پیام شما با موفقیت ثبت شد.
                                </div>
                            )}

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-gray-600 mb-1.5">
                                        نام و نام خانوادگی
                                    </label>
                                    <input
                                        className={INPUT}
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                    {errors.name && (
                                        <p className="text-xs text-red-600 mt-1">{errors.name}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-600 mb-1.5">
                                        ایمیل
                                    </label>
                                    <input
                                        type="email"
                                        className={INPUT}
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                    {errors.email && (
                                        <p className="text-xs text-red-600 mt-1">{errors.email}</p>
                                    )}
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-bold text-gray-600 mb-1.5">
                                        شماره تماس (اختیاری)
                                    </label>
                                    <input
                                        className={INPUT}
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-bold text-gray-600 mb-1.5">
                                        موضوع
                                    </label>
                                    <input
                                        className={INPUT}
                                        value={data.subject}
                                        onChange={(e) => setData('subject', e.target.value)}
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-bold text-gray-600 mb-1.5">
                                        متن پیام
                                    </label>
                                    <textarea
                                        rows={6}
                                        className={INPUT}
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                    />
                                    {errors.message && (
                                        <p className="text-xs text-red-600 mt-1">{errors.message}</p>
                                    )}
                                </div>
                            </div>

                            <button
                                type="button"
                                disabled={processing}
                                onClick={() => post('/contact')}
                                className="mt-5 px-6 py-3 rounded-xl bg-[#1B365D] text-white text-sm font-bold hover:bg-[#2a4a7a] transition-colors disabled:opacity-50"
                            >
                                {processing ? 'در حال ارسال...' : 'ارسال پیام'}
                            </button>
                        </div>

                        <div className="space-y-4">
                            <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                                <h2 className="font-black text-[#1B365D] mb-4">راه‌های ارتباطی</h2>
                                {settings.footerAddress && (
                                    <p className="text-sm text-gray-600 mb-3">
                                        <span className="font-bold text-gray-700">نشانی: </span>
                                        {settings.footerAddress}
                                    </p>
                                )}
                                {socials.map(([label, url]) => (
                                    <a
                                        key={label}
                                        href={url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="block text-sm text-[#1B365D] hover:text-[#C9A96E] font-bold mt-2"
                                    >
                                        {label} ↗
                                    </a>
                                ))}
                            </div>

                            <div className="bg-[#1B365D] rounded-2xl p-6 text-white shadow-lg">
                                <h2 className="font-black mb-2">عضویت در خبرنامه</h2>
                                <p className="text-white/70 text-sm leading-6">
                                    تازه‌ترین اخبار را در ایمیل خود دریافت کنید.
                                </p>
                                <a
                                    href="/#newsletter"
                                    className="mt-3 inline-block px-4 py-2 rounded-lg bg-white/15 text-white text-sm font-bold hover:bg-white/25"
                                >
                                    ثبت‌نام
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}