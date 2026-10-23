import React, { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { faDateTime } from '@/Components/admin/faFormat';

const box = 'w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]';
const label = 'block text-xs font-bold text-gray-600 mb-1.5';

function Flash() {
    const { flash } = usePage().props;
    if (!flash) return null;
    const isErr = typeof flash === 'object';
    return (
        <div className={`mb-5 px-5 py-3 rounded-xl text-sm font-bold border ${isErr ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'}`}>
            {isErr ? flash.error : flash.success}
        </div>
    );
}

function Panel({ title, children, note }) {
    return (
        <div className="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
            <div className="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                <h2 className="text-sm font-black text-[#1B365D]">{title}</h2>
                {note && <span className="text-xs text-gray-400">{note}</span>}
            </div>
            {children}
        </div>
    );
}

const PLACEMENTS = [
    ['latest', 'آخرین اخبار'], ['hero', 'هیرو'],
    ['analysis', 'تحلیل خبر'], ['featured', 'ویژه'],
];

// ───────────────────────────────────────────────────── چیدمان صفحه اصلی
export function HomepagePage({ section = 'hero', title = 'هیرو', articles = [], slots = [] }) {
    return (
        <AdminLayout title={title}>
            <Head title={`${title} صفحه اصلی | پنل لیان دید`} />
            <Flash />

            <div className="flex gap-2 mb-5">
                {[['hero', 'هیرو'], ['latest', 'آخرین اخبار'], ['analysis', 'تحلیل خبر']].map(([k, l]) => (
                    <a key={k} href={`/admin/homepage/${k}`}
                        className={`px-4 py-2 rounded-xl text-xs font-bold ${section === k ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-600'}`}>{l}</a>
                ))}
            </div>

            <Panel title={`${articles.length} خبر در بخش «${title}»`} note="محل نمایش خبر را از اینجا تغییر دهید">
                {articles.length === 0 ? (
                    <p className="text-sm text-gray-400 py-8 text-center">
                        خبری با محل نمایش «{title}» وجود ندارد. از بخش «اخبار سایت» محل نمایش خبر را تغییر دهید.
                    </p>
                ) : (
                    <div className="space-y-2">
                        {articles.map((a) => (
                            <div key={a.id} className="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50">
                                <div className="min-w-0">
                                    <p className="font-bold text-sm truncate">{a.title}</p>
                                    <p className="text-[11px] text-gray-400 mt-0.5" dir="ltr">{a.slug}</p>
                                </div>
                                <select
                                    value={a.placement}
                                    onChange={(e) => router.post(`/admin/articles/${a.id}/placement`, { placement: e.target.value })}
                                    className="shrink-0 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-bold bg-white"
                                >
                                    {PLACEMENTS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                                </select>
                            </div>
                        ))}
                    </div>
                )}
            </Panel>

            <Panel title="جایگاه‌های صفحه اصلی">
                {slots.length === 0 ? (
                    <p className="text-sm text-gray-400 py-6 text-center">جایگاهی تعریف نشده است.</p>
                ) : slots.map((s) => (
                    <SlotRow key={s.id} slot={s} />
                ))}
            </Panel>
        </AdminLayout>
    );
}

function SlotRow({ slot }) {
    return (
        <details className="border-b border-gray-100 last:border-0 py-3">
            <summary className="cursor-pointer font-bold text-sm flex items-center justify-between">
                <span>{slot.label} <span className="text-gray-400 font-normal text-xs">({slot.slotKey})</span></span>
                <span className={`text-[11px] px-2 py-1 rounded font-bold ${slot.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
                    {slot.isActive ? 'فعال' : 'غیرفعال'}
                </span>
            </summary>
            <form onSubmit={(e) => {
                e.preventDefault();
                const f = new FormData(e.target);
                router.post(`/admin/slots/${slot.slotKey}`, {
                    label: f.get('label'), customTitle: f.get('customTitle'),
                    customContent: f.get('customContent'), customLink: f.get('customLink'),
                    isActive: f.get('isActive') === 'on',
                });
            }} className="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                <input name="label" defaultValue={slot.label} placeholder="برچسب" className={box} />
                <input name="customTitle" defaultValue={slot.customTitle || ''} placeholder="عنوان سفارشی" className={box} />
                <input name="customLink" defaultValue={slot.customLink || ''} placeholder="لینک" dir="ltr" className={box} />
                <textarea name="customContent" defaultValue={slot.customContent || ''} rows="2" placeholder="متن" className={box} />
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="isActive" defaultChecked={!!slot.isActive} className="w-4 h-4 rounded" /> فعال باشد
                </label>
                <button className="px-4 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold md:col-span-2">ذخیره جایگاه</button>
            </form>
        </details>
    );
}

// ───────────────────────────────────────────────────── اخبار لحظه‌ای
const NEWS_STATUS = {
    APPROVED: ['تأیید شده', 'bg-emerald-100 text-emerald-700'],
    PENDING: ['در انتظار', 'bg-amber-100 text-amber-800'],
    REJECTED: ['رد شده', 'bg-red-100 text-red-700'],
};

export function ExternalNewsPage({ news = [], sources = [], counts = {}, filters = {} }) {
    const [sel, setSel] = useState([]);
    const [busy, setBusy] = useState(false);

    const bulk = (status) => {
        if (!sel.length) return;
        router.post('/admin/external-news/bulk', { ids: sel, status }, { preserveScroll: true });
        setSel([]);
    };

    const fetchNow = async () => {
        setBusy(true);
        try {
            const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]) || '');
            const body = new URLSearchParams({ budget: 60, sources: 6, cooldown: 60 });
            const res = await fetch('/admin/rss/fetch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-XSRF-TOKEN': xsrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.props?.flash?.success) {
                alert(data.props.flash.success);
            } else if (data.props?.flash?.error) {
                alert(data.props.flash.error);
            } else {
                alert('دریافت انجام شد.');
            }
            router.reload({ only: ['news', 'counts'] });
        } catch {
            alert('خطا در ارتباط با سرور');
        } finally {
            setBusy(false);
        }
    };

    return (
        <AdminLayout title="اخبار لحظه‌ای">
            <Head title="اخبار لحظه‌ای | پنل لیان دید" />
            <Flash />

            <div className="bg-[#C9A96E]/15 border border-[#C9A96E]/30 rounded-2xl p-4 mb-5 flex flex-wrap items-center gap-3">
                <div className="flex-1 min-w-[220px]">
                    <p className="text-sm font-black text-[#1B365D]">دریافت از خبرگزاری‌ها</p>
                    <p className="text-xs text-gray-600 mt-1 leading-6">
                        خبرهای جدید <b>منتظر تأیید شما</b> می‌مانند و تا زمانی که تأیید نکنید در سایت منتشر نمی‌شوند.
                    </p>
                </div>
                <a href="/admin/rss-sources" className="px-4 py-2.5 rounded-xl bg-white border border-gray-200 text-xs font-bold text-gray-700">
                    مدیریت منابع
                </a>
                <button onClick={fetchNow} disabled={busy}
                    className="px-6 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">
                    {busy ? 'در حال دریافت…' : 'دریافت فوری'}
                </button>
            </div>

            <div className="grid grid-cols-4 gap-4 mb-5">
                {[['total', 'کل'], ['APPROVED', 'تأیید شده'], ['PENDING', 'در انتظار'], ['REJECTED', 'رد شده']].map(([k, l]) => (
                    <a key={k} href={k === 'total' ? '/admin/external-news' : `/admin/external-news?status=${k}`}
                        className={`bg-white rounded-xl border p-4 text-center transition-colors ${filters.status === k ? 'border-[#C9A96E] bg-[#C9A96E]/10' : 'border-gray-200'}`}>
                        <p className="text-xs text-gray-400 font-bold">{l}</p>
                        <p className="text-2xl font-black text-[#1B365D] mt-1">{counts[k] ?? 0}</p>
                    </a>
                ))}
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-4 mb-5 flex flex-wrap items-center gap-3">
                <form onSubmit={(e) => { e.preventDefault(); const f = new FormData(e.target); router.get('/admin/external-news', { status: filters.status, source: filters.source, search: f.get('search') }, { preserveState: true }); }}>
                    <input name="search" defaultValue={filters.search} placeholder="جستجو در عنوان یا منبع..." className={box + ' w-64'} />
                </form>
                <select onChange={(e) => router.get('/admin/external-news', { source: e.target.value, status: filters.status }, { preserveState: true })}
                    value={filters.source || ''} className={box + ' w-48'}>
                    <option value="">همه منابع</option>
                    {sources.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <div className="mr-auto flex gap-2">
                    <button onClick={() => bulk('APPROVED')} className="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold">تأیید {sel.length || ''}</button>
                    <button onClick={() => bulk('REJECTED')} className="px-3 py-2 rounded-lg bg-red-50 text-red-600 text-xs font-bold">رد {sel.length || ''}</button>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-sm">
                        <thead className="bg-gray-50 text-gray-500 text-xs">
                            <tr>{['', 'عنوان', 'منبع', 'دسته', 'وضعیت', 'تاریخ', 'عملیات'].map((h, i) => <th key={i} className="p-3 font-bold">{h}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {news.length === 0 && <tr><td colSpan="7" className="text-center py-12 text-gray-400">خبری یافت نشد.</td></tr>}
                            {news.map((n) => (
                                <tr key={n.id} className="hover:bg-gray-50/70">
                                    <td className="p-3">
                                        <input type="checkbox" checked={sel.includes(n.id)}
                                            onChange={(e) => setSel((s) => e.target.checked ? [...s, n.id] : s.filter((x) => x !== n.id))}
                                            className="w-4 h-4 rounded" />
                                    </td>
                                    <td className="p-3 max-w-sm">
                                        <a href={n.link} target="_blank" rel="noreferrer" className="font-bold text-gray-900 hover:text-[#C9A96E] line-clamp-2">
                                            {n.title}
                                        </a>
                                        {n.isBreaking && <span className="mr-1 bg-red-100 text-red-700 text-[10px] px-1.5 rounded font-bold">فوری</span>}
                                    </td>
                                    <td className="p-3 text-gray-500 whitespace-nowrap">{n.sourceName}</td>
                                    <td className="p-3 text-gray-400 text-xs">{n.category || '—'}</td>
                                    <td className="p-3"><span className={`text-[11px] px-2 py-1 rounded font-bold ${NEWS_STATUS[n.status]?.[1]}`}>{NEWS_STATUS[n.status]?.[0] || n.status}</span></td>
                                    <td className="p-3 text-gray-400 text-xs whitespace-nowrap">{faDateTime(n.publishedAt ?? '')}</td>
                                    <td className="p-3">
                                        <div className="flex gap-1.5">
                                            <button onClick={() => router.post(`/admin/external-news/${n.id}/status`, { status: 'APPROVED' })}
                                                className="text-[11px] px-2 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold">تأیید</button>
                                            <button onClick={() => router.delete(`/admin/external-news/${n.id}`)}
                                                className="text-[11px] px-2 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}

// ───────────────────────────────────────────────────── مناسبت‌های تقویمی
export function EventsPage({ events = [] }) {
    const { data, setData, post, processing } = useForm({ date: '', title: '', text: '' });

    return (
        <AdminLayout title="مناسبت‌های تقویمی">
            <Head title="مناسبت‌ها | پنل لیان دید" />
            <Flash />

            <Panel title="افزودن مناسبت">
                <form onSubmit={(e) => { e.preventDefault(); post('/admin/events', data); setData({ date: '', title: '', text: '' }); }}
                    className="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div><label className={label}>تاریخ</label>
                        <input value={data.date} onChange={(e) => setData('date', e.target.value)} required placeholder="۱۴۰۴/۰۷/۰۱" className={box} /></div>
                    <div><label className={label}>عنوان *</label>
                        <input value={data.title} onChange={(e) => setData('title', e.target.value)} required className={box} /></div>
                    <div><label className={label}>متن</label>
                        <input value={data.text} onChange={(e) => setData('text', e.target.value)} className={box} /></div>
                    <button disabled={processing} className="px-4 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">افزودن</button>
                </form>
            </Panel>

            <Panel title={`${events.length} مناسبت`}>
                {events.length === 0 ? (
                    <p className="text-sm text-gray-400 py-8 text-center">مناسبتی ثبت نشده است.</p>
                ) : (
                    <div className="space-y-2">
                        {events.map((e) => (
                            <div key={e.key} className="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100">
                                <div className="min-w-0">
                                    <span className="text-[11px] px-2 py-0.5 rounded bg-[#C9A96E]/20 text-[#1B365D] font-bold ml-2">{e.date}</span>
                                    <span className="font-bold text-sm">{e.title}</span>
                                    {e.text && <p className="text-xs text-gray-400 mt-0.5">{e.text}</p>}
                                </div>
                                <button onClick={() => confirm('حذف این مناسبت؟') && router.delete(`/admin/events/${encodeURIComponent(e.key)}`)}
                                    className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold shrink-0">حذف</button>
                            </div>
                        ))}
                    </div>
                )}
            </Panel>
        </AdminLayout>
    );
}

// ───────────────────────────────────────────────────── ارسالی مخاطبین
export function SubmissionsPage({ submissions = [] }) {
    const set = (id, status) => router.post(`/admin/submissions/${id}/status`, { status });

    return (
        <AdminLayout title="ارسالی مخاطبین">
            <Head title="ارسالی مخاطبین | پنل لیان دید" />
            <Flash />
            <Panel title={`${submissions.length} ارسال`}>
                {submissions.length === 0 ? (
                    <p className="text-sm text-gray-400 py-8 text-center">ارسالی وجود ندارد.</p>
                ) : submissions.map((s) => (
                    <div key={s.id} className="border-b border-gray-100 last:border-0 py-4">
                        <div className="flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <p className="font-bold text-sm">{s.title}</p>
                                <p className="text-xs text-gray-500 mt-1 leading-6">{s.content}</p>
                                <p className="text-[11px] text-gray-400 mt-2">
                                    {s.senderName} · <span dir="ltr">{s.senderPhone || s.senderEmail}</span> · {s.category} · {s.mediaType}
                                </p>
                            </div>
                            <span className={`shrink-0 text-[11px] px-2 py-1 rounded font-bold ${NEWS_STATUS[s.status]?.[1] || 'bg-gray-100 text-gray-600'}`}>
                                {s.status}
                            </span>
                        </div>
                        <div className="flex gap-1.5 mt-3">
                            <button onClick={() => set(s.id, 'APPROVED')} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold">تأیید</button>
                            <button onClick={() => set(s.id, 'REJECTED')} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-800 font-bold">رد</button>
                            <button onClick={() => confirm('حذف؟') && router.delete(`/admin/submissions/${s.id}`)} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                        </div>
                    </div>
                ))}
            </Panel>
        </AdminLayout>
    );
}

// ───────────────────────────────────────────────────── گالری
export function GalleryPage({ images = [], maxMb = '1' }) {
    return (
        <AdminLayout title="گالری">
            <Head title="گالری | پنل لیان دید" />
            <Flash />
            <div className="mb-5 px-5 py-3 rounded-xl bg-[#C9A96E]/15 border border-[#C9A96E]/30 text-sm text-[#1B365D]">
                حداکثر حجم مجاز: <b>{maxMb}</b> مگابایت — برای آپلود از بخش «رسانه» استفاده کنید.
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                {images.length === 0 && <p className="col-span-full text-sm text-gray-400 py-12 text-center">تصویری در گالری نیست.</p>}
                {images.map((img) => (
                    <div key={img.url} className="bg-white rounded-xl border border-gray-200 overflow-hidden group relative">
                        <img src={img.url} alt={img.name} className="w-full h-28 object-cover" />
                        <div className="p-2">
                            <p className="text-[11px] font-bold truncate" dir="ltr">{img.name}</p>
                            <p className="text-[10px] text-gray-400">{img.size}</p>
                        </div>
                        <button
                            onClick={() => confirm(`حذف ${img.name}؟`) && router.post('/admin/gallery/delete', { name: img.name, dir: img.dir || 'public/images' })}
                            className="absolute top-2 left-2 opacity-0 group-hover:opacity-100 bg-red-600 text-white text-[10px] px-2 py-1 rounded-lg font-bold transition-opacity"
                        >
                            حذف
                        </button>
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}

// ───────────────────────────────────────────────────── لینک کوتاه
export function ShortlinksPage({ links = [] }) {
    const { data, setData, post, processing } = useForm({ url: '' });

    return (
        <AdminLayout title="کوتاه‌کننده لینک">
            <Head title="کوتاه‌کننده لینک | پنل لیان دید" />
            <Flash />
            <Panel title="ساخت لینک کوتاه">
                <form onSubmit={(e) => { e.preventDefault(); post('/admin/shortlinks', data); setData({ url: '' }); }}
                    className="flex gap-3 items-end">
                    <div className="flex-1"><label className={label}>آدرس بلند</label>
                        <input value={data.url} onChange={(e) => setData('url', e.target.value)} required dir="ltr" placeholder="https://example.com/a/very/long/article/path" className={box} /></div>
                    <button disabled={processing} className="px-6 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">بساز</button>
                </form>
                <p className="text-xs text-gray-400 mt-3">لینک کوتاه به شکل <code dir="ltr">/s/xxxxxx</code> ساخته می‌شود.</p>
            </Panel>

            <Panel title={`${links.length} لینک`}>
                {links.length === 0 ? <p className="text-sm text-gray-400 py-8 text-center">لینکی ساخته نشده است.</p> : (
                    <div className="space-y-2">
                        {links.map((l) => (
                            <div key={l.code} className="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100">
                                <a href={`/s/${l.code}`} dir="ltr" className="text-sm font-bold text-blue-600 hover:underline">/s/{l.code}</a>
                                <span className="text-xs text-gray-400 truncate flex-1" dir="ltr">{l.url}</span>
                                <span className="text-[11px] px-2 py-1 rounded bg-gray-100 text-gray-600 font-bold shrink-0">{l.hits} بازدید</span>
                                <button onClick={() => confirm('حذف؟') && router.delete(`/admin/shortlinks/${l.code}`)}
                                    className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold shrink-0">حذف</button>
                            </div>
                        ))}
                    </div>
                )}
            </Panel>
        </AdminLayout>
    );
}

// ───────────────────────────────────────────────────── خاموشی سایت
const MODES = {
    online: ['🟢 آنلاین', 'سایت برای همه قابل مشاهده است.', 'bg-emerald-100 text-emerald-700'],
    maintenance: ['🟡 تعمیرات', 'بازدیدکنندگان پیام نگهداری می‌بینند.', 'bg-amber-100 text-amber-800'],
    offline: ['🔴 خاموش', 'فقط مدیر سایت را می‌بیند.', 'bg-red-100 text-red-700'],
};

export function ShutdownPage({ mode = 'online', message = '' }) {
    const [sel, setSel] = useState(mode);
    const [msg, setMsg] = useState(message);

    return (
        <AdminLayout title="خاموش کردن سایت">
            <Head title="وضعیت سایت | پنل لیان دید" />
            <Flash />

            <div className="grid grid-cols-3 gap-4 mb-5">
                {Object.entries(MODES).map(([k, [l, d, cls]]) => (
                    <button key={k} onClick={() => setSel(k)}
                        className={`bg-white rounded-2xl border-2 p-5 text-right transition-all ${sel === k ? 'border-[#C9A96E] shadow-lg' : 'border-gray-200 hover:border-gray-300'}`}>
                        <p className={`text-sm font-black px-3 py-1 rounded-lg inline-block ${cls}`}>{l}</p>
                        <p className="text-xs text-gray-400 mt-3 leading-6">{d}</p>
                    </button>
                ))}
            </div>

            <Panel title="پیام نمایشی">
                <textarea value={msg} onChange={(e) => setMsg(e.target.value)} rows="3" className={box} placeholder="پیامی که بازدیدکنندگان می‌بینند..." />
                <button onClick={() => router.post('/admin/shutdown', { mode: sel, message: msg }, { preserveScroll: true })}
                    className="mt-4 px-6 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">اعمال وضعیت</button>
            </Panel>
        </AdminLayout>
    );
}
