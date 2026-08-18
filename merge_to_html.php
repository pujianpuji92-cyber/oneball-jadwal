<?php
/**
 * Merge master M3U8 into existing HTML playlist
 * RAW M3U (NO HTML ESCAPE, NO #EXTM3U)
 */

$sourceM3U  = "data/playlists/oneball.m3u8";
$targetHTML = "data/playlists/oneball.html";

if (!file_exists($sourceM3U) || !file_exists($targetHTML)) {
    fwrite(STDERR, "Source or target file not found\n");
    exit(1);
}

$m3uLines = file($sourceM3U, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$html     = file_get_contents($targetHTML);

/* =========================
   FILTER M3U CONTENT
   ========================= */
$filtered = [];

foreach ($m3uLines as $line) {

    // HAPUS header M3U
    if (trim($line) === '#EXTM3U') {
        continue;
    }

    $filtered[] = $line;
}

$m3uContent = implode("\n", $filtered);

/* =========================
   REPLACE BETWEEN MARKERS
   ========================= */
$pattern = '/<!-- START AUTO ONEBALL -->(.*?)<!-- END AUTO ONEBALL -->/s';

$replacement = <<<HTML
<!-- START AUTO ONEBALL -->
{$m3uContent}
<!-- END AUTO ONEBALL -->
HTML;

if (!preg_match($pattern, $html)) {
    fwrite(STDERR, "Marker not found in HTML\n");
    exit(1);
}

$html = preg_replace($pattern, $replacement, $html);

file_put_contents($targetHTML, $html);

echo "Merged RAW M3U into oneball.html\n";
