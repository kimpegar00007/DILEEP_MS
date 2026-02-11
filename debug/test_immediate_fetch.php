<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "Testing Immediate Fetch After Insert\n";
echo "=====================================\n\n";

$controlNum = 'IMMEDIATE-TEST-' . time();

echo "Inserting record...\n";
$stmt = $db->prepare("
    INSERT INTO proponents (
        proponent_type, proponent_name, project_title, amount,
        total_beneficiaries, category, date_turnover, status,
        control_number, created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$result = $stmt->execute([
    'Non-LGU-associated',
    'Immediate Test',
    'Test',
    10000,
    50,
    'Formation',
    '2026-02-01',
    'pending',
    $controlNum,
    1,
    1
]);

if ($result) {
    $id = $db->lastInsertId();
    echo "✓ Insert successful, ID: $id\n\n";
    
    echo "Attempting immediate fetch...\n";
    $stmt2 = $db->prepare("SELECT * FROM proponents WHERE id = ?");
    $stmt2->execute([$id]);
    $record = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        echo "✓ Immediate fetch successful\n";
        echo "  ID: " . $record['id'] . "\n";
        echo "  Type: " . $record['proponent_type'] . "\n";
        echo "  Liquidation Deadline: " . ($record['liquidation_deadline'] ?? 'NULL') . "\n";
    } else {
        echo "✗ Immediate fetch FAILED\n";
        echo "  Fetch returned: " . var_export($record, true) . "\n";
        echo "  Statement error: " . var_export($stmt2->errorInfo(), true) . "\n";
    }
    
    echo "\nChecking with control_number...\n";
    $stmt3 = $db->prepare("SELECT * FROM proponents WHERE control_number = ?");
    $stmt3->execute([$controlNum]);
    $record2 = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    if ($record2) {
        echo "✓ Fetch by control_number successful\n";
        echo "  ID: " . $record2['id'] . "\n";
    } else {
        echo "✗ Fetch by control_number FAILED\n";
    }
    
    $db->prepare("DELETE FROM proponents WHERE id = ?")->execute([$id]);
    echo "\nCleaned up\n";
} else {
    echo "✗ Insert failed\n";
}

echo "\n--- Testing with Proponent Model ---\n\n";

session_start();
$_SESSION['user_id'] = 1;

require_once 'models/Proponent.php';
$model = new Proponent();

$data = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => null,
    'noted_findings' => '',
    'control_number' => 'MODEL-TEST-' . time(),
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
    'check_number' => '',
    'check_date_issued' => null,
    'or_number' => '',
    'or_date_issued' => null,
    'date_turnover' => '2026-02-01',
    'date_implemented' => null,
    'date_liquidated' => null,
    'date_monitoring' => null,
    'source_of_funds' => '',
    'latitude' => null,
    'longitude' => null,
    'status' => 'pending'
];

$modelId = $model->create($data);

if ($modelId) {
    echo "Model create returned ID: $modelId\n";
    
    echo "Using model's findById...\n";
    $modelRecord = $model->findById($modelId);
    echo "Result: " . ($modelRecord ? 'Success' : 'Failed (false)') . "\n";
    
    if ($modelRecord) {
        echo "  Liquidation Deadline: " . ($modelRecord['liquidation_deadline'] ?? 'NULL') . "\n";
    }
    
    echo "\nDirect database query for same ID...\n";
    $stmt = $db->prepare("SELECT * FROM proponents WHERE id = ?");
    $stmt->execute([$modelId]);
    $directRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result: " . ($directRecord ? 'Success' : 'Failed') . "\n";
    
    if ($directRecord) {
        echo "  Liquidation Deadline: " . ($directRecord['liquidation_deadline'] ?? 'NULL') . "\n";
    }
    
    $model->delete($modelId);
    echo "\nCleaned up\n";
} else {
    echo "Model create failed\n";
}
