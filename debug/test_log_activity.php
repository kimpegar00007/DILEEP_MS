<?php
session_start();
require_once 'config/database.php';

$_SESSION['user_id'] = 1;

$db = Database::getInstance()->getConnection();

echo "Testing logActivity method\n";
echo "==========================\n\n";

try {
    $stmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
         VALUES (?, ?, 'proponents', ?, ?, ?)"
    );
    
    $result = $stmt->execute([
        $_SESSION['user_id'],
        'test',
        999,
        'Test log entry',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    if ($result) {
        echo "✓ Activity log insert successful\n";
        $logId = $db->lastInsertId();
        echo "  Log ID: $logId\n";
        
        $db->prepare("DELETE FROM activity_logs WHERE id = ?")->execute([$logId]);
        echo "  Cleaned up test log\n";
    } else {
        echo "✗ Activity log insert failed\n";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- Testing complete Proponent create with error handling ---\n\n";

require_once 'models/Proponent.php';
$proponentModel = new Proponent();

$testData = [
    'proponent_type' => 'Non-LGU-associated',
    'date_received' => '2026-01-15',
    'noted_findings' => '',
    'control_number' => 'LOG-TEST-' . time(),
    'number_of_copies' => 0,
    'date_copies_received' => null,
    'district' => '',
    'proponent_name' => 'Log Test Proponent',
    'project_title' => 'Log Test Project',
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

try {
    echo "Creating proponent via model...\n";
    $id = $proponentModel->create($testData);
    
    if ($id) {
        echo "✓ Created with ID: $id\n";
        
        $stmt = $db->prepare("SELECT * FROM proponents WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "✓ Record retrieved successfully\n";
            echo "  Proponent Type: " . $record['proponent_type'] . "\n";
            echo "  Date Turnover: " . $record['date_turnover'] . "\n";
            echo "  Liquidation Deadline: " . ($record['liquidation_deadline'] ?? 'NULL') . "\n";
        } else {
            echo "✗ Could not retrieve record\n";
        }
        
        $proponentModel->delete($id);
        echo "✓ Cleaned up\n";
    } else {
        echo "✗ Create returned false\n";
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
}
