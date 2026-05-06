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

$province = filter_input(INPUT_GET, 'province', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$approvedOnly = filter_input(INPUT_GET, 'approved_only', FILTER_VALIDATE_BOOLEAN);

$db = Database::getInstance()->getConnection();

try {
    $provinceConditionBenef = '';
    $provinceConditionProp = '';
    $yearConditionBenef = '';
    $yearConditionProp = '';
    $yearConditionFieldwork = '';
    $statusConditionBenef = '';
    $statusConditionProp = '';

    if ($province) {
        $provinceConditionBenef = " AND province = " . $db->quote($province);
        $provinceConditionProp = " AND province = " . $db->quote($province);
    }

    if ($year) {
        // Filter by year - check both date_approved and created_at
        // Include records where either date_approved OR created_at matches the year
        $yearConditionBenef = " AND (YEAR(date_approved) = " . (int)$year . " OR (date_approved IS NULL AND YEAR(created_at) = " . (int)$year . "))";
        $yearConditionProp = " AND (YEAR(date_approved) = " . (int)$year . " OR (date_approved IS NULL AND YEAR(created_at) = " . (int)$year . "))";
        // Fieldwork schedule uses start_date for year filtering
        $yearConditionFieldwork = " AND YEAR(start_date) = " . (int)$year;
    }

    if ($approvedOnly) {
        $statusConditionBenef = " AND status = 'approved'";
        $statusConditionProp = " AND status = 'approved'";
    }
    
    // Beneficiary Statistics
    $benefSql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
        SUM(CASE WHEN status = 'monitored' THEN 1 ELSE 0 END) as monitored,
        SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_count,
        SUM(amount_worth) as total_amount
    FROM beneficiaries WHERE 1=1" . $provinceConditionBenef . $yearConditionBenef . $statusConditionBenef;
    
    $benefStats = $db->query($benefSql)->fetch(PDO::FETCH_ASSOC);
    
    // Proponent Statistics
    $propSql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN proponent_type = 'LGU-associated' THEN 1 ELSE 0 END) as lgu_count,
        SUM(CASE WHEN proponent_type = 'Non-LGU-associated' THEN 1 ELSE 0 END) as non_lgu_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
        SUM(CASE WHEN status = 'liquidated' THEN 1 ELSE 0 END) as liquidated,
        SUM(CASE WHEN status = 'monitored' THEN 1 ELSE 0 END) as monitored,
        SUM(total_beneficiaries) as total_beneficiaries,
        SUM(male_beneficiaries) as total_male,
        SUM(female_beneficiaries) as total_female,
        SUM(amount) as total_amount
    FROM proponents WHERE 1=1" . $provinceConditionProp . $yearConditionProp . $statusConditionProp;
    
    $propStats = $db->query($propSql)->fetch(PDO::FETCH_ASSOC);

    // Fieldwork Schedule Statistics (filtered by year via start_date)
    $fieldworkSql = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed
    FROM fieldwork_schedule WHERE 1=1" . $yearConditionFieldwork;

    $fieldworkStats = $db->query($fieldworkSql)->fetch(PDO::FETCH_ASSOC);

    // Municipality Distribution (Beneficiaries)
    $munDistSql = "SELECT municipality, COUNT(*) as count 
        FROM beneficiaries 
        WHERE municipality IS NOT NULL AND municipality != ''" . $yearConditionBenef . $statusConditionBenef . "
        GROUP BY municipality 
        ORDER BY count DESC 
        LIMIT 10";
    $munDist = $db->query($munDistSql)->fetchAll(PDO::FETCH_ASSOC);
    
    // District Distribution (Proponents)
    $distDistSql = "SELECT district, COUNT(*) as count, SUM(amount) as total_amount 
        FROM proponents 
        WHERE district IS NOT NULL AND district != ''" . $yearConditionProp . $statusConditionProp . "
        GROUP BY district 
        ORDER BY count DESC";
    $distDist = $db->query($distDistSql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Worker Type Distribution
    $workerTypeSql = "SELECT type_of_worker, COUNT(*) as count 
        FROM beneficiaries 
        WHERE type_of_worker IS NOT NULL AND type_of_worker != ''" . $yearConditionBenef . $statusConditionBenef . "
        GROUP BY type_of_worker 
        ORDER BY count DESC";
    $workerType = $db->query($workerTypeSql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Funding Source Breakdown
    $fundingSql = "SELECT 
        CASE 
            WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
            ELSE source_of_funds
        END as source_of_funds,
        COUNT(*) as count, 
        SUM(amount) as total_amount 
        FROM proponents WHERE 1=1" . $yearConditionProp . $statusConditionProp . "
        GROUP BY 
            CASE 
                WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
                ELSE source_of_funds
            END
        ORDER BY total_amount DESC";
    $fundingSource = $db->query($fundingSql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Category Distribution
    $categorySql = "SELECT category, COUNT(*) as count 
        FROM proponents 
        WHERE category IS NOT NULL AND category != ''" . $yearConditionProp . $statusConditionProp . "
        GROUP BY category 
        ORDER BY count DESC";
    $categoryDist = $db->query($categorySql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly Trends (Beneficiaries)
    $monthlyBenefSql = "SELECT 
        DATE_FORMAT(date_approved, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(amount_worth) as total_amount
        FROM beneficiaries 
        WHERE date_approved IS NOT NULL" . $yearConditionBenef . $statusConditionBenef . "
        GROUP BY DATE_FORMAT(date_approved, '%Y-%m')
        ORDER BY month ASC";
    $monthlyBenef = $db->query($monthlyBenefSql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly Trends (Proponents)
    $monthlyPropSql = "SELECT 
        DATE_FORMAT(date_approved, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(amount) as total_amount,
        SUM(total_beneficiaries) as total_beneficiaries
        FROM proponents 
        WHERE date_approved IS NOT NULL" . $yearConditionProp . $statusConditionProp . "
        GROUP BY DATE_FORMAT(date_approved, '%Y-%m')
        ORDER BY month ASC";
    $monthlyProp = $db->query($monthlyPropSql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if filters resulted in zero data
    $hasData = ($benefStats['total'] > 0) || ($propStats['total'] > 0);
    
    echo json_encode([
        'success' => true,
        'filters' => [
            'year' => $year,
            'approved_only' => $approvedOnly
        ],
        'hasData' => $hasData,
        'beneficiaryStats' => $benefStats,
        'proponentStats' => $propStats,
        'municipalityDistribution' => $munDist,
        'districtDistribution' => $distDist,
        'workerTypeDistribution' => $workerType,
        'fundingSourceBreakdown' => $fundingSource,
        'categoryDistribution' => $categoryDist,
        'monthlyBeneficiaryTrends' => $monthlyBenef,
        'monthlyProponentTrends' => $monthlyProp,
        'fieldworkStats' => $fieldworkStats
    ]);
    
} catch (PDOException $e) {
    error_log('Dashboard Stats API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
