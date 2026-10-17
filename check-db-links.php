<?php

/**
 * Checks every link that comes from the DATABASE (hero slots, footer links,
 * newspaper sites, static pages) against the real routes.
 * These are the ones that silently 404 in production.
 */

require 'D:/2/vendor/autoload.php';
$app = require_once 'D:/2/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

$json = trim((new Process(['php', 'artisan', 'route:list', '--json'], 'D:/2'))->mustRun()->getOutput());
$routes = json_decode($json, true);
$uris = [];
foreach ($routes as $r) {
    $uris[] = $r['uri'];
}

$routeOk = function (string $href) use ($uris): bool {
    $p = parse_url($href, PHP_URL_PATH) ?: $href;
    $p = '/' . trim((string) $p, '/');
    if ($p === '/') {
        return true;
    }
    foreach ($uris as $u) {
        $nu = '/' . trim($u, '/');
        if ($nu === $p) {
            return true;
        }
        // compare against parameterised routes
        $re = preg_quote($nu, '#');
        $re = preg_replace('#\\\\\{[^}]+\\\\\}#', '[^/]+', $re);
        if (preg_match('#^' . $re . '$#', $p)) {
            return true;
        }
    }

    return false;
};

$found = [];

// 1. homepage_slots customLink / customImage
foreach (DB::table('homepage_slots')->where('isActive', 1)->get() as $s) {
    if (! empty($s->customLink)) {
        $found[] = ["slot:{$s->slotKey}", $s->customLink];
    }
    if (! empty($s->customImage) && str_starts_with($s->customImage, '/')) {
        $found[] = ["slot-image:{$s->slotKey}", $s->customImage];
    }
}

// 2. site_settings link-ish values
foreach (DB::table('site_settings')->get() as $r) {
    $k = $r->key;
    $v = (string) $r->value;
    if (! preg_match('/(link|url|href|path)$/i', $k)) {
        continue;
    }
    if (str_starts_with($v, '/') && ! preg_match('#^/(storage|images|uploads|logo|og-image|images/|.*\.(png|jpg|jpeg|svg|webp|ico|gif|mp4))$#i', $v)) {
        $found[] = ["setting:$k", $v];
    }
}

// 3. footerLinks JSON
$fl = DB::table('site_settings')->where('key', 'footerLinks')->value('value');
if ($fl) {
    $decoded = json_decode($fl, true);
    if (is_array($decoded)) {
        foreach ($decoded as $i => $item) {
            $href = $item['href'] ?? null;
            if ($href) {
                $found[] = ["footer[$i]:".($item['name'] ?? ''), $href];
            }
        }
    }
}

$broken = [];
foreach ($found as [$where, $href]) {
    if (preg_match('#^(https?:|mailto:|tel:|javascript:)#i', $href)) {
        continue;
    }
    if (str_starts_with($href, '#')) {
        continue;
    }
    if (! $routeOk($href)) {
        $broken[] = [$where, $href];
    }
}

echo "=== DB-DRIVEN LINKS CHECK ===\n";
printf("  checked %d links from database\n\n", count($found));

if (! $broken) {
    echo "  ALL DATABASE LINKS RESOLVE\n";
    exit(0);
}

echo "  BROKEN:\n";
foreach ($broken as [$where, $href]) {
    printf("    %-34s -> %s\n", $where, $href);
}
printf("\n  total broken: %d\n", count($broken));
exit(1);