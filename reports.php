<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';
require_once 'models/Proponent.php';
require_once 'includes/status-badge.php';

$auth = new Auth();
$auth->requireLogin();

$sessionProvince = $auth->getProvince();
$isSuperAdmin    = $auth->isSuperAdmin();
$isCrossProvince = $auth->isCrossProvince(); // super_admin OR regional_director

$beneficiaryModel = new Beneficiary();
$proponentModel = new Proponent();

// =========================================================
// Inputs (everything from $_GET is whitelisted or validated here)
// =========================================================

// Query values can arrive as arrays (?type[]=x); treat anything but a string as empty.
$getString = static function (string $key): string {
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};

$reportType = $getString('type') ?: 'beneficiaries';
if (!in_array($reportType, ['beneficiaries', 'proponents', 'cqpr'], true)) {
    $reportType = 'beneficiaries';
}
$dateFrom = $getString('date_from');
$dateTo = $getString('date_to');
$municipality = $getString('municipality');
$district = $getString('district');
$dateField = $getString('date_field');          // which date the range applies to
$selectedStatus = $getString('status');         // record status (pending, approved, ...)
$sourceOfFunds = $getString('source_of_funds');
$proponentLink = $getString('proponent_link');  // Individual report: linked / unlinked
if (!in_array($proponentLink, ['linked', 'unlinked'], true)) {
    $proponentLink = '';
}
$proponentId = ctype_digit($getString('proponent_id')) ? (int) $getString('proponent_id') : 0;

// Province filter: cross-province roles (super_admin, regional_director) can choose freely; provincial users are locked
$provinceFilter = '';
if ($isCrossProvince) {
    $provinceFilter = $getString('province');
    if (!in_array($provinceFilter, Auth::PROVINCES, true)) {
        $provinceFilter = '';
    }
} elseif ($sessionProvince) {
    $provinceFilter = $sessionProvince;
}

$reportData = [];
$reportGenerated = false;
$errorMessage = '';

// =========================================================
// Filter options
// =========================================================

// Date fields the date range can apply to, per report type. Labels that name the provincial office
// follow the province; "All provinces" gets a neutral label (Auth falls back to 'NOFO' without one).
$dateFieldOptionsFor = static function (string $province): array {
    $office = $province !== '' ? Auth::getOfficeForProvince($province) : 'Provincial/Field Office';
    $forwardedToOffice = $province !== ''
        ? Auth::getForwardedToOfficeLabel($province)
        : 'Date Forwarded of Approved Proposal to Provincial/Field Office';

    return [
        'beneficiaries' => [
            'date_complied_by_proponent' => 'Date Complied by Proponent/ACP',
            'date_forwarded_to_ro6'      => Auth::getForwardedToRoLabel(),
            'date_approved'              => 'Date Approved',
            'date_forwarded_to_nofo'     => $forwardedToOffice,
            'date_turnover'              => 'Date of Turn-over',
            'date_monitoring'            => 'Date of Monitoring',
        ],
        'proponents' => [
            'letter_of_intent_date'           => 'Letter of Intent - Date Received',
            'date_forwarded_to_ro6'           => 'Date Forwarded to NIR for RPMT',
            'date_complied_by_proponent'      => 'Date Complied by Proponent/ACP',
            'date_complied_by_proponent_nofo' => 'Date Complied by Proponent/ACP/' . $office,
            'date_forwarded_to_nofo'          => $forwardedToOffice,
            'date_approved'                   => 'Date Approved',
            'date_check_release'              => 'Date of Check Release',
            'check_date_issued'               => 'Check Date Issued',
            'or_date_issued'                  => 'OR Date Issued',
            'date_turnover'                   => 'Date of Turn-over',
            'date_implemented'                => 'Date Implemented',
            'date_liquidated'                 => 'Date Liquidated',
            'date_monitoring'                 => 'Date of Monitoring',
        ],
        'cqpr' => [], // the CQPR always counts records by Date Approved
    ];
};
$dateFieldOptionsByProvince = ['' => $dateFieldOptionsFor('')];
foreach (Auth::PROVINCES as $prov) {
    $dateFieldOptionsByProvince[$prov] = $dateFieldOptionsFor($prov);
}
$dateFieldOptionsByProvince[$provinceFilter] ??= $dateFieldOptionsFor($provinceFilter);
$dateFieldOptions = $dateFieldOptionsByProvince[$provinceFilter][$reportType];
$defaultDateBasisLabel = 'Date Approved, or date encoded if not approved yet';
$defaultDateBasisOption = 'Date Approved (default)';

$recordStatusOptions = [
    'beneficiaries' => [
        'pending' => 'Pending', 'approved' => 'Approved', 'implemented' => 'Implemented', 'monitored' => 'Monitored',
    ],
    'proponents' => [
        'pending' => 'Pending', 'approved' => 'Approved', 'implemented' => 'Implemented',
        'liquidated' => 'Liquidated', 'monitored' => 'Monitored',
    ],
    // The CQPR lists approved and later only
    'cqpr' => [
        'approved' => 'Approved', 'implemented' => 'Implemented', 'liquidated' => 'Liquidated', 'monitored' => 'Monitored',
    ],
];
$statusOptions = $recordStatusOptions[$reportType];

// Same values as the Source of Funds select on the beneficiary and proponent forms
$sourceOfFundsOptions = [
    'GAA'                    => 'GAA (General Appropriation Act)',
    'Centrally Managed Fund' => 'Centrally Managed Fund/Central Office',
    'Other'                  => 'Other',
];

// Old links carried the date field in ?status= (the dropdown used to mix the two up).
if ($dateField === '' && isset($dateFieldOptions[$selectedStatus])) {
    $dateField = $selectedStatus;
    $selectedStatus = '';
}
if (!isset($dateFieldOptions[$dateField])) {
    $dateField = '';
}
if (!isset($statusOptions[$selectedStatus])) {
    $selectedStatus = '';
}
if (!isset($sourceOfFundsOptions[$sourceOfFunds])) {
    $sourceOfFunds = '';
}

// Municipality (beneficiaries) and district (proponents) lists, keyed by province ('' = all provinces),
// so the form can swap them when the province changes. Provincial users only get their own province;
// one without a province gets nothing (province = NULL), like the models' fail-closed scope.
$db = Database::getInstance()->getConnection();
$locationLists = static function (string $table, string $column) use ($db, $isCrossProvince, $sessionProvince): array {
    $sql    = "SELECT DISTINCT province, {$column} AS value FROM {$table} WHERE {$column} <> ''";
    $params = [];
    if (!$isCrossProvince) {
        $sql     .= " AND province = ?";
        $params[] = $sessionProvince;
    }
    $stmt = $db->prepare($sql . " ORDER BY value");
    $stmt->execute($params);

    $byProvince = ['' => []];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byProvince[$row['province'] ?? ''][] = $row['value'];
        $byProvince[''][] = $row['value'];
    }
    return array_map(static fn(array $values): array => array_values(array_unique($values)), $byProvince);
};
$municipalitiesByProvince = $locationLists('beneficiaries', 'municipality');
$districtsByProvince = $locationLists('proponents', 'district');
$municipalities = $municipalitiesByProvince[$provinceFilter] ?? [];
$districts = $districtsByProvince[$provinceFilter] ?? [];
if (!in_array($municipality, $municipalities, true)) {
    $municipality = '';
}
if (!in_array($district, $districts, true)) {
    $district = '';
}

// Proponents the Individual report can be narrowed to, keyed by province ('' = all provinces) like the
// location lists above. Provincial users only get their own province; one without a province gets none.
$proponentsByProvince = ['' => []];
$proponentSql = "SELECT id, proponent_name, project_title, province FROM proponents";
$proponentParams = [];
if (!$isCrossProvince) {
    $proponentSql .= " WHERE province = ?";
    $proponentParams[] = $sessionProvince;
}
$stmt = $db->prepare($proponentSql . " ORDER BY proponent_name, project_title");
$stmt->execute($proponentParams);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $option = [
        'id'    => (int) $row['id'],
        'label' => trim($row['proponent_name'] . ($row['project_title'] !== '' && $row['project_title'] !== null ? ' — ' . $row['project_title'] : '')),
    ];
    if (($row['province'] ?? '') !== '') {
        $proponentsByProvince[$row['province']][] = $option;
    }
    $proponentsByProvince[''][] = $option;
}
$proponentOptions = $proponentsByProvince[$provinceFilter] ?? [];
$proponentLabels = array_column($proponentOptions, 'label', 'id');
if (!isset($proponentLabels[$proponentId]) || $proponentLink === 'unlinked') {
    $proponentId = 0; // unknown, out-of-scope, or contradicts "Not linked"
}
$selectedProponentLabel = $proponentLabels[$proponentId] ?? '';
if ($proponentId) {
    $proponentLink = 'linked';
}

// Quarter shortcuts for the date range: this year and the two before it.
$quarterPresets = [];
$currentYear = (int) date('Y');
for ($year = $currentYear; $year >= $currentYear - 2; $year--) {
    for ($quarter = 4; $quarter >= 1; $quarter--) {
        $quarterPresets["{$year}-{$quarter}"] = [
            'label' => "Q{$quarter} {$year}",
            'from'  => sprintf('%d-%02d-01', $year, $quarter * 3 - 2),
            'to'    => date('Y-m-t', strtotime(sprintf('%d-%02d-01', $year, $quarter * 3))),
        ];
    }
}

// A real Y-m-d calendar date (rejects "2026-02-30" and free text).
$isValidDate = static function (string $date): bool {
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
};

// Period shown in the report headers, e.g. "Jan 01, 2026 – Mar 31, 2026" or "From Jan 01, 2026".
$periodLabel = 'All dates';

if (isset($_GET['generate'])) {
    $reportGenerated = true;

    if (($dateFrom !== '' && !$isValidDate($dateFrom)) || ($dateTo !== '' && !$isValidDate($dateTo))) {
        $errorMessage = 'Invalid date: please pick the dates with the date picker (YYYY-MM-DD).';
        $reportGenerated = false;
    } elseif ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
        $errorMessage = 'Invalid date range: "Date From" cannot be later than "Date To".';
        $reportGenerated = false;
    }

    if ($reportGenerated) {
        if ($dateFrom !== '' && $dateTo !== '') {
            $periodLabel = date('M d, Y', strtotime($dateFrom)) . ' – ' . date('M d, Y', strtotime($dateTo));
        } elseif ($dateFrom !== '') {
            $periodLabel = 'From ' . date('M d, Y', strtotime($dateFrom));
        } elseif ($dateTo !== '') {
            $periodLabel = 'Until ' . date('M d, Y', strtotime($dateTo));
        }

        $filters = [
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
            'date_field'      => $dateField,
            'status'          => $selectedStatus,
            'source_of_funds' => $sourceOfFunds,
        ];
        if ($provinceFilter) {
            $filters['province'] = $provinceFilter;
        }

        if ($reportType === 'cqpr') {
            // Queried by includes/cqpr-report-template.php
        } elseif ($reportType === 'beneficiaries') {
            $filters['municipality'] = $municipality;
            $filters['proponent_id'] = $proponentId;
            $filters['proponent_link'] = $proponentLink;
            $reportData = $beneficiaryModel->getAll($filters);
        } else {
            $filters['district'] = $district;
            $reportData = $proponentModel->getAll($filters);
        }
    }
}

// The date column in the report table shows the date the range was applied to.
$dateColumnKey = $dateField ?: 'date_approved';
$dateColumnLabel = $dateField ? $dateFieldOptions[$dateField] : 'Date Approved';

// "Applied filters" line shown above the report (and printed / put in the PDF).
$appliedFilters = ['Province' => $provinceFilter ?: 'All provinces'];
if ($reportType === 'beneficiaries') {
    $appliedFilters['Municipality'] = $municipality ?: 'All';
    $appliedFilters['Proponent'] = $proponentId
        ? $selectedProponentLabel
        : ['' => 'All', 'linked' => 'Any linked proponent', 'unlinked' => 'Not linked to a proponent'][$proponentLink];
} elseif ($reportType === 'proponents') {
    $appliedFilters['District'] = $district ?: 'All';
}
$appliedFilters['Period'] = $periodLabel;
$appliedFilters['Date basis'] = $reportType === 'cqpr'
    ? 'Date Approved'
    : ($dateField ? $dateFieldOptions[$dateField] : $defaultDateBasisLabel);
$appliedFilters['Status'] = $selectedStatus
    ? $statusOptions[$selectedStatus]
    : ($reportType === 'cqpr' ? 'Approved and later' : 'All');
$appliedFilters['Source of funds'] = $sourceOfFunds ? $sourceOfFundsOptions[$sourceOfFunds] : 'All';
$filterSummaryText = implode(' · ', array_map(
    static fn(string $label, string $value): string => "{$label}: {$value}",
    array_keys($appliedFilters),
    $appliedFilters
));

// =========================================================
// Summary numbers and lookups for the beneficiary/proponent tables
// =========================================================

$isIndividualReport = $reportType === 'beneficiaries';
$showProvinceColumn = $provinceFilter === '';

$summary = ['amount' => 0.0, 'beneficiaries' => 0, 'male' => 0, 'female' => 0, 'linked' => 0, 'unlinked' => 0, 'byStatus' => []];
foreach ($reportData as $item) {
    if ($isIndividualReport) {
        $summary[empty($item['proponent_id']) ? 'unlinked' : 'linked']++;
        $summary['amount'] += (float) ($item['amount_worth'] ?? 0);
        $summary['beneficiaries']++;
        $summary['male'] += ($item['gender'] ?? '') === 'Male' ? 1 : 0;
        $summary['female'] += ($item['gender'] ?? '') === 'Female' ? 1 : 0;
    } else {
        $summary['amount'] += (float) ($item['amount'] ?? 0);
        $summary['beneficiaries'] += (int) ($item['total_beneficiaries'] ?? 0);
        $summary['male'] += (int) ($item['male_beneficiaries'] ?? 0);
        $summary['female'] += (int) ($item['female_beneficiaries'] ?? 0);
    }
    $itemStatus = $item['status'] ?? '';
    $summary['byStatus'][$itemStatus] = ($summary['byStatus'][$itemStatus] ?? 0) + 1;
}
// Status counts in pipeline order (pending → monitored), then anything unexpected
$orderedStatusCounts = [];
foreach (array_keys($statusOptions) as $statusKey) {
    if (isset($summary['byStatus'][$statusKey])) {
        $orderedStatusCounts[$statusKey] = $summary['byStatus'][$statusKey];
    }
}
$summary['byStatus'] = $orderedStatusCounts + $summary['byStatus'];

// Names of the proponents the listed beneficiaries are linked to
$linkedProponents = []; // id => [proponent_name, project_title, status]
if ($isIndividualReport && $reportData) {
    $proponentIds = array_values(array_unique(array_filter(array_map('intval', array_column($reportData, 'proponent_id')))));
    if ($proponentIds) {
        $stmt = $db->prepare('SELECT id, proponent_name, project_title, status FROM proponents WHERE id IN (' . implode(',', array_fill(0, count($proponentIds), '?')) . ')');
        $stmt->execute($proponentIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $linkedProponents[(int) $row['id']] = $row;
        }
    }
}

// Export file names, e.g. "beneficiaries_report_Siquijor_2026-07-01_to_2026-09-30" (the export adds today's date).
$exportFilename = preg_replace('/[^A-Za-z0-9_-]+/', '_', implode('_', array_filter([
    $reportType === 'cqpr' ? 'CQPR' : $reportType . '_report',
    $provinceFilter ?: 'All_provinces',
    $dateFrom !== '' ? $dateFrom : null,
    $dateTo !== '' ? 'to_' . $dateTo : null,
])));

// Formats a DATE column for the tables ('-' when empty)
$formatDate = static fn(?string $date): string => $date ? date('M d, Y', strtotime($date)) : '-';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .report-summary {
            background: linear-gradient(135deg, var(--dole-primary), var(--dole-secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--dole-border-radius);
            margin-bottom: 2rem;
        }
        .summary-item { text-align: center; }
        .summary-item h3 { font-size: clamp(1.25rem, 4vw, 2rem); margin-bottom: 0.5rem; overflow-wrap: anywhere; }
        .summary-item p { margin-bottom: 0; opacity: 0.9; }
        .summary-statuses {
            display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem;
            margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.25);
            font-size: 0.9rem;
        }
        .summary-statuses .badge { font-size: 0.8rem; font-weight: 500; }
        @media (max-width: 576px) {
            .report-summary { padding: 1.25rem; }
        }
        @media print {
            .dataTables_length, .dataTables_filter, .dataTables_paginate { display: none !important; }
            .report-summary { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <?php $currentPage = 'reports'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2><i class="bi bi-file-earmark-text"></i> Reports</h2>
                </div>

                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Generate Report</h5>
                        <form method="GET" action="" class="row g-3" id="reportForm">
                            <input type="hidden" name="generate" value="1">

                            <div class="col-12 col-sm-6 col-lg-3">
                                <label class="form-label" for="reportType">Report Type</label>
                                <select name="type" id="reportType" class="form-select" required>
                                    <option value="beneficiaries" <?php echo $reportType === 'beneficiaries' ? 'selected' : ''; ?>>Individual Beneficiaries</option>
                                    <option value="proponents" <?php echo $reportType === 'proponents' ? 'selected' : ''; ?>>Group Proponents</option>
                                    <option value="cqpr" <?php echo $reportType === 'cqpr' ? 'selected' : ''; ?>>CQPR (Consolidated Quarterly)</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3">
                                <?php if ($isCrossProvince): ?>
                                <label class="form-label" for="provinceFilter">Province</label>
                                <select name="province" id="provinceFilter" class="form-select">
                                    <option value="">All Provinces</option>
                                    <?php foreach (Auth::PROVINCES as $prov): ?>
                                    <option value="<?php echo htmlspecialchars($prov); ?>"
                                            <?php echo $provinceFilter === $prov ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <label class="form-label" for="provinceLocked">Province</label>
                                <input type="hidden" name="province" id="provinceFilter" value="<?php echo htmlspecialchars($sessionProvince ?? ''); ?>">
                                <select id="provinceLocked" class="form-select bg-light" disabled aria-describedby="provinceLockedHint">
                                    <option selected><?php echo htmlspecialchars($sessionProvince ?? 'All'); ?></option>
                                </select>
                                <small class="text-muted d-flex align-items-center gap-1 mt-1" id="provinceLockedHint">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    <span>Locked to your assigned province</span>
                                </small>
                                <?php endif; ?>
                            </div>

                            <div class="col-6 col-lg-3">
                                <label class="form-label" for="dateFrom">Date From</label>
                                <input type="date" name="date_from" id="dateFrom" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>

                            <div class="col-6 col-lg-3">
                                <label class="form-label" for="dateTo">Date To</label>
                                <input type="date" name="date_to" id="dateTo" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>

                            <div class="col-12">
                                <div class="d-flex flex-wrap align-items-center gap-2" role="group" aria-label="Date range shortcuts">
                                    <span class="small text-muted me-1">Quick range:</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="month">This month</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="quarter">This quarter</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="last-quarter">Last quarter</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="year">This year</button>
                                    <select id="quarterPreset" class="form-select form-select-sm w-auto" aria-label="Pick a quarter">
                                        <option value="">Pick a quarter…</option>
                                        <?php foreach ($quarterPresets as $key => $preset): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>"
                                                <?php echo ($dateFrom === $preset['from'] && $dateTo === $preset['to']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($preset['label']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none" data-preset="clear">Clear dates</button>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3" id="municipalityGroup" <?php echo $reportType === 'beneficiaries' ? '' : 'style="display: none;"'; ?>>
                                <label class="form-label" for="municipality">Municipality</label>
                                <select name="municipality" id="municipality" class="form-select" <?php echo $reportType === 'beneficiaries' ? '' : 'disabled'; ?>>
                                    <option value="">All</option>
                                    <?php foreach ($municipalities as $mun): ?>
                                    <option value="<?php echo htmlspecialchars($mun); ?>" <?php echo $municipality === $mun ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mun); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3" id="districtGroup" <?php echo $reportType === 'proponents' ? '' : 'style="display: none;"'; ?>>
                                <label class="form-label" for="district">District</label>
                                <select name="district" id="district" class="form-select" <?php echo $reportType === 'proponents' ? '' : 'disabled'; ?>>
                                    <option value="">All</option>
                                    <?php foreach ($districts as $dist): ?>
                                    <option value="<?php echo htmlspecialchars($dist); ?>" <?php echo $district === $dist ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dist); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3" id="dateFieldGroup" <?php echo $reportType === 'cqpr' ? 'style="display: none;"' : ''; ?>>
                                <label class="form-label" for="dateField">Date basis <i class="bi bi-info-circle text-muted" aria-hidden="true" title="Which date the range is checked against. The default also includes records not approved yet, by the date they were encoded."></i></label>
                                <select name="date_field" id="dateField" class="form-select" aria-describedby="dateFieldHint" <?php echo $reportType === 'cqpr' ? 'disabled' : ''; ?>>
                                    <option value=""><?php echo htmlspecialchars($defaultDateBasisOption); ?></option>
                                    <?php foreach ($dateFieldOptions as $key => $label): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $dateField === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="visually-hidden" id="dateFieldHint">Which date the range is checked against. The default also includes records not approved yet, by the date they were encoded.</div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3" id="cqprDateNote" <?php echo $reportType === 'cqpr' ? '' : 'style="display: none;"'; ?>>
                                <span class="form-label d-block">Date basis</span>
                                <p class="form-text mt-2 mb-0"><i class="bi bi-info-circle" aria-hidden="true"></i> The CQPR counts records by Date Approved.</p>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3">
                                <label class="form-label" for="status">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value=""><?php echo $reportType === 'cqpr' ? 'Approved and later' : 'All'; ?></option>
                                    <?php foreach ($statusOptions as $key => $label): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $selectedStatus === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3">
                                <label class="form-label" for="sourceOfFunds">Source of Funds</label>
                                <select name="source_of_funds" id="sourceOfFunds" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($sourceOfFundsOptions as $key => $label): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $sourceOfFunds === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-3" id="proponentLinkGroup" <?php echo $reportType === 'beneficiaries' ? '' : 'style="display: none;"'; ?>>
                                <label class="form-label" for="proponentLink">Proponent link</label>
                                <select name="proponent_link" id="proponentLink" class="form-select" <?php echo $reportType === 'beneficiaries' ? '' : 'disabled'; ?>>
                                    <option value="">All</option>
                                    <option value="linked" <?php echo $proponentLink === 'linked' ? 'selected' : ''; ?>>Linked to a proponent</option>
                                    <option value="unlinked" <?php echo $proponentLink === 'unlinked' ? 'selected' : ''; ?>>Not linked to any proponent</option>
                                </select>
                            </div>

                            <div class="col-12 col-lg-6" id="proponentGroup" <?php echo $reportType === 'beneficiaries' ? '' : 'style="display: none;"'; ?>>
                                <label class="form-label" for="proponentSearch">Proponent</label>
                                <div class="position-relative" id="proponentCombobox">
                                    <input type="hidden" name="proponent_id" id="proponentId" value="<?php echo $proponentId ?: ''; ?>" <?php echo $reportType === 'beneficiaries' ? '' : 'disabled'; ?>>
                                    <div class="input-group">
                                        <input type="text" id="proponentSearch" class="form-control" autocomplete="off"
                                               placeholder="All proponents (type to search)"
                                               value="<?php echo htmlspecialchars($selectedProponentLabel); ?>"
                                               <?php echo ($reportType === 'beneficiaries' && $proponentLink !== 'unlinked') ? '' : 'disabled'; ?>>
                                        <button type="button" id="proponentClear" class="btn btn-outline-secondary" title="Clear proponent" aria-label="Clear proponent">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div id="proponentDropdown" class="list-group position-absolute w-100 shadow-sm"
                                         style="z-index: 1050; max-height: 260px; overflow-y: auto; display: none; top: 100%;"></div>
                                </div>
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Create Report
                                </button>
                                <a href="reports.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($reportType === 'cqpr' && $reportGenerated): ?>
                <!-- CQPR Report -->
                <?php include 'includes/cqpr-report-template.php'; ?>
                <?php elseif ($reportGenerated): ?>
                <?php if (empty($reportData)): ?>
                <div class="alert alert-warning" role="status">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i> <strong>No records found</strong> matching your filter criteria. Try adjusting the date range, location, or status filters.
                    <div class="small mt-1"><?php echo htmlspecialchars($filterSummaryText); ?></div>
                </div>
                <?php else: ?>
                <div class="report-summary">
                    <div class="row g-3">
                        <div class="col-6 col-lg-3 summary-item">
                            <h3><?php echo number_format(count($reportData)); ?></h3>
                            <p><?php echo $isIndividualReport ? 'Individual Projects' : 'Group Projects'; ?></p>
                        </div>
                        <div class="col-6 col-lg-3 summary-item">
                            <h3>₱<?php echo number_format($summary['amount'], 2); ?></h3>
                            <p>Total Amount</p>
                        </div>
                        <div class="col-6 col-lg-3 summary-item">
                            <h3><?php echo number_format($summary['beneficiaries']); ?></h3>
                            <p>Beneficiaries</p>
                        </div>
                        <div class="col-6 col-lg-3 summary-item">
                            <h3><?php echo number_format($summary['female']); ?> / <?php echo number_format($summary['male']); ?></h3>
                            <p>Female / Male</p>
                        </div>
                    </div>
                    <div class="summary-statuses">
                        <span class="me-1">By status:</span>
                        <?php foreach ($summary['byStatus'] as $statusKey => $statusCount): ?>
                        <span class="badge <?php echo statusBadgeClass($statusKey); ?>">
                            <?php echo htmlspecialchars($statusOptions[$statusKey] ?? ucfirst($statusKey)); ?>: <?php echo number_format($statusCount); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($isIndividualReport): ?>
                    <div class="summary-statuses">
                        <span class="me-1">Proponent link:</span>
                        <span class="badge text-bg-success">Linked: <?php echo number_format($summary['linked']); ?></span>
                        <span class="badge text-bg-secondary">Not linked: <?php echo number_format($summary['unlinked']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h5 class="mb-1">
                                <?php echo $reportType === 'beneficiaries' ? 'Individual Beneficiaries' : 'Group Proponents'; ?> Report
                            </h5>
                            <?php include 'includes/report-applied-filters.php'; ?>
                        </div>
                        <div class="no-print d-flex flex-wrap gap-2">
                            <button type="button" data-export="pdf" class="btn btn-sm btn-danger" title="Export as PDF">
                                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
                            </button>
                            <button type="button" data-export="excel" class="btn btn-sm btn-success" title="Export as Excel (.xlsx)">
                                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel
                            </button>
                            <button type="button" data-export="csv" class="btn btn-sm btn-info" title="Export as CSV">
                                <i class="bi bi-filetype-csv" aria-hidden="true"></i> CSV
                            </button>
                            <button type="button" data-export="print" class="btn btn-sm btn-primary" title="Print">
                                <i class="bi bi-printer" aria-hidden="true"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php if ($isIndividualReport): ?>
                            <table id="reportTable" class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Gender</th>
                                        <th>Location</th>
                                        <?php if ($showProvinceColumn): ?><th>Province</th><?php endif; ?>
                                        <th>Project Name</th>
                                        <th>Proponent</th>
                                        <th>Proponent Link</th>
                                        <th>Type of Worker</th>
                                        <th>Source of Funds</th>
                                        <th data-sum="money">Amount</th>
                                        <th>Status</th>
                                        <th><?php echo htmlspecialchars($dateColumnLabel); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $item): ?>
                                    <?php
                                    $amount = (float) ($item['amount_worth'] ?? 0);
                                    $linkedProponent = $linkedProponents[(int) ($item['proponent_id'] ?? 0)] ?? null;
                                    ?>
                                    <tr>
                                        <td data-num="<?php echo (int) $item['id']; ?>"><?php echo (int) $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars(trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''))); ?></td>
                                        <td><?php echo htmlspecialchars($item['gender'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(implode(', ', array_filter([$item['barangay'] ?? '', $item['municipality'] ?? '']))); ?></td>
                                        <?php if ($showProvinceColumn): ?><td><?php echo htmlspecialchars($item['province'] ?? ''); ?></td><?php endif; ?>
                                        <td><?php echo htmlspecialchars($item['project_name'] ?? ''); ?></td>
                                        <td>
                                            <?php if ($linkedProponent): ?>
                                            <?php echo htmlspecialchars($linkedProponent['proponent_name']); ?>
                                            <?php if (($linkedProponent['project_title'] ?? '') !== ''): ?>
                                            – <span class="text-muted small"><?php echo htmlspecialchars($linkedProponent['project_title']); ?></span>
                                            <?php endif; ?>
                                            (<?php echo htmlspecialchars(ucfirst($linkedProponent['status'] ?? '')); ?>)
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?php echo $linkedProponent ? 'text-bg-success' : 'text-bg-secondary'; ?> badge-status"><?php echo $linkedProponent ? 'Linked' : 'Not linked'; ?></span></td>
                                        <td><?php echo htmlspecialchars($item['type_of_worker'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['source_of_funds'] ?? ''); ?></td>
                                        <td class="text-end text-nowrap" data-order="<?php echo $amount; ?>" data-num="<?php echo $amount; ?>">₱<?php echo number_format($amount, 2); ?></td>
                                        <td><span class="badge <?php echo statusBadgeClass($item['status'] ?? ''); ?> badge-status"><?php echo htmlspecialchars(ucfirst($item['status'] ?? '')); ?></span></td>
                                        <td class="text-nowrap" data-order="<?php echo htmlspecialchars($item[$dateColumnKey] ?? ''); ?>" data-date="<?php echo htmlspecialchars($item[$dateColumnKey] ?? ''); ?>"><?php echo $formatDate($item[$dateColumnKey] ?? null); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>Total</td>
                                        <?php echo str_repeat('<td></td>', $showProvinceColumn ? 9 : 8); ?>
                                        <td class="text-end text-nowrap" data-num="<?php echo $summary['amount']; ?>">₱<?php echo number_format($summary['amount'], 2); ?></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php else: ?>
                            <table id="reportTable" class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Control #</th>
                                        <th>Proponent Name</th>
                                        <th>Project Title</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <?php if ($showProvinceColumn): ?><th>Province</th><?php endif; ?>
                                        <th>District</th>
                                        <th>Source of Funds</th>
                                        <th data-sum="money">Amount</th>
                                        <th data-sum="count">Beneficiaries</th>
                                        <th data-sum="count">Male</th>
                                        <th data-sum="count">Female</th>
                                        <th>Status</th>
                                        <th><?php echo htmlspecialchars($dateColumnLabel); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $item): ?>
                                    <?php
                                    $amount = (float) ($item['amount'] ?? 0);
                                    $total  = (int) ($item['total_beneficiaries'] ?? 0);
                                    $male   = (int) ($item['male_beneficiaries'] ?? 0);
                                    $female = (int) ($item['female_beneficiaries'] ?? 0);
                                    ?>
                                    <tr>
                                        <td data-num="<?php echo (int) $item['id']; ?>"><?php echo (int) $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['control_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['proponent_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['project_title'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['proponent_type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['category'] ?? ''); ?></td>
                                        <?php if ($showProvinceColumn): ?><td><?php echo htmlspecialchars($item['province'] ?? ''); ?></td><?php endif; ?>
                                        <td><?php echo htmlspecialchars($item['district'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['source_of_funds'] ?? ''); ?></td>
                                        <td class="text-end text-nowrap" data-order="<?php echo $amount; ?>" data-num="<?php echo $amount; ?>">₱<?php echo number_format($amount, 2); ?></td>
                                        <td class="text-end" data-order="<?php echo $total; ?>" data-num="<?php echo $total; ?>"><?php echo number_format($total); ?></td>
                                        <td class="text-end" data-order="<?php echo $male; ?>" data-num="<?php echo $male; ?>"><?php echo number_format($male); ?></td>
                                        <td class="text-end" data-order="<?php echo $female; ?>" data-num="<?php echo $female; ?>"><?php echo number_format($female); ?></td>
                                        <td><span class="badge <?php echo statusBadgeClass($item['status'] ?? ''); ?> badge-status"><?php echo htmlspecialchars(ucfirst($item['status'] ?? '')); ?></span></td>
                                        <td class="text-nowrap" data-order="<?php echo htmlspecialchars($item[$dateColumnKey] ?? ''); ?>" data-date="<?php echo htmlspecialchars($item[$dateColumnKey] ?? ''); ?>"><?php echo $formatDate($item[$dateColumnKey] ?? null); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>Total</td>
                                        <?php echo str_repeat('<td></td>', $showProvinceColumn ? 8 : 7); ?>
                                        <td class="text-end text-nowrap" data-num="<?php echo $summary['amount']; ?>">₱<?php echo number_format($summary['amount'], 2); ?></td>
                                        <td class="text-end" data-num="<?php echo $summary['beneficiaries']; ?>"><?php echo number_format($summary['beneficiaries']); ?></td>
                                        <td class="text-end" data-num="<?php echo $summary['male']; ?>"><?php echo number_format($summary['male']); ?></td>
                                        <td class="text-end" data-num="<?php echo $summary['female']; ?>"><?php echo number_format($summary['female']); ?></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
                <?php endif; ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Select report parameters above and click "Create Report" to generate a report.
                </div>
                <?php endif; ?>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <?php if ($reportGenerated): ?>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <?php endif; ?>
    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                pageLength: 50,
                lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
                dom: 'lfrtip',
                order: [],          // keep the server's order (newest first) until a header is clicked
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries"
                },
                // Totals row follows the search box: sum the data-num of every matching row
                // in the columns whose header has data-sum ("money" or "count").
                footerCallback: function() {
                    var api = this.api();
                    api.columns().every(function(index) {
                        var kind = $(this.header()).data('sum');
                        if (!kind) return;
                        var total = 0;
                        api.column(index, { search: 'applied' }).nodes().toArray().forEach(function(cell) {
                            total += parseFloat(cell.dataset.num) || 0;
                        });
                        var text = kind === 'money'
                            ? '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                            : total.toLocaleString('en-PH');
                        $(this.footer()).attr('data-num', total).text(text);
                    });
                }
            });

            // ---- Exports (and an activity-log entry for each) ----
            var reportMeta = <?php echo json_encode([
                'type'     => $reportType,
                'title'    => $reportType === 'beneficiaries' ? 'Beneficiaries Report' : ($reportType === 'proponents' ? 'Proponents Report' : 'CQPR'),
                'subtitle' => $filterSummaryText,
                'filename' => $exportFilename,
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

            // Global so the CQPR template's buttons can log too. Logging failures never block an export.
            window.logReportExport = function(format, rowCount) {
                var body = new FormData();
                body.append('format', format);
                body.append('report', reportMeta.type);
                body.append('rows', rowCount);
                body.append('filters', reportMeta.subtitle);
                fetch('api/log-report-export.php', { method: 'POST', body: body }).catch(function() {});
            };

            $('[data-export]').on('click', function() {
                var format = $(this).data('export');
                var table = document.querySelector('#reportTable');
                if (!table) return;
                var rowCount = DILP.export._rows(table).length;
                if (format === 'pdf') {
                    DILP.export.toPDF('#reportTable', reportMeta.title, reportMeta.subtitle);
                } else if (format === 'excel') {
                    DILP.export.toExcel('#reportTable', reportMeta.filename, { title: reportMeta.title, subtitle: reportMeta.subtitle });
                } else if (format === 'csv') {
                    DILP.export.toCSV('#reportTable', reportMeta.filename);
                } else if (format === 'print') {
                    DILP.export.print('#reportTable');
                }
                window.logReportExport(format, rowCount);
            });
            
            // Option lists for every province and report type, so the form can follow its own dropdowns.
            var filterData = <?php echo json_encode([
                'dateFields'       => $dateFieldOptionsByProvince,
                'statuses'         => $recordStatusOptions,
                'municipalities'   => $municipalitiesByProvince,
                'districts'        => $districtsByProvince,
                'proponents'       => $proponentsByProvince,
                'defaultDateBasis' => $defaultDateBasisOption,
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

            var $type = $('#reportType');
            var $province = $('#provinceFilter');

            // Replace a select's options, keeping the current choice when it is still offered.
            // options is {value: label} or, for location lists, an array of values.
            function fillSelect($select, options, allLabel) {
                var current = $select.val();
                $select.empty().append($('<option>').val('').text(allLabel));
                $.each(options || {}, function(key, label) {
                    $select.append($('<option>').val(Array.isArray(options) ? label : key).text(label));
                });
                var stillOffered = $select.find('option').filter(function() { return this.value === current; }).length > 0;
                $select.val(stillOffered ? current : '');
            }

            // Hidden filters are disabled too, so they are not submitted with the form.
            function showGroup(selector, visible) {
                $(selector).toggle(visible).find('select, input').prop('disabled', !visible);
            }

            // ---- Proponent picker (searchable, filtered client-side from filterData.proponents) ----
            var $proponentId = $('#proponentId'), $proponentSearch = $('#proponentSearch');
            var $proponentList = $('#proponentDropdown'), $proponentLink = $('#proponentLink');

            function proponentOptions() {
                return filterData.proponents[$province.val() || ''] || [];
            }

            function renderProponentList() {
                var term = $.trim($proponentSearch.val()).toLowerCase();
                var matches = proponentOptions().filter(function(p) {
                    return !term || p.label.toLowerCase().indexOf(term) !== -1;
                }).slice(0, 50);
                $proponentList.empty();
                if (!matches.length) {
                    $proponentList.append($('<div class="list-group-item text-muted small">').text('No matching proponents found.'));
                }
                matches.forEach(function(p) {
                    $('<button type="button" class="list-group-item list-group-item-action text-start">')
                        .text(p.label)
                        // mousedown (not click) so it fires before the input loses focus
                        .on('mousedown', function(e) {
                            e.preventDefault();
                            $proponentId.val(p.id);
                            $proponentSearch.val(p.label);
                            $proponentList.hide();
                        })
                        .appendTo($proponentList);
                });
                $proponentList.show();
            }

            // Drop a pick that is no longer offered (province changed) and lock the picker for "Not linked".
            function refreshProponentPicker() {
                var picked = parseInt($proponentId.val(), 10) || 0;
                var stillOffered = proponentOptions().some(function(p) { return p.id === picked; });
                if (picked && !stillOffered) clearProponent();
                var unlinked = $proponentLink.val() === 'unlinked';
                if (unlinked) clearProponent();
                var visible = $type.val() === 'beneficiaries';
                $proponentSearch.prop('disabled', !visible || unlinked);
                $('#proponentClear').prop('disabled', !visible || unlinked);
            }

            function clearProponent() {
                $proponentId.val('');
                $proponentSearch.val('');
                $proponentList.hide();
            }

            $proponentSearch.on('focus', renderProponentList).on('input', function() {
                $proponentId.val(''); // typing again un-picks until a result is chosen
                renderProponentList();
            }).on('blur', function() {
                $proponentList.hide();
                if (!$proponentId.val()) $proponentSearch.val(''); // free text is not a filter
            });
            $('#proponentClear').on('click', clearProponent);
            $proponentLink.on('change', refreshProponentPicker);

            function refreshFilters() {
                var type = $type.val();
                var province = $province.val() || '';
                var dateFields = (filterData.dateFields[province] || filterData.dateFields[''])[type] || {};

                fillSelect($('#municipality'), filterData.municipalities[province] || [], 'All');
                fillSelect($('#district'), filterData.districts[province] || [], 'All');
                fillSelect($('#dateField'), dateFields, filterData.defaultDateBasis);
                fillSelect($('#status'), filterData.statuses[type], type === 'cqpr' ? 'Approved and later' : 'All');

                showGroup('#municipalityGroup', type === 'beneficiaries');
                showGroup('#districtGroup', type === 'proponents');
                showGroup('#proponentLinkGroup', type === 'beneficiaries');
                showGroup('#proponentGroup', type === 'beneficiaries');
                refreshProponentPicker();
                showGroup('#dateFieldGroup', type !== 'cqpr');
                $('#cqprDateNote').toggle(type === 'cqpr');
            }

            $type.on('change', refreshFilters);
            $province.on('change', refreshFilters);
            refreshFilters(); // also corrects the form if the browser restored old values on Back

            // ---- Date range shortcuts (local dates, not UTC) ----
            function ymd(date) {
                return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            }

            function setRange(from, to) {
                $('#dateFrom').val(from ? ymd(from) : '');
                $('#dateTo').val(to ? ymd(to) : '');
            }

            $('[data-preset]').on('click', function() {
                var now = new Date();
                var y = now.getFullYear(), m = now.getMonth(), q = Math.floor(m / 3);
                switch ($(this).data('preset')) {
                    case 'month':        setRange(new Date(y, m, 1), new Date(y, m + 1, 0)); break;
                    case 'quarter':      setRange(new Date(y, q * 3, 1), new Date(y, q * 3 + 3, 0)); break;
                    case 'last-quarter': setRange(new Date(y, q * 3 - 3, 1), new Date(y, q * 3, 0)); break;
                    case 'year':         setRange(new Date(y, 0, 1), new Date(y, 11, 31)); break;
                    case 'clear':        setRange(null, null); break;
                }
                $('#quarterPreset').val('');
            });

            $('#quarterPreset').on('change', function() {
                var parts = this.value.split('-');
                if (parts.length !== 2) return;
                var year = parseInt(parts[0], 10), quarter = parseInt(parts[1], 10) - 1;
                setRange(new Date(year, quarter * 3, 1), new Date(year, quarter * 3 + 3, 0));
            });

            // Typing a date by hand no longer matches the picked quarter.
            $('#dateFrom, #dateTo').on('input', function() { $('#quarterPreset').val(''); });
        });
    </script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
</body>
</html>
