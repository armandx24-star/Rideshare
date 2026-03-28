<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireRole('user');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'search';


function fetchUrl($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'RideShare-App/1.0 (contact@rideshare.local)',
            CURLOPT_HTTPHEADER     => ['Accept-Language: en', 'Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false, // needed on some WAMP setups
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($response !== false && $httpCode === 200) ? $response : false;
    }

    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: RideShare-App/1.0 (contact@rideshare.local)\r\nAccept-Language: en\r\n",
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);
    return @file_get_contents($url, false, $ctx);
}

if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode([]); exit(); }

    $url = 'https://nominatim.openstreetmap.org/search?format=json&q='
         . urlencode($q) . '&limit=6&accept-language=en&addressdetails=0';

    $response = fetchUrl($url);
    if ($response === false) {
        exit();
    }
    $data = json_decode($response, true);
    echo json_encode(is_array($data) ? $data : []);

} elseif ($action === 'reverse') {
    $lat = (float)($_GET['lat'] ?? 0);
    $lng = (float)($_GET['lng'] ?? 0);

    if (!$lat || !$lng) {
        echo json_encode(['display_name' => '']); exit();
    }

    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&accept-language=en";
    $response = fetchUrl($url);
    if ($response === false) {
        echo json_encode(['display_name' => round($lat, 4) . ', ' . round($lng, 4)]);
        exit();
    }
    $data = json_decode($response, true);
    echo json_encode(is_array($data) ? $data : ['display_name' => round($lat, 4) . ', ' . round($lng, 4)]);

} else {
    echo json_encode(['error' => 'Unknown action']);
}
