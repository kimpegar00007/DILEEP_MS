<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Proponent.php';

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';

$proponentModel = new Proponent();
$errors = [];

echo "Testing Form Submission for Non-LGU Proponent\n";
echo "==============================================\n\n";

$_POST = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => '2026-01-15',
    'noted_findings' => 'Test findings',
    'control_number' => 'FORM-TEST-' . time(),
    'number_of_copies' => '3',
    'date_copies_received' => '2026-01-16',
    'district' => 'Test District',
    'proponent_name' => 'Form Test Non-LGU Proponent',
    'project_title' => 'Form Test Non-LGU Project',
    'amount' => '50000.00',
    'number_of_associations' => '2',
    'total_beneficiaries' => '100',
    'male_beneficiaries' => '50',
    'female_beneficiaries' => '50',
    'type_of_beneficiaries' => 'Farmers',
    'category' => 'Formation',
    'recipient_barangays' => 'Test Barangay',
    'letter_of_intent_date' => '2026-01-10',
    'date_forwarded_to_ro6' => '',
    'rpmt_findings' => '',
    'date_complied_by_proponent' => '',
    'date_complied_by_proponent_nofo' => '',
    'date_forwarded_to_nofo' => '',
    'date_approved' => '',
    'date_check_release' => '',
    'check_number' => '',
    'check_date_issued' => '',
    'or_number' => '',
    'or_date_issued' => '',
    'date_turnover' => '2026-02-01',
    'date_implemented' => '',
    'date_liquidated' => '',
    'date_monitoring' => '',
    'source_of_funds' => 'DOLE',
    'latitude' => '10.5',
    'longitude' => '123.0',
    'status' => 'pending'
];

echo "Simulating form POST data...\n";
echo "Proponent Type: " . $_POST['proponent_type'] . "\n";
echo "Date Turnover: " . $_POST['date_turnover'] . "\n\n";

$data = [
    'proponent_type' => $_POST['proponent_type'] ?? '',
    'date_received' => $_POST['date_received'] ?: null,
    'noted_findings' => trim($_POST['noted_findings'] ?? ''),
    'control_number' => trim($_POST['control_number'] ?? ''),
    'number_of_copies' => intval($_POST['number_of_copies'] ?? 0),
    'date_copies_received' => $_POST['date_copies_received'] ?: null,
    'district' => trim($_POST['district'] ?? ''),
    'proponent_name' => trim($_POST['proponent_name'] ?? ''),
    'project_title' => trim($_POST['project_title'] ?? ''),
    'amount' => floatval($_POST['amount'] ?? 0),
    'number_of_associations' => intval($_POST['number_of_associations'] ?? 0),
    'total_beneficiaries' => intval($_POST['total_beneficiaries'] ?? 0),
    'male_beneficiaries' => intval($_POST['male_beneficiaries'] ?? 0),
    'female_beneficiaries' => intval($_POST['female_beneficiaries'] ?? 0),
    'type_of_beneficiaries' => trim($_POST['type_of_beneficiaries'] ?? ''),
    'category' => $_POST['category'] ?? '',
    'recipient_barangays' => trim($_POST['recipient_barangays'] ?? ''),
    'letter_of_intent_date' => $_POST['letter_of_intent_date'] ?: null,
    'date_forwarded_to_ro6' => $_POST['date_forwarded_to_ro6'] ?: null,
    'rpmt_findings' => trim($_POST['rpmt_findings'] ?? ''),
    'date_complied_by_proponent' => $_POST['date_complied_by_proponent'] ?: null,
    'date_complied_by_proponent_nofo' => $_POST['date_complied_by_proponent_nofo'] ?: null,
    'date_forwarded_to_nofo' => $_POST['date_forwarded_to_nofo'] ?: null,
    'date_approved' => $_POST['date_approved'] ?: null,
    'date_check_release' => $_POST['date_check_release'] ?: null,
    'check_number' => trim($_POST['check_number'] ?? ''),
    'check_date_issued' => $_POST['check_date_issued'] ?: null,
    'or_number' => trim($_POST['or_number'] ?? ''),
    'or_date_issued' => $_POST['or_date_issued'] ?: null,
    'date_turnover' => $_POST['date_turnover'] ?: null,
    'date_implemented' => $_POST['date_implemented'] ?: null,
    'date_liquidated' => $_POST['date_liquidated'] ?: null,
    'date_monitoring' => $_POST['date_monitoring'] ?: null,
    'source_of_funds' => trim($_POST['source_of_funds'] ?? ''),
    'latitude' => !empty(trim($_POST['latitude'] ?? '')) ? floatval(trim($_POST['latitude'])) : null,
    'longitude' => !empty(trim($_POST['longitude'] ?? '')) ? floatval(trim($_POST['longitude'])) : null,
    'status' => $_POST['status'] ?? 'pending'
];

echo "Running validation...\n";

if (empty($data['proponent_type'])) {
    $errors[] = 'Proponent type is required';
} elseif (!in_array($data['proponent_type'], ['LGU-associated', 'Non-LGU-associated'])) {
    $errors[] = 'Invalid proponent type. Must be either LGU-associated or Non-LGU-associated';
}
if (empty($data['proponent_name'])) $errors[] = 'Proponent name is required';
if (empty($data['project_title'])) $errors[] = 'Project title is required';
if ($data['amount'] <= 0) $errors[] = 'Amount must be greater than zero';
if ($data['total_beneficiaries'] <= 0) $errors[] = 'Total beneficiaries must be greater than zero';
if (empty($data['category'])) $errors[] = 'Category is required';

if ($data['male_beneficiaries'] + $data['female_beneficiaries'] > $data['total_beneficiaries']) {
    $errors[] = 'Male + Female beneficiaries cannot exceed total beneficiaries';
}

if ($data['latitude'] !== null && ($data['latitude'] < 9.0 || $data['latitude'] > 12.0)) {
    $errors[] = 'Latitude must be between 9.0 and 12.0 for Negros Occidental';
}
if ($data['longitude'] !== null && ($data['longitude'] < 122.0 || $data['longitude'] > 124.0)) {
    $errors[] = 'Longitude must be between 122.0 and 124.0 for Negros Occidental';
}
if (($data['latitude'] !== null && $data['longitude'] === null) || ($data['latitude'] === null && $data['longitude'] !== null)) {
    $errors[] = 'Both latitude and longitude must be provided together';
}

if (!empty($errors)) {
    echo "✗ VALIDATION FAILED:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}

echo "✓ Validation passed\n\n";

try {
    echo "Attempting to create proponent...\n";
    $id = $proponentModel->create($data);
    
    if ($id) {
        echo "✓ Proponent created successfully with ID: $id\n\n";
        
        $proponent = $proponentModel->findById($id);
        
        if ($proponent) {
            echo "Verification:\n";
            echo "- ID: " . $proponent['id'] . "\n";
            echo "- Proponent Type: " . $proponent['proponent_type'] . "\n";
            echo "- Proponent Name: " . $proponent['proponent_name'] . "\n";
            echo "- Date Turnover: " . $proponent['date_turnover'] . "\n";
            echo "- Liquidation Deadline: " . ($proponent['liquidation_deadline'] ?? 'NULL') . "\n";
            
            if ($proponent['liquidation_deadline']) {
                $expected = date('Y-m-d', strtotime($proponent['date_turnover'] . ' +60 days'));
                $actual = $proponent['liquidation_deadline'];
                
                if ($expected === $actual) {
                    echo "\n✓ SUCCESS: Liquidation deadline is correct (60 days from turnover)!\n";
                    echo "  Expected: $expected\n";
                    echo "  Actual: $actual\n";
                } else {
                    echo "\n✗ FAIL: Liquidation deadline is incorrect!\n";
                    echo "  Expected: $expected (60 days)\n";
                    echo "  Actual: $actual\n";
                    
                    $days = (strtotime($actual) - strtotime($proponent['date_turnover'])) / (60 * 60 * 24);
                    echo "  Actual days: $days\n";
                }
            } else {
                echo "\n✗ FAIL: Liquidation deadline is NULL!\n";
            }
            
            $proponentModel->delete($id);
            echo "\nTest record cleaned up.\n";
        } else {
            echo "✗ Could not retrieve created proponent!\n";
        }
    } else {
        echo "✗ FAIL: Failed to create proponent (returned false)\n";
        
        $db = Database::getInstance()->getConnection();
        $errorInfo = $db->errorInfo();
        if ($errorInfo[0] !== '00000') {
            echo "Database Error:\n";
            echo "  SQLSTATE: " . $errorInfo[0] . "\n";
            echo "  Error Code: " . $errorInfo[1] . "\n";
            echo "  Message: " . $errorInfo[2] . "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
