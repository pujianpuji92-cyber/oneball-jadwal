<?php

header('Content-Type: application/json');

$url = 'https://oneball.live/list.json';

// Fetch the JSON data from the URL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// Adding a User-Agent just in case the server requires it
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$response = curl_exec($ch);

if(curl_errno($ch)){
    echo json_encode(['error' => 'Failed to fetch data: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid JSON data received or empty response.']);
    exit;
}

// Leagues to filter by
$allowed_leagues = [
    'ASEAN Championship',
    'International Club Friendly'
];

// Filter the data
$filtered_data = array_filter($data, function ($match) use ($allowed_leagues) {
    return isset($match['league_name']) && in_array($match['league_name'], $allowed_leagues);
});

// Reset array keys to ensure it's a JSON array and not a JSON object
$filtered_data = array_values($filtered_data);

// Output the filtered JSON
echo json_encode($filtered_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
