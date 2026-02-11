<?php
session_start();
require_once 'config/database.php';
require_once 'models/Proponent.php';

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';

$db = Database::getInstance()->getConnection();

echo "==============================================\n";
echo "FINAL VERIFICATION: Non-LGU Submission Test\n";
echo "==============================================\n\n";

echo "Step 1: Testing LGU-associated proponent\n";
echo "-----------------------------------------\n";

$lguData = [
    'proponent_type' => 'LGU-associated',
    'date_received' => null,
    'noted_findings' => '',
    'control_number' => 'LGU-VERIFY-' . time(),
    'number_of_copies' => 0,
    'date_copies_received' => null,
    'district' => '',
    'proponent_name' => 'Test LGU Proponent',
    'project_title' => 'Test LGU Project',
    'amount' => 25000,
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

$proponentModel = new Proponent();
$lguId = $proponentModel->create($lguData);

if ($lguId) {
    $stmt = $db->prepare("SELECT proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$lguId]);
    $lguRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $expectedLgu = date('Y-m-d', strtotime($lguData['date_turnover'] . ' +10 days'));
    $actualLgu = $lguRecord['liquidation_deadline'];
    
    if ($expectedLgu === $actualLgu) {
        echo "✓ LGU Test PASSED\n";
        echo "  Expected: $expectedLgu (10 days)\n";
        echo "  Actual: $actualLgu\n";
    } else {
        echo "✗ LGU Test FAILED\n";
        echo "  Expected: $expectedLgu (10 days)\n";
        echo "  Actual: $actualLgu\n";
    }
    
    $proponentModel->delete($lguId);
} else {
    echo "✗ Failed to create LGU proponent\n";
}

echo "\nStep 2: Testing Non-LGU-associated proponent\n";
echo "---------------------------------------------\n";

$nonLguData = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => null,
    'noted_findings' => '',
    'control_number' => 'NON-LGU-VERIFY-' . time(),
    'number_of_copies' => 0,
    'date_copies_received' => null,
    'district' => '',
    'proponent_name' => 'Test Non-LGU Proponent',
    'project_title' => 'Test Non-LGU Project',
    'amount' => 50000,
    'number_of_associations' => 0,
    'total_beneficiaries' => 100,
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

$nonLguId = $proponentModel->create($nonLguData);

if ($nonLguId) {
    $stmt = $db->prepare("SELECT proponent_type, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$nonLguId]);
    $nonLguRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $expectedNonLgu = date('Y-m-d', strtotime($nonLguData['date_turnover'] . ' +60 days'));
    $actualNonLgu = $nonLguRecord['liquidation_deadline'];
    
    if ($expectedNonLgu === $actualNonLgu) {
        echo "✓ Non-LGU Test PASSED\n";
        echo "  Expected: $expectedNonLgu (60 days)\n";
        echo "  Actual: $actualNonLgu\n";
    } else {
        echo "✗ Non-LGU Test FAILED\n";
        echo "  Expected: $expectedNonLgu (60 days)\n";
        echo "  Actual: $actualNonLgu\n";
    }
    
    $proponentModel->delete($nonLguId);
} else {
    echo "✗ Failed to create Non-LGU proponent\n";
}

echo "\nStep 3: Testing proponent type change (LGU → Non-LGU)\n";
echo "------------------------------------------------------\n";

$changeTestId = $proponentModel->create($lguData);

if ($changeTestId) {
    $stmt = $db->prepare("SELECT liquidation_deadline FROM proponents WHERE id = ?");
    $stmt->execute([$changeTestId]);
    $before = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Before change (LGU): " . $before['liquidation_deadline'] . " (should be 10 days)\n";
    
    $updateData = $lguData;
    $updateData['proponent_type'] = 'Non-LGU-associated';
    
    $proponentModel->update($changeTestId, $updateData);
    
    $stmt->execute([$changeTestId]);
    $after = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "After change (Non-LGU): " . $after['liquidation_deadline'] . " (should be 60 days)\n";
    
    $expectedAfter = date('Y-m-d', strtotime($lguData['date_turnover'] . ' +60 days'));
    if ($after['liquidation_deadline'] === $expectedAfter) {
        echo "✓ Type Change Test PASSED\n";
    } else {
        echo "✗ Type Change Test FAILED\n";
        echo "  Expected: $expectedAfter\n";
        echo "  Actual: " . $after['liquidation_deadline'] . "\n";
    }
    
    $proponentModel->delete($changeTestId);
}

echo "\n==============================================\n";
echo "VERIFICATION COMPLETE\n";
echo "==============================================\n";
