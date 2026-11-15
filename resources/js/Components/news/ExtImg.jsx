import React, { useState } from 'react';

// تصویر اخبار خارجی: اگر لینک عکس خراب/بسته بود، پلاهو رنگی جایگزین می‌شود
export default function ExtImg({ src, alt, className, fallbackClass = 'from-[#1e3a64] to-[#0f2440]', style }) {
    const [err, setErr] = useState(false);
    if (!src || err) {
        return <div className={`w-full h-full bg-gradient-to-br ${fallbackClass} ${className || ''}`} style={style} />;
    }
    return (
        <img
            src={src}
            alt={alt || ''}
            className={className}
            style={style}
            loading="lazy"
            referrerPolicy="no-referrer"
            onError={() => setErr(true)}
        />
    );
}
