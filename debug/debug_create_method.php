<?php
session_start();
require_once 'config/database.php';

$_SESSION['user_id'] = 1;

$db = Database::getInstance()->getConnection();

$testData = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => '2026-01-15',
    'noted_findings' => 'Test findings',
    'control_number' => 'DEBUG-TEST-' . time(),
    'number_of_copies' => 3,
    'date_copies_received' => '2026-01-16',
    'district' => 'Test District',
    'proponent_name' => 'Debug Test Proponent',
    'project_title' => 'Debug Test Project',
    'amount' => 50000.00,
    'number_of_associations' => 2,
    'total_beneficiaries' => 100,
    'male_beneficiaries' => 50,
    'female_beneficiaries' => 50,
    'type_of_beneficiaries' => 'Farmers',
    'category' => 'Formation',
    'recipient_barangays' => 'Test Barangay',
    'letter_of_intent_date' => '2026-01-10',
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
    'source_of_funds' => 'DOLE',
    'latitude' => 10.5,
    'longitude' => 123.0,
    'status' => 'pending'
];

echo "Debugging Proponent Model Create Method\n";
echo "========================================\n\n";

try {
    $sql = "INSERT INTO proponents (
        proponent_type, date_received, noted_findings, control_number, number_of_copies,
        date_copies_received, district, proponent_name, project_title, amount,
        number_of_associations, total_beneficiaries, male_beneficiaries, female_beneficiaries,
        type_of_beneficiaries, category, recipient_barangays, letter_of_intent_date,
        date_forwarded_to_ro6, rpmt_findings, date_complied_by_proponent,
        date_complied_by_proponent_nofo, date_forwarded_to_nofo, date_approved,
        date_check_release, check_number, check_date_issued, or_number, or_date_issued,
        date_turnover, date_implemented, date_liquidated, date_monitoring,
        source_of_funds, latitude, longitude, status, created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    
    $params = [
        $testData['proponent_type'], $testData['date_received'], $testData['noted_findings'],
        $testData['control_number'], $testData['number_of_copies'], $testData['date_copies_received'],
        $testData['district'], $testData['proponent_name'], $testData['project_title'], $testData['amount'],
        $testData['number_of_associations'], $testData['total_beneficiaries'],
        $testData['male_beneficiaries'], $testData['female_beneficiaries'],
        $testData['type_of_beneficiaries'], $testData['category'], $testData['recipient_barangays'],
        $testData['letter_of_intent_date'], $testData['date_forwarded_to_ro6'], $testData['rpmt_findings'],
        $testData['date_complied_by_proponent'], $testData['date_complied_by_proponent_nofo'],
        $testData['date_forwarded_to_nofo'], $testData['date_approved'], $testData['date_check_release'],
        $testData['check_number'], $testData['check_date_issued'], $testData['or_number'],
        $testData['or_date_issued'], $testData['date_turnover'], $testData['date_implemented'],
        $testData['date_liquidated'], $testData['date_monitoring'], $testData['source_of_funds'],
        $testData['latitude'], $testData['longitude'], $testData['status'],
        $_SESSION['user_id'], $_SESSION['user_id']
    ];
    
    echo "Executing INSERT with exact same SQL as Proponent model...\n";
    echo "Proponent Type: " . $testData['proponent_type'] . "\n";
    echo "Date Turnover: " . $testData['date_turnover'] . "\n\n";
    
    $result = $stmt->execute($params);
    
    if ($result) {
        $id = $db->lastInsertId();
        echo "✓ Insert successful, ID: $id\n\n";
        
        $stmt = $db->prepare("SELECT id, proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        
        if ($record) {
            echo "Retrieved Record:\n";
            echo "- ID: " . $record['id'] . "\n";
            echo "- Proponent Type: " . $record['proponent_type'] . "\n";
            echo "- Date Turnover: " . $record['date_turnover'] . "\n";
            echo "- Liquidation Deadline: " . ($record['liquidation_deadline'] ?? 'NULL') . "\n";
            
            if ($record['liquidation_deadline']) {
                $expected = date('Y-m-d', strtotime($record['date_turnover'] . ' +60 days'));
                if ($expected === $record['liquidation_deadline']) {
                    echo "\n✓ SUCCESS: Liquidation deadline is correct (60 days)!\n";
                } else {
                    echo "\n✗ FAIL: Liquidation deadline is incorrect!\n";
                    echo "  Expected: $expected\n";
                    echo "  Actual: " . $record['liquidation_deadline'] . "\n";
                }
            } else {
                echo "\n✗ FAIL: Liquidation deadline is NULL!\n";
            }
        } else {
            echo "✗ FAIL: Could not retrieve record!\n";
        }
        
        $db->prepare("DELETE FROM proponents WHERE id = ?")->execute([$id]);
        echo "\nTest record cleaned up.\n";
    } else {
        echo "✗ Insert failed!\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
}
