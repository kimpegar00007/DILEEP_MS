<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

// The toggle lives on system-admin.php, which is super_admin only.
// Going through Auth also re-checks that the account is still active.
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

$db = Database::getInstance()->getConnection();

$value = isset($_POST['value']) && ($_POST['value'] === '1' || $_POST['value'] === '0') ? $_POST['value'] : null;
if ($value === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid value']);
    exit;
}

try {
    // Ensure the system_settings table exists — create if missing to avoid SQLSTATE[42S02] errors
    $createSQL = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(191) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($createSQL);

    // Upsert (an UPDATE that sets the same value reports 0 rows, so don't branch on rowCount)
    $stmt = $db->prepare(
        "INSERT INTO system_settings (setting_key, setting_value) VALUES ('maintenance_mode', ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute([$value]);
    $auth->logActivity($_SESSION['user_id'], 'update', 'system_settings', 0,
        'Maintenance mode ' . ($value === '1' ? 'enabled' : 'disabled'));

    echo json_encode(['success' => true, 'message' => $value === '1' ? 'Maintenance mode enabled' : 'Maintenance mode disabled']);
} catch (PDOException $e) {
    // Return a safe message to the client; log the real message to error log
    error_log('toggle-maintenance DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error while updating maintenance mode. Please check server logs.']);
}
