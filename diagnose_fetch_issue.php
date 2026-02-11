<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "Diagnosing PDO Fetch Issue\n";
echo "===========================\n\n";

$testId = 18;

echo "Test 1: Direct query with default fetch mode\n";
$stmt = $db->prepare("SELECT * FROM proponents WHERE id = ?");
$stmt->execute([$testId]);
$result1 = $stmt->fetch();
echo "Result type: " . gettype($result1) . "\n";
echo "Result value: " . ($result1 ? 'Has data' : 'false/empty') . "\n\n";

echo "Test 2: Query with explicit PDO::FETCH_ASSOC\n";
$stmt = $db->prepare("SELECT * FROM proponents WHERE id = ?");
$stmt->execute([$testId]);
$result2 = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Result type: " . gettype($result2) . "\n";
echo "Result value: " . ($result2 ? 'Has data' : 'false/empty') . "\n\n";

echo "Test 3: Check if record exists\n";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM proponents WHERE id = ?");
$stmt->execute([$testId]);
$count = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Record count: " . ($count['count'] ?? 0) . "\n\n";

echo "Test 4: Get last inserted ID\n";
$stmt = $db->prepare("
    INSERT INTO proponents (
        proponent_type, proponent_name, project_title, amount,
        total_beneficiaries, category, date_turnover, status,
        created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    'Non-LGU-associated',
    'Diagnose Test',
    'Test',
    10000,
    50,
    'Formation',
    '2026-02-01',
    'pending',
    1,
    1
]);

$newId = $db->lastInsertId();
echo "Inserted ID: $newId\n";

$stmt = $db->prepare("SELECT * FROM proponents WHERE id = ?");
$stmt->execute([$newId]);
$newRecord = $stmt->fetch();
echo "Fetch result: " . ($newRecord ? 'Success' : 'Failed') . "\n";

if ($newRecord) {
    echo "Record data exists: " . (isset($newRecord['id']) ? 'Yes' : 'No') . "\n";
    echo "Liquidation deadline: " . ($newRecord['liquidation_deadline'] ?? 'NULL') . "\n";
}

$db->prepare("DELETE FROM proponents WHERE id = ?")->execute([$newId]);
echo "\nCleaned up test record\n";
