<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';

require_once 'config/database.php';
require_once 'models/Proponent.php';

echo "==============================================\n";
echo "FINAL TEST: Non-LGU Proponent Submission\n";
echo "==============================================\n\n";

$db = Database::getInstance()->getConnection();
$proponentModel = new Proponent();

// Test 1: Create Non-LGU with empty control_number
echo "Test 1: Non-LGU with empty control_number\n";
echo "-------------------------------------------\n";

$data1 = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => null,
    'noted_findings' => '',
    'control_number' => null, // Fixed: NULL instead of empty string
    'number_of_copies' => 0,
    'date_copies_received' => null,
    'district' => '',
    'proponent_name' => 'Test Non-LGU 1',
    'project_title' => 'Test Project 1',
    'amount' => 50000,
    'number_of_associations' => 0,
    'total_beneficiaries' => 100,
    'male_beneficiaries' => 50,
    'female_beneficiaries' => 50,
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

$id1 = $proponentModel->create($data1);

if ($id1) {
    echo "✓ Created with ID: $id1\n";
    
    $stmt = $db->prepare("SELECT proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$id1]);
    $record1 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record1) {
        $expected = date('Y-m-d', strtotime($data1['date_turnover'] . ' +60 days'));
        $actual = $record1['liquidation_deadline'];
        
        if ($expected === $actual) {
            echo "✓ PASS: Liquidation deadline correct ($actual = 60 days)\n";
        } else {
            echo "✗ FAIL: Expected $expected, got $actual\n";
        }
    }
    
    $proponentModel->delete($id1);
} else {
    echo "✗ FAIL: Could not create\n";
}

// Test 2: Create another Non-LGU with empty control_number (should not conflict)
echo "\nTest 2: Another Non-LGU with empty control_number\n";
echo "---------------------------------------------------\n";

$data2 = $data1;
$data2['proponent_name'] = 'Test Non-LGU 2';
$data2['project_title'] = 'Test Project 2';

$id2 = $proponentModel->create($data2);

if ($id2) {
    echo "✓ Created with ID: $id2 (no UNIQUE constraint violation)\n";
    
    $stmt = $db->prepare("SELECT liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$id2]);
    $record2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record2) {
        echo "✓ PASS: Liquidation deadline = " . $record2['liquidation_deadline'] . "\n";
    }
    
    $proponentModel->delete($id2);
} else {
    echo "✗ FAIL: Could not create (UNIQUE constraint issue?)\n";
}

// Test 3: LGU vs Non-LGU comparison
echo "\nTest 3: LGU vs Non-LGU deadline comparison\n";
echo "-------------------------------------------\n";

$lguData = $data1;
$lguData['proponent_type'] = 'LGU-associated';
$lguData['proponent_name'] = 'Test LGU';
$lguData['project_title'] = 'LGU Project';

$lguId = $proponentModel->create($lguData);
$nonLguId = $proponentModel->create($data1);

if ($lguId && $nonLguId) {
    $stmt = $db->prepare("SELECT proponent_type, liquidation_deadline FROM proponents WHERE id IN (?, ?)");
    $stmt->execute([$lguId, $nonLguId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $r) {
        $days = (strtotime($r['liquidation_deadline']) - strtotime($data1['date_turnover'])) / (60 * 60 * 24);
        $expected = $r['proponent_type'] === 'LGU-associated' ? 10 : 60;
        
        if ($days == $expected) {
            echo "✓ " . $r['proponent_type'] . ": $days days (correct)\n";
        } else {
            echo "✗ " . $r['proponent_type'] . ": $days days (expected $expected)\n";
        }
    }
    
    $proponentModel->delete($lguId);
    $proponentModel->delete($nonLguId);
}

echo "\n==============================================\n";
echo "ALL TESTS COMPLETE\n";
echo "==============================================\n";
