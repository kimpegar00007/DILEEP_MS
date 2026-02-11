<?php
session_start();
require_once 'config/database.php';

$diagnosticResults = [];

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>DILP System - Production Diagnostics</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .container { max-width: 900px; margin-top: 20px; }
        .card { margin-bottom: 20px; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .warn { color: #ffc107; font-weight: bold; }
        .test-item { padding: 10px; border-bottom: 1px solid #eee; }
        .test-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h4>DILP Monitoring System - Production Diagnostics</h4>
            </div>
            <div class='card-body'>";

// Test 1: Database Connection
echo "<div class='test-item'>
    <strong>1. Database Connection</strong><br>";
try {
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "<span class='pass'>✓ PASS</span> - Database connection successful<br>";
        $diagnosticResults['db_connection'] = 'pass';
    } else {
        echo "<span class='fail'>✗ FAIL</span> - Database connection failed<br>";
        $diagnosticResults['db_connection'] = 'fail';
    }
} catch (Exception $e) {
    echo "<span class='fail'>✗ FAIL</span> - " . htmlspecialchars($e->getMessage()) . "<br>";
    $diagnosticResults['db_connection'] = 'fail';
}
echo "</div>";

// Test 2: Database Credentials
echo "<div class='test-item'>
    <strong>2. Database Credentials</strong><br>";
echo "Host: " . htmlspecialchars(DB_HOST) . "<br>";
echo "Database: " . htmlspecialchars(DB_NAME) . "<br>";
echo "User: " . htmlspecialchars(DB_USER) . "<br>";
echo "Port: " . htmlspecialchars(DB_PORT) . "<br>";
echo "</div>";

// Test 3: Beneficiaries Table
echo "<div class='test-item'>
    <strong>3. Beneficiaries Table</strong><br>";
try {
    $stmt = $db->prepare("DESCRIBE beneficiaries");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    if (count($columns) > 0) {
        echo "<span class='pass'>✓ PASS</span> - Table exists with " . count($columns) . " columns<br>";
        $diagnosticResults['beneficiaries_table'] = 'pass';
        echo "<small>Columns: " . implode(", ", array_column($columns, 'Field')) . "</small>";
    } else {
        echo "<span class='fail'>✗ FAIL</span> - Table exists but no columns found<br>";
        $diagnosticResults['beneficiaries_table'] = 'fail';
    }
} catch (Exception $e) {
    echo "<span class='fail'>✗ FAIL</span> - " . htmlspecialchars($e->getMessage()) . "<br>";
    $diagnosticResults['beneficiaries_table'] = 'fail';
}
echo "</div>";

// Test 4: Proponents Table
echo "<div class='test-item'>
    <strong>4. Proponents Table</strong><br>";
try {
    $stmt = $db->prepare("DESCRIBE proponents");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    if (count($columns) > 0) {
        echo "<span class='pass'>✓ PASS</span> - Table exists with " . count($columns) . " columns<br>";
        $diagnosticResults['proponents_table'] = 'pass';
        echo "<small>Columns: " . implode(", ", array_column($columns, 'Field')) . "</small>";
    } else {
        echo "<span class='fail'>✗ FAIL</span> - Table exists but no columns found<br>";
        $diagnosticResults['proponents_table'] = 'fail';
    }
} catch (Exception $e) {
    echo "<span class='fail'>✗ FAIL</span> - " . htmlspecialchars($e->getMessage()) . "<br>";
    $diagnosticResults['proponents_table'] = 'fail';
}
echo "</div>";

// Test 5: Activity Logs Table
echo "<div class='test-item'>
    <strong>5. Activity Logs Table</strong><br>";
try {
    $stmt = $db->prepare("DESCRIBE activity_logs");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    if (count($columns) > 0) {
        echo "<span class='pass'>✓ PASS</span> - Table exists<br>";
        $diagnosticResults['activity_logs_table'] = 'pass';
    } else {
        echo "<span class='fail'>✗ FAIL</span> - Table exists but no columns found<br>";
        $diagnosticResults['activity_logs_table'] = 'fail';
    }
} catch (Exception $e) {
    echo "<span class='fail'>✗ FAIL</span> - " . htmlspecialchars($e->getMessage()) . "<br>";
    $diagnosticResults['activity_logs_table'] = 'fail';
}
echo "</div>";

// Test 6: User Authentication
echo "<div class='test-item'>
    <strong>6. User Authentication</strong><br>";
if (isset($_SESSION['user_id'])) {
    echo "<span class='pass'>✓ PASS</span> - User logged in (ID: " . htmlspecialchars($_SESSION['user_id']) . ")<br>";
    $diagnosticResults['auth'] = 'pass';
} else {
    echo "<span class='warn'>⚠ WARNING</span> - No user session. Login required to test form submission<br>";
    $diagnosticResults['auth'] = 'warn';
}
echo "</div>";

// Test 7: File Permissions
echo "<div class='test-item'>
    <strong>7. File Permissions</strong><br>";
$testFile = __DIR__ . '/test_write.tmp';
try {
    if (file_put_contents($testFile, 'test')) {
        unlink($testFile);
        echo "<span class='pass'>✓ PASS</span> - Write permissions OK<br>";
        $diagnosticResults['permissions'] = 'pass';
    } else {
        echo "<span class='fail'>✗ FAIL</span> - Cannot write to directory<br>";
        $diagnosticResults['permissions'] = 'fail';
    }
} catch (Exception $e) {
    echo "<span class='fail'>✗ FAIL</span> - " . htmlspecialchars($e->getMessage()) . "<br>";
    $diagnosticResults['permissions'] = 'fail';
}
echo "</div>";

// Test 8: PHP Version & Extensions
echo "<div class='test-item'>
    <strong>8. PHP Environment</strong><br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO Extension: " . (extension_loaded('pdo') ? "<span class='pass'>✓ Loaded</span>" : "<span class='fail'>✗ Not Loaded</span>") . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? "<span class='pass'>✓ Loaded</span>" : "<span class='fail'>✗ Not Loaded</span>") . "<br>";
echo "</div>";

// Test 9: Test Insert Operation
echo "<div class='test-item'>
    <strong>9. Test Insert Operation (Beneficiaries)</strong><br>";
if (isset($_SESSION['user_id'])) {
    try {
        $testData = [
            'last_name' => 'TEST',
            'first_name' => 'DIAGNOSTIC',
            'middle_name' => '',
            'suffix' => '',
            'gender' => 'Male',
            'barangay' => 'Test Barangay',
            'municipality' => 'Test Municipality',
            'contact_number' => '09000000000',
            'project_name' => 'Diagnostic Test Project',
            'type_of_worker' => 'Test',
            'amount_worth' => 1000.00,
            'noted_findings' => 'Diagnostic test',
            'date_complied_by_proponent' => null,
            'date_forwarded_to_ro6' => null,
            'rpmt_findings' => '',
            'date_approved' => null,
            'date_forwarded_to_nofo' => null,
            'date_turnover' => null,
            'date_monitoring' => null,
            'latitude' => null,
            'longitude' => null,
            'status' => 'pending'
        ];
        
        $sql = "INSERT INTO beneficiaries (
            last_name, first_name, middle_name, suffix, gender, barangay, municipality,
            contact_number, project_name, type_of_worker, amount_worth, noted_findings,
            date_complied_by_proponent, date_forwarded_to_ro6, rpmt_findings, date_approved,
            date_forwarded_to_nofo, date_turnover, date_monitoring, latitude, longitude,
            status, created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $testData['last_name'], $testData['first_name'], $testData['middle_name'],
            $testData['suffix'], $testData['gender'], $testData['barangay'],
            $testData['municipality'], $testData['contact_number'], $testData['project_name'],
            $testData['type_of_worker'], $testData['amount_worth'], $testData['noted_findings'],
            $testData['date_complied_by_proponent'], $testData['date_forwarded_to_ro6'],
            $testData['rpmt_findings'], $testData['date_approved'], $testData['date_forwarded_to_nofo'],
            $testData['date_turnover'], $testData['date_monitoring'], $testData['latitude'],
            $testData['longitude'], $testData['status'], $_SESSION['user_id'], $_SESSION['user_id']
        ]);
        
        if ($result) {
            $insertId = $db->lastInsertId();
            echo "<span class='pass'>✓ PASS</span> - Test record inserted (ID: " . $insertId . ")<br>";
            echo "<small>Cleaning up test record...</small><br>";
            
            $deleteStmt = $db->prepare("DELETE FROM beneficiaries WHERE id = ?");
            $deleteStmt->execute([$insertId]);
            echo "<span class='pass'>✓ Test record deleted</span>";
            $diagnosticResults['insert_test'] = 'pass';
        } else {
            echo "<span class='fail'>✗ FAIL</span> - Insert failed<br>";
            echo "<small>Error: " . implode(" | ", $stmt->errorInfo()) . "</small>";
            $diagnosticResults['insert_test'] = 'fail';
        }
    } catch (Exception $e) {
        echo "<span class='fail'>✗ FAIL</span> - " . htmlspecialchars($e->getMessage()) . "<br>";
        $diagnosticResults['insert_test'] = 'fail';
    }
} else {
    echo "<span class='warn'>⚠ SKIPPED</span> - Login required for this test<br>";
}
echo "</div>";

// Summary
echo "</div>
    <div class='card-footer'>";
$passCount = count(array_filter($diagnosticResults, fn($v) => $v === 'pass'));
$failCount = count(array_filter($diagnosticResults, fn($v) => $v === 'fail'));
$warnCount = count(array_filter($diagnosticResults, fn($v) => $v === 'warn'));

echo "<strong>Summary:</strong> ";
echo "<span class='pass'>$passCount Passed</span> | ";
echo "<span class='fail'>$failCount Failed</span> | ";
echo "<span class='warn'>$warnCount Warnings</span><br><br>";

if ($failCount > 0) {
    echo "<div class='alert alert-danger'>
        <strong>Issues Found!</strong> Please review the failed tests above and refer to PRODUCTION_DEBUG_GUIDE.md for solutions.
    </div>";
} elseif ($warnCount > 0) {
    echo "<div class='alert alert-warning'>
        <strong>Warnings:</strong> Some tests were skipped or require attention.
    </div>";
} else {
    echo "<div class='alert alert-success'>
        <strong>All Tests Passed!</strong> Your production environment appears to be configured correctly.
    </div>";
}

echo "</div>
    </div>
    <div class='alert alert-info mt-3'>
        <strong>Note:</strong> This diagnostic tool is for troubleshooting only. Remove or restrict access to this file in production for security reasons.
    </div>
</body>
</html>";
?>
