<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$action = $_GET['action'] ?? '';
$db = Database::getInstance()->getConnection();

// Ensure system_settings table exists
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
} catch (PDOException $e) {
    error_log('[GDrive Auth] Failed to create system_settings table: ' . $e->getMessage());
}

// Load Google credentials from environment
$clientId     = getenv('GOOGLE_CLIENT_ID') ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: ($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$redirectUri  = getenv('GOOGLE_REDIRECT_URI') ?: ($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

// Auto-detect redirect URI if not set
if (empty($redirectUri)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirectUri = $scheme . '://' . $host . '/dilp-system/api/settings-gdrive-auth.php?action=callback';
}

if (empty($clientId) || empty($clientSecret)) {
    if ($action === 'disconnect') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Google Drive is not configured.']);
    } else {
        header('Location: ../settings.php?gdrive=error&message=' . urlencode('Google API credentials not configured.'));
    }
    exit;
}

// ==================== CONNECT ====================
if ($action === 'connect') {
    $scopes = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/userinfo.email';
    $state = bin2hex(random_bytes(16));
    $_SESSION['gdrive_oauth_state'] = $state;

    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => $scopes,
        'access_type'   => 'offline',
        'prompt'        => 'consent',
        'state'         => $state,
    ]);

    header('Location: ' . $authUrl);
    exit;
}

// ==================== CALLBACK ====================
if ($action === 'callback') {
    $code  = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';

    if ($error) {
        header('Location: ../settings.php?gdrive=error&message=' . urlencode('Authorization denied: ' . $error));
        exit;
    }

    if (empty($code)) {
        header('Location: ../settings.php?gdrive=error&message=' . urlencode('No authorization code received.'));
        exit;
    }

    // Validate state parameter
    if (empty($state) || $state !== ($_SESSION['gdrive_oauth_state'] ?? '')) {
        header('Location: ../settings.php?gdrive=error&message=' . urlencode('Invalid state parameter. Please try again.'));
        exit;
    }
    unset($_SESSION['gdrive_oauth_state']);

    // Exchange authorization code for tokens
    $tokenData = [
        'code'          => $code,
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ];

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($tokenData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('[GDrive Auth] cURL error: ' . $curlError);
        header('Location: ../settings.php?gdrive=error&message=' . urlencode('Connection error. Please try again.'));
        exit;
    }

    $tokens = json_decode($response, true);

    if ($httpCode !== 200 || empty($tokens['access_token'])) {
        $errorMsg = $tokens['error_description'] ?? $tokens['error'] ?? 'Failed to obtain access token.';
        error_log('[GDrive Auth] Token exchange failed: ' . $response);
        header('Location: ../settings.php?gdrive=error&message=' . urlencode($errorMsg));
        exit;
    }

    // Get user email
    $userEmail = '';
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokens['access_token']],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $userInfoResponse = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($userInfoResponse, true);
    if (!empty($userInfo['email'])) {
        $userEmail = $userInfo['email'];
    }

    // Store tokens in system_settings
    $settings = [
        'gdrive_access_token'  => $tokens['access_token'],
        'gdrive_refresh_token' => $tokens['refresh_token'] ?? '',
        'gdrive_token_expiry'  => date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600)),
        'gdrive_user_email'    => $userEmail,
    ];

    foreach ($settings as $key => $value) {
        $stmt = $db->prepare(
            "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$key, $value]);
    }

    // Log the connection
    $logStmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $logStmt->execute([
        $_SESSION['user_id'],
        'update',
        'system_settings',
        0,
        'Connected Google Drive account: ' . $userEmail,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    header('Location: ../settings.php?gdrive=connected');
    exit;
}

// ==================== DISCONNECT ====================
if ($action === 'disconnect') {
    header('Content-Type: application/json');

    try {
        // Revoke token if possible
        $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gdrive_access_token'");
        $row = $stmt->fetch();
        if ($row && !empty($row['setting_value'])) {
            $ch = curl_init('https://oauth2.googleapis.com/revoke?token=' . urlencode($row['setting_value']));
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT        => 10,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }

        // Remove stored tokens
        $db->exec("DELETE FROM system_settings WHERE setting_key LIKE 'gdrive_%'");

        // Log the disconnection
        $logStmt = $db->prepare(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $logStmt->execute([
            $_SESSION['user_id'],
            'update',
            'system_settings',
            0,
            'Disconnected Google Drive account',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        echo json_encode(['success' => true, 'message' => 'Google Drive disconnected.']);
    } catch (PDOException $e) {
        error_log('[GDrive Auth] Disconnect error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to disconnect. Please try again.']);
    }
    exit;
}

http_response_code(400);
echo 'Invalid action.';
