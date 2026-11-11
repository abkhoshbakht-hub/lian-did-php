import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminBackup({ stats = {}, telegram = {}, lastBackup = null, weekly = {} }) {
    const { flash } = usePage().props;
    const [busy, setBusy] = useState(false);
    const [token, setToken] = useState('');
    const [chat, setChat] = useState('');
    const [askDest, setAskDest] = useState(false);

    const w = { enabled: false, dest: 'host', lastAt: null, result: null, hostFiles: [], ...weekly };

    // روشن‌کردن = سوال «کجا ذخیره شود؟»؛ خاموش‌کردن = بلافاصله
    const toggleWeekly = () => {
        if (w.enabled) {
            router.post('/admin/backup/weekly', { on: 0, dest: w.dest === 'telegram' ? 'telegram' : 'host' }, { preserveScroll: true });
            return;
        }
        setAskDest(true);
    };

    const chooseWeeklyDest = (dest) => {
        setAskDest(false);
        router.post('/admin/backup/weekly', { on: 1, dest }, { preserveScroll: true });
    };

    const changeWeeklyDest = (dest) => {
        if (dest === w.dest || !w.enabled) return;
        router.post('/admin/backup/weekly', { on: 1, dest }, { preserveScroll: true });
    };

    // The dump is streamed by the server straight into the browser download,
    // so nothing is ever written to the hosting disk.
    const download = async () => {
        setBusy(true);
        const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]) || '');
        try {
            const res = await fetch('/admin/backup/download', {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': xsrf, 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error();

            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const stamp = new Date().toISOString().slice(0, 19).replace(/[-:T]/g, '_');
            a.href = url;
            a.download = `liandid_php_${stamp}.sql`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        } catch {
            alert('دریافت فایل پشتیبان ناموفق بود.');
        } finally {
            setBusy(false);
        }
    };

    const sendTelegram = async () => {
        if (!confirm('ارسال کل پایگاه داده به تلگرام انجام شود؟')) return;
        setBusy(true);
        const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]) || '');
        try {
            const res = await fetch('/admin/backup/telegram', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-XSRF-TOKEN': xsrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json().catch(() => ({}));
            const f = data.props?.flash;
            if (f?.error) alert(f.error);
            else if (f?.success) alert(f.success);
        } catch {
            alert('ارسال ناموفق بود.');
        } finally {
            setBusy(false);
        }
    };

    const saveTg = async () => {
        setBusy(true);
        try {
            const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]) || '');
            const res = await fetch('/admin/backup/telegram/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-XSRF-TOKEN': xsrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ token, chat }),
            });
            const data = await res.json().catch(() => ({}));
            const msg = data.props?.flash?.success || data.props?.flash?.error;
            if (msg) {
                alert(msg);
            } else if (!res.ok) {
                alert('ذخیره تنظیمات ناموفق بود.');
            }
        } finally {
            setBusy(false);
        }
    };

    const box = 'w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]';

    return (
        <AdminLayout title="پشتیبان‌گیری">
            <Head title="پشتیبان‌گیری | پنل لیان دید" />

            {flash && (
                <div className={`mb-5 px-5 py-3 rounded-xl text-sm font-bold border ${typeof flash === 'object' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'}`}>
                    {typeof flash === 'object' ? flash.error : flash.success}
                </div>
            )}

            <div className="max-w-3xl space-y-5">
                <div className="bg-white rounded-2xl border border-gray-200 p-6">
                    <h2 className="text-base font-black text-[#1B365D] mb-4">وضعیت پایگاه داده</h2>
                    <div className="grid grid-cols-3 gap-4">
                        {[['جدول', stats.tables], ['رکورد', stats.rows], ['حجم', stats.size]].map(([l, v]) => (
                            <div key={l} className="bg-gray-50 rounded-xl p-4 text-center">
                                <p className="text-xs text-gray-400 font-bold">{l}</p>
                                <p className="text-xl font-black text-[#1B365D] mt-1">{v}</p>
                            </div>
                        ))}
                    </div>
                    {lastBackup && (
                        <p className="text-xs text-gray-400 mt-4">آخرین پشتیبان‌گیری: {lastBackup}</p>
                    )}
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6">
                    <div className="flex items-center justify-between gap-3 mb-3">
                        <h2 className="text-base font-black text-[#1B365D]">بکاپ هفتگی خودکار</h2>
                        <button onClick={toggleWeekly} type="button"
                            className={`px-6 py-2 rounded-xl text-sm font-bold transition-colors ${w.enabled ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`}>
                            {w.enabled ? '✓ فعال — برای خاموش‌کردن کلیک کنید' : 'خاموش — برای روشن‌کردن کلیک کنید'}
                        </button>
                    </div>
                    <p className="text-sm text-gray-600 leading-7 mb-4">
                        هر هفته یک نسخهٔ کامل از دیتابیس به‌صورت خودکار گرفته می‌شود — از طریق
                        <b> همان کرون موجود سایت</b>، بدون نیاز به ثبت کرون جدید.
                    </p>

                    <div className="flex flex-wrap items-center gap-3 mb-3">
                        <span className="text-xs font-bold text-gray-600">مقصد ذخیره:</span>
                        <div className="flex rounded-full bg-gray-100 p-1">
                            <button onClick={() => changeWeeklyDest('host')} type="button"
                                className={`px-4 py-1.5 rounded-full text-[11px] font-black transition-colors ${w.dest !== 'telegram' ? 'bg-[#1B365D] text-white shadow' : 'text-gray-500 hover:text-gray-700'}`}>
                                💾 روی همین هاست
                            </button>
                            <button onClick={() => changeWeeklyDest('telegram')} type="button"
                                className={`px-4 py-1.5 rounded-full text-[11px] font-black transition-colors ${w.dest === 'telegram' ? 'bg-[#1B365D] text-white shadow' : 'text-gray-500 hover:text-gray-700'}`}>
                                ✈️ تلگرام
                            </button>
                        </div>
                    </div>

                    {w.enabled && w.lastAt && (
                        <p className="text-xs text-gray-500">
                            آخرین بکاپ هفتگی: <b>{w.lastAt}</b>
                            {w.result?.target === 'telegram' && ' — ارسال به تلگرام ✓'}
                            {w.result?.target === 'host' && w.result?.file && <> — فایل <span dir="ltr" className="font-mono">{w.result.file}</span></>}
                        </p>
                    )}

                    {(w.hostFiles || []).length > 0 && (
                        <div className="mt-3 space-y-1.5">
                            <p className="text-xs font-bold text-gray-500">نسخه‌های هفتگی روی هاست (۴ نسخهٔ آخر):</p>
                            {w.hostFiles.map((f) => (
                                <div key={f.name} className="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2 text-xs">
                                    <span dir="ltr" className="font-mono text-gray-700">{f.name}</span>
                                    <span className="flex items-center gap-3">
                                        <span className="text-gray-400">{f.size}</span>
                                        <a href={`/admin/backup/weekly/file?f=${encodeURIComponent(f.name)}`}
                                            className="font-bold text-[#1B365D] hover:underline">دانلود</a>
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6">
                    <h2 className="text-base font-black text-[#1B365D]">دانلود روی کامپیوتر خودتان</h2>
                    <p className="text-sm text-gray-600 mt-2 mb-4 leading-7">
                        فایل پشتیبان <b>مستقیماً در مرورگر شما ساخته و دانلود می‌شود</b> و
                        <b> هیچ فایلی روی هاست ذخیره نمی‌شود</b>؛ بنابراین فضای هاست پر نمی‌شود و
                        نسخه پشتیبان هم جایی امن (خارج از هاست) قرار می‌گیرد.
                    </p>
                    <button onClick={download} disabled={busy}
                        className="px-8 py-3 rounded-xl bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">
                        {busy ? 'در حال آماده‌سازی…' : 'دانلود پشتیبان'}
                    </button>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6">
                    <h2 className="text-base font-black text-[#1B365D]">ارسال به تلگرام</h2>
                    <p className="text-sm text-gray-600 mt-2 mb-4 leading-7">
                        برای پشتیبان‌گیری خودکار، فایل به سرورهای تلگرام ارسال می‌شود —
                        یعنی یک نسخه همیشه <b>بیرون از هاست</b> نگه داشته می‌شود.
                    </p>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-1.5">توکن ربات {telegram.hasToken && <span className="text-emerald-600">(ذخیره شده)</span>}</label>
                            <input value={token} onChange={(e) => setToken(e.target.value)} dir="ltr"
                                placeholder="1234567890:ABCdef..." className={box} />
                        </div>
                        <div>
                            <label className="block text-xs font-bold text-gray-600 mb-1.5">شناسه چت {telegram.hasChat && <span className="text-emerald-600">(ذخیره شده)</span>}</label>
                            <input value={chat} onChange={(e) => setChat(e.target.value)} dir="ltr"
                                placeholder="987654321" className={box} />
                        </div>
                    </div>

                    <div className="flex gap-3">
                        <button onClick={saveTg} className="px-5 py-2.5 rounded-xl bg-gray-200 text-gray-700 text-sm font-bold">
                            ذخیره تنظیمات
                        </button>
                        <button onClick={sendTelegram} disabled={busy || (!telegram.hasToken && !token)}
                            className="px-6 py-2.5 rounded-xl bg-[#C9A96E] text-[#1B365D] text-sm font-bold disabled:opacity-50">
                            ارسال پشتیبان به تلگرام
                        </button>
                    </div>
                </div>

                <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                    <p className="text-sm font-black text-amber-900 mb-2">⚠️ نکتهٔ امنیتی</p>
                    <p className="text-xs text-amber-800 leading-7">
                        فایل پشتیبان شامل <b>هش رمز کاربران</b> است. آن را در جای عمومی یا
                        ریپازیتوری گیت‌هاب قرار ندهید و فایل را رمزگذاری کنید.
                    </p>
                </div>
            </div>

            {/* پنجرهٔ سوال: بکاپ هفتگی کجا ذخیره شود؟ */}
            {askDest && (
                <div className="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
                    onClick={() => setAskDest(false)}>
                    <div className="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl"
                        onClick={(e) => e.stopPropagation()}>
                        <h3 className="text-base font-black text-[#1B365D] mb-2">بکاپ هفتگی کجا ذخیره شود؟</h3>
                        <p className="text-xs text-gray-600 leading-6 mb-4">
                            هر هفته یک نسخهٔ کامل از دیتابیس تهیه می‌شود. مقصد را انتخاب کنید
                            (بعداً هم از همین صفحه قابل تغییر است):
                        </p>
                        <div className="space-y-2">
                            <button onClick={() => chooseWeeklyDest('host')} type="button"
                                className="w-full text-right px-4 py-3 rounded-xl border border-gray-200 hover:border-[#C9A96E] hover:bg-[#C9A96E]/5 text-sm font-bold text-gray-800 transition-colors">
                                💾 روی همین هاست
                                <span className="block text-[11px] font-bold text-gray-500 mt-1">۴ نسخهٔ آخر نگه داشته می‌شود</span>
                            </button>
                            <button onClick={() => chooseWeeklyDest('telegram')} type="button"
                                className="w-full text-right px-4 py-3 rounded-xl border border-gray-200 hover:border-[#C9A96E] hover:bg-[#C9A96E]/5 text-sm font-bold text-gray-800 transition-colors">
                                ✈️ ارسال به تلگرام
                                <span className="block text-[11px] font-bold text-gray-500 mt-1">بیرون از هاست نگه داشته می‌شود</span>
                                {(!telegram.hasToken || !telegram.hasChat) && (
                                    <span className="block text-[11px] font-bold text-amber-600 mt-1">
                                        ⚠️ اول «توکن ربات» و «شناسه چت» را در بخش تلگرام همین صفحه ذخیره کنید
                                    </span>
                                )}
                            </button>
                        </div>
                        <button onClick={() => setAskDest(false)} type="button"
                            className="mt-4 w-full py-2 rounded-xl bg-gray-100 text-gray-500 text-xs font-bold hover:bg-gray-200">
                            انصراف
                        </button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
