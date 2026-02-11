<?php
// Simulate the exact form submission flow
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';

require_once 'config/database.php';
require_once 'models/Proponent.php';

$proponentModel = new Proponent();
$errors = [];

echo "==============================================\n";
echo "Testing Actual Form Submission Flow\n";
echo "==============================================\n\n";

// Simulate exact POST data from form
$_POST = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => '',
    'noted_findings' => '',
    'control_number' => '',
    'number_of_copies' => '',
    'date_copies_received' => '',
    'district' => '',
    'proponent_name' => 'Test Non-LGU Organization',
    'project_title' => 'Community Livelihood Project',
    'amount' => '75000',
    'number_of_associations' => '',
    'total_beneficiaries' => '150',
    'male_beneficiaries' => '75',
    'female_beneficiaries' => '75',
    'type_of_beneficiaries' => 'Farmers',
    'category' => 'Formation',
    'recipient_barangays' => 'Barangay Test',
    'letter_of_intent_date' => '',
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
    'date_turnover' => '2026-02-15',
    'date_implemented' => '',
    'date_liquidated' => '',
    'date_monitoring' => '',
    'source_of_funds' => '',
    'latitude' => '',
    'longitude' => '',
    'status' => 'pending'
];

echo "Form Data:\n";
echo "- Proponent Type: " . $_POST['proponent_type'] . "\n";
echo "- Proponent Name: " . $_POST['proponent_name'] . "\n";
echo "- Date Turnover: " . $_POST['date_turnover'] . "\n";
echo "- Expected Liquidation: " . date('Y-m-d', strtotime($_POST['date_turnover'] . ' +60 days')) . "\n\n";

// Process exactly as proponent-form.php does
$data = [
    'proponent_type' => $_POST['proponent_type'] ?? '',
    'date_received' => $_POST['date_received'] ?: null,
    'noted_findings' => trim($_POST['noted_findings'] ?? ''),
    'control_number' => !empty(trim($_POST['control_number'] ?? '')) ? trim($_POST['control_number']) : null,
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
    'check_number' => !empty(trim($_POST['check_number'] ?? '')) ? trim($_POST['check_number']) : null,
    'check_date_issued' => $_POST['check_date_issued'] ?: null,
    'or_number' => !empty(trim($_POST['or_number'] ?? '')) ? trim($_POST['or_number']) : null,
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

echo "Validation:\n";

// Exact validation from proponent-form.php
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

echo "✓ All validations passed\n\n";

echo "Creating proponent...\n";
try {
    $id = $proponentModel->create($data);
    
    if ($id) {
        echo "✓ Proponent created successfully!\n";
        echo "  ID: $id\n\n";
        
        // Verify in database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, proponent_type, proponent_name, date_turnover, liquidation_deadline FROM proponents WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "Database Verification:\n";
            echo "- ID: " . $record['id'] . "\n";
            echo "- Type: " . $record['proponent_type'] . "\n";
            echo "- Name: " . $record['proponent_name'] . "\n";
            echo "- Turnover Date: " . $record['date_turnover'] . "\n";
            echo "- Liquidation Deadline: " . ($record['liquidation_deadline'] ?? 'NULL') . "\n";
            
            if ($record['liquidation_deadline']) {
                $expected = date('Y-m-d', strtotime($record['date_turnover'] . ' +60 days'));
                $actual = $record['liquidation_deadline'];
                
                if ($expected === $actual) {
                    echo "\n✓✓✓ SUCCESS! Non-LGU submission works correctly!\n";
                    echo "    Liquidation deadline is properly set to 60 days.\n";
                } else {
                    echo "\n✗ ISSUE: Liquidation deadline is incorrect\n";
                    echo "  Expected: $expected (60 days)\n";
                    echo "  Actual: $actual\n";
                }
            } else {
                echo "\n✗ ISSUE: Liquidation deadline is NULL\n";
            }
        } else {
            echo "✗ Could not verify record in database\n";
        }
        
        // Clean up
        $proponentModel->delete($id);
        echo "\nTest record cleaned up.\n";
        
    } else {
        echo "✗ FAILED: create() returned false\n";
        
        // Check for database errors
        $db = Database::getInstance()->getConnection();
        $errorInfo = $db->errorInfo();
        if ($errorInfo[0] !== '00000') {
            echo "\nDatabase Error:\n";
            echo "  SQLSTATE: " . $errorInfo[0] . "\n";
            echo "  Error: " . $errorInfo[2] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n==============================================\n";
echo "Test Complete\n";
echo "==============================================\n";
