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

// -----------------------------------------------------------------------
// Province scoping
//   CROSS_PROVINCE_ROLES (super_admin, regional_director)
//          → may filter by ?province= GET param; if no param, sees all data.
//   everyone else
//          → session province is enforced unconditionally; ?province= ignored.
// -----------------------------------------------------------------------
$isSuperAdmin    = $auth->isSuperAdmin();
$isCrossProvince = $auth->isCrossProvince(); // true for super_admin + regional_director
$sessionProvince = $auth->getProvince();     // null for cross-province roles

if ($isCrossProvince) {
    // Cross-province role: optional province filter from GET
    $filterProvince = filter_input(INPUT_GET, 'province', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: null;
} else {
    // Province user: always scoped to session province, GET param ignored
    $filterProvince = $sessionProvince;
}

// Build reusable province WHERE fragments (prepared-statement style)
$hasProvinceFilter = ($filterProvince !== null);
$noDataGuard       = (!$isCrossProvince && $sessionProvince === null); // non-cross with no province → empty set

$year        = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$approvedOnly = filter_input(INPUT_GET, 'approved_only', FILTER_VALIDATE_BOOLEAN);

$db = Database::getInstance()->getConnection();

// Helper: builds [sql_fragment, params_array] for province condition
function provinceCondition(?string $province, bool $noData, string $col = 'province'): array {
    if ($noData)  return [' AND 1 = 0', []];
    if ($province === null) return ['', []];
    return [" AND {$col} = ?", [$province]];
}

try {
    [$pBenefSql, $pBenefParams] = provinceCondition($filterProvince, $noDataGuard);
    [$pPropSql,  $pPropParams]  = provinceCondition($filterProvince, $noDataGuard);

    $yearConditionBenef    = '';
    $yearConditionProp     = '';
    $yearConditionFieldwork = '';
    $yearParamsBenef       = [];
    $yearParamsProp        = [];
    $yearParamsFieldwork   = [];
    $statusConditionBenef  = '';
    $statusConditionProp   = '';

    if ($year) {
        $yearConditionBenef     = ' AND (YEAR(date_approved) = ? OR (date_approved IS NULL AND YEAR(created_at) = ?))';
        $yearParamsBenef        = [(int)$year, (int)$year];
        $yearConditionProp      = ' AND (YEAR(date_approved) = ? OR (date_approved IS NULL AND YEAR(created_at) = ?))';
        $yearParamsProp         = [(int)$year, (int)$year];
        $yearConditionFieldwork = ' AND YEAR(start_date) = ?';
        $yearParamsFieldwork    = [(int)$year];
    }

    if ($approvedOnly) {
        $statusConditionBenef = " AND status = 'approved'";
        $statusConditionProp  = " AND status = 'approved'";
    }

    // ------------------------------------------------------------------
    // Beneficiary Statistics
    // ------------------------------------------------------------------
    $benefSql = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved'    THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
        SUM(CASE WHEN status = 'monitored'   THEN 1 ELSE 0 END) as monitored,
        SUM(CASE WHEN gender = 'Male'        THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN gender = 'Female'      THEN 1 ELSE 0 END) as female_count,
        SUM(amount_worth) as total_amount
    FROM beneficiaries WHERE 1=1" . $pBenefSql . $yearConditionBenef . $statusConditionBenef;

    $stmt = $db->prepare($benefSql);
    $stmt->execute(array_merge($pBenefParams, $yearParamsBenef));
    $benefStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Proponent Statistics
    // ------------------------------------------------------------------
    $propSql = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN proponent_type = 'LGU-associated'     THEN 1 ELSE 0 END) as lgu_count,
        SUM(CASE WHEN proponent_type = 'Non-LGU-associated' THEN 1 ELSE 0 END) as non_lgu_count,
        SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved'    THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
        SUM(CASE WHEN status = 'liquidated'  THEN 1 ELSE 0 END) as liquidated,
        SUM(CASE WHEN status = 'monitored'   THEN 1 ELSE 0 END) as monitored,
        SUM(total_beneficiaries) as total_beneficiaries,
        SUM(male_beneficiaries)  as total_male,
        SUM(female_beneficiaries) as total_female,
        SUM(amount) as total_amount
    FROM proponents WHERE 1=1" . $pPropSql . $yearConditionProp . $statusConditionProp;

    $stmt = $db->prepare($propSql);
    $stmt->execute(array_merge($pPropParams, $yearParamsProp));
    $propStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Fieldwork Schedule Statistics
    // Province scope applied; fieldwork_schedule has its own province col.
    // ------------------------------------------------------------------
    [$pFwSql, $pFwParams] = provinceCondition($filterProvince, $noDataGuard);
    $fieldworkSql = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'ongoing'   THEN 1 ELSE 0 END) as ongoing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed
    FROM fieldwork_schedule WHERE 1=1" . $pFwSql . $yearConditionFieldwork;

    $stmt = $db->prepare($fieldworkSql);
    $stmt->execute(array_merge($pFwParams, $yearParamsFieldwork));
    $fieldworkStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Municipality Distribution (Beneficiaries)
    // When no province filter is active (cross-province overview), include
    // the province column so the frontend can label bars by province.
    // ------------------------------------------------------------------
    if (!$hasProvinceFilter && $isCrossProvince) {
        // All-province view: group by province+municipality, return province info
        $munDistSql = "SELECT municipality, province, COUNT(*) as count
            FROM beneficiaries
            WHERE municipality IS NOT NULL AND municipality != ''"
            . $pBenefSql . $yearConditionBenef . $statusConditionBenef .
            " GROUP BY province, municipality ORDER BY count DESC LIMIT 15";
    } else {
        $munDistSql = "SELECT municipality, COUNT(*) as count
            FROM beneficiaries
            WHERE municipality IS NOT NULL AND municipality != ''"
            . $pBenefSql . $yearConditionBenef . $statusConditionBenef .
            " GROUP BY municipality ORDER BY count DESC LIMIT 10";
    }

    $stmt = $db->prepare($munDistSql);
    $stmt->execute(array_merge($pBenefParams, $yearParamsBenef));
    $munDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // District Distribution (Proponents)
    // ------------------------------------------------------------------
    $distDistSql = "SELECT district, COUNT(*) as count, SUM(amount) as total_amount
        FROM proponents
        WHERE district IS NOT NULL AND district != ''"
        . $pPropSql . $yearConditionProp . $statusConditionProp .
        " GROUP BY district ORDER BY count DESC";

    $stmt = $db->prepare($distDistSql);
    $stmt->execute(array_merge($pPropParams, $yearParamsProp));
    $distDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Worker Type Distribution
    // ------------------------------------------------------------------
    $workerTypeSql = "SELECT type_of_worker, COUNT(*) as count
        FROM beneficiaries
        WHERE type_of_worker IS NOT NULL AND type_of_worker != ''"
        . $pBenefSql . $yearConditionBenef . $statusConditionBenef .
        " GROUP BY type_of_worker ORDER BY count DESC";

    $stmt = $db->prepare($workerTypeSql);
    $stmt->execute(array_merge($pBenefParams, $yearParamsBenef));
    $workerType = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Funding Source Breakdown
    // ------------------------------------------------------------------
    $fundingSql = "SELECT
        CASE
            WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
            ELSE source_of_funds
        END as source_of_funds,
        COUNT(*) as count,
        SUM(amount) as total_amount
        FROM proponents WHERE 1=1"
        . $pPropSql . $yearConditionProp . $statusConditionProp .
        " GROUP BY
            CASE
                WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
                ELSE source_of_funds
            END
        ORDER BY total_amount DESC";

    $stmt = $db->prepare($fundingSql);
    $stmt->execute(array_merge($pPropParams, $yearParamsProp));
    $fundingSource = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Category Distribution
    // ------------------------------------------------------------------
    $categorySql = "SELECT category, COUNT(*) as count
        FROM proponents
        WHERE category IS NOT NULL AND category != ''"
        . $pPropSql . $yearConditionProp . $statusConditionProp .
        " GROUP BY category ORDER BY count DESC";

    $stmt = $db->prepare($categorySql);
    $stmt->execute(array_merge($pPropParams, $yearParamsProp));
    $categoryDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Monthly Trends (Beneficiaries)
    // ------------------------------------------------------------------
    $monthlyBenefSql = "SELECT
        DATE_FORMAT(date_approved, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(amount_worth) as total_amount
        FROM beneficiaries
        WHERE date_approved IS NOT NULL"
        . $pBenefSql . $yearConditionBenef . $statusConditionBenef .
        " GROUP BY DATE_FORMAT(date_approved, '%Y-%m') ORDER BY month ASC";

    $stmt = $db->prepare($monthlyBenefSql);
    $stmt->execute(array_merge($pBenefParams, $yearParamsBenef));
    $monthlyBenef = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Monthly Trends (Proponents)
    // ------------------------------------------------------------------
    $monthlyPropSql = "SELECT
        DATE_FORMAT(date_approved, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(amount) as total_amount,
        SUM(total_beneficiaries) as total_beneficiaries
        FROM proponents
        WHERE date_approved IS NOT NULL"
        . $pPropSql . $yearConditionProp . $statusConditionProp .
        " GROUP BY DATE_FORMAT(date_approved, '%Y-%m') ORDER BY month ASC";

    $stmt = $db->prepare($monthlyPropSql);
    $stmt->execute(array_merge($pPropParams, $yearParamsProp));
    $monthlyProp = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasData = ($benefStats['total'] > 0) || ($propStats['total'] > 0);

    echo json_encode([
        'success'                  => true,
        'filters'                  => [
            'province'      => $filterProvince,
            'year'          => $year,
            'approved_only' => $approvedOnly,
        ],
        'isCrossProvince'          => $isCrossProvince,
        'hasData'                  => $hasData,
        'beneficiaryStats'         => $benefStats,
        'proponentStats'           => $propStats,
        'fieldworkStats'           => $fieldworkStats,
        'municipalityDistribution' => $munDist,
        'districtDistribution'     => $distDist,
        'workerTypeDistribution'   => $workerType,
        'fundingSourceBreakdown'   => $fundingSource,
        'categoryDistribution'     => $categoryDist,
        'monthlyBeneficiaryTrends' => $monthlyBenef,
        'monthlyProponentTrends'   => $monthlyProp,
    ]);

} catch (PDOException $e) {
    error_log('Dashboard Stats API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
