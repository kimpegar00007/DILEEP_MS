<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

echo "Testing Statement Cursor Issue\n";
echo "===============================\n\n";

$controlNum = 'CURSOR-TEST-' . time();

echo "Test 1: Insert without closing statement\n";
$stmt1 = $db->prepare("
    INSERT INTO proponents (
        proponent_type, proponent_name, project_title, amount,
        total_beneficiaries, category, date_turnover, status,
        control_number, created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt1->execute([
    'Non-LGU-associated', 'Test 1', 'Test', 10000, 50,
    'Formation', '2026-02-01', 'pending', $controlNum, 1, 1
]);

$id1 = $db->lastInsertId();
echo "Inserted ID: $id1\n";

// Simulate logActivity without closing $stmt1
$stmt2 = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
$stmt2->execute([1, 'test', 'proponents', $id1, 'Test', 'unknown']);
echo "Activity logged\n";

// Try to fetch without closing previous statements
$stmt3 = $db->prepare("SELECT * FROM proponents WHERE id = ?");
$stmt3->execute([$id1]);
$result1 = $stmt3->fetch(PDO::FETCH_ASSOC);

echo "Fetch result: " . ($result1 ? 'Success' : 'Failed') . "\n";

if ($result1) {
    echo "  Liquidation Deadline: " . ($result1['liquidation_deadline'] ?? 'NULL') . "\n";
}

$db->prepare("DELETE FROM proponents WHERE id = ?")->execute([$id1]);
$db->prepare("DELETE FROM activity_logs WHERE record_id = ? AND table_name = 'proponents'")->execute([$id1]);

echo "\n\nTest 2: Insert with explicit statement closure\n";
$controlNum2 = 'CURSOR-TEST2-' . time();

$stmt4 = $db->prepare("
    INSERT INTO proponents (
        proponent_type, proponent_name, project_title, amount,
        total_beneficiaries, category, date_turnover, status,
        control_number, created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt4->execute([
    'Non-LGU-associated', 'Test 2', 'Test', 10000, 50,
    'Formation', '2026-02-01', 'pending', $controlNum2, 1, 1
]);

$id2 = $db->lastInsertId();
$stmt4->closeCursor(); // Close cursor explicitly
echo "Inserted ID: $id2 (cursor closed)\n";

$stmt5 = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
$stmt5->execute([1, 'test', 'proponents', $id2, 'Test', 'unknown']);
$stmt5->closeCursor(); // Close cursor explicitly
echo "Activity logged (cursor closed)\n";

$stmt6 = $db->prepare("SELECT * FROM proponents WHERE id = ?");
$stmt6->execute([$id2]);
$result2 = $stmt6->fetch(PDO::FETCH_ASSOC);

echo "Fetch result: " . ($result2 ? 'Success' : 'Failed') . "\n";

if ($result2) {
    echo "  Liquidation Deadline: " . ($result2['liquidation_deadline'] ?? 'NULL') . "\n";
}

$db->prepare("DELETE FROM proponents WHERE id = ?")->execute([$id2]);
$db->prepare("DELETE FROM activity_logs WHERE record_id = ? AND table_name = 'proponents'")->execute([$id2]);

echo "\nConclusion: " . ($result1 && $result2 ? "Both work" : ($result2 ? "Only explicit closure works" : "Both fail")) . "\n";
