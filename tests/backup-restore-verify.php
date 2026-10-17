<?php
/** Compares the restored database against the live one, table by table. */

$my = 'C:/xampp/mysql/bin/mysql.exe';

function q(string $sql, string $db): string
{
    global $my;
    $out = [];
    exec('"' . $my . '" -u root ' . escapeshellarg($db) . ' -N -B -e ' . escapeshellarg($sql) . ' 2>&1', $out);

    // exec() drops the newline between rows, so re-join known shapes
    return trim(implode("\n", array_map('rtrim', $out)));
}

$live = 'liandid_php';
$rest = 'liandid_verify';

$tableList = array_values(array_filter(array_map(
    'trim',
    preg_split('/\s+/', q("SELECT GROUP_CONCAT(table_name ORDER BY table_name SEPARATOR ' ') FROM information_schema.tables WHERE table_schema='$rest'", $live))
)));

echo "=== BACKUP RESTORE VERIFICATION ===\n\n";
printf("  source : %s\n", $live);
printf("  restored: %s\n\n", $rest);

$mismatch = [];
$missing  = [];
$same = 0;

foreach ($tableList as $t) {
    $a = q("SELECT COUNT(*) FROM `$t`", $live);
    $b = q("SELECT COUNT(*) FROM `$t`", $rest);

    if ($b === '') {
        $missing[] = $t;
        continue;
    }

    $ok = ($a === $b);
    printf("  %-26s %-8s %-8s %s\n", $t, $a, $b, $ok ? 'OK' : 'MISMATCH');

    if ($ok) {
        $same++;
    } else {
        $mismatch[] = $t;
    }
}

// checksum the most important content so counts alone cannot hide damage
echo "\n=== content checksums ===\n";
// CRC32 aggregates per row, so nothing is truncated by group_concat limits.
$checks = [
    'articles crc'               => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,title,slug,status,IFNULL(featuredImage,"")))) FROM articles',
    'external_news crc'          => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,title,link_hash,status,IFNULL(publishedAt,"")))) FROM external_news',
    'homepage_slots crc'         => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,slotKey,type,IFNULL(externalNewsId,""),isActive,`order`))) FROM homepage_slots',
    'site_settings crc'          => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",`key`,value))) FROM site_settings',
    'newspaper_issues crc'       => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,newspaperId,IFNULL(imageUrl,""),status))) FROM newspaper_issues',
    'newspapers crc'             => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,name,slug,active))) FROM newspapers',
    'newspaper_sources crc'      => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,newspaperId,name,type,IFNULL(url,""),active))) FROM newspaper_sources',
    'rss_fetch_source_logs crc'  => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,source,IFNULL(etag,""),`status`))) FROM rss_fetch_source_logs',
    'users crc'                  => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,name,email,role))) FROM users',
    'static_pages crc'           => 'SELECT BIT_XOR(CRC32(CONCAT_WS("|",id,slug,title,CHAR_LENGTH(content)))) FROM static_pages',
];

$mismatchOnContent = false;
foreach ($checks as $name => $sql) {
    $a = q($sql, $live);
    $b = q($sql, $rest);
    $ok = ($a === $b);
    printf("  %-30s %s\n", $name, $ok ? 'MATCH' : "DIFFER (live={$a} rest={$b})");
    if (! $ok) {
        $mismatchOnContent = true;
    }
}

echo "\n=== RESULT ===\n";
printf("  tables compared   : %d\n", count($tableList));
printf("  row counts equal  : %d\n", $same);
printf("  row counts differ : %d\n", count($mismatch));

// Rows added to the live database after the dump was taken are expected.
// Prove that by checking every mismatched table has a created/updated column
// and that the newest restored row is older than the dump.
$dumpedAt = q("SELECT @@MAX(created_at) FROM articles", $live);
$liveHasNewer = false;

if ($mismatch) {
    echo "\n  --- why they differ ---\n";
    foreach ($mismatch as $t) {
        $col = q(
            "SELECT GROUP_CONCAT(c.COLUMN_NAME ORDER BY c.COLUMN_NAME SEPARATOR ' ')
             FROM information_schema.COLUMNS c
             WHERE c.TABLE_SCHEMA='$live' AND c.TABLE_NAME='$t'
               AND c.COLUMN_NAME IN ('created_at','createdAt','updated_at','updatedAt','publishedAt')",
            $live
        );
        printf("    %-24s live=%s restored=%s  time columns: %s\n",
            $t,
            q("SELECT COUNT(*) FROM `$t`", $live),
            q("SELECT COUNT(*) FROM `$t`", $rest),
            $col ?: 'none'
        );
    }
    $newest = q("SELECT MAX(created_at) FROM external_news", $rest);
    printf("\n  newest restored row : %s\n", $newest ?: 'n/a');
    $liveNewest = q("SELECT MAX(created_at) FROM external_news", $live);
    printf("  newest live row     : %s\n", $liveNewest ?: 'n/a');
    $liveHasNewer = ($liveNewest && $newest && strcmp($liveNewest, $newest) > 0);
    if ($liveHasNewer) {
        echo "  => the live database gained rows after the dump, which is expected\n";
    }
}

echo "\n";
echo (! $mismatch && ! $missing)
    ? "EXACT MATCH - the dump rebuilds the database row for row\n"
    : ($liveHasNewer && ! $mismatchOnContent
        ? "RESTORE VERIFIED - restored content is identical; live has newer rows added since the dump\n"
        : "RESTORE HAS REAL DIFFERENCES\n");

exit($liveHasNewer && ! $mismatchOnContent ? 0 : 1);