import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const FIELD_LABELS = {
    logo_image: ['تصویر لوگو', 'image'],
    logo_width: ['عرض لوگو (پیکسل)', 'number'],
    show_text: ['نمایش نام «لیان دید» کنار لوگو', 'checkbox'],
    license_text: ['متن پروانه', 'textarea'],
    license_number: ['شماره پروانه', 'text'],
    license_image: ['تصویر پروانه', 'image'],
    about_title: ['عنوان', 'text'],
    about_body: ['متن', 'textarea'],
    about_image: ['تصویر', 'image'],
    contact_address: ['نشانی', 'textarea'],
    contact_phone: ['تلفن', 'text'],
    contact_mobile: ['همراه', 'text'],
    contact_email: ['ایمیل', 'text'],
    contact_map: ['نشانی نقشه (لینک)', 'text'],
    search_enabled: ['نمایش جستجو در هدر', 'checkbox'],
    search_placeholder: ['متن راهنمای جعبه جستجو', 'text'],
    nav_items: ['آیتم‌های منو (هر خط یک آیتم: عنوان | لینک)', 'textarea'],
    show_breaking: ['نمایش نوار خبر فوری', 'checkbox'],
    breaking_text: ['متن نوار خبر فوری', 'text'],
    footer_about: ['معرفی کوتاه', 'textarea'],
    footer_copyright: ['متن کپی‌رایت', 'text'],
    footer_links: ['لینک‌های فوتر (هر خط یکی)', 'textarea'],
    social_instagram: ['اینستاگرام', 'text'],
    social_telegram: ['تلگرام', 'text'],
    social_twitter: ['ایکس (توییتر)', 'text'],
    social_whatsapp: ['واتس‌اپ', 'text'],
    social_email: ['ایمیل', 'text'],
};

export default function AdminSettings({ group, title, settings = {} }) {
    const { data, setData, post, processing, errors } = useForm(settings);
    const [flash, setFlash] = useState(null);

    const submit = (e) => {
        e.preventDefault();
        post(`/admin/settings/${group}`, {
            onSuccess: () => setFlash('تنظیمات ذخیره شد.'),
            onError: () => setFlash('خطا در ذخیره.'),
        });
    };

    const box = 'w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-[#C9A96E] bg-white text-sm';

    return (
        <AdminLayout title={title}>
            <Head title={`${title} | پنل لیان دید`} />

            <form onSubmit={submit} className="max-w-3xl bg-white rounded-2xl shadow-lg border border-gray-200 p-8 space-y-6">
                <div className="flex items-center justify-between pb-4 border-b border-gray-100">
                    <h1 className="text-lg font-black text-[#1B365D]">{title}</h1>
                    {flash && <span className="text-xs font-bold text-emerald-600">{flash}</span>}
                </div>

                {Object.entries(FIELD_LABELS).filter(([k]) => k in (settings || {}) || Object.keys(settings).length === 0).map(([key, [label, type]]) => (
                    <div key={key}>
                        {type === 'checkbox' ? (
                            <label className="flex items-center gap-2.5 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={!!data[key]}
                                    onChange={(e) => setData(key, e.target.checked)}
                                    className="w-4 h-4 rounded border-gray-300 text-[#1B365D]"
                                />
                                <span className="text-sm text-gray-700">{label}</span>
                            </label>
                        ) : (
                            <>
                                <label className="block text-sm font-bold text-gray-700 mb-1.5">{label}</label>
                                {type === 'textarea' ? (
                                    <textarea rows="4" value={data[key] ?? ''} onChange={(e) => setData(key, e.target.value)} className={box} />
                                ) : (
                                    <input
                                        type={type === 'number' ? 'number' : 'text'}
                                        dir={type === 'image' || type === 'number' ? 'ltr' : undefined}
                                        value={data[key] ?? ''}
                                        onChange={(e) => setData(key, e.target.value)}
                                        className={box}
                                        placeholder={type === 'image' ? '/images/logo.png' : ''}
                                    />
                                )}
                            </>
                        )}
                        {errors[key] && <p className="text-red-600 text-xs mt-1">{errors[key]}</p>}
                    </div>
                ))}

                {Object.keys(settings).length === 0 && (
                    <p className="text-sm text-gray-400">هنوز چیزی برای این بخش ذخیره نشده. فیلدها پس از اولین ذخیره نمایش داده می‌شوند.</p>
                )}

                <button type="submit" disabled={processing}
                    className="w-full px-4 py-3 bg-[#1B365D] text-white rounded-lg font-bold hover:bg-[#0f2d52] transition-colors disabled:opacity-50">
                    {processing ? 'در حال ذخیره...' : 'ذخیره تنظیمات'}
                </button>
            </form>
        </AdminLayout>
    );
}
