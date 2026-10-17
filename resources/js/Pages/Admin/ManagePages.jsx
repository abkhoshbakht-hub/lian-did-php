import React, { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const box = 'w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]';
const label = 'block text-xs font-bold text-gray-600 mb-1.5';

function Flash() {
    const { flash } = usePage().props;
    if (!flash) return null;
    const isErr = typeof flash === 'object';
    return (
        <div className={`mb-5 px-5 py-3 rounded-xl text-sm font-bold border ${
            isErr ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'
        }`}>
            {isErr ? flash.error : flash.success}
        </div>
    );
}

function Panel({ title, children, action }) {
    return (
        <div className="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
            <div className="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                <h2 className="text-sm font-black text-[#1B365D]">{title}</h2>
                {action}
            </div>
            {children}
        </div>
    );
}

// ─────────────────────────────────────────────────────────── دسته‌بندی‌ها
export function CategoriesPage({ categories = [] }) {
    const { data, setData, post, put, processing } = useForm({
        id: '', name: '', slug: '', icon: '', color: '',
    });
    const [editId, setEditId] = useState(null);

    const submit = (e) => {
        e.preventDefault();
        if (editId) put(`/admin/categories/${editId}`, data);
        else post('/admin/categories', data);
        setData({ id: '', name: '', slug: '', icon: '', color: '' });
        setEditId(null);
    };

    const startEdit = (c) => {
        setEditId(c.id);
        setData({ id: c.id, name: c.name, slug: c.slug, icon: c.icon || '', color: c.color || '' });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const remove = (c) => {
        if (confirm(`حذف دسته‌بندی «${c.name}»؟`)) router.delete(`/admin/categories/${c.id}`);
    };

    return (
        <AdminLayout title="دسته‌بندی‌ها">
            <Head title="دسته‌بندی‌ها | پنل لیان دید" />
            <Flash />

            <Panel title={editId ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی'}>
                <form onSubmit={submit} className="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                    <div>
                        <label className={label}>نام *</label>
                        <input value={data.name} onChange={(e) => setData('name', e.target.value)} required className={box} />
                    </div>
                    <div>
                        <label className={label}>شناسه (انگلیسی)</label>
                        <input value={data.slug} onChange={(e) => setData('slug', e.target.value)} dir="ltr" className={box} placeholder="خودکار" />
                    </div>
                    <div>
                        <label className={label}>آیکون</label>
                        <input value={data.icon} onChange={(e) => setData('icon', e.target.value)} className={box} placeholder="🏛️" />
                    </div>
                    <div>
                        <label className={label}>رنگ</label>
                        <input type="color" value={data.color || '#1B365D'} onChange={(e) => setData('color', e.target.value)} className="w-full h-10 rounded-xl border border-gray-200" />
                    </div>
                    <div className="flex gap-2">
                        <button disabled={processing} className="flex-1 px-4 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">
                            {editId ? 'ذخیره' : 'افزودن'}
                        </button>
                        {editId && (
                            <button type="button" onClick={() => { setEditId(null); setData({ id: '', name: '', slug: '', icon: '', color: '' }); }}
                                className="px-4 py-2.5 rounded-xl bg-gray-200 text-gray-700 text-sm font-bold">انصراف</button>
                        )}
                    </div>
                </form>
            </Panel>

            <Panel title={`${categories.length} دسته‌بندی`}>
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-sm">
                        <thead className="bg-gray-50 text-gray-500 text-xs">
                            <tr>{['ترتیب', 'نام', 'شناسه', 'رنگ', 'اخبار', 'عملیات'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {categories.map((c) => (
                                <tr key={c.id} className="hover:bg-gray-50/70">
                                    <td className="p-3 text-gray-500">{c.order}</td>
                                    <td className="p-3 font-bold">{c.icon} {c.name}</td>
                                    <td className="p-3 text-gray-400 text-xs" dir="ltr">{c.slug}</td>
                                    <td className="p-3"><span className="w-6 h-6 inline-block rounded border border-gray-200" style={{ background: c.color || '#fff' }} /></td>
                                    <td className="p-3">{c.articles_count}</td>
                                    <td className="p-3">
                                        <div className="flex gap-1.5">
                                            <button onClick={() => startEdit(c)} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-700 font-bold">ویرایش</button>
                                            <button onClick={() => remove(c)} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Panel>
        </AdminLayout>
    );
}

// ─────────────────────────────────────────────────────────── کاربران
export function UsersPage({ users = [] }) {
    const { data, setData, post, put, processing } = useForm({
        id: '', name: '', email: '', password: '', role: 'USER', phone: '', isActive: true,
    });
    const [editId, setEditId] = useState(null);

    const submit = (e) => {
        e.preventDefault();
        if (editId) put(`/admin/users/${editId}`, data);
        else post('/admin/users', data);
        setData({ id: '', name: '', email: '', password: '', role: 'USER', phone: '', isActive: true });
        setEditId(null);
    };

    const startEdit = (u) => {
        setEditId(u.id);
        setData({ id: u.id, name: u.name, email: u.email, password: '', role: u.role, phone: u.phone || '', isActive: !!u.isActive });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <AdminLayout title="کاربران">
            <Head title="کاربران | پنل لیان دید" />
            <Flash />

            <Panel title={editId ? 'ویرایش کاربر' : 'افزودن کاربر'}>
                <form onSubmit={submit} className="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                    <div><label className={label}>نام *</label>
                        <input value={data.name} onChange={(e) => setData('name', e.target.value)} required className={box} /></div>
                    <div><label className={label}>ایمیل *</label>
                        <input value={data.email} onChange={(e) => setData('email', e.target.value)} required type="email" dir="ltr" className={box} /></div>
                    <div><label className={label}>{editId ? 'رمز (خالی = بدون تغییر)' : 'رمز *'}</label>
                        <input value={data.password} onChange={(e) => setData('password', e.target.value)} type="password" dir="ltr" className={box} /></div>
                    <div><label className={label}>نقش</label>
                        <select value={data.role} onChange={(e) => setData('role', e.target.value)} className={box}>
                            <option value="USER">نویسنده</option>
                            <option value="ADMIN">مدیر</option>
                        </select></div>
                    <div><label className={label}>تلفن</label>
                        <input value={data.phone} onChange={(e) => setData('phone', e.target.value)} dir="ltr" className={box} /></div>
                    <div className="flex gap-2">
                        <button disabled={processing} className="flex-1 px-4 py-2.5 rounded-xl bg-[#1B365D] text-white text-sm font-bold">
                            {editId ? 'ذخیره' : 'افزودن'}
                        </button>
                        {editId && (
                            <button type="button" onClick={() => { setEditId(null); setData({ id: '', name: '', email: '', password: '', role: 'USER', phone: '', isActive: true }); }}
                                className="px-4 py-2.5 rounded-xl bg-gray-200 text-gray-700 text-sm font-bold">×</button>
                        )}
                    </div>
                </form>
            </Panel>

            <Panel title={`${users.length} کاربر`}>
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-sm">
                        <thead className="bg-gray-50 text-gray-500 text-xs">
                            <tr>{['نام', 'ایمیل', 'نقش', 'وضعیت', 'عملیات'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {users.map((u) => (
                                <tr key={u.id} className="hover:bg-gray-50/70">
                                    <td className="p-3 font-bold">{u.name}</td>
                                    <td className="p-3 text-gray-500" dir="ltr">{u.email}</td>
                                    <td className="p-3">
                                        <span className={`text-[11px] px-2 py-1 rounded font-bold ${u.role === 'ADMIN' ? 'bg-[#1B365D] text-white' : 'bg-gray-100 text-gray-600'}`}>{u.role}</span>
                                    </td>
                                    <td className="p-3">
                                        <button onClick={() => router.post(`/admin/users/${u.id}/toggle`)}
                                            className={`text-[11px] px-2 py-1 rounded font-bold ${u.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {u.isActive ? 'فعال' : 'غیرفعال'}
                                        </button>
                                    </td>
                                    <td className="p-3">
                                        <div className="flex gap-1.5">
                                            <button onClick={() => startEdit(u)} className="text-[11px] px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-700 font-bold">ویرایش</button>
                                            {u.email !== 'admin@liandid.ir' && (
                                                <button onClick={() => confirm(`حذف ${u.name}؟`) && router.delete(`/admin/users/${u.id}`)}
                                                    className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Panel>
        </AdminLayout>
    );
}

// ─────────────────────────────────────────────────────────── نظرات
export function CommentsPage({ comments = [] }) {
    return (
        <AdminLayout title="نظرات">
            <Head title="نظرات | پنل لیان دید" />
            <Flash />
            <Panel title={`${comments.length} نظر`}>
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-sm">
                        <thead className="bg-gray-50 text-gray-500 text-xs">
                            <tr>{['متن', 'خبر', 'وضعیت', 'تاریخ', 'عملیات'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {comments.length === 0 && <tr><td colSpan="5" className="text-center py-12 text-gray-400">نظری ثبت نشده است.</td></tr>}
                            {comments.map((c) => (
                                <tr key={c.id} className="hover:bg-gray-50/70">
                                    <td className="p-3 max-w-md"><p className="line-clamp-2">{c.content}</p></td>
                                    <td className="p-3 text-gray-500 text-xs max-w-[200px]"><span className="line-clamp-1">{c.article_title}</span></td>
                                    <td className="p-3">
                                        <button onClick={() => router.post(`/admin/comments/${c.id}/toggle`)}
                                            className={`text-[11px] px-2 py-1 rounded font-bold ${c.approved ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'}`}>
                                            {c.approved ? 'تأیید شده' : 'در انتظار'}
                                        </button>
                                    </td>
                                    <td className="p-3 text-gray-400 text-xs">{String(c.created_at ?? '').slice(0, 16)}</td>
                                    <td className="p-3">
                                        <button onClick={() => confirm('حذف این نظر؟') && router.delete(`/admin/comments/${c.id}`)}
                                            className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Panel>
        </AdminLayout>
    );
}

// ─────────────────────────────────────────────────────────── خبرنامه
export function SubscribersPage({ subscribers = [] }) {
    const active = subscribers.filter((s) => s.isActive).length;

    return (
        <AdminLayout title="خبرنامه">
            <Head title="خبرنامه | پنل لیان دید" />
            <Flash />
            <div className="grid grid-cols-3 gap-4 mb-5">
                {[['کل اعضا', subscribers.length], ['فعال', active], ['غیرفعال', subscribers.length - active]].map(([t, n]) => (
                    <div key={t} className="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <p className="text-xs text-gray-400 font-bold">{t}</p>
                        <p className="text-2xl font-black text-[#1B365D] mt-1">{n}</p>
                    </div>
                ))}
            </div>
            <Panel title={`${subscribers.length} عضو`}>
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-sm">
                        <thead className="bg-gray-50 text-gray-500 text-xs">
                            <tr>{['ایمیل', 'نام', 'وضعیت', 'تاریخ', 'عملیات'].map((h) => <th key={h} className="p-3 font-bold">{h}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {subscribers.map((s) => (
                                <tr key={s.id} className="hover:bg-gray-50/70">
                                    <td className="p-3" dir="ltr">{s.email}</td>
                                    <td className="p-3 text-gray-600">{s.name || '—'}</td>
                                    <td className="p-3">
                                        <button onClick={() => router.post(`/admin/subscribers/${s.id}/toggle`)}
                                            className={`text-[11px] px-2 py-1 rounded font-bold ${s.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
                                            {s.isActive ? 'فعال' : 'غیرفعال'}
                                        </button>
                                    </td>
                                    <td className="p-3 text-gray-400 text-xs">{String(s.created_at ?? '').slice(0, 16)}</td>
                                    <td className="p-3">
                                        <button onClick={() => confirm('حذف این عضو؟') && router.delete(`/admin/subscribers/${s.id}`)}
                                            className="text-[11px] px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 font-bold">حذف</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Panel>
        </AdminLayout>
    );
}
