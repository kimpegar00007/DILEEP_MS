<?php
// Test script to verify database connection and user authentication
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/Auth.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connection successful<br>";
    
    // Test users table structure
    echo "<h3>Users Table Structure:</h3>";
    $stmt = $db->query("DESCRIBE users");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test user query
    echo "<h3>Test User Query (kayzel):</h3>";
    $stmt = $db->prepare("SELECT id, username, email, role, province, full_name, is_active FROM users WHERE username = ?");
    $stmt->execute(['kayzel']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        echo "✓ User query successful<br>";
    } else {
        echo "✗ User not found<br>";
    }
    
    // Test Auth class
    echo "<h3>Auth Class Test:</h3>";
    $auth = new Auth();
    echo "✓ Auth class instantiated successfully<br>";
    
    // Test login
    echo "<h3>Login Test (kayzel):</h3>";
    $loginResult = $auth->login('kayzel', 'kayzel123');
    if ($loginResult) {
        echo "✓ Login successful<br>";
        echo "Session data:<br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
    } else {
        echo "✗ Login failed - check password<br>";
    }
    
    // Test all tables exist
    echo "<h3>Required Tables Check:</h3>";
    $requiredTables = [
        'users', 'beneficiaries', 'proponents', 'activity_logs',
        'provinces', 'user_provinces', 'org_chart', 'system_settings'
    ];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table exists<br>";
        } else {
            echo "✗ $table missing<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
    echo "Error code: " . $e->getCode() . "<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
