import AdminList from '@/Pages/Admin/List';

export default function AdminMedia({ files = [], maxMb = '1', format = 'webp' }) {
    return (
        <AdminList
            title="رسانه"
            columns={[
                { key: 'name', label: 'نام فایل' },
                { key: 'size', label: 'حجم' },
                { key: 'time', label: 'تاریخ' },
                { key: 'url', label: 'نشانی', render: (r) => (
                    <a href={r.url} target="_blank" rel="noreferrer" dir="ltr" className="text-xs text-blue-600 hover:underline">{r.url}</a>
                ) },
            ]}
            rows={files}
            empty="هنوز فایلی آپلود نشده است."
        >
            <div className="mb-5 px-5 py-4 rounded-xl bg-[#C9A96E]/15 border border-[#C9A96E]/30 text-sm text-[#1B365D]">
                طبق تنظیمات عکس: حداکثر حجم <b>{maxMb}</b> مگابایت — فر خروجی <b>{format}</b>.
            </div>
        </AdminList>
    );
}
