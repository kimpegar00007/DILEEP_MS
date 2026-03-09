<?php
// Quick connection test
require_once __DIR__ . '/config/database.php';

echo "=== Database Connection Test ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Test basic query
    $result = $conn->query("SELECT 1 as test, DATABASE() as db, USER() as user");
    $row = $result->fetch();
    
    echo "✓ Connection Successful!\n\n";
    echo "Test Result: " . $row['test'] . "\n";
    echo "Current Database: " . $row['db'] . "\n";
    echo "Current User: " . $row['user'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Connection Failed\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Next Steps:\n";
    echo "1. Run diagnostic: /debug/diagnose-mariadb-permissions.php\n";
    echo "2. Run fix script: /fixes/fix-mariadb-permissions.php\n";
}
?>
