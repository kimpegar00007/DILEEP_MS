<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$barangay = filter_input(INPUT_GET, 'barangay', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$municipality = filter_input(INPUT_GET, 'municipality', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$province = filter_input(INPUT_GET, 'province', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (empty($municipality)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Municipality is required']);
    exit;
}

if (empty($province)) {
    $province = 'Negros Occidental';
}

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: DILP-Monitoring-System/1.0 (contact@dilp.gov.ph)',
        'timeout' => 10
    ]
]);

function getProvinceViewbox($province) {
    $bounds = [
        'Negros Occidental' => ['left' => 122.0, 'bottom' => 9.0, 'right' => 124.0, 'top' => 12.0],
        'Negros Oriental' => ['left' => 122.5, 'bottom' => 9.0, 'right' => 123.5, 'top' => 10.5],
        'Siquijor' => ['left' => 123.0, 'bottom' => 9.0, 'right' => 123.8, 'top' => 9.5]
    ];

    return $bounds[$province] ?? null;
}

function tryGeocode($query, $province, $context) {
    $params = [
        'q' => $query,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1,
        'countrycodes' => 'ph',
        'accept-language' => 'en'
    ];

    $viewbox = getProvinceViewbox($province);
    if ($viewbox) {
        $params['viewbox'] = implode(',', [$viewbox['left'], $viewbox['bottom'], $viewbox['right'], $viewbox['top']]);
        $params['bounded'] = 1;
    }

    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return null;
    
    $data = json_decode($response, true);
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        return $data[0];
    }
    return null;
}

$searchQueries = [];

// Build search queries with multiple fallback options
if (!empty($barangay)) {
    $searchQueries[] = $barangay . ', ' . $municipality . ', ' . $province . ', Philippines';
    $searchQueries[] = 'Barangay ' . $barangay . ', ' . $municipality . ', ' . $province . ', Philippines';
}

// Try municipality/city variations
$searchQueries[] = $municipality . ', ' . $province . ', Philippines';

// Special handling for cities (like Bacolod City)
if (stripos($municipality, 'City') !== false) {
    $searchQueries[] = $municipality . ', Philippines';
    $cityName = trim(str_ireplace('City', '', $municipality));
    if ($cityName !== '') {
        $searchQueries[] = $cityName . ' City, ' . $province . ', Philippines';
        $searchQueries[] = $cityName . ', ' . $province . ', Philippines';
    }
}

$result = null;
$searchedQuery = '';

try {
    foreach ($searchQueries as $query) {
        $searchedQuery = $query;
        $result = tryGeocode($query, $province, $context);
        if ($result) break;
        usleep(100000); // 100ms delay between requests to respect rate limits
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'display_name' => $result['display_name'] ?? $searchedQuery
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Location not found. Please enter coordinates manually.',
            'searched' => $searchedQuery
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Geocoding Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Geocoding service error']);
}
