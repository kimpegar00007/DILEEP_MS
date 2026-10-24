<?php
// Public, unauthenticated: province totals for the landing page. Aggregates only.
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/Auth.php';
require_once dirname(__DIR__) . '/models/PublicStats.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $stats = new PublicStats();
    header('Cache-Control: public, max-age=600');
    echo json_encode(['success' => true, 'data' => $stats->getTotals()]);
} catch (Throwable $e) {
    error_log('public-stats: ' . $e->getMessage());
    http_response_code(503);
    header('Cache-Control: no-store');
    echo json_encode(['success' => false, 'message' => 'Figures are unavailable right now.']);
}
