<?php
/** Checks Persian needles in the built chunks, raw and \u-escaped. */
$dir = 'D:/2/public/build/assets';

$needles = ['۳۰ روز اخیر استان بوشهر', 'اخبار استان بوشهر', '۷ روز اخیر'];

foreach ($needles as $needle) {
    $escaped = implode('', array_map(
        fn ($ch) => mb_ord($ch) > 127 ? sprintf('\u%04x', mb_ord($ch)) : $ch,
        preg_split('//u', $needle, -1, PREG_SPLIT_NO_EMPTY)
    ));

    $rawHits = $escHits = [];
    foreach (glob("$dir/*.js") as $file) {
        $c = file_get_contents($file);
        if (str_contains($c, $needle)) {
            $rawHits[] = basename($file);
        }
        if (str_contains($c, $escaped)) {
            $escHits[] = basename($file);
        }
    }

    printf("%-30s raw: %-28s escaped: %s\n",
        $needle,
        $rawHits ? implode(',', $rawHits) : '-',
        $escHits ? implode(',', $escHits) : '-'
    );
}