import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminChangePassword() {
    const { auth } = usePage().props;
    const [form, setForm] = useState({ current: '', password: '', confirmation: '' });
    const [msg, setMsg] = useState(null);

    const submit = async (e) => {
        e.preventDefault();
        const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]) || '');
        const body = new URLSearchParams({ ...form, _method: 'PUT' });

        const res = await fetch('/admin/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': xsrf,
                'X-XSRF-TOKEN': xsrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
        });
        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            setMsg({ ok: data.message || 'رمز عبور با موفقیت تغییر کرد.' });
            setForm({ current: '', password: '', confirmation: '' });
        } else {
            setMsg({
                err:
                    data.errors?.password?.[0] ||
                    data.errors?.current?.[0] ||
                    data.message ||
                    'تغییر رمز ناموفق بود.',
            });
        }
    };

    const box = 'w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-[#1B365D]';

    return (
        <AdminLayout title="تغییر رمز عبور">
            <Head title="تغییر رمز | پنل لیان دید" />

            <form onSubmit={submit} className="max-w-md bg-white rounded-2xl border border-gray-200 p-7 space-y-4">
                <div className="text-center pb-4 border-b border-gray-100">
                    <div className="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-[#C9A96E] to-[#8a6d3b] flex items-center justify-center text-white font-black text-xl">
                        {(auth?.user?.name || 'A')[0]}
                    </div>
                    <p className="font-black text-[#1B365D] mt-3">{auth?.user?.name}</p>
                    <p className="text-xs text-gray-400" dir="ltr">{auth?.user?.email}</p>
                </div>

                {msg && (
                    <div className={`px-4 py-2.5 rounded-xl text-sm font-bold ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'}`}>
                        {msg.ok || msg.err}
                    </div>
                )}

                {[
                    ['current', 'رمز فعلی'],
                    ['password', 'رمز جدید'],
                    ['confirmation', 'تکرار رمز جدید'],
                ].map(([k, l]) => (
                    <div key={k}>
                        <label className="block text-xs font-bold text-gray-600 mb-1.5">{l}</label>
                        <input type="password" value={form[k]} onChange={(e) => setForm({ ...form, [k]: e.target.value })}
                            required dir="ltr" className={box} />
                    </div>
                ))}

                <button className="w-full px-4 py-3 bg-[#1B365D] text-white rounded-xl font-bold hover:bg-[#0f2d52] transition-colors">
                    ذخیره رمز جدید
                </button>
            </form>
        </AdminLayout>
    );
}
