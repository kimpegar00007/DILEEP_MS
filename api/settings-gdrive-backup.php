<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$db = Database::getInstance()->getConnection();

// ==================== GET VALID ACCESS TOKEN ====================
function getValidAccessToken($db) {
    $clientId     = getenv('GOOGLE_CLIENT_ID') ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? '');
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: ($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

    if (empty($clientId) || empty($clientSecret)) {
        return ['error' => 'Google API credentials not configured.'];
    }

    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('gdrive_access_token', 'gdrive_refresh_token', 'gdrive_token_expiry')");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        return ['error' => 'Google Drive is not connected.'];
    }

    $accessToken  = $settings['gdrive_access_token'] ?? '';
    $refreshToken = $settings['gdrive_refresh_token'] ?? '';
    $expiry       = $settings['gdrive_token_expiry'] ?? '';

    if (empty($accessToken)) {
        return ['error' => 'Google Drive is not connected. Please connect first.'];
    }

    // Check if token is still valid (with 5 min buffer)
    if (!empty($expiry) && strtotime($expiry) > time() + 300) {
        return ['token' => $accessToken];
    }

    // Token expired — refresh it
    if (empty($refreshToken)) {
        return ['error' => 'Google Drive session expired. Please reconnect.'];
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('[GDrive Backup] cURL error during token refresh: ' . $curlError);
        return ['error' => 'Connection error while refreshing token.'];
    }

    $tokens = json_decode($response, true);

    if ($httpCode !== 200 || empty($tokens['access_token'])) {
        error_log('[GDrive Backup] Token refresh failed: ' . $response);
        return ['error' => 'Google Drive session expired. Please reconnect.'];
    }

    // Update stored tokens
    $updateStmt = $db->prepare(
        "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $updateStmt->execute(['gdrive_access_token', $tokens['access_token']]);
    $updateStmt->execute(['gdrive_token_expiry', date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600))]);

    return ['token' => $tokens['access_token']];
}

// ==================== GENERATE SQL DUMP IN MEMORY ====================
function generateSqlDump($db) {
    $dbName = DB_NAME;
    $sql = "";

    $sql .= "-- Database Backup: $dbName\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Uploaded to Google Drive\n";
    $sql .= "-- --------------------------------------------------------\n\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "START TRANSACTION;\n";
    $sql .= "SET time_zone = \"+00:00\";\n\n";
    $sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $sql .= "/*!40101 SET NAMES utf8mb4 */;\n\n";

    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $tableName) {
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "-- Table structure for table `$tableName`\n";
        $sql .= "-- --------------------------------------------------------\n\n";
        $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";

        $createStmt = $db->query("SHOW CREATE TABLE `$tableName`")->fetch();
        $sql .= $createStmt['Create Table'] . ";\n\n";

        $rowCount = $db->query("SELECT COUNT(*) FROM `$tableName`")->fetchColumn();

        if ($rowCount > 0) {
            $columns = $db->query("SHOW COLUMNS FROM `$tableName`")->fetchAll();
            $colNames = array_map(function($col) { return '`' . $col['Field'] . '`'; }, $columns);
            $colNameStr = implode(', ', $colNames);

            $chunkSize = 500;
            $offset = 0;

            while ($offset < $rowCount) {
                $dataStmt = $db->query("SELECT * FROM `$tableName` LIMIT $chunkSize OFFSET $offset");
                $dataRows = $dataStmt->fetchAll(PDO::FETCH_NUM);

                if (empty($dataRows)) break;

                $sql .= "INSERT INTO `$tableName` ($colNameStr) VALUES\n";

                $rowStrings = [];
                foreach ($dataRows as $row) {
                    $values = array_map(function($val) use ($db) {
                        if ($val === null) return 'NULL';
                        return $db->quote($val);
                    }, $row);
                    $rowStrings[] = '(' . implode(', ', $values) . ')';
                }

                $sql .= implode(",\n", $rowStrings) . ";\n\n";
                $offset += $chunkSize;
            }
        }
    }

    // Triggers
    $triggers = $db->query("SHOW TRIGGERS")->fetchAll();
    if (!empty($triggers)) {
        $sql .= "DELIMITER \$\$\n";
        foreach ($triggers as $trigger) {
            $triggerName = $trigger['Trigger'];
            $createTrigger = $db->query("SHOW CREATE TRIGGER `$triggerName`")->fetch();
            $triggerSql = $createTrigger['SQL Original Statement'] ?? '';
            if ($triggerSql) {
                $sql .= "DROP TRIGGER IF EXISTS `$triggerName`\$\$\n";
                $sql .= $triggerSql . "\$\$\n\n";
            }
        }
        $sql .= "DELIMITER ;\n\n";
    }

    $sql .= "COMMIT;\n";
    $sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

    return $sql;
}

// ==================== FIND OR CREATE BACKUP FOLDER ====================
function getOrCreateFolder($accessToken, $folderName) {
    // Search for existing folder
    $query = "name='$folderName' and mimeType='application/vnd.google-apps.folder' and trashed=false";
    $searchUrl = 'https://www.googleapis.com/drive/v3/files?' . http_build_query(['q' => $query, 'fields' => 'files(id,name)']);

    $ch = curl_init($searchUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!empty($result['files'][0]['id'])) {
        return $result['files'][0]['id'];
    }

    // Create folder
    $metadata = json_encode([
        'name'     => $folderName,
        'mimeType' => 'application/vnd.google-apps.folder',
    ]);

    $ch = curl_init('https://www.googleapis.com/drive/v3/files');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $metadata,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $folder = json_decode($response, true);
    return $folder['id'] ?? null;
}

// ==================== UPLOAD FILE TO GOOGLE DRIVE ====================
function uploadToGoogleDrive($accessToken, $folderId, $fileName, $fileContent) {
    $boundary = 'dilp_backup_boundary_' . uniqid();

    $metadata = json_encode([
        'name'    => $fileName,
        'parents' => [$folderId],
    ]);

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= $metadata . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/sql\r\n\r\n";
    $body .= $fileContent . "\r\n";
    $body .= "--$boundary--";

    $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Content-Length: ' . strlen($body),
        ],
        CURLOPT_TIMEOUT        => 120,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('[GDrive Backup] Upload cURL error: ' . $curlError);
        return ['error' => 'Connection error during upload.'];
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200 || empty($result['id'])) {
        error_log('[GDrive Backup] Upload failed (HTTP ' . $httpCode . '): ' . $response);
        return ['error' => 'Failed to upload backup to Google Drive.'];
    }

    return $result;
}

// ==================== MAIN EXECUTION ====================
try {
    // Get valid access token
    $tokenResult = getValidAccessToken($db);
    if (isset($tokenResult['error'])) {
        echo json_encode(['success' => false, 'message' => $tokenResult['error']]);
        exit;
    }
    $accessToken = $tokenResult['token'];

    // Generate SQL dump
    $sqlDump = generateSqlDump($db);
    $fileName = DB_NAME . '_backup_' . date('Y-m-d_His') . '.sql';

    // Get or create backup folder
    $folderId = getOrCreateFolder($accessToken, 'DILP_Backups');
    if (!$folderId) {
        echo json_encode(['success' => false, 'message' => 'Failed to create backup folder on Google Drive.']);
        exit;
    }

    // Upload the backup
    $uploadResult = uploadToGoogleDrive($accessToken, $folderId, $fileName, $sqlDump);
    if (isset($uploadResult['error'])) {
        echo json_encode(['success' => false, 'message' => $uploadResult['error']]);
        exit;
    }

    // Log the backup activity
    $logStmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $logStmt->execute([
        $_SESSION['user_id'],
        'backup',
        'system',
        0,
        'Uploaded database backup to Google Drive: ' . $fileName,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    $message = 'Backup uploaded successfully as "' . $fileName . '"';
    if (!empty($uploadResult['webViewLink'])) {
        $message .= '. View in Google Drive.';
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    error_log('[GDrive Backup] Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred during backup.']);
}
