<?php
session_start();
require_once 'config/database.php';
require_once 'models/Proponent.php';

$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Test User';

$proponentModel = new Proponent();

$testData = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => '2026-01-15',
    'noted_findings' => 'Test findings',
    'control_number' => 'TEST-NON-LGU-' . time(),
    'number_of_copies' => 3,
    'date_copies_received' => '2026-01-16',
    'district' => 'Test District',
    'proponent_name' => 'Test Non-LGU Proponent',
    'project_title' => 'Test Non-LGU Project',
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

echo "Testing Non-LGU Proponent Submission\n";
echo "=====================================\n\n";

echo "Test Data:\n";
echo "- Proponent Type: " . $testData['proponent_type'] . "\n";
echo "- Date Turnover: " . $testData['date_turnover'] . "\n";
echo "- Expected Liquidation Deadline: " . date('Y-m-d', strtotime($testData['date_turnover'] . ' +60 days')) . " (60 days)\n\n";

try {
    $id = $proponentModel->create($testData);
    
    if ($id) {
        echo "✓ SUCCESS: Proponent created with ID: $id\n\n";
        
        $proponent = $proponentModel->findById($id);
        
        echo "Verification:\n";
        echo "- ID: " . $proponent['id'] . "\n";
        echo "- Proponent Type: " . $proponent['proponent_type'] . "\n";
        echo "- Date Turnover: " . $proponent['date_turnover'] . "\n";
        echo "- Liquidation Deadline: " . ($proponent['liquidation_deadline'] ?? 'NULL') . "\n";
        
        if ($proponent['liquidation_deadline']) {
            $expected = date('Y-m-d', strtotime($testData['date_turnover'] . ' +60 days'));
            $actual = $proponent['liquidation_deadline'];
            
            if ($expected === $actual) {
                echo "\n✓ PASS: Liquidation deadline is correct (60 days)\n";
            } else {
                echo "\n✗ FAIL: Liquidation deadline is incorrect!\n";
                echo "  Expected: $expected\n";
                echo "  Actual: $actual\n";
            }
        } else {
            echo "\n✗ FAIL: Liquidation deadline is NULL!\n";
        }
        
        echo "\n--- Cleaning up test record ---\n";
        $proponentModel->delete($id);
        echo "✓ Test record deleted\n";
        
    } else {
        echo "✗ FAIL: Failed to create proponent\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
