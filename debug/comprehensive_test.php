<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';

require_once 'config/database.php';
require_once 'models/Proponent.php';

$db = Database::getInstance()->getConnection();
$proponentModel = new Proponent();

echo "==============================================\n";
echo "COMPREHENSIVE NON-LGU SUBMISSION TEST\n";
echo "==============================================\n\n";

$testResults = [];

// Test 1: Non-LGU with NULL control_number
echo "Test 1: Non-LGU with NULL control_number\n";
$data = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => null,
    'noted_findings' => '',
    'control_number' => null,
    'number_of_copies' => 0,
    'date_copies_received' => null,
    'district' => '',
    'proponent_name' => 'Non-LGU Test Organization',
    'project_title' => 'Community Development Project',
    'amount' => 75000,
    'number_of_associations' => 0,
    'total_beneficiaries' => 150,
    'male_beneficiaries' => 75,
    'female_beneficiaries' => 75,
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

$id = $proponentModel->create($data);
if ($id) {
    $stmt = $db->prepare("SELECT proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $expected = date('Y-m-d', strtotime('2026-02-15 +60 days'));
    $actual = $record['liquidation_deadline'];
    
    if ($expected === $actual) {
        echo "✓ PASS: Liquidation deadline = $actual (60 days)\n";
        $testResults[] = true;
    } else {
        echo "✗ FAIL: Expected $expected, got $actual\n";
        $testResults[] = false;
    }
    $proponentModel->delete($id);
} else {
    echo "✗ FAIL: Could not create\n";
    $testResults[] = false;
}

// Test 2: Multiple Non-LGU with NULL control_number (no conflict)
echo "\nTest 2: Multiple Non-LGU with NULL control_number\n";
$id1 = $proponentModel->create($data);
$data['proponent_name'] = 'Another Non-LGU';
$id2 = $proponentModel->create($data);

if ($id1 && $id2) {
    echo "✓ PASS: Both created without UNIQUE constraint violation\n";
    $testResults[] = true;
    $proponentModel->delete($id1);
    $proponentModel->delete($id2);
} else {
    echo "✗ FAIL: UNIQUE constraint issue\n";
    $testResults[] = false;
}

// Test 3: LGU comparison
echo "\nTest 3: LGU vs Non-LGU deadline comparison\n";
$lguData = $data;
$lguData['proponent_type'] = 'LGU-associated';
$lguData['proponent_name'] = 'LGU Test';

$lguId = $proponentModel->create($lguData);
$nonLguId = $proponentModel->create($data);

if ($lguId && $nonLguId) {
    $stmt = $db->prepare("SELECT id, proponent_type, liquidation_deadline FROM proponents WHERE id IN (?, ?)");
    $stmt->execute([$lguId, $nonLguId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $lguCorrect = false;
    $nonLguCorrect = false;
    
    foreach ($records as $r) {
        $days = (strtotime($r['liquidation_deadline']) - strtotime('2026-02-15')) / (60 * 60 * 24);
        if ($r['proponent_type'] === 'LGU-associated' && $days == 10) {
            echo "✓ LGU: 10 days\n";
            $lguCorrect = true;
        } elseif ($r['proponent_type'] === 'Non-LGU-associated' && $days == 60) {
            echo "✓ Non-LGU: 60 days\n";
            $nonLguCorrect = true;
        }
    }
    
    $testResults[] = $lguCorrect && $nonLguCorrect;
    $proponentModel->delete($lguId);
    $proponentModel->delete($nonLguId);
} else {
    echo "✗ FAIL: Could not create records\n";
    $testResults[] = false;
}

// Test 4: Type change (LGU → Non-LGU)
echo "\nTest 4: Proponent type change (LGU → Non-LGU)\n";
$changeId = $proponentModel->create($lguData);

if ($changeId) {
    $stmt = $db->prepare("SELECT liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$changeId]);
    $before = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $updateData = $lguData;
    $updateData['proponent_type'] = 'Non-LGU-associated';
    $proponentModel->update($changeId, $updateData);
    
    $stmt->execute([$changeId]);
    $after = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $expectedAfter = date('Y-m-d', strtotime('2026-02-15 +60 days'));
    if ($after['liquidation_deadline'] === $expectedAfter) {
        echo "✓ PASS: Deadline updated from 10 to 60 days\n";
        $testResults[] = true;
    } else {
        echo "✗ FAIL: Deadline not updated correctly\n";
        $testResults[] = false;
    }
    
    $proponentModel->delete($changeId);
} else {
    echo "✗ FAIL: Could not create\n";
    $testResults[] = false;
}

// Summary
echo "\n==============================================\n";
$passed = count(array_filter($testResults));
$total = count($testResults);
echo "RESULTS: $passed/$total tests passed\n";

if ($passed === $total) {
    echo "✓✓✓ ALL TESTS PASSED!\n";
    echo "\nNon-LGU proponent submission is working correctly:\n";
    echo "- Empty control_number handled properly (NULL)\n";
    echo "- 60-day liquidation deadline calculated correctly\n";
    echo "- Type changes update deadline properly\n";
    echo "- No UNIQUE constraint violations\n";
} else {
    echo "✗ Some tests failed\n";
}
echo "==============================================\n";
