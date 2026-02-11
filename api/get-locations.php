<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400');

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action parameter is required']);
    exit;
}

$jsonBasePath = dirname(__DIR__) . '/philippines-region-province-citymun-brgy-master/json/';

try {
    switch ($action) {
        case 'cities':
            $citiesData = file_get_contents($jsonBasePath . 'refcitymun.json');
            if ($citiesData === false) {
                throw new Exception('Failed to load cities data');
            }
            $cities = json_decode($citiesData, true);
            
            if (!isset($cities['RECORDS']) || !is_array($cities['RECORDS'])) {
                throw new Exception('Invalid cities data format');
            }
            
            $negrosOccidentalProvCode = '0645';
            
            $cityList = [];
            foreach ($cities['RECORDS'] as $city) {
                if (isset($city['citymunCode']) && isset($city['citymunDesc']) && isset($city['provCode'])) {
                    if ($city['provCode'] === $negrosOccidentalProvCode) {
                        $cityList[] = [
                            'code' => htmlspecialchars($city['citymunCode'], ENT_QUOTES, 'UTF-8'),
                            'name' => htmlspecialchars($city['citymunDesc'], ENT_QUOTES, 'UTF-8')
                        ];
                    }
                }
            }
            
            usort($cityList, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            echo json_encode(['success' => true, 'data' => $cityList], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'barangays':
            $cityCode = filter_input(INPUT_GET, 'city_code', FILTER_SANITIZE_STRING);
            
            if (empty($cityCode)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'City code is required']);
                exit;
            }
            
            if (!preg_match('/^[0-9]{6}$/', $cityCode)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid city code format']);
                exit;
            }
            
            $barangaysData = file_get_contents($jsonBasePath . 'refbrgy.json');
            if ($barangaysData === false) {
                throw new Exception('Failed to load barangays data');
            }
            $barangays = json_decode($barangaysData, true);
            
            if (!isset($barangays['RECORDS']) || !is_array($barangays['RECORDS'])) {
                throw new Exception('Invalid barangays data format');
            }
            
            $barangayList = [];
            foreach ($barangays['RECORDS'] as $barangay) {
                if (isset($barangay['citymunCode']) && $barangay['citymunCode'] === $cityCode) {
                    if (isset($barangay['brgyCode']) && isset($barangay['brgyDesc'])) {
                        $barangayList[] = [
                            'code' => htmlspecialchars($barangay['brgyCode'], ENT_QUOTES, 'UTF-8'),
                            'name' => htmlspecialchars($barangay['brgyDesc'], ENT_QUOTES, 'UTF-8')
                        ];
                    }
                }
            }
            
            usort($barangayList, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            echo json_encode(['success' => true, 'data' => $barangayList], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Location API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}
