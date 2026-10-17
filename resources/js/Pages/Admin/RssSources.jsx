import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const STATUS_STYLE = {
    SUCCESS: ['bg-emerald-100 text-emerald-700', 'موفق'],
    UNCHANGED: ['bg-cyan-100 text-cyan-700', 'بدون تغییر'],
    COOLDOWN: ['bg-gray-100 text-gray-500', 'در فاصله'],
    FAILED: ['bg-red-100 text-red-700', 'ناموفق'],
    RUNNING: ['bg-amber-100 text-amber-800', 'در حال اجرا'],
    PARTIAL: ['bg-amber-100 text-amber-800', 'ناقص'],
};

export default function AdminRssSources({ sources = [], pending = 0, approved = 0, lastResult = null }) {
    const { flash } = usePage().props;
    const [busy, setBusy] = useState(false);

    const post = async (url, body) => {
        const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]) || '');
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-XSRF-TOKEN': xsrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams(body),
        });
        return res;
    };

    const run = async () => {
        setBusy(true);
        try {
            const res = await post('/admin/rss/fetch', { budget: 60, sources: 6, cooldown: 60 });
            const data = await res.json().catch(() => ({}));
            const f = data.props?.flash;
            if (f?.error) alert(f.error);
            else if (f?.success) alert(f.success);
            router.reload();
        } catch {
            alert('خطا در ارتباط با سرور');
        } finally {
            setBusy(false);
        }
    };

    const prune = async () => {
        if (!confirm('خبرهای «در انتظار تأیید» که بیش از ۱۴ روز قدیمی هستند حذف شوند؟')) return;
        const res = await post('/admin/rss/prune', { days: 14 });
        const data = await res.json().catch(() => ({}));
        if (data.props?.flash?.success) alert(data.props.flash.success);
        router.reload();
    };

    // ── حذف اخبار دریافتی تا یک تاریخ ──
    const [cutDate, setCutDate] = useState('');
    const [scope, setScope] = useState('pending');
    const [preview, setPreview] = useState(null);
    const [counting, setCounting] = useState(false);

    const countBefore = async () => {
        if (!cutDate) return alert('ابتدا تاریخ را انتخاب کنید.');
        setCounting(true);
        try {
            const res = await post('/admin/rss/count', { date: cutDate, scope });
            const data = await res.json().catch(() => ({}));
            setPreview(data.count ?? 0);
        } catch {
            alert('خطا در محاسبه');
        } finally {
            setCounting(false);
        }
    };

    const deleteBefore = async () => {
        if (preview === null) return;
        const what = scope === 'pending' ? 'در انتظار تأیید' : 'منتشرشده و در انتظار تأیید';
        if (!confirm(`${preview} خبر ${what} که تاریخ آن‌ها تا ${cutDate} است برای همیشه حذف شود؟\n\nاین عمل بازگشت‌پذیر نیست.`)) return;

        const res = await post('/admin/rss/delete-before', { date: cutDate, scope });
        const data = await res.json().catch(() => ({}));
        const f = data.props?.flash;
        if (f?.error) alert(f.error);
        else if (f?.success) alert(f.success);
        setPreview(null);
        setCutDate('');
        router.reload();
    };

    const main = sources.filter((s) => !s.fallback);
    const fallbacks = sources.filter((s) => s.fallback);

    const Table = ({ rows }) => (
        <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
                <thead className="bg-gray-50 text-gray-500 text-xs">
                    <tr>{['منبع', 'دسته', 'آخرین دریافت', 'نتیجه', 'جدید', 'خطا'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {rows.map((s) => (
                        <tr key={s.url} className="hover:bg-gray-50/70">
                            <td className="p-3">
                                <p className="font-bold text-xs">{s.name}</p>
                                <a href={s.url} target="_blank" rel="noreferrer" dir="ltr" className="text-[10px] text-blue-500 hover:underline">
                                    {s.url.replace(/^https?:\/\//, '')}
                                </a>
                            </td>
                            <td className="p-3 text-gray-500 text-xs">{s.category}</td>
                            <td className="p-3 text-gray-400 text-xs whitespace-nowrap">
                                {s.lastFetch ?? 'هنوز نه'}
                                {s.hasEtag && <span className="mr-1 text-[10px] text-cyan-600" title="درخواست شرطی فعال">⚡</span>}
                            </td>
                            <td className="p-3">
                                {s.status ? (
                                    <span className={`text-[10px] px-2 py-1 rounded font-bold ${STATUS_STYLE[s.status]?.[0] || 'bg-gray-100 text-gray-600'}`}>
                                        {STATUS_STYLE[s.status]?.[1] || s.status}
                                    </span>
                                ) : <span className="text-gray-300 text-xs">—</span>}
                            </td>
                            <td className="p-3 text-gray-500">{s.newCount ?? '—'}</td>
                            <td className="p-3 text-xs text-red-400 max-w-[200px]">
                                <span className="line-clamp-1">{s.error || '—'}</span>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );

    return (
        <AdminLayout title="منابع RSS">
            <Head title="منابع RSS | پنل لیان دید" />

            {flash && (
                <div className="mb-5 px-5 py-3 rounded-xl text-sm font-bold border bg-emerald-50 text-emerald-700 border-emerald-200">
                    {flash.success || flash.error}
                </div>
            )}

            <div className="grid grid-cols-3 gap-4 mb-5">
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
                    <p className="text-xs text-amber-700 font-bold">در انتظار تأیید</p>
                    <p className="text-2xl font-black text-amber-800 mt-1">{pending}</p>
                </div>
                <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                    <p className="text-xs text-emerald-700 font-bold">منتشر شده</p>
                    <p className="text-2xl font-black text-emerald-800 mt-1">{approved}</p>
                </div>
                <div className="bg-white border border-gray-200 rounded-xl p-4 text-center">
                    <p className="text-xs text-gray-400 font-bold">تعداد منابع</p>
                    <p className="text-2xl font-black text-[#1B365D] mt-1">{main.length}</p>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6 flex flex-wrap items-center gap-3">
                <div className="flex-1 min-w-[240px]">
                    <p className="text-sm font-black text-[#1B365D]">دریافت فوری</p>
                    <p className="text-xs text-gray-500 mt-1 leading-6">
                        هر بار فقط ۶ منبع و حداکثر ۶۰ ثانیه — تا به هاست فشار نیاید.
                        هر منبع تا یک ساعت دوباره بررسی نمی‌شود و اگر تغییری نکرده باشد تقریباً چیزی دانلود نمی‌شود.
                    </p>
                </div>
                <button onClick={run} disabled={busy}
                    className="px-6 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">
                    {busy ? 'در حال دریافت…' : 'شروع دریافت'}
                </button>
                <button onClick={prune}
                    className="px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-700 text-sm font-bold">
                    پاک‌سازی قدیمی‌ها
                </button>
            </div>

            <div className="bg-red-50 border border-red-200 rounded-2xl p-5 mb-6">
                <h3 className="text-sm font-black text-red-900 mb-1">حذف اخبار دریافتی از آر اس اس تا تاریخ</h3>
                <p className="text-xs text-red-700 mb-4 leading-6">
                    تاریخ را انتخاب کنید تا تمام خبرهای دریافت‌شده از RSS تا آن روز حذف شوند.
                    ابتدا تعداد را می‌بینید و بعد از تأیید شما حذف می‌شود.
                </p>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label className="block text-xs font-bold text-red-800 mb-1.5">تا تاریخ</label>
                        <input type="date" value={cutDate} onChange={(e) => { setCutDate(e.target.value); setPreview(null); }}
                            className="w-full px-4 py-2.5 bg-white border border-red-200 rounded-xl text-sm outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-bold text-red-800 mb-1.5">کدام خبرها</label>
                        <select value={scope} onChange={(e) => { setScope(e.target.value); setPreview(null); }}
                            className="w-full px-4 py-2.5 bg-white border border-red-200 rounded-xl text-sm outline-none">
                            <option value="pending">فقط در انتظار تأیید</option>
                            <option value="all">همه (شامل منتشرشده‌ها)</option>
                        </select>
                    </div>
                    <button onClick={countBefore} disabled={counting}
                        className="px-5 py-2.5 rounded-xl bg-white border border-red-300 text-red-800 text-sm font-bold disabled:opacity-50">
                        {counting ? 'در حال محاسبه…' : 'شمارش'}
                    </button>
                    <button onClick={deleteBefore} disabled={!preview}
                        className="px-5 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold disabled:opacity-40">
                        {preview === null ? 'ابتدا تاریخ و شمارش' : `حذف ${preview} خبر`}
                    </button>
                </div>

                {preview !== null && (
                    <p className="mt-3 text-xs font-bold text-red-800">
                        {preview === 0
                            ? 'هیچ خبری با این شرایط پیدا نشد.'
                            : `${preview} خبر برای حذف آماده است — برای انجام، دکمهٔ قرمز را بزنید.`}
                    </p>
                )}
            </div>

            {lastResult?.summary && (
                <div className="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6">
                    <p className="text-xs text-gray-500 font-bold mb-1">آخرین اجرا — {lastResult.at}</p>
                    <p className="text-sm text-gray-700">{lastResult.summary}</p>
                </div>
            )}

            <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-6">
                <div className="px-5 py-3 border-b border-gray-100 font-black text-sm text-[#1B365D]">
                    منابع اصلی ({main.length})
                </div>
                <Table rows={main} />
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div className="px-5 py-3 border-b border-gray-100 font-black text-sm text-gray-500">
                    آدرس‌های جایگزین ({fallbacks.length}) — فقط وقتی منبع اصلی جواب ندهد
                </div>
                <Table rows={fallbacks} />
            </div>
        </AdminLayout>
    );
}
