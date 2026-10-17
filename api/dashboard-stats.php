<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/Auth.php';
require_once dirname(__DIR__) . '/models/Dashboard.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// -----------------------------------------------------------------------
// Same data index.php renders on first load, narrowed by the dashboard filters.
// Province scoping lives in Dashboard::getData(): cross-province roles may pick a
// province from Auth::PROVINCES; everyone else is held to their session province.
// -----------------------------------------------------------------------
$filters = [
    'province'      => $auth->isCrossProvince() ? ($_GET['province'] ?? null) : null,
    'year'          => filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: null,
    'approved_only' => (bool) filter_input(INPUT_GET, 'approved_only', FILTER_VALIDATE_BOOLEAN),
];

try {
    $dashboard = new Dashboard();
    echo json_encode(['success' => true] + $dashboard->getData($filters));
} catch (PDOException $e) {
    error_log('Dashboard Stats API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
