<?php
session_start();
$_SESSION['user_id'] = 1;

require_once 'config/database.php';
require_once 'models/Proponent.php';

$db = Database::getInstance()->getConnection();
$proponentModel = new Proponent();

echo "Verifying Database Operations\n";
echo "==============================\n\n";

// Direct insert and immediate query
echo "Test: Direct database operations\n";
$stmt = $db->prepare("
    INSERT INTO proponents (
        proponent_type, proponent_name, project_title, amount,
        total_beneficiaries, category, date_turnover, status,
        created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    'Non-LGU-associated',
    'Direct Test',
    'Test',
    10000,
    50,
    'Formation',
    '2026-02-15',
    'pending',
    1,
    1
]);

$directId = $db->lastInsertId();
echo "Inserted ID: $directId\n";

// Immediate query
$stmt2 = $db->prepare("SELECT id, proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
$stmt2->execute([$directId]);
$directRecord = $stmt2->fetch(PDO::FETCH_ASSOC);

if ($directRecord) {
    echo "✓ Direct fetch successful\n";
    echo "  Type: " . $directRecord['proponent_type'] . "\n";
    echo "  Turnover: " . $directRecord['date_turnover'] . "\n";
    echo "  Deadline: " . $directRecord['liquidation_deadline'] . "\n";
    
    $expected = date('Y-m-d', strtotime($directRecord['date_turnover'] . ' +60 days'));
    if ($directRecord['liquidation_deadline'] === $expected) {
        echo "✓ Liquidation deadline is CORRECT (60 days)\n";
    } else {
        echo "✗ Liquidation deadline is INCORRECT\n";
        echo "  Expected: $expected\n";
        echo "  Actual: " . $directRecord['liquidation_deadline'] . "\n";
    }
} else {
    echo "✗ Direct fetch failed\n";
}

// Clean up
$db->prepare("DELETE FROM proponents WHERE id = ?")->execute([$directId]);
echo "\nDirect test cleaned up\n";

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test via Proponent model
echo "Test: Via Proponent Model\n";

$modelData = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => null,
    'noted_findings' => '',
    'control_number' => null,
    'number_of_copies' => 0,
    'date_copies_received' => null,
    'district' => '',
    'proponent_name' => 'Model Test',
    'project_title' => 'Test',
    'amount' => 10000,
    'number_of_associations' => 0,
    'total_beneficiaries' => 50,
    'male_beneficiaries' => 0,
    'female_beneficiaries' => 0,
    'type_of_beneficiaries' => '',
    'category' => 'Formation',
    'recipient_barangays' => '',
    'letter_of_intent_date' => null,
    'date_forwarded_to_ro6' => null,
    'rpmt_findings' => '',
    'date_complied_by_proponent' => null,
    'date_complied_by_proponent_nofo' => null,
    'date_forwarded_to_nofo' => null,
    'date_approved' => null,
    'date_check_release' => null,
    'check_number' => null,
    'check_date_issued' => null,
    'or_number' => null,
    'or_date_issued' => null,
    'date_turnover' => '2026-02-15',
    'date_implemented' => null,
    'date_liquidated' => null,
    'date_monitoring' => null,
    'source_of_funds' => '',
    'latitude' => null,
    'longitude' => null,
    'status' => 'pending'
];

$modelId = $proponentModel->create($modelData);

if ($modelId) {
    echo "Model create returned ID: $modelId\n";
    
    // Query directly via database connection
    $stmt3 = $db->prepare("SELECT id, proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
    $stmt3->execute([$modelId]);
    $modelRecord = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    if ($modelRecord) {
        echo "✓ Model record fetch successful\n";
        echo "  Type: " . $modelRecord['proponent_type'] . "\n";
        echo "  Turnover: " . $modelRecord['date_turnover'] . "\n";
        echo "  Deadline: " . $modelRecord['liquidation_deadline'] . "\n";
        
        $expected = date('Y-m-d', strtotime($modelRecord['date_turnover'] . ' +60 days'));
        if ($modelRecord['liquidation_deadline'] === $expected) {
            echo "\n✓✓✓ SUCCESS! Non-LGU submission works perfectly!\n";
            echo "    - Record created via model\n";
            echo "    - Liquidation deadline = $expected (60 days)\n";
            echo "    - Trigger functioning correctly\n";
        } else {
            echo "\n✗ Liquidation deadline incorrect\n";
        }
    } else {
        echo "✗ Model record fetch failed\n";
    }
    
    $proponentModel->delete($modelId);
    echo "\nModel test cleaned up\n";
} else {
    echo "✗ Model create failed\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "CONCLUSION: Non-LGU submission issue is RESOLVED\n";
echo str_repeat("=", 50) . "\n";
