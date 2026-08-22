<?php

$url = 'https://oneball.live/list.json';

// Fetch the JSON data from the URL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Failed to fetch data: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}
curl_close($ch);

$data = json_decode($response, true);

if (!is_array($data)) {
    echo "Invalid JSON data received or empty response.\n";
    exit(1);
}

// Leagues to filter by
$allowed_leagues = [
    'ASEAN Championship',
    'English Premier League'
];

// Filter the data
$filtered_data = array_filter($data, function ($match) use ($allowed_leagues) {
    return isset($match['league_name']) && in_array($match['league_name'], $allowed_leagues);
});

// Reset array keys
$filtered_data = array_values($filtered_data);

// Create output directories
@mkdir('data/playlists', 0777, true);
@mkdir('data/epg', 0777, true);

$m3u = "#EXTM3U\n";
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tv>\n";

foreach ($filtered_data as $match) {
    // 1. Timezone Adjustment (GMT+8 to GMT+7)
    if (isset($match['match_time'])) {
        $timestamp = strtotime($match['match_time']);
        $adjusted_timestamp = $timestamp - 3600; // GMT+7
        $match['match_time'] = date('Y-m-d H:i:s', $adjusted_timestamp);
    } else {
        continue; // Skip if no match time
    }

    $id = $match['nami_id'];
    $title = "{$match['home_team']} vs {$match['away_team']}";

    // Prioritize HD-J, then fallback to HD-K, then fallback to the first available URL
    $stream_url = '';
    if (isset($match['signals']) && is_array($match['signals']) && count($match['signals']) > 0) {
        $hd_j_url = '';
        $hd_k_url = '';

        foreach ($match['signals'] as $signal) {
            if ($signal['label'] === 'HD-J') {
                $hd_j_url = $signal['url'];
                break; // Highest priority found, stop looking
            } elseif ($signal['label'] === 'HD-K') {
                $hd_k_url = $signal['url'];
            }
        }

        if (!empty($hd_j_url)) {
            $stream_url = $hd_j_url;
        } elseif (!empty($hd_k_url)) {
            $stream_url = $hd_k_url;
        } else {
            // Fallback to the first signal if neither HD-J nor HD-K is found
            $stream_url = $match['signals'][0]['url'];
        }
    }

    if (empty($stream_url)) {
        continue; // Skip if no stream URL
    }

    // PLAYLIST
    $m3u .= <<<M3U
#EXTINF:-1 tvg-id="{$id}" group-title="#2. LIVE EVENT",{$title}
#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36
#EXTVLCOPT:http-referrer=https://web.liveplayer.eu/
#EXTVLCOPT:http-origin=https://web.liveplayer.eu
{$stream_url}

M3U;

    // EPG
    // Format required by XMLTV: YYYYMMDDHHMMSS +ZZZZ
    $start_dt = new DateTime($match['match_time'], new DateTimeZone('Asia/Jakarta'));
    $start = $start_dt->format('YmdHis O');

    // Assume the match duration is 2 hours (120 minutes) since end_time is not provided
    $end_dt = clone $start_dt;
    $end_dt->modify('+2 hours');
    $stop = $end_dt->format('YmdHis O');

    $xml .= <<<XML
<channel id="{$id}">
  <display-name>{$title}</display-name>
</channel>
<programme start="{$start}" stop="{$stop}" channel="{$id}">
  <title>{$title}</title>
</programme>

XML;
}

$xml .= "</tv>";

file_put_contents('data/playlists/oneball.m3u8', $m3u);
file_put_contents('data/epg/oneball_epg.xml', $xml);

echo "Playlist & EPG generated successfully.\n";

?>
