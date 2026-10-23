export async function uploadAdminImage(file) {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    const token = m ? decodeURIComponent(m[1]) : '';
    const fd = new FormData();
    fd.append('file', file);

    const res = await fetch('/admin/upload', {
        method: 'POST',
        headers: {
            'X-XSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        credentials: 'same-origin',
        body: fd,
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data.url) {
        throw new Error(data.message || 'بارگذاری تصویر ناموفق بود.');
    }

    return data.url;
}
