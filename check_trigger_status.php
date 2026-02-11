<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "Checking Database Trigger Status\n";
echo "=================================\n\n";

try {
    $stmt = $db->query("SHOW TRIGGERS WHERE `Trigger` = 'calculate_liquidation_deadline'");
    $insertTrigger = $stmt->fetch();
    
    $stmt = $db->query("SHOW TRIGGERS WHERE `Trigger` = 'update_liquidation_deadline'");
    $updateTrigger = $stmt->fetch();
    
    echo "INSERT Trigger (calculate_liquidation_deadline):\n";
    if ($insertTrigger) {
        echo "✓ EXISTS\n";
        echo "  Timing: " . $insertTrigger['Timing'] . "\n";
        echo "  Event: " . $insertTrigger['Event'] . "\n";
    } else {
        echo "✗ NOT FOUND\n";
    }
    
    echo "\nUPDATE Trigger (update_liquidation_deadline):\n";
    if ($updateTrigger) {
        echo "✓ EXISTS\n";
        echo "  Timing: " . $updateTrigger['Timing'] . "\n";
        echo "  Event: " . $updateTrigger['Event'] . "\n";
    } else {
        echo "✗ NOT FOUND\n";
    }
    
    echo "\n--- Testing with actual INSERT ---\n";
    
    $testControlNumber = 'TRIGGER-TEST-' . time();
    $stmt = $db->prepare("
        INSERT INTO proponents (
            proponent_type, proponent_name, project_title, amount, 
            total_beneficiaries, category, date_turnover, status,
            created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'Non-LGU-associated',
        'Trigger Test Proponent',
        'Test Project',
        10000,
        50,
        'Formation',
        '2026-02-01',
        'pending',
        1,
        1
    ]);
    
    $testId = $db->lastInsertId();
    echo "Created test record ID: $testId\n";
    
    $stmt = $db->prepare("SELECT id, proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$testId]);
    $result = $stmt->fetch();
    
    echo "\nResult:\n";
    echo "- Proponent Type: " . $result['proponent_type'] . "\n";
    echo "- Date Turnover: " . $result['date_turnover'] . "\n";
    echo "- Liquidation Deadline: " . ($result['liquidation_deadline'] ?? 'NULL') . "\n";
    
    if ($result['liquidation_deadline']) {
        $expected = date('Y-m-d', strtotime($result['date_turnover'] . ' +60 days'));
        if ($expected === $result['liquidation_deadline']) {
            echo "\n✓ TRIGGER WORKS: Deadline is correct!\n";
        } else {
            echo "\n✗ TRIGGER ISSUE: Deadline is incorrect!\n";
            echo "  Expected: $expected\n";
            echo "  Actual: " . $result['liquidation_deadline'] . "\n";
        }
    } else {
        echo "\n✗ TRIGGER NOT WORKING: Deadline is NULL!\n";
    }
    
    $db->prepare("DELETE FROM proponents WHERE id = ?")->execute([$testId]);
    echo "\nTest record cleaned up.\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
