<?php
/**
 * Simple DB connection test - DELETE after use
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== Database Connection Test ===\n\n";

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    echo "[OK] .env file exists\n";
    $envFile = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envFile);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
} else {
    echo "[WARN] .env file NOT found\n";
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'dilp_monitoring';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$socket = getenv('DB_SOCKET') ?: '';

echo "\nConnection Parameters:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  Database: $dbname\n";
echo "  User: $user\n";
echo "  Password: " . (empty($pass) ? '(empty)' : str_repeat('*', strlen($pass))) . "\n";
echo "  Socket: " . (empty($socket) ? '(none)' : $socket) . "\n\n";

try {
    if (!empty($socket)) {
        $dsn = "mysql:unix_socket=$socket;dbname=$dbname;charset=utf8mb4";
    } else {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    }
    
    echo "DSN: $dsn\n\n";
    echo "Attempting connection...\n";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "[OK] Connection successful!\n";
    echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM proponents");
    $row = $stmt->fetch();
    echo "Proponents count: " . $row['cnt'] . "\n";
    
    echo "\n[SUCCESS] Database connection is working!\n";
    
} catch (PDOException $e) {
    echo "\n[ERROR] Connection failed!\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "DELETE THIS FILE IMMEDIATELY!\n";
echo "</pre>";
