import React, { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

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

export default function AdminNewspapers({ newspapers = [], sources = [], issues = [], logs = [], stats = {} }) {
    const [tab, setTab] = useState('papers');
    const paper = useForm({ name: '', slug: '', category: '', website: '', telegram: '', description: '', displayOrder: '' });
    const source = useForm({ newspaperId: '', name: '', type: 'official', url: '', priority: 0 });

    const TABS = [
        ['papers', 'روزنامه‌ها', stats.papers],
        ['sources', 'منابع دریافت', stats.sources],
        ['issues', 'شماره‌ها', stats.issues],
        ['logs', 'تاریخچه دریافت', logs.length],
    ];

    return (
        <AdminLayout title="صفحه اول روزنامه‌ها">
            <Head title="روزنامه‌ها | پنل لیان دید" />
            <Flash />

            <div className="grid grid-cols-4 gap-4 mb-5">
                {[['papers', 'روزنامه'], ['sources', 'منبع'], ['issues', 'شماره'], ['noImage', 'بدون تصویر']].map(([k, l]) => (
                    <div key={k} className="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <p className="text-xs text-gray-400 font-bold">{l}</p>
                        <p className="text-2xl font-black text-[#1B365D] mt-1">{stats[k] ?? 0}</p>
                    </div>
                ))}
            </div>

            <div className="flex gap-2 mb-5">
                {TABS.map(([k, l, n]) => (
                    <button key={k} onClick={() => setTab(k)}
                        className={`px-4 py-2 rounded-xl text-xs font-bold ${tab === k ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-600'}`}>
                        {l} <span className="opacity-70">({n ?? 0})</span>
                    </button>
                ))}
            </div>

            {tab === 'papers' && (
                <>
                    <Panel title="افزودن روزنامه">
                        <form onSubmit={(e) => { e.preventDefault(); paper.post('/admin/newspapers'); paper.setData({ name: '', slug: '', category: '', website: '', telegram: '', description: '', displayOrder: '' }); }}
                            className="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div><label className={label}>نام *</label>
                                <input value={paper.data.name} onChange={(e) => paper.setData('name', e.target.value)} required className={box} /></div>
                            <div><label className={label}>شناسه</label>
                                <input value={paper.data.slug} onChange={(e) => paper.setData('slug', e.target.value)} dir="ltr" className={box} placeholder="خودکار" /></div>
                            <div><label className={label}>دسته</label>
                                <input value={paper.data.category} onChange={(e) => paper.setData('category', e.target.value)} className={box} placeholder="ملی / بوشهر" /></div>
                            <div><label className={label}>وب‌سایت</label>
                                <input value={paper.data.website} onChange={(e) => paper.setData('website', e.target.value)} dir="ltr" className={box} placeholder="https://" /></div>
                            <div><label className={label}>تلگرام</label>
                                <input value={paper.data.telegram} onChange={(e) => paper.setData('telegram', e.target.value)} dir="ltr" className={box} placeholder="https://t.me/" /></div>
                            <div><label className={label}>ترتیب نمایش</label>
                                <input type="number" value={paper.data.displayOrder} onChange={(e) => paper.setData('displayOrder', e.target.value)} className={box} /></div>
                            <div className="md:col-span-3">
                                <button disabled={paper.processing} className="px-6 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">افزودن</button>
                            </div>
                        </form>
                    </Panel>

                    <Panel title={`${newspapers.length} روزنامه`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-sm">
                                <thead className="bg-gray-50 text-gray-500 text-xs">
                                    <tr>{['نام', 'دسته', 'منابع', 'شماره‌ها', 'وضعیت', 'عملیات'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {newspapers.length === 0 && <tr><td colSpan="6" className="text-center py-12 text-gray-400">روزنامه‌ای ثبت نشده است.</td></tr>}
                                    {newspapers.map((p) => (
                                        <tr key={p.id} className="hover:bg-gray-50/70">
                                            <td className="p-3 font-bold">
                                                {p.name}
                                                {p.website && <a href={p.website} target="_blank" rel="noreferrer" className="text-[11px] text-blue-500 mr-1">↗</a>}
                                            </td>
                                            <td className="p-3 text-gray-500">{p.category}</td>
                                            <td className="p-3">{p.sourcesCount}</td>
                                            <td className="p-3">{p.issuesCount}</td>
                                            <td className="p-3">
                                                <span className={`text-[11px] px-2 py-1 rounded font-bold ${p.active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
                                                    {p.active ? 'فعال' : 'غیرفعال'}
                                                </span>
                                            </td>
                                            <td className="p-3">
                                                <button onClick={() => confirm(`حذف «${p.name}» و همه شماره‌هایش؟`) && router.delete(`/admin/newspapers/${p.id}`)}
                                                    className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Panel>
                </>
            )}

            {tab === 'sources' && (
                <>
                    <Panel title="افزودن منبع دریافت">
                        <form onSubmit={(e) => { e.preventDefault(); source.post('/admin/newspapers/sources'); source.setData({ newspaperId: '', name: '', type: 'official', url: '', priority: 0 }); }}
                            className="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                            <div><label className={label}>روزنامه *</label>
                                <select value={source.data.newspaperId} onChange={(e) => source.setData('newspaperId', e.target.value)} required className={box}>
                                    <option value="">انتخاب کنید</option>
                                    {newspapers.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                                </select></div>
                            <div><label className={label}>نام منبع *</label>
                                <input value={source.data.name} onChange={(e) => source.setData('name', e.target.value)} required className={box} /></div>
                            <div><label className={label}>نوع</label>
                                <select value={source.data.type} onChange={(e) => source.setData('type', e.target.value)} className={box}>
                                    <option value="official">رسمی</option>
                                    <option value="archive">آرشیو</option>
                                    <option value="custom">سفارشی</option>
                                </select></div>
                            <div><label className={label}>آدرس *</label>
                                <input value={source.data.url} onChange={(e) => source.setData('url', e.target.value)} required dir="ltr" className={box} placeholder="https://" /></div>
                            <button disabled={source.processing} className="px-4 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">افزودن</button>
                        </form>
                    </Panel>

                    <Panel title={`${sources.length} منبع`}>
                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-sm">
                                <thead className="bg-gray-50 text-gray-500 text-xs">
                                    <tr>{['منبع', 'نوع', 'اولویت', 'آخرین موفقیت', 'آخرین خطا', 'عملیات'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {sources.length === 0 && <tr><td colSpan="6" className="text-center py-12 text-gray-400">منبعی ثبت نشده است.</td></tr>}
                                    {sources.map((s) => (
                                        <tr key={s.id} className="hover:bg-gray-50/70">
                                            <td className="p-3">
                                                <p className="font-bold text-xs">{s.name}</p>
                                                <a href={s.url} target="_blank" rel="noreferrer" dir="ltr" className="text-[11px] text-blue-500">{s.url}</a>
                                            </td>
                                            <td className="p-3 text-gray-500 text-xs">{s.type}</td>
                                            <td className="p-3">{s.priority}</td>
                                            <td className="p-3 text-xs text-gray-400">{s.lastSuccessAt ? String(s.lastSuccessAt).slice(0, 16) : '—'}</td>
                                            <td className="p-3 text-xs text-red-400 max-w-[200px]"><span className="line-clamp-1">{s.lastError || '—'}</span></td>
                                            <td className="p-3">
                                                <button onClick={() => confirm('حذف این منبع؟') && router.delete(`/admin/newspapers/sources/${s.id}`)}
                                                    className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Panel>
                </>
            )}

            {tab === 'issues' && (
                <Panel title={`${issues.length} شمارهٔ اخیر`} note="شماره‌هایی که تصویر ندارند با نشان هشدار مشخص شده‌اند">
                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        {issues.length === 0 && <p className="col-span-full text-sm text-gray-400 py-12 text-center">شماره‌ای دریافت نشده است.</p>}
                        {issues.map((i) => (
                            <div key={i.id} className="bg-white rounded-xl border border-gray-200 overflow-hidden relative group">
                                {i.hasImage ? (
                                    <img src={i.imageUrl} alt={i.title || ''} className="w-full h-32 object-cover" />
                                ) : (
                                    <div className="w-full h-32 bg-gray-100 flex items-center justify-center text-gray-300 text-xs">بدون تصویر</div>
                                )}
                                <div className="p-2">
                                    <p className="text-[11px] font-black text-[#1B365D]">{i.persianDate}</p>
                                    <p className="text-[10px] text-gray-400 truncate">{i.title || i.newspaperId}</p>
                                </div>
                                {!i.hasImage && <span className="absolute top-2 right-2 bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded font-bold">بدون تصویر</span>}
                                <button onClick={() => confirm('حذف این شماره؟') && router.delete(`/admin/newspapers/issues/${i.id}`)}
                                    className="absolute top-2 left-2 opacity-0 group-hover:opacity-100 bg-red-600 text-white text-[10px] px-2 py-1 rounded-lg font-bold transition-opacity">
                                    حذف
                                </button>
                            </div>
                        ))}
                    </div>
                </Panel>
            )}

            {tab === 'logs' && (
                <Panel title="تاریخچهٔ دریافت خودکار">
                    <div className="overflow-x-auto">
                        <table className="w-full text-right text-sm">
                            <thead className="bg-gray-50 text-gray-500 text-xs">
                                <tr>{['روزنامه', 'وضعیت', 'کد', 'زمان پاسخ', 'شروع', 'پایان', 'خطا'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {logs.length === 0 && <tr><td colSpan="7" className="text-center py-12 text-gray-400">رویدادی ثبت نشده است.</td></tr>}
                                {logs.map((l) => (
                                    <tr key={l.id} className="hover:bg-gray-50/70">
                                        <td className="p-3 text-xs">{l.newspaperId}</td>
                                        <td className="p-3">
                                            <span className={`text-[11px] px-2 py-1 rounded font-bold ${
                                                l.status === 'SUCCESS' ? 'bg-emerald-100 text-emerald-700'
                                                    : l.status === 'FAILED' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}`}>
                                                {l.status}
                                            </span>
                                        </td>
                                        <td className="p-3 text-gray-400">{l.httpStatus ?? '—'}</td>
                                        <td className="p-3 text-gray-400">{l.responseTimeMs ? l.responseTimeMs + 'ms' : '—'}</td>
                                        <td className="p-3 text-xs text-gray-400">{String(l.startedAt ?? '').slice(0, 16)}</td>
                                        <td className="p-3 text-xs text-gray-400">{l.finishedAt ? String(l.finishedAt).slice(0, 16) : '—'}</td>
                                        <td className="p-3 text-xs text-red-400 max-w-[220px]"><span className="line-clamp-1">{l.errorMessage || '—'}</span></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Panel>
            )}
        </AdminLayout>
    );
}
