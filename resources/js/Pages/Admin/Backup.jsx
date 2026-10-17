import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminBackup({ stats = {}, telegram = {}, lastBackup = null }) {
    const { flash } = usePage().props;
    const [busy, setBusy] = useState(false);
    const [token, setToken] = useState('');
    const [chat, setChat] = useState('');

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
        </AdminLayout>
    );
}
