<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Only allow administrators to toggle maintenance mode
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$value = isset($_POST['value']) && ($_POST['value'] === '1' || $_POST['value'] === '0') ? $_POST['value'] : null;
if ($value === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid value']);
    http_response_code(400);
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

    // Try update first
    $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
    $stmt->execute([$value]);

    if ($stmt->rowCount() === 0) {
        // Insert new
        $ins = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('maintenance_mode', ?)");
        $ins->execute([$value]);
    }

    echo json_encode(['success' => true, 'message' => $value === '1' ? 'Maintenance mode enabled' : 'Maintenance mode disabled']);
} catch (PDOException $e) {
    // Return a safe message to the client; log the real message to error log
    error_log('toggle-maintenance DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error while updating maintenance mode. Please check server logs.']);
    http_response_code(500);
}
