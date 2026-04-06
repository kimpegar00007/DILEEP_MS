<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$barangay = filter_input(INPUT_GET, 'barangay', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$municipality = filter_input(INPUT_GET, 'municipality', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (empty($municipality)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Municipality is required']);
    exit;
}

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: DILP-Monitoring-System/1.0 (contact@dilp.gov.ph)',
        'timeout' => 10
    ]
]);

function tryGeocode($query, $context) {
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $query,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1
    ]);
    
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
    // Try with barangay first
    $searchQueries[] = $barangay . ', ' . $municipality . ', Negros Occidental, Philippines';
    $searchQueries[] = 'Barangay ' . $barangay . ', ' . $municipality . ', Negros Occidental, Philippines';
}

// Try municipality/city variations
$searchQueries[] = $municipality . ', Negros Occidental, Philippines';

// Special handling for cities (like Bacolod City)
if (stripos($municipality, 'City') !== false) {
    $searchQueries[] = $municipality . ', Philippines';
    // Also try without "City" suffix
    $cityName = trim(str_ireplace('City', '', $municipality));
    $searchQueries[] = $cityName . ' City, Negros Occidental, Philippines';
    $searchQueries[] = $cityName . ', Negros Occidental, Philippines';
}

$result = null;
$searchedQuery = '';

try {
    foreach ($searchQueries as $query) {
        $searchedQuery = $query;
        $result = tryGeocode($query, $context);
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
