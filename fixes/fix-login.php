<?php
/**
 * Emergency Login Fix Script
 * This script fixes the admin user password hash in the database
 * Run this once if you've already imported the database with the incorrect hash
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // The correct bcrypt hash for password 'admin123'
    $correctHash = '$2y$12$cxubeCJxgDoHaci9zO4Ud.b7uJ7PQQpWfOafrfLY2efdUQGNuRDLi';
    
    // Update the admin user's password hash
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$correctHash]);
    
    echo "✅ SUCCESS: Admin password hash has been corrected!\n";
    echo "You can now login with:\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Make sure the database is set up and the connection is working.\n";
}
?>
