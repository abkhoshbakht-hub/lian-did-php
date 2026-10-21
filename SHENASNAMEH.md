# شناسنامه سایت لیاندید

⚠️ **هشدار:** این سند حاوی رمز عبور است — فقط نگه‌داری شخصی، هرگز عمومی نکن.

## آدرس‌ها
| مورد | مقدار |
|---|---|
| سایت | https://liandid.ir |
| پنل مدیریت | https://liandid.ir/admin (ورود از /login) |
| پیشخوان روزنامه‌ها | https://liandid.ir/newspapers |
| آرشیو روزنامه‌ها | https://liandid.ir/newspapers/archive |
| کیوسک | https://liandid.ir/kiosk |
| API اخبار | https://liandid.ir/api/rss?category=... |
| API پیشخوان | https://liandid.ir/api/newspapers |

## ثبت‌نام ادمین
- `admin@liandid.ir` — رمز: `12345678`
- `info@liandid.ir` — رمز: `12345678`

## هاست و دامنه
- میزبان: **ایران‌سرور** (cPanel، هاست اشتراکی)
- سرور FTP: `185.10.75.57` — کاربر `liandidir`
- رمز FTP: `6Sr!=b3AR9%m4`
- MySQL: `127.0.0.1` دیتابیس `liandidir_liandid` — کاربر `liandidir_liadb` — رمز `LianDid-db#8477`
- SSL: Let's Encrypt `*.liandid.ir` اعتبار تا **2027-01-18**
- PHP: **8.2.34** (بدون intl)

## فناوری
- بک‌اند: **Laravel 12** (به‌جای Next.js قبلی)
- فرانت: **React 18 + Inertia 2 + Vite 7 + Tailwind 4**
- ریپو: `github.com/abkhoshbakht-hub/lian-did-php` — برنچ `php-version-release`
- دیپلوی: FTP — کد اصلی در `/lian_app` و خروجی build در **دو مسیر**: `lian_app/public/build/` و `public_html/build/`

## صفحات عمومی
| مسیر | توضیح |
|---|---|
| `/` | صفحه اصلی (اخبار لحظه‌ای، بازار، روزنامه‌ها) |
| `/category/{slug}` | آرشیو دسته |
| `/article/{id}` | صفحه خبر |
| `/city` | اخبار شهرستان‌ها |
| `/newspapers` | پیشخوان روزنامه‌های امروز |
| `/newspapers/archive` | آرشیو صفحات اول |
| `/kiosk` | کیوسک مطبوعات |

## صفحات مدیریت
- داشبورد، مقاله‌ها، اخبار خارجی، دسته‌ها، کاربران، نظرها، گالری، لینک کوتاه، اعلان‌نامه‌ها (فرم پذیرش)، روزنامه‌ها (+منابع و صفحات)، کرون، خاموشی موقت

## حجم و آمار
- build: **۵۸ فایل (۰.۸ مگ)** / ریپو: ۷۲ مگ / DB: **۶۷ مگ**
- مقاله ۲۱ | نظر ۱ | کاربر ۳ | اخبار خارجی ۱۰۴۳۸ | روزنامه ۱۸ | منبع ۳۳ | صفحه اول ۷۹ | دسته ۱۳ | زیر‌دسته ۷

## خودکارسازی (cron-job.org)
| جاب | زمان‌بندی | آدرس |
|---|---|---|
| RSS Refresh | هر ۱۵ دقیقه | https://liandid.ir/api/rss/refresh |
| Newspapers Hourly | هر ۱ ساعت | https://liandid.ir/api/cron/newspapers |

- هر دو endpoint همیشه **200 JSON** برمی‌دهند (بدون ۴۰۴/۴۱۹ برای کرن‌جاب)
- GitHub Action «به‌روزرسانی RSS» در ریپوی `abkhoshbakht-hub/liandid` → **Disable** شده
- توکن فراخوانی اجباری کرون پابلیک: `8c8d06c494dc42ef37f7b2e09f4f0c54e9ebd9504405472c` (اختیاری، بدون توکن محدودیت ۱۸ دقیقه فاصله اعمال می‌شود)

## نکات مهم نگهداری
1. هر build باید هر دو مسیر بالا آپلود شود؛ در غیر این صورت سایت خراب ظاهر می‌شود
2. وب سرور با `Host: liandid.ir` روی IP سرور قابل تست است
3. برای تست بدون کش مرورگر: `Ctrl+Shift+R`
4. اخبار خارجی با وضعیت PENDING ذخیره‌شده و تایید ادمین لازم است (دسته «روزنامه» خودکار APPROVED)
5. تاریخ‌های شمسی در PHP به‌صورت خالص محاسبه می‌شوند (تابعی `jdate` در NewspaperFetcher و فرمول 355666 در routes)
