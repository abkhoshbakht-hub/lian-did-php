const DS = { 0: '۰', 1: '۱', 2: '۲', 3: '۳', 4: '۴', 5: '۵', 6: '۶', 7: '۷', 8: '۸', 9: '۹' };

export function faNum(v) {
    if (v === null || v === undefined || v === '') return '۰';
    return String(v).replace(/[0-9]/g, function (d) { return DS[d]; });
}

export function faDate(v) {
    if (!v) return '—';
    var d = new Date(v);
    if (isNaN(d.getTime())) return '—';
    return faNum(new Intl.DateTimeFormat('fa-IR', {
        calendar: 'persian',
        timeZone: 'Asia/Tehran',
        year: 'numeric', month: '2-digit', day: '2-digit',
    }).format(d));
}

export function faDateTime(v) {
    if (!v) return '—';
    var d = new Date(v);
    if (isNaN(d.getTime())) return '—';
    return faNum(new Intl.DateTimeFormat('fa-IR', {
        calendar: 'persian',
        timeZone: 'Asia/Tehran',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hour12: false,
    }).format(d));
}
