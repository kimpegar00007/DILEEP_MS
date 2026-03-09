<?php
// Fix script for MariaDB Error 1130: Host not allowed to connect
// This script attempts to fix user permissions issues

require_once __DIR__ . '/../config/database.php';

echo "=== MariaDB Permission Fix Script ===\n\n";

// First, try to connect with any available method
$connection = null;
$connectionMethods = [
    ['type' => 'host', 'value' => '127.0.0.1'],
    ['type' => 'host', 'value' => 'localhost'],
];

$hostname = gethostname();
if ($hostname && $hostname !== false) {
    $connectionMethods[] = ['type' => 'host', 'value' => $hostname];
}

if (!empty(DB_SOCKET)) {
    array_unshift($connectionMethods, ['type' => 'socket', 'value' => DB_SOCKET]);
}

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

        $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        echo "✓ Connected successfully via " . ($method['type'] === 'socket' ? 'Unix Socket' : $method['value']) . "\n\n";
        break;
    } catch (PDOException $e) {
        continue;
    }
}

if (!$connection) {
    // Try to connect to mysql database without specifying dilp_monitoring
    echo "Cannot connect to dilp_monitoring database. Attempting to connect to mysql database...\n\n";
    
    foreach ($connectionMethods as $method) {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ];

            if ($method['type'] === 'socket') {
                $dsn = "mysql:unix_socket=" . $method['value'] . ";dbname=mysql;charset=utf8mb4";
            } else {
                $dsn = "mysql:host=" . $method['value'] . ";port=" . DB_PORT . ";dbname=mysql;charset=utf8mb4";
            }

            $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            echo "✓ Connected to mysql database via " . ($method['type'] === 'socket' ? 'Unix Socket' : $method['value']) . "\n\n";
            break;
        } catch (PDOException $e) {
            continue;
        }
    }
}

if (!$connection) {
    echo "✗ Cannot establish any database connection.\n";
    echo "Please ensure:\n";
    echo "1. MariaDB/MySQL service is running\n";
    echo "2. Credentials are correct (User: " . DB_USER . ", Password: " . (empty(DB_PASS) ? 'empty' : '***') . ")\n";
    echo "3. Run the diagnostic script: /debug/diagnose-mariadb-permissions.php\n";
    exit(1);
}

echo "Attempting to fix user permissions...\n\n";

$fixedIssues = [];
$errors = [];

// Fix 1: Ensure database exists
try {
    echo "1. Checking/Creating database '" . DB_NAME . "'...\n";
    $connection->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "   ✓ Database ready\n";
    $fixedIssues[] = "Database verified/created";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Database creation failed: " . $e->getMessage();
}

// Fix 2: Create/Update user for localhost
try {
    echo "2. Setting up user 'root'@'localhost'...\n";
    $connection->exec("CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';");
    $connection->exec("GRANT ALL PRIVILEGES ON `" . DB_NAME . "`.* TO 'root'@'localhost';");
    echo "   ✓ User 'root'@'localhost' configured\n";
    $fixedIssues[] = "User root@localhost configured";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "localhost user setup failed: " . $e->getMessage();
}

// Fix 3: Create/Update user for 127.0.0.1
try {
    echo "3. Setting up user 'root'@'127.0.0.1'...\n";
    $connection->exec("CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';");
    $connection->exec("GRANT ALL PRIVILEGES ON `" . DB_NAME . "`.* TO 'root'@'127.0.0.1';");
    echo "   ✓ User 'root'@'127.0.0.1' configured\n";
    $fixedIssues[] = "User root@127.0.0.1 configured";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "127.0.0.1 user setup failed: " . $e->getMessage();
}

// Fix 4: Create/Update user for hostname if available
if ($hostname && $hostname !== false) {
    try {
        echo "4. Setting up user 'root'@'" . $hostname . "'...\n";
        $connection->exec("CREATE USER IF NOT EXISTS 'root'@'" . $hostname . "' IDENTIFIED BY '';");
        $connection->exec("GRANT ALL PRIVILEGES ON `" . DB_NAME . "`.* TO 'root'@'" . $hostname . "';");
        echo "   ✓ User 'root'@'" . $hostname . "' configured\n";
        $fixedIssues[] = "User root@" . $hostname . " configured";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
        $errors[] = "Hostname user setup failed: " . $e->getMessage();
    }
}

// Fix 5: Flush privileges
try {
    echo "5. Flushing privileges...\n";
    $connection->exec("FLUSH PRIVILEGES;");
    echo "   ✓ Privileges flushed\n";
    $fixedIssues[] = "Privileges flushed";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Privilege flush failed: " . $e->getMessage();
}

echo "\n=== Summary ===\n";
echo "Fixed Issues:\n";
foreach ($fixedIssues as $issue) {
    echo "  ✓ " . $issue . "\n";
}

if (!empty($errors)) {
    echo "\nErrors Encountered:\n";
    foreach ($errors as $error) {
        echo "  ✗ " . $error . "\n";
    }
}

echo "\n=== Next Steps ===\n";
echo "1. Test the connection by visiting: /debug/diagnose-mariadb-permissions.php\n";
echo "2. If issues persist, check MariaDB configuration in /Applications/XAMPP/xamppfiles/etc/my.cnf\n";
echo "3. Ensure bind-address is set to 0.0.0.0 or 127.0.0.1\n";
echo "4. Restart MariaDB service if configuration was changed\n";

$connection = null;
?>
