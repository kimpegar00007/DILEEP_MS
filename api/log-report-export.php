<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

// =========================================================
// Activity-log entry for a report export or print on reports.php
// (who exported which report, in what format, with which filters).
// =========================================================

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

$formats = ['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV', 'print' => 'Print'];
$reports = ['beneficiaries' => 'Individual Beneficiaries', 'proponents' => 'Group Proponents', 'cqpr' => 'CQPR'];

$format = is_string($_POST['format'] ?? null) ? $_POST['format'] : '';
$report = is_string($_POST['report'] ?? null) ? $_POST['report'] : '';
if (!isset($formats[$format], $reports[$report])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid format or report']);
    exit;
}

$rows    = filter_var($_POST['rows'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
$filters = is_string($_POST['filters'] ?? null) ? trim($_POST['filters']) : '';
$filters = function_exists('mb_substr') ? mb_substr($filters, 0, 400) : substr($filters, 0, 400);

$description = $format === 'print'
    ? sprintf('Printed the %s report (%d rows).', $reports[$report], $rows)
    : sprintf('Exported the %s report as %s (%d rows).', $reports[$report], $formats[$format], $rows);
if ($filters !== '') {
    $description .= ' ' . $filters;
}

$auth->logActivity($_SESSION['user_id'], 'export', 'reports', 0, $description);

echo json_encode(['success' => true]);
