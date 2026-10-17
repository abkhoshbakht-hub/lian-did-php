<?php
/**
 * Extracts helpData from the Next.js help page with correct UTF-8 handling
 * and writes resources/js/data/helpData.js.
 */
$ref = 'C:/Users/LianDesign/AppData/Local/Temp/opencode/liandid-nextjs/src/app/dashboard/help/page.tsx';
$out = 'D:/2/resources/js/data/helpData.js';

$raw = file_get_contents($ref);

// Locate the helpData array
$start = strpos($raw, 'const helpData');
$end   = strpos($raw, "\n];", $start);
if ($start === false || $end === false) {
    fwrite(STDERR, "could not locate helpData in the reference\n");
    exit(1);
}
$end += 3;

$block = substr($raw, $start, $end - $start);

// The file is proper UTF-8; strip the TS type annotation to make it plain JS.
$block = preg_replace('/^const helpData:\s*HelpSection\[\]\s*=/m', 'const helpData =', $block);

$header = "/**\n"
    . " * Admin help content, ported from the Next.js admin help page.\n"
    . " * Sections and items are the same, so the guide matches the old panel.\n"
    . " */\n";

file_put_contents($out, $header . $block . "\nexport default helpData;\n");

// verify
$check = file_get_contents($out);
$persian = preg_match_all('/[\x{0600}-\x{06FF}]/u', $check);
$moji    = preg_match_all('/Ø|Ù|ƒ|â€|ðŸ/', $check);
$items   = preg_match_all("/^\s*\{ id: '/m", $check);
$sections = preg_match_all('/^\s{4}title: /m', $check);

printf("written  : %s (%d bytes)\n", $out, strlen($check));
printf("persian  : %d letters\n", $persian);
printf("mojibake : %d marks\n", $moji);
printf("sections : %d\n", $sections);
printf("items    : %d\n", $items);

exit($persian > 0 && $moji === 0 ? 0 : 1);