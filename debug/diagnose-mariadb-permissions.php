<?php
// Diagnostic script to identify MariaDB permission issues
// This helps troubleshoot error 1130: Host not allowed to connect

require_once __DIR__ . '/../config/database.php';

echo "=== MariaDB Connection Diagnostic Tool ===\n\n";

// Test 1: Check PDO Driver
echo "1. Checking PDO MySQL Driver...\n";
if (extension_loaded('pdo_mysql')) {
    echo "   ✓ PDO MySQL driver is loaded\n";
} else {
    echo "   ✗ PDO MySQL driver is NOT loaded\n";
}
echo "\n";

// Test 2: Display Configuration
echo "2. Current Configuration:\n";
echo "   Host: " . DB_HOST . "\n";
echo "   Port: " . DB_PORT . "\n";
echo "   Database: " . DB_NAME . "\n";
echo "   User: " . DB_USER . "\n";
echo "   Socket: " . (empty(DB_SOCKET) ? 'Not configured' : DB_SOCKET) . "\n";
echo "\n";

// Test 3: Try multiple connection methods
echo "3. Testing Connection Methods:\n";

$connectionMethods = [
    ['type' => 'host', 'value' => '127.0.0.1', 'label' => '127.0.0.1 (TCP)'],
    ['type' => 'host', 'value' => 'localhost', 'label' => 'localhost (TCP)'],
];

$hostname = gethostname();
if ($hostname && $hostname !== false) {
    $connectionMethods[] = ['type' => 'host', 'value' => $hostname, 'label' => $hostname . ' (TCP)'];
}

if (!empty(DB_SOCKET)) {
    $connectionMethods[] = ['type' => 'socket', 'value' => DB_SOCKET, 'label' => 'Unix Socket: ' . DB_SOCKET];
}

$successfulConnection = null;

foreach ($connectionMethods as $method) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];

        if ($method['type'] === 'socket') {
            $dsn = "mysql:unix_socket=" . $method['value'] . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        } else {
            $dsn = "mysql:host=" . $method['value'] . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        }

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        echo "   ✓ " . $method['label'] . " - SUCCESS\n";
        $successfulConnection = $pdo;
    } catch (PDOException $e) {
        echo "   ✗ " . $method['label'] . " - FAILED\n";
        echo "     Error: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 4: If connection successful, check user privileges
if ($successfulConnection) {
    echo "4. Checking User Privileges:\n";
    try {
        $query = "SELECT USER(), DATABASE(), VERSION();";
        $stmt = $successfulConnection->query($query);
        $result = $stmt->fetch();
        
        echo "   Current User: " . $result['USER()'] . "\n";
        echo "   Current Database: " . $result['DATABASE()'] . "\n";
        echo "   MariaDB Version: " . $result['VERSION()'] . "\n";
        echo "\n";

        // Check grants for current user
        echo "5. User Grants:\n";
        $grantsQuery = "SHOW GRANTS FOR CURRENT_USER();";
        $grantsStmt = $successfulConnection->query($grantsQuery);
        $grants = $grantsStmt->fetchAll();
        
        foreach ($grants as $grant) {
            $grantKey = key($grant);
            echo "   " . $grant[$grantKey] . "\n";
        }
        echo "\n";

        // Check all users with database access
        echo "6. All Database Users:\n";
        $usersQuery = "SELECT User, Host FROM mysql.user WHERE User = '" . DB_USER . "' ORDER BY Host;";
        $usersStmt = $successfulConnection->query($usersQuery);
        $users = $usersStmt->fetchAll();
        
        if (empty($users)) {
            echo "   No users found for '" . DB_USER . "'\n";
        } else {
            foreach ($users as $user) {
                echo "   User: " . $user['User'] . " @ " . $user['Host'] . "\n";
            }
        }
        echo "\n";

        // Check database existence
        echo "7. Database Status:\n";
        $dbQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "';";
        $dbStmt = $successfulConnection->query($dbQuery);
        $dbResult = $dbStmt->fetch();
        
        if ($dbResult) {
            echo "   ✓ Database '" . DB_NAME . "' exists\n";
        } else {
            echo "   ✗ Database '" . DB_NAME . "' does NOT exist\n";
        }
        echo "\n";

    } catch (Exception $e) {
        echo "   Error checking privileges: " . $e->getMessage() . "\n";
    }
} else {
    echo "4. Cannot check privileges - no successful connection\n";
    echo "\n";
    echo "RECOMMENDED FIXES:\n";
    echo "1. Ensure MariaDB/MySQL service is running\n";
    echo "2. Create user with proper grants:\n";
    echo "   mysql -u root -p\n";
    echo "   CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';\n";
    echo "   CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';\n";
    echo "   GRANT ALL PRIVILEGES ON dilp_monitoring.* TO 'root'@'localhost';\n";
    echo "   GRANT ALL PRIVILEGES ON dilp_monitoring.* TO 'root'@'127.0.0.1';\n";
    echo "   FLUSH PRIVILEGES;\n";
    echo "\n";
}

echo "=== Diagnostic Complete ===\n";
?>
