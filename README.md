# لیان دید — PHP / Laravel + Inertia

بازسازی کامل سایت خبری «لیان دید» با Laravel و Inertia + React، مطابق نسخهٔ Next.js.

---

## پیش‌نیازها

| مورد | حداقل |
|---|---|
| PHP | 8.2 |
| MySQL / MariaDB | 5.7 / 10.3 |
| اکستنشن‌ها | `pdo_mysql` `mbstring` `openssl` `curl` `fileinfo` `gd` |
| Node.js | 18 یا بالاتر |
| Composer | 2 |

GD برای پردازش تصویر لازم است. اگر فعال نبود، پنل ← **کرون و سلامت سرور** آن را نشان می‌دهد.

---

## راه‌اندازی از گیت‌هاب

```bash
git clone https://github.com/abkhoshbakht-hub/lian-did-php.git
cd lian-did-php

composer install
npm install

cp .env.example .env
php artisan key:generate
```

سپس `.env` را با اطلاعات دیتابیس خود پر کنید:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=liandid_php
DB_USERNAME=root
DB_PASSWORD=
```

بعد:

```bash
php artisan migrate --force
php artisan storage:link
npm run build
```

**نکتهٔ مهم برای هاست اشتراکی:** اگر دایرکتوری `public_html` ریشهٔ دامنه است، فایل‌ها را طوری بگذارید که `public/` به‌عنوان ریشهٔ وب سرو شود (یا محتوای `public/` را در `public_html` بریزید و بقیه را یک سطح بالاتر نگه دارید).

---

## دیتابیس

اسکیما از طریق migration ساخته می‌شود. برای بازگردانی یک دامپ آماده:

```bash
mysql -u root -p liandid_php < database/liandid_php_YYYYMMDD-HHMMSS.sql
```

## مدیریت

پس از بازگردانی دیتابیس، حساب مدیر موجود است. نام کاربری در دامپ آمده و **رمز در هیچ فایلی در گیت نیست** — اگر آن را نمی‌دانید، از طریق دیتابیس یا artisan رمز را تنظیم کنید:

```bash
php artisan tinker
>>> App\Models\User::first()->update(['password' => bcrypt('رمز-جدید-قوی')]);
```

پس از اولین ورود، رمز را از **پنل ← تنظیمات ← تغییر رمز** عوض کنید.

---

## اجرای تست‌های نیازمند ورود

```bash
$env:ADMIN_EMAIL="..."; $env:ADMIN_PASSWORD="..."
node check-admin-param-pages.mjs
```

---

## کارهای زمان‌بندی‌شده (Cron)

هاست اشتراکی معمولاً فقط اجازهٔ «فراخوانی این URL» می‌دهد. توکن را از پنل ← **کرون و سلامت سرور** بردارید.

| آدرس | کار | فاصلهٔ پیشنهادی |
|---|---|---|
| `/_cron/rss?token=TOKEN` | دریافت RSS و پاکسازی خبرهای قدیمی | هر ۳۰ دقیقه |
| `/_cron/backup?token=TOKEN` | بکاپ دیتابیس | روزی یک‌بار |
| `/_cron/health?token=TOKEN` | گزارش وضعیت سرور | هفتگی |

بدون توکن درست، پاسخ `403` برمی‌گردد.

---

## ساختار

```
app/Support/          منطق دامنه
  CityNews.php          اخبار صفحهٔ شهرهای استان
  HomepageResolver.php  هیرو،Breaking،آخرین اخبار،تحلیل
  RssFetcher.php        دریافت و پارس فید
  DatabaseBackup.php    بکاپ بدون فایل موقت
  ServerHealth.php      بررسی پیش‌نیازهای هاست

resources/js/Pages/   صفحه‌های Inertia
resources/js/Components/news/   نقشه و اخبار
public/maps/          نقشهٔ SVG استان بوشهر

tests/                اسکریپت‌های بررسی
```

---

## بررسی قبل از انتشار

```bash
node check-pages-exist.mjs        # همهٔ صفحه‌ها فایل دارند
node check-nav-links.mjs          # لینک‌های پنل به مسیر موجود می‌رسند
node check-admin-param-pages.mjs  # صفحه‌های پارامتری پنل
php tests/map-fit-test.php        # اندازهٔ نقشه
php tests/parser-live-check.php   # تاریخ فیدها
php tests/about-contact-test.php  # دقت متن درباره ما و تماس
```

---

## محرمانه‌ها داخل گیت نیستند

`.env`، کلید اپلیکیشن و دیتابیس هرگز commit نمی‌شوند. فایل `.env.example` تنظیمات نمونه را نگه می‌دارد.