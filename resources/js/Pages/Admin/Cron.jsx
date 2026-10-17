import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const INPUT = 'w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D] font-mono text-xs';
const BTN = 'px-5 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold hover:bg-[#2a4a7a] transition-colors disabled:opacity-50';

export default function AdminCron({ token = '', health = null, lastRun = null }) {
    const [busy, setBusy] = useState('');
    const [result, setResult] = useState(null);

    const base = window.location.origin;
    const urls = {
        rss: `${base}/_cron/rss?token=${encodeURIComponent(token)}`,
        backup: `${base}/_cron/backup?token=${encodeURIComponent(token)}`,
        health: `${base}/_cron/health?token=${encodeURIComponent(token)}`,
    };

    const run = async (key) => {
        setBusy(key);
        setResult(null);
        try {
            const r = await fetch(urls[key], { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            setResult({ key, ok: r.ok, body: await r.json().catch(() => ({})) });
        } catch (e) {
            setResult({ key, ok: false, body: { message: String(e) } });
        } finally {
            setBusy('');
        }
    };

    const copy = (text) => navigator.clipboard?.writeText(text);

    const rows = health?.checks ? Object.entries(health.checks) : [];

    return (
        <AdminLayout title="کرون و سلامت سرور">
            <Head title="کرون | پنل لیان دید" />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-[#1B365D]">کرون و سلامت سرور</h1>
                <p className="text-gray-500 text-sm mt-1">
                    هاست اشتراکی فقط «فراخوانی یک URL» را پشتیبانی می‌کند؛ این آدرس‌ها را در بخش cron هاست ثبت کنید.
                </p>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6">
                <h2 className="font-black text-[#1B365D] mb-3">آدرس‌های کرون</h2>
                <p className="text-xs text-gray-500 mb-4 leading-6">
                    توکن در همهٔ آدرس‌ها یکی است. آن را در پنل هاست در بخش «Cron Job» به‌صورت فراخوانی وب ثبت کنید.
                </p>

                <div className="space-y-4">
                    {[
                        ['rss', 'دریافت RSS و پاکسازی خبرهای قدیمی', 'هر ۳۰ دقیقه'],
                        ['backup', 'پشتیبان‌گیری از دیتابیس', 'هر روز'],
                        ['health', 'بررسی سلامت سرور', 'هر ۶ ساعت'],
                    ].map(([key, label, when]) => (
                        <div key={key} className="border border-gray-100 rounded-xl p-4">
                            <div className="flex items-center justify-between mb-2">
                                <div>
                                    <p className="font-bold text-sm text-[#1B365D]">{label}</p>
                                    <p className="text-xs text-gray-400">پیشنهاد: {when}</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => run(key)}
                                    disabled={!!busy}
                                    className={BTN}
                                >
                                    {busy === key ? 'در حال اجرا...' : 'اجرا'}
                                </button>
                            </div>
                            <div className="flex gap-2">
                                <input readOnly className={INPUT} value={urls[key]} onFocus={(e) => e.target.select()} />
                                <button
                                    type="button"
                                    onClick={() => copy(urls[key])}
                                    className="px-4 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold hover:bg-gray-200"
                                >
                                    کپی
                                </button>
                            </div>
                            {result?.key === key && (
                                <pre className="mt-3 p-3 rounded-lg bg-gray-50 text-xs leading-6 overflow-x-auto text-gray-700">
                                    {JSON.stringify(result.body, null, 2)}
                                </pre>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-5">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="font-black text-[#1B365D]">سلامت سرور</h2>
                    <button type="button" onClick={() => run('health')} disabled={!!busy} className={BTN}>
                        {busy === 'health' ? 'در حال بررسی...' : 'بررسی دوباره'}
                    </button>
                </div>

                {health === null ? (
                    <p className="text-sm text-gray-400">هنوز بررسی نشده است.</p>
                ) : (
                    <>
                        <div className={`mb-4 px-4 py-3 rounded-xl text-sm font-bold ${
                            health.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'
                        }`}>
                            {health.ok
                                ? 'سرور آمادهٔ انتقال است.'
                                : `${health.failed?.length ?? 0} مورد باید برطرف شود: ${(health.failed || []).join(', ')}`}
                        </div>

                        <div className="space-y-2">
                            {rows.map(([name, c]) => (
                                <div key={name} className="flex items-center justify-between px-4 py-2.5 rounded-lg bg-gray-50">
                                    <span className="text-sm font-bold text-gray-700">{name}</span>
                                    <span className="flex items-center gap-3">
                                        <span className="text-xs text-gray-400">{c.got}</span>
                                        <span className={`text-xs font-bold ${c.ok ? 'text-emerald-600' : 'text-red-600'}`}>
                                            {c.ok ? '✓' : '✗'}
                                        </span>
                                    </span>
                                </div>
                            ))}
                        </div>

                        <p className="text-xs text-gray-400 mt-4">آخرین بررسی: {health.time}</p>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}