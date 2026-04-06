<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$dateFrom = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$dateTo = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$regionalOffice = filter_input(INPUT_GET, 'regional_office', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$db = Database::getInstance()->getConnection();

try {
    $dateCondition = '';
    $params = [];
    
    if ($dateFrom && $dateTo) {
        $dateCondition = " AND date_approved BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $dateFrom;
        $params[':date_to'] = $dateTo;
    } elseif ($dateFrom) {
        $dateCondition = " AND date_approved >= :date_from";
        $params[':date_from'] = $dateFrom;
    } elseif ($dateTo) {
        $dateCondition = " AND date_approved <= :date_to";
        $params[':date_to'] = $dateTo;
    }
    
    // Get proponents data (Group projects)
    $proponentsSql = "SELECT 
        p.id,
        p.project_title as name_nature_of_project,
        p.proponent_name as name_of_implementor,
        p.recipient_barangays as barangay,
        '' as city_municipality,
        'Negros Occidental' as province,
        p.district,
        '' as income_class,
        CASE p.category 
            WHEN 'Formation' THEN 'F'
            WHEN 'Enhancement' THEN 'E'
            WHEN 'Restoration' THEN 'R'
            ELSE ''
        END as purpose_of_project,
        'G' as type_of_project,
        p.total_beneficiaries,
        p.female_beneficiaries as female,
        p.type_of_beneficiaries as beneficiary_type,
        p.amount as amount_released,
        p.date_check_release as date_released,
        p.source_of_funds,
        '' as convergence_project,
        p.status as project_status,
        'proponent' as source_table
    FROM proponents p
    WHERE p.status IN ('approved', 'implemented', 'liquidated', 'monitored')" . $dateCondition . "
    ORDER BY p.date_approved DESC";
    
    $stmt = $db->prepare($proponentsSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $proponents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get beneficiaries data (Individual projects)
    $beneficiariesSql = "SELECT 
        b.id,
        b.project_title as name_nature_of_project,
        b.full_name as name_of_implementor,
        b.barangay,
        b.municipality as city_municipality,
        'Negros Occidental' as province,
        '' as district,
        '' as income_class,
        '' as purpose_of_project,
        'I' as type_of_project,
        1 as total_beneficiaries,
        CASE WHEN b.gender = 'Female' THEN 1 ELSE 0 END as female,
        b.type_of_worker as beneficiary_type,
        b.amount_worth as amount_released,
        b.date_approved as date_released,
        '' as source_of_funds,
        '' as convergence_project,
        b.status as project_status,
        'beneficiary' as source_table
    FROM beneficiaries b
    WHERE b.status IN ('approved', 'implemented', 'monitored')" . $dateCondition . "
    ORDER BY b.date_approved DESC";
    
    $stmt = $db->prepare($beneficiariesSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and calculate totals
    $allRecords = array_merge($proponents, $beneficiaries);
    
    $totals = [
        'total_beneficiaries' => 0,
        'total_female' => 0,
        'total_amount' => 0
    ];
    
    foreach ($allRecords as $record) {
        $totals['total_beneficiaries'] += (int) $record['total_beneficiaries'];
        $totals['total_female'] += (int) $record['female'];
        $totals['total_amount'] += (float) $record['amount_released'];
    }
    
    echo json_encode([
        'success' => true,
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'regional_office' => $regionalOffice
        ],
        'data' => $allRecords,
        'totals' => $totals,
        'record_count' => count($allRecords)
    ]);
    
} catch (PDOException $e) {
    error_log('CQPR Report API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
