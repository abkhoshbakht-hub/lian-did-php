import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const SLUGS = [
    { slug: 'about', title: 'درباره ما' },
    { slug: 'contact', title: 'تماس با ما' },
    { slug: 'terms', title: 'قوانین و مقررات' },
    { slug: 'privacy', title: 'حریم خصوصی' },
    { slug: 'launch', title: 'پیام مدیر مسئول' },
];

export default function AdminStaticPages({ pages = [] }) {
    const { flash } = usePage().props;
    const byslug = Object.fromEntries(pages.map((p) => [p.slug, p]));

    return (
        <AdminLayout title="ویرایش صفحات">
            <Head title="صفحات ایستا | پنل لیان دید" />

            {flash && (
                <div className="mb-5 px-5 py-3 rounded-xl text-sm font-bold border bg-emerald-50 text-emerald-700 border-emerald-200">
                    {flash.success || flash.error}
                </div>
            )}

            <div className="space-y-5">
                {SLUGS.map((s) => {
                    const p = byslug[s.slug];
                    return (
                        <details key={s.slug} className="bg-white rounded-2xl border border-gray-200">
                            <summary className="p-5 cursor-pointer font-black text-[#1B365D] flex items-center justify-between">
                                {s.title}
                                <span className={`text-[11px] px-2 py-1 rounded font-bold ${p ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
                                    {p ? 'ذخیره شده' : 'خالی'}
                                </span>
                            </summary>
                            <form onSubmit={(e) => {
                                e.preventDefault();
                                const f = new FormData(e.target);
                                const payload = { title: f.get('title'), content: f.get('content'), excerpt: f.get('excerpt') };
                                import('@inertiajs/react').then(({ router }) =>
                                    router.post(`/admin/static-pages/${s.slug}`, payload, { preserveScroll: true }));
                            }} className="p-5 border-t border-gray-100 space-y-3">
                                <input name="title" defaultValue={p?.title || ''} placeholder="عنوان صفحه" required className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm" />
                                <input name="excerpt" defaultValue={p?.excerpt || ''} placeholder="چکیده کوتاه" className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm" />
                                <textarea name="content" defaultValue={p?.content || ''} rows="8" required placeholder="متن کامل صفحه" className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm leading-7" />
                                <button className="px-6 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">ذخیره صفحه</button>
                            </form>
                        </details>
                    );
                })}
            </div>
        </AdminLayout>
    );
}
