import AdminList from '@/Pages/Admin/List';

export default function AdminRegions({ regions = [], counties = [] }) {
    return (
        <AdminList
            title="شهرها و شهرستان‌ها"
            columns={[
                { key: 'label', label: 'عنوان' },
                { key: 'county', label: 'شهرستان', render: (r) => <span dir="ltr" className="text-gray-400 text-xs">{r.county}</span> },
                { key: 'kind', label: 'نوع', render: (r) => <span className="text-xs px-2 py-1 rounded bg-gray-100">{r.kind === 'village' ? 'روستا' : 'شهر'}</span> },
                { key: 'count', label: 'تعداد اخبار' },
            ]}
            rows={regions}
            empty="هنوز خبری به شهرستانی نسبت داده نشده است."
        />
    );
}
