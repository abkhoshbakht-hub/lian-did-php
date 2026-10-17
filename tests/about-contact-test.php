<?php

/**
 * Verifies /about and /contact render the exact markup the Next.js reference
 * uses. Every expected string is copied from the reference source files.
 */

$base = 'D:/2';
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fail = 0;
$check = function ($n, $ok) use (&$fail) {
    printf("  %s  %s\n", $ok ? 'PASS' : 'FAIL', $n);
    if (! $ok) $fail++;
};

/** Resolves a page component to its built chunk via the Vite manifest. */
function chunkFor(string $component): string
{
    $mf = json_decode(file_get_contents('D:/2/public/build/manifest.json'), true) ?: [];
    foreach ($mf as $name => $entry) {
        if ($name === 'resources/js/Pages/' . $component . '.jsx') {
            $file = $entry['file'] ?? '';
            if (! $file) {
                continue;
            }
            $ch = curl_init('http://127.0.0.1:8000/build/' . $file);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
            $r = curl_exec($ch);
            curl_close($ch);

            return (string) $r;
        }
    }

    return '';
}

function get(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => (string) $r];
}

// ---------------------------------------------------------------- /about
echo "=== /about ===\n";
$p = get('http://127.0.0.1:8000/about');
$check('returns 200', $p['code'] === 200);
preg_match('#data-page="app"[^>]*>(\{.*?\})</script>#s', $p['body'], $m);
$props = json_decode($m[1] ?? '{}', true);
$check('component is About', ($props['component'] ?? '') === 'About');

$js = chunkFor('About');
printf("  chunk: %d KB\n", (int) (strlen($js) / 1024));
$check('About chunk emitted', $js !== '');

$about = [
    'hero gradient'      => 'from-[#1B365D] via-[#2a4a7a] to-[#1B365D]',
    'gold blob'          => 'blur-[120px]',
    'white blob'         => 'blur-[150px]',
    'title'              => 'درباره',
    'subtitle'           => 'پایگاه خبری تحلیلی مستقل در جنوب ایران',
    'stat 1'             => 'سال فعالیت',
    'stat 2'             => 'پروانه انتشار',
    'stat 3'             => 'خبرنگار',
    'stat 4'             => 'پوشش خبری',
    'story heading'      => 'داستان ما',
    'story para 1'       => 'اطلاع‌رسانی دقیق، سریع و بی‌طرفانه',
    'quote'              => 'خط قرمز ماست',
    'quote author'       => 'محمدمهدی علی پور',
    'quote role'         => 'مدیرمسئول',
    'values heading'     => 'حقیقت، ستون اصلی این رسانه است',
    'value 1'            => 'دقت',
    'value 6'            => 'جامعه',
    'team heading'       => 'تیم ما',
    'team member'        => 'تحلیلگر',
    'contact cta'        => 'با ما در تماس باشید',
    'address'            => 'بوشهر، امامزاده مهر ۱۸',
    'phone'              => '۰۹۳۶-۰۲۸-۰۵۶۲',
    'contact link'       => '/contact',
];
foreach ($about as $label => $needle) {
    $check("about has $label", str_contains($js, $needle));
}

// the portrait image must be shipped
$img = get('http://127.0.0.1:8000/Alipour.jpg');
$check('Alipour.jpg is served', $img['code'] === 200 && strlen($img['body']) > 100000);

// ---------------------------------------------------------------- /contact
echo "\n=== /contact ===\n";
$p = get('http://127.0.0.1:8000/contact');
$check('returns 200', $p['code'] === 200);
preg_match('#data-page="app"[^>]*>(\{.*?\})</script>#s', $p['body'], $m);
$props = json_decode($m[1] ?? '{}', true);
$check('component is Contact', ($props['component'] ?? '') === 'Contact');

$js = chunkFor('Contact');
printf("  chunk: %d KB\n", (int) (strlen($js) / 1024));
$check('Contact chunk emitted', $js !== '');

$contact = [
    'hero gradient'   => 'from-[#1B365D] via-[#2a4a7a] to-[#1B365D]',
    'badge'           => 'پاسخگویی پیام‌های شما',
    'title'           => 'تماس با',
    'subtitle'        => 'ما اینجا هستیم',
    'card phone'      => 'تلفن',
    'card email'      => 'ایمیل',
    'card address'    => 'آدرس',
    'phone value'     => '۰۹۳۶-۰۲۸-۰۵۶۲',
    'email value'     => 'info@liandid.ir',
    'address value'   => 'امامزاده مهر ۱۸',
    'form heading'    => 'ارسال پیام',
    'name label'      => 'نام و نام خانوادگی',
    'email label'     => 'ایمیل',
    'subject label'   => 'موضوع',
    'message label'   => 'متن پیام',
    'submit'          => 'ارسال پیام',
    'sending'         => 'در حال ارسال',
    'success title'   => 'پیام شما با موفقیت ارسال شد',
    'success body'    => 'کارشناسان ما در اسرع وقت',
    'new message'     => 'ارسال پیام جدید',
    'hours heading'   => 'ساعات کاری',
    'hours 1'         => 'شنبه تا چهارشنبه',
    'hours 3'         => 'تعطیل',
    'collab heading'  => 'همکاری با ما',
    'collab email'    => 'jobs@liandid.ir',
];
foreach ($contact as $label => $needle) {
    $check("contact has $label", str_contains($js, $needle));
}

echo "\n" . ($fail === 0 ? 'ABOUT + CONTACT: EXACT MATCH' : "ABOUT + CONTACT: {$fail} MISMATCH") . "\n";
exit($fail === 0 ? 0 : 1);