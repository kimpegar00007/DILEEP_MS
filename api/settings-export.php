<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$table = $_GET['table'] ?? '';
$allowedTables = ['beneficiaries', 'proponents'];

if (!in_array($table, $allowedTables, true)) {
    http_response_code(400);
    echo 'Invalid table specified.';
    exit;
}

$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

// Validate date formats (YYYY-MM-DD)
if ($dateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    http_response_code(400);
    echo 'Invalid date_from format. Use YYYY-MM-DD.';
    exit;
}
if ($dateTo && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    http_response_code(400);
    echo 'Invalid date_to format. Use YYYY-MM-DD.';
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $sql = "SELECT * FROM `$table` WHERE 1=1";
    $params = [];

    if ($dateFrom) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $dateTo;
    }

    $sql .= " ORDER BY id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        http_response_code(404);
        $msg = 'No data found in the ' . $table . ' table';
        if ($dateFrom && $dateTo) {
            $msg .= ' for the selected date range (' . $dateFrom . ' to ' . $dateTo . ')';
        }
        echo $msg . '.';
        exit;
    }

    $filename = $table . '_export_' . date('Y-m-d_His');
    if ($dateFrom && $dateTo) {
        $filename .= '_' . $dateFrom . '_to_' . $dateTo;
    }
    $filename .= '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Write header row
    fputcsv($output, array_keys($rows[0]));

    // Write data rows
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);

    // Log the export activity
    $logStmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $logStmt->execute([
        $_SESSION['user_id'],
        'export',
        $table,
        0,
        'Exported ' . count($rows) . ' records from ' . $table . ' as CSV' . ($dateFrom && $dateTo ? ' (' . $dateFrom . ' to ' . $dateTo . ')' : ''),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

} catch (PDOException $e) {
    error_log('[Settings Export] Database error: ' . $e->getMessage());
    http_response_code(500);
    echo 'An error occurred while exporting data.';
}
