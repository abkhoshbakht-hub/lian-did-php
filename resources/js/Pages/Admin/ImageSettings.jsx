import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const ASPECTS = [
    { value: '16:9', label: '۱۶:۹ (پهن — مناسب هیرو و کارت خبر)' },
    { value: '4:3', label: '۴:۳ (کلاسیک)' },
    { value: '1:1', label: '۱:۱ (مربع)' },
    { value: 'free', label: 'آزاد (بدون برش اجباری)' },
];

const FORMATS = [
    { value: 'webp', label: 'WebP (کم‌حجم و سریع — استاندارد سایت‌های خبری)' },
    { value: 'jpeg', label: 'JPEG (سازگار با همه)' },
    { value: 'png', label: 'PNG (کیفیت کامل، حجم بیشتر)' },
    { value: 'original', label: 'فرمت اصلی عکس (بدون تبدیل)' },
];

export default function AdminImageSettings({ settings = {} }) {
    const [form, setForm] = useState({
        image_max_mb: settings.image_max_mb ?? '1',
        image_quality: settings.image_quality ?? '75',
        image_max_dim: settings.image_max_dim ?? '1280',
        image_aspect: settings.image_aspect ?? '16:9',
        image_format: settings.image_format ?? 'webp',
    });
    const [saving, setSaving] = useState(false);

    const set = (k, v) => setForm((f) => ({ ...f, [k]: v }));

    const save = (e) => {
        e.preventDefault();
        setSaving(true);
        const body = new URLSearchParams(form);
        fetch('/admin/image-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-XSRF-TOKEN': decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]) || ''),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
        })
            .then((r) => {
                if (r.ok) alert('تنظیمات عکس ذخیره شد');
                else alert('خطا در ذخیره تنظیمات');
            })
            .catch(() => alert('خطا در ذخیره تنظیمات'))
            .finally(() => setSaving(false));
    };

    const box = 'w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-[#C9A96E] bg-white text-sm';

    return (
        <AdminLayout title="تنظیم عکس">
            <Head title="تنظیم عکس | پنل لیان دید" />

            <form onSubmit={save} className="max-w-3xl bg-white rounded-2xl shadow-lg border border-gray-200 p-8 space-y-8">
                <p className="text-sm text-gray-500 leading-7 text-justify">
                    این تنظیمات روی همه عکس‌های خبری (عکس شاخص خبر، ارسال مخاطبین و گالری) اعمال می‌شود.
                    عکس‌ها به‌صورت خودکار فشرده و به فرمت انتخابی تبدیل می‌شوند تا سایت سریع بماند.
                </p>

                <div>
                    <label className="block text-sm font-bold text-gray-700 mb-2">
                        حداکثر حجم عکس: <span className="text-[#1B365D]">{form.image_max_mb} مگابایت</span>
                    </label>
                    <input type="range" min={1} max={10} step={0.5} value={form.image_max_mb}
                        onChange={(e) => set('image_max_mb', e.target.value)} className="w-full accent-[#C9A96E]" />
                    <div className="flex justify-between text-xs text-gray-400 mt-1"><span>۱۰ مگ</span><span>۱ مگ</span></div>
                </div>

                <div>
                    <label className="block text-sm font-bold text-gray-700 mb-2">
                        کیفیت فشرده‌سازی: <span className="text-[#1B365D]">{form.image_quality}٪</span>
                    </label>
                    <input type="range" min={10} max={100} step={5} value={form.image_quality}
                        onChange={(e) => set('image_quality', e.target.value)} className="w-full accent-[#C9A96E]" />
                    <div className="flex justify-between text-xs text-gray-400 mt-1"><span>۱۰۰ (بهترین)</span><span>۱۰ (کمترین حجم)</span></div>
                </div>

                <div>
                    <label className="block text-sm font-bold text-gray-700 mb-2">حداکثر عرض یا ارتفاع عکس (پیکسل)</label>
                    <select value={form.image_max_dim} onChange={(e) => set('image_max_dim', e.target.value)} className={box}>
                        <option value="800">۸۰۰ پیکسل (سبک — مناسب موبایل)</option>
                        <option value="1280">۱۲۸۰ پیکسل (استاندارد — پیشنهادی)</option>
                        <option value="1920">۱۹۲۰ پیکسل (بزرگ — مناسب هیرو تمام‌صفحه)</option>
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-bold text-gray-700 mb-2">قاب برش پیش‌فرض عکس شاخص</label>
                    <select value={form.image_aspect} onChange={(e) => set('image_aspect', e.target.value)} className={box}>
                        {ASPECTS.map((a) => <option key={a.value} value={a.value}>{a.label}</option>)}
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-bold text-gray-700 mb-2">فرمت تبدیل عکس</label>
                    <select value={form.image_format} onChange={(e) => set('image_format', e.target.value)} className={box}>
                        {FORMATS.map((f) => <option key={f.value} value={f.value}>{f.label}</option>)}
                    </select>
                    <p className="text-xs text-gray-400 mt-2 leading-6 text-justify">
                        هر عکسی با هر فرمتی آپلود شود، به‌صورت خودکار به این فرمت تبدیل می‌شود.
                    </p>
                </div>

                <button type="submit" disabled={saving}
                    className="w-full px-4 py-3 bg-[#1B365D] text-white rounded-lg font-bold hover:bg-[#0f2d52] transition-colors disabled:opacity-50">
                    {saving ? 'در حال ذخیره...' : 'ذخیره تنظیمات'}
                </button>
            </form>
        </AdminLayout>
    );
}
