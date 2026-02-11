<?php
/**
 * Diagnostic script to test proponent creation on production.
 * Upload this file, run it once, then DELETE it immediately.
 */
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin']);

header('Content-Type: text/plain; charset=utf-8');
echo "=== Proponent Creation Diagnostic ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "[OK] Database connection established\n";
    echo "PDO Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "Server Version: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "Emulate Prepares: " . ($db->getAttribute(PDO::ATTR_EMULATE_PREPARES) ? 'ON' : 'OFF') . "\n";
    echo "Error Mode: " . $db->getAttribute(PDO::ATTR_ERRMODE) . " (2=EXCEPTION)\n\n";

    // Check proponents table structure
    echo "--- Table Structure ---\n";
    $cols = $db->query("SHOW COLUMNS FROM proponents")->fetchAll();
    echo "Columns in proponents table: " . count($cols) . "\n";
    foreach ($cols as $col) {
        echo "  " . $col['Field'] . " | " . $col['Type'] . " | " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " | Default: " . ($col['Default'] ?? 'none') . "\n";
    }
    echo "\n";

    // Check current row count
    $countBefore = $db->query("SELECT COUNT(*) as cnt FROM proponents")->fetch()['cnt'];
    echo "Current proponent count: $countBefore\n\n";

    // Test a minimal INSERT
    echo "--- Testing Minimal INSERT ---\n";
    $testSql = "INSERT INTO proponents (
        proponent_type, date_received, noted_findings, control_number, number_of_copies,
        date_copies_received, district, proponent_name, project_title, amount,
        number_of_associations, total_beneficiaries, male_beneficiaries, female_beneficiaries,
        type_of_beneficiaries, category, recipient_barangays, letter_of_intent_date,
        date_forwarded_to_ro6, rpmt_findings, date_complied_by_proponent,
        date_complied_by_proponent_nofo, date_forwarded_to_nofo, date_approved,
        date_check_release, check_number, check_date_issued, or_number, or_date_issued,
        date_turnover, date_implemented, date_liquidated, liquidation_deadline, date_monitoring,
        source_of_funds, latitude, longitude, status, created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    echo "Preparing statement...\n";
    $stmt = $db->prepare($testSql);
    echo "[OK] Statement prepared\n";

    $params = [
        'LGU-associated',   // proponent_type
        null,                // date_received
        'DIAGNOSTIC TEST',   // noted_findings
        null,                // control_number
        0,                   // number_of_copies
        null,                // date_copies_received
        'Test District',     // district
        'DIAG TEST Proponent', // proponent_name
        'DIAG TEST Project',   // project_title
        10000.00,            // amount
        1,                   // number_of_associations
        10,                  // total_beneficiaries
        5,                   // male_beneficiaries
        5,                   // female_beneficiaries
        'Test Type',         // type_of_beneficiaries
        'Formation',         // category
        'Test Barangay',     // recipient_barangays
        null,                // letter_of_intent_date
        null,                // date_forwarded_to_ro6
        '',                  // rpmt_findings
        null,                // date_complied_by_proponent
        null,                // date_complied_by_proponent_nofo
        null,                // date_forwarded_to_nofo
        null,                // date_approved
        null,                // date_check_release
        null,                // check_number
        null,                // check_date_issued
        null,                // or_number
        null,                // or_date_issued
        null,                // date_turnover
        null,                // date_implemented
        null,                // date_liquidated
        null,                // liquidation_deadline
        null,                // date_monitoring
        '',                  // source_of_funds
        null,                // latitude
        null,                // longitude
        'pending',           // status
        $_SESSION['user_id'] ?? null, // created_by
        $_SESSION['user_id'] ?? null  // updated_by
    ];

    echo "Parameter count: " . count($params) . "\n";
    echo "Session user_id: " . var_export($_SESSION['user_id'] ?? null, true) . "\n";
    echo "Executing INSERT...\n";

    $result = $stmt->execute($params);
    echo "Execute result: " . var_export($result, true) . "\n";
    echo "Row count: " . $stmt->rowCount() . "\n";

    $lastId = $db->lastInsertId();
    echo "lastInsertId() raw: " . var_export($lastId, true) . "\n";
    echo "lastInsertId() as int: " . (int)$lastId . "\n";
    echo "lastInsertId() truthiness: " . ($lastId ? 'TRUTHY' : 'FALSY') . "\n";

    $lastIdQuery = $db->query("SELECT LAST_INSERT_ID() AS id")->fetch();
    echo "LAST_INSERT_ID() via query: " . var_export($lastIdQuery['id'], true) . "\n";

    // Verify the row exists
    $countAfter = $db->query("SELECT COUNT(*) as cnt FROM proponents")->fetch()['cnt'];
    echo "\nProponent count after: $countAfter (was $countBefore)\n";

    if ($countAfter > $countBefore) {
        echo "[OK] INSERT SUCCEEDED - row was created\n";
        
        // Clean up the test row
        $deleteId = (int)$lastId ?: (int)$lastIdQuery['id'];
        if ($deleteId > 0) {
            $db->exec("DELETE FROM proponents WHERE id = $deleteId");
            echo "[OK] Test row deleted (id=$deleteId)\n";
        } else {
            $db->exec("DELETE FROM proponents WHERE noted_findings = 'DIAGNOSTIC TEST' AND proponent_name = 'DIAG TEST Proponent'");
            echo "[OK] Test row deleted by name match\n";
        }
    } else {
        echo "[FAIL] INSERT did NOT create a row\n";
    }

    // Also check if there are duplicate proponents from failed attempts
    echo "\n--- Checking for potential duplicates from previous attempts ---\n";
    $recent = $db->query("SELECT id, proponent_name, project_title, created_at FROM proponents ORDER BY id DESC LIMIT 5")->fetchAll();
    foreach ($recent as $r) {
        echo "  ID: {$r['id']} | {$r['proponent_name']} | {$r['project_title']} | {$r['created_at']}\n";
    }

} catch (PDOException $e) {
    echo "\n[PDO ERROR] " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
echo "IMPORTANT: Delete this file after use!\n";
