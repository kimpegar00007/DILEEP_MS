<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'includes/status-badge.php';
require_once 'models/Beneficiary.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();

$sessionProvince = $auth->getProvince();
$isSuperAdmin    = $auth->isSuperAdmin();
$isCrossProvince = $auth->isCrossProvince(); // super_admin OR regional_director
$canEdit         = $auth->hasRole(['admin', 'encoder']);

$beneficiaryModel = new Beneficiary();
$proponentModel   = new Proponent();

// =========================================================
// View and filter options
// =========================================================

$currentView = $_GET['view'] ?? 'beneficiaries';
if (!in_array($currentView, ['beneficiaries', 'proponents'], true)) {
    $currentView = 'beneficiaries';
}
$isBeneficiaryView = $currentView === 'beneficiaries';

$beneficiaryStatuses = ['pending', 'approved', 'implemented', 'monitored'];
$proponentStatuses   = ['pending', 'approved', 'implemented', 'liquidated', 'monitored'];
$proponentTypes      = ['LGU-associated', 'Non-LGU-associated', 'By Administration', 'Others'];
$categories          = ['Formation', 'Enhancement', 'Restoration'];

// Province filter: cross-province roles (super_admin, regional_director) can choose freely; provincial users are locked to session province
$provinceFilter = '';
if ($isCrossProvince) {
    $provinceFilter = in_array($_GET['province'] ?? '', Auth::PROVINCES, true) ? $_GET['province'] : '';
} elseif ($sessionProvince) {
    $provinceFilter = $sessionProvince;
}

// Values that don't belong to this view (e.g. status=liquidated carried over from the
// proponents view) are dropped, so no filter is ever applied without showing in the form.
$pickFrom = fn(string $key, array $allowed): string => in_array($_GET[$key] ?? '', $allowed, true) ? $_GET[$key] : '';
$getText  = fn(string $key): string => is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';

$search = $getText('search');

if ($isBeneficiaryView) {
    $filters = [
        'province'     => $provinceFilter,
        'municipality' => $getText('municipality'),
        'barangay'     => $getText('barangay'),
        'status'       => $pickFrom('status', $beneficiaryStatuses),
        'search'       => $search,
    ];
    $items = $beneficiaryModel->getAll($filters) ?: [];
} else {
    $filters = [
        'province'       => $provinceFilter,
        'proponent_type' => $pickFrom('proponent_type', $proponentTypes),
        'district'       => $getText('district'),
        'status'         => $pickFrom('status', $proponentStatuses),
        'category'       => $pickFrom('category', $categories),
        'search'         => $search,
    ];
    $items = $proponentModel->getAll($filters) ?: [];
}

// =========================================================
// Location options (province → municipality → barangays, province → districts)
// Scoped like the models: provincial users only see their own province.
// =========================================================

$db          = Database::getInstance()->getConnection();
$scopeSql    = $isCrossProvince ? '' : ' AND province = ?';
$scopeParams = $isCrossProvince ? [] : [$sessionProvince];

$locationTree = [];
$stmt = $db->prepare(
    "SELECT DISTINCT province, municipality, barangay FROM beneficiaries
     WHERE municipality <> '' AND barangay <> ''" . $scopeSql . "
     ORDER BY municipality, barangay"
);
$stmt->execute($scopeParams);
foreach ($stmt->fetchAll() as $row) {
    $locationTree[$row['province'] ?? ''][$row['municipality']][] = $row['barangay'];
}

$districtTree = [];
$stmt = $db->prepare(
    "SELECT DISTINCT province, district FROM proponents
     WHERE district IS NOT NULL AND district <> ''" . $scopeSql . "
     ORDER BY district"
);
$stmt->execute($scopeParams);
foreach ($stmt->fetchAll() as $row) {
    $districtTree[$row['province'] ?? ''][] = $row['district'];
}

// Options for the first render; the page JS rebuilds them when the province or municipality changes.
$provincesInScope = $provinceFilter !== '' ? [$provinceFilter] : array_keys($locationTree + $districtTree);
$municipalities = [];
$barangays      = [];
$districts      = [];
foreach ($provincesInScope as $prov) {
    foreach ($locationTree[$prov] ?? [] as $municipality => $brgys) {
        $municipalities[$municipality] = true;
        if (($filters['municipality'] ?? '') === '' || $filters['municipality'] === $municipality) {
            foreach ($brgys as $brgy) {
                $barangays[$brgy] = true;
            }
        }
    }
    foreach ($districtTree[$prov] ?? [] as $district) {
        $districts[$district] = true;
    }
}
// Keep a selected value listed even when it no longer exists, so the form shows what is applied.
$optionList = function (array $set, string $key) use ($filters): array {
    if (($filters[$key] ?? '') !== '') {
        $set[$filters[$key]] = true;
    }
    $list = array_map('strval', array_keys($set));
    natcasesort($list);
    return $list;
};
$municipalities = $optionList($municipalities, 'municipality');
$barangays      = $optionList($barangays, 'barangay');
$districts      = $optionList($districts, 'district');

// =========================================================
// URLs, active filters and pagination
// =========================================================

function unifiedUrl(array $params): string {
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'unified-beneficiaries-proponents.php' . ($params ? '?' . http_build_query($params) : '');
}

// Every filter that is actually applied, in the order the form shows them.
// Provincial users' province is fixed, so it is not a removable filter.
$filterLabels = $isBeneficiaryView
    ? ['province' => 'Province', 'search' => 'Search', 'municipality' => 'Municipality', 'barangay' => 'Barangay', 'status' => 'Status']
    : ['province' => 'Province', 'search' => 'Search', 'proponent_type' => 'Type', 'district' => 'District', 'category' => 'Category', 'status' => 'Status'];

$activeParams = [];
foreach (array_keys($filterLabels) as $key) {
    if ($key === 'province' && !$isCrossProvince) {
        continue;
    }
    if (($filters[$key] ?? '') !== '') {
        $activeParams[$key] = $filters[$key];
    }
}
$hasFilters = !empty($activeParams);
$baseParams = ['view' => $currentView] + $activeParams;
$clearUrl   = unifiedUrl(['view' => $currentView]);

// Switching views keeps only the filters both views share.
$sharedParams = ['province' => $isCrossProvince ? $provinceFilter : '', 'search' => $search];
$viewUrls = [
    'beneficiaries' => unifiedUrl(['view' => 'beneficiaries'] + $sharedParams),
    'proponents'    => unifiedUrl(['view' => 'proponents'] + $sharedParams),
];
$tableUrl = ($isBeneficiaryView ? 'beneficiaries.php' : 'proponents.php')
          . ($activeParams ? '?' . http_build_query($activeParams) : '');

const CARDS_PER_PAGE = 12;
$totalItems = count($items);
$totalPages = max(1, (int) ceil($totalItems / CARDS_PER_PAGE));
$pageNum    = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
$offset     = ($pageNum - 1) * CARDS_PER_PAGE;
$pageItems  = array_slice($items, $offset, CARDS_PER_PAGE);

$noun       = $isBeneficiaryView ? 'beneficiary' : 'proponent';
$nounPlural = $isBeneficiaryView ? 'beneficiaries' : 'proponents';
$addUrl     = $isBeneficiaryView ? 'beneficiary-form.php' : 'proponent-form.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiaries & Proponents - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        /* Unified Page Specific Styles */
        .unified-header {
            padding: 1.5rem 0 1rem;
            margin-bottom: 1.5rem;
        }

        .unified-header h1 {
            font-size: clamp(1.6rem, 1.2rem + 1.5vw, 2.25rem);
        }

        .view-toggle {
            display: inline-flex;
            background: rgba(0, 0, 0, 0.06);
            border-radius: 50px;
            padding: 4px;
            gap: 4px;
        }

        .view-toggle a {
            color: #5c656d;
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            white-space: nowrap;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .view-toggle a[aria-current="page"] {
            background: white;
            color: var(--dole-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .view-toggle a:hover:not([aria-current="page"]) {
            color: var(--dole-primary);
            background: rgba(255, 255, 255, 0.6);
        }

        .view-toggle a:focus-visible {
            outline: 3px solid var(--dole-primary);
            outline-offset: 2px;
        }

        .table-view-link {
            font-size: 0.8rem;
            color: #6c757d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .table-view-link:hover {
            color: var(--dole-primary);
        }

        .filter-section {
            background: var(--dole-light);
            border-radius: var(--dole-border-radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--dole-box-shadow);
        }

        .locked-field {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background-color: #e9ecef;
            color: #495057;
            cursor: not-allowed;
        }

        .results-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.4rem 0.25rem 0.75rem;
            border-radius: 50px;
            background: rgba(27, 122, 61, 0.1);
            color: var(--dole-secondary);
            font-size: 0.85rem;
            text-decoration: none;
            max-width: 100%;
        }

        .filter-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .filter-chip:hover,
        .filter-chip:focus-visible {
            background: rgba(27, 122, 61, 0.2);
            color: var(--dole-secondary);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .entity-card {
            background: white;
            border-radius: var(--dole-border-radius);
            box-shadow: var(--dole-box-shadow);
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .entity-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--dole-primary), var(--dole-accent));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .entity-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .entity-card:hover::before,
        .entity-card:has(.entity-link:focus-visible)::before {
            transform: scaleX(1);
        }

        .entity-card:has(.entity-link:focus-visible) {
            outline: 3px solid var(--dole-primary);
            outline-offset: 2px;
        }

        .entity-card .entity-link {
            color: inherit;
            text-decoration: none;
        }

        .entity-card .entity-link:focus-visible {
            outline: none;
        }

        .entity-card .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .entity-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dole-dark);
            margin: 0;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .entity-card .card-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
            overflow-wrap: anywhere;
        }

        .entity-card .card-content {
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .entity-card .info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            min-width: 0;
        }

        .entity-card .info-row i {
            color: var(--dole-primary);
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .entity-card .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .entity-card .amount {
            font-weight: 600;
            color: var(--dole-primary);
            font-size: 1.1rem;
        }

        .province-tag {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background: white;
            border-radius: var(--dole-border-radius);
            box-shadow: var(--dole-box-shadow);
        }

        .empty-state > i {
            font-size: 3.5rem;
            color: #6c757d;
        }

        /* Responsive Design */
        @media (max-width: 575.98px) {
            .header-actions,
            .header-actions .view-toggle {
                width: 100%;
            }

            .header-actions .view-toggle a {
                flex: 1;
                padding: 0.6rem 0.75rem;
            }

            .header-actions .btn {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .filter-section {
                padding: 1rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1025px) {
            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1400px) {
            .cards-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .entity-card,
            .entity-card::before {
                transition: none;
            }

            .entity-card:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <?php $currentPage = 'unified'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <!-- Unified Header -->
                <div class="unified-header d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <h1 class="mb-2">Beneficiaries & Proponents</h1>
                        <p class="mb-0 text-muted">Manage individual beneficiaries and group proponents in one place</p>
                    </div>
                    <div class="header-actions d-flex flex-column align-items-stretch align-items-sm-end gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <nav class="view-toggle" aria-label="Record type">
                                <a href="<?php echo htmlspecialchars($viewUrls['beneficiaries']); ?>"
                                   <?php echo $isBeneficiaryView ? 'aria-current="page"' : ''; ?>>
                                    <i class="bi bi-person" aria-hidden="true"></i> Beneficiaries
                                </a>
                                <a href="<?php echo htmlspecialchars($viewUrls['proponents']); ?>"
                                   <?php echo !$isBeneficiaryView ? 'aria-current="page"' : ''; ?>>
                                    <i class="bi bi-people" aria-hidden="true"></i> Proponents
                                </a>
                            </nav>
                            <?php if ($canEdit): ?>
                            <a href="<?php echo $addUrl; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add <?php echo $noun; ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo htmlspecialchars($tableUrl); ?>" class="table-view-link align-self-end">
                            <i class="bi bi-table" aria-hidden="true"></i> View as table
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <section class="filter-section" aria-labelledby="filterHeading">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <h2 class="h5 mb-0" id="filterHeading">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Filter <?php echo ucfirst($nounPlural); ?>
                        </h2>
                        <button class="btn btn-sm btn-outline-secondary d-md-none" type="button"
                                data-bs-toggle="collapse" data-bs-target="#filterBody"
                                aria-expanded="false" aria-controls="filterBody">
                            <i class="bi bi-sliders" aria-hidden="true"></i>
                            Filters<?php echo $hasFilters ? ' (' . count($activeParams) . ')' : ''; ?>
                        </button>
                    </div>
                    <div class="collapse d-md-block" id="filterBody">
                        <form method="GET" action="" class="row g-3 pt-3" id="filterForm">
                            <input type="hidden" name="view" value="<?php echo $currentView; ?>">
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <label class="form-label" for="filterProvince">Province</label>
                                <?php if ($isCrossProvince): ?>
                                <select name="province" id="filterProvince" class="form-select">
                                    <option value="">All Provinces</option>
                                    <?php foreach (Auth::PROVINCES as $prov): ?>
                                    <option value="<?php echo htmlspecialchars($prov); ?>"
                                            <?php echo $provinceFilter === $prov ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <div class="form-control locked-field" id="filterProvince"
                                     title="Locked to your assigned province" aria-readonly="true">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($sessionProvince ?? 'None assigned'); ?></span>
                                    <span class="visually-hidden">(locked to your assigned province)</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                                <label class="form-label" for="filterSearch">Search</label>
                                <input type="search" name="search" id="filterSearch" class="form-control"
                                       placeholder="<?php echo $isBeneficiaryView ? 'Name, project or proponent' : 'Name, project or control #'; ?>"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <?php if ($isBeneficiaryView): ?>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <label class="form-label" for="filterMunicipality">Municipality</label>
                                <select name="municipality" id="filterMunicipality" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($municipalities as $municipality): ?>
                                    <option value="<?php echo htmlspecialchars($municipality); ?>"
                                            <?php echo $filters['municipality'] === $municipality ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($municipality); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <label class="form-label" for="filterBarangay">Barangay</label>
                                <select name="barangay" id="filterBarangay" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?php echo htmlspecialchars($barangay); ?>"
                                            <?php echo $filters['barangay'] === $barangay ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($barangay); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <label class="form-label" for="filterType">Proponent Type</label>
                                <select name="proponent_type" id="filterType" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($proponentTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"
                                            <?php echo $filters['proponent_type'] === $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <label class="form-label" for="filterDistrict">District</label>
                                <select name="district" id="filterDistrict" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo htmlspecialchars($district); ?>"
                                            <?php echo $filters['district'] === $district ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <label class="form-label" for="filterCategory">Category</label>
                                <select name="category" id="filterCategory" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category; ?>"
                                            <?php echo $filters['category'] === $category ? 'selected' : ''; ?>><?php echo $category; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <label class="form-label" for="filterStatus">Status</label>
                                <select name="status" id="filterStatus" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($isBeneficiaryView ? $beneficiaryStatuses : $proponentStatuses as $status): ?>
                                    <option value="<?php echo $status; ?>"
                                            <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-lg col-xl-auto d-flex align-items-end justify-content-end gap-2">
                                <a href="<?php echo htmlspecialchars($clearUrl); ?>" class="btn btn-outline-secondary">
                                    Clear
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search" aria-hidden="true"></i> Apply
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Result summary + active filters -->
                <div class="results-bar">
                    <span class="fw-semibold">
                        <?php echo number_format($totalItems) . ' ' . ($totalItems === 1 ? $noun : $nounPlural); ?>
                    </span>
                    <?php if ($totalPages > 1): ?>
                    <span class="text-muted small">· page <?php echo $pageNum; ?> of <?php echo $totalPages; ?></span>
                    <?php endif; ?>
                    <?php foreach ($activeParams as $key => $value):
                        $display  = $key === 'status' ? ucfirst($value) : $value;
                        $chipText = $filterLabels[$key] . ': ' . ($key === 'search' ? '“' . $display . '”' : $display);
                        $without  = $baseParams;
                        unset($without[$key]);
                    ?>
                    <a class="filter-chip" href="<?php echo htmlspecialchars(unifiedUrl($without)); ?>"
                       aria-label="Remove filter <?php echo htmlspecialchars($chipText); ?>">
                        <span><?php echo htmlspecialchars($chipText); ?></span>
                        <i class="bi bi-x" aria-hidden="true"></i>
                    </a>
                    <?php endforeach; ?>
                    <?php if (count($activeParams) > 1): ?>
                    <a href="<?php echo htmlspecialchars($clearUrl); ?>" class="small">Clear all</a>
                    <?php endif; ?>
                </div>

                <!-- Cards -->
                <div id="cardsContainer">
                    <?php if (!empty($pageItems)): ?>
                    <div class="cards-grid">
                        <?php if ($isBeneficiaryView): ?>
                        <?php foreach ($pageItems as $beneficiary):
                            $fullName = $beneficiary['first_name'] . ' '
                                      . ($beneficiary['middle_name'] ? mb_substr($beneficiary['middle_name'], 0, 1) . '. ' : '')
                                      . $beneficiary['last_name']
                                      . ($beneficiary['suffix'] ? ' ' . $beneficiary['suffix'] : '');
                        ?>
                        <article class="entity-card" data-type="beneficiary" data-id="<?php echo (int) $beneficiary['id']; ?>">
                            <div class="card-header">
                                <div style="min-width: 0;">
                                    <h3 class="card-title">
                                        <a class="entity-link stretched-link" href="beneficiary-view.php?id=<?php echo (int) $beneficiary['id']; ?>">
                                            <?php echo htmlspecialchars($fullName); ?>
                                        </a>
                                    </h3>
                                    <div class="card-subtitle">
                                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars($beneficiary['barangay'] . ', ' . $beneficiary['municipality']); ?>
                                        <?php if ($isCrossProvince && $provinceFilter === '' && !empty($beneficiary['province'])): ?>
                                        <span class="badge text-bg-light border province-tag"><?php echo htmlspecialchars($beneficiary['province']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge <?php echo statusBadgeClass($beneficiary['status']); ?> badge-status">
                                    <?php echo htmlspecialchars(ucfirst($beneficiary['status'] ?? '')); ?>
                                </span>
                            </div>
                            <div class="card-content">
                                <div class="info-row">
                                    <i class="bi bi-briefcase" aria-hidden="true"></i>
                                    <span class="text-truncate"><?php echo htmlspecialchars($beneficiary['project_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($beneficiary['gender']); ?></span>
                                </div>
                                <?php if ($beneficiary['contact_number']): ?>
                                <div class="info-row">
                                    <i class="bi bi-telephone" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($beneficiary['contact_number']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($beneficiary['proponent_id'])):
                                    // Linked proponent name, cached per page of cards
                                    $linkedProponentCache ??= [];
                                    $lpId = (int) $beneficiary['proponent_id'];
                                    if (!array_key_exists($lpId, $linkedProponentCache)) {
                                        $lpRow = $proponentModel->findById($lpId);
                                        $linkedProponentCache[$lpId] = $lpRow ? $lpRow['proponent_name'] : null;
                                    }
                                    if ($linkedProponentCache[$lpId]): ?>
                                <div class="info-row">
                                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                                    <span class="text-truncate" title="<?php echo htmlspecialchars($linkedProponentCache[$lpId]); ?>">
                                        <?php echo htmlspecialchars($linkedProponentCache[$lpId]); ?>
                                    </span>
                                </div>
                                <?php endif; endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="amount">₱<?php echo number_format((float) $beneficiary['amount_worth'], 2); ?></div>
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </div>
                        </article>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <?php foreach ($pageItems as $proponent): ?>
                        <article class="entity-card" data-type="proponent" data-id="<?php echo (int) $proponent['id']; ?>">
                            <div class="card-header">
                                <div style="min-width: 0;">
                                    <h3 class="card-title">
                                        <a class="entity-link stretched-link" href="proponent-view.php?id=<?php echo (int) $proponent['id']; ?>">
                                            <?php echo htmlspecialchars($proponent['proponent_name']); ?>
                                        </a>
                                    </h3>
                                    <div class="card-subtitle">
                                        <i class="bi bi-briefcase" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars($proponent['project_title']); ?>
                                        <?php if ($isCrossProvince && $provinceFilter === '' && !empty($proponent['province'])): ?>
                                        <span class="badge text-bg-light border province-tag"><?php echo htmlspecialchars($proponent['province']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge <?php echo statusBadgeClass($proponent['status']); ?> badge-status">
                                    <?php echo htmlspecialchars(ucfirst($proponent['status'] ?? '')); ?>
                                </span>
                            </div>
                            <div class="card-content">
                                <div class="info-row">
                                    <i class="bi bi-tag" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($proponent['proponent_type']); ?></span>
                                </div>
                                <div class="info-row">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    <span><?php echo number_format((int) $proponent['total_beneficiaries']); ?> beneficiaries</span>
                                </div>
                                <?php if ($proponent['district']): ?>
                                <div class="info-row">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($proponent['district']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($proponent['control_number']): ?>
                                <div class="info-row">
                                    <i class="bi bi-hash" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($proponent['control_number']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="amount">₱<?php echo number_format((float) $proponent['amount'], 2); ?></div>
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </div>
                        </article>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($hasFilters): ?>
                    <div class="empty-state">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <h2 class="h4 mt-3">No matching <?php echo $nounPlural; ?></h2>
                        <p class="text-muted">No <?php echo $nounPlural; ?> match the current filters.</p>
                        <a href="<?php echo htmlspecialchars($clearUrl); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle" aria-hidden="true"></i> Clear filters
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                        <h2 class="h4 mt-3">No <?php echo $nounPlural; ?> yet</h2>
                        <p class="text-muted"><?php echo ucfirst($nounPlural); ?> you encode will show up here.</p>
                        <?php if ($canEdit): ?>
                        <a href="<?php echo $addUrl; ?>" class="btn btn-primary">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Add <?php echo $noun; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Card pagination" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">
                            Showing <?php echo $offset + 1; ?>–<?php echo min($offset + CARDS_PER_PAGE, $totalItems); ?> of <?php echo $totalItems; ?>
                        </small>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $pageNum <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars(unifiedUrl($baseParams + ['page' => $pageNum - 1])); ?>" aria-label="Previous page">
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                </a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php if ($p === 1 || $p === $totalPages || abs($p - $pageNum) <= 2): ?>
                            <li class="page-item <?php echo $p === $pageNum ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars(unifiedUrl($baseParams + ['page' => $p])); ?>"
                                   <?php echo $p === $pageNum ? 'aria-current="page"' : ''; ?>><?php echo $p; ?></a>
                            </li>
                            <?php elseif (abs($p - $pageNum) === 3): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                            <?php endif; ?>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $pageNum >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars(unifiedUrl($baseParams + ['page' => $pageNum + 1])); ?>" aria-label="Next page">
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Quick-view Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="detailModalTitle">Details</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailModalContent" aria-live="polite"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Close</button>
                    <?php if ($canEdit): ?>
                    <a href="#" class="btn btn-outline-primary" id="editEntityBtn">
                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                    </a>
                    <?php endif; ?>
                    <a href="#" class="btn btn-primary" id="viewFullDetailsBtn">View full details</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>
        // =========================================================
        // Location cascade: Province → Municipality → Barangay, Province → District
        // =========================================================
        (function () {
            const LOCATIONS = <?php echo json_encode((object) $locationTree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const DISTRICTS = <?php echo json_encode((object) $districtTree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            const provinceSel     = document.querySelector('select#filterProvince');
            const municipalitySel = document.getElementById('filterMunicipality');
            const barangaySel     = document.getElementById('filterBarangay');
            const districtSel     = document.getElementById('filterDistrict');

            const provincesInScope = () => {
                const p = provinceSel ? provinceSel.value : '';
                return p ? [p] : Object.keys(LOCATIONS).concat(Object.keys(DISTRICTS));
            };
            const sorted = (set) => [...set].sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base', numeric: true }));

            // Rebuild a select's options, keeping the current choice only if it is still offered.
            function fill(select, values) {
                if (!select) return;
                const current = select.value;
                select.length = 1; // keep "All"
                values.forEach(v => select.add(new Option(v, v, false, v === current)));
                if (!values.includes(current)) select.value = '';
            }

            function refreshBarangays() {
                const muni = municipalitySel ? municipalitySel.value : '';
                const set = new Set();
                provincesInScope().forEach(p => {
                    Object.entries(LOCATIONS[p] || {}).forEach(([m, brgys]) => {
                        if (!muni || m === muni) brgys.forEach(b => set.add(b));
                    });
                });
                fill(barangaySel, sorted(set));
            }

            function refreshForProvince() {
                const munis = new Set();
                const districts = new Set();
                provincesInScope().forEach(p => {
                    Object.keys(LOCATIONS[p] || {}).forEach(m => munis.add(m));
                    (DISTRICTS[p] || []).forEach(d => districts.add(d));
                });
                fill(municipalitySel, sorted(munis));
                fill(districtSel, sorted(districts));
                refreshBarangays();
            }

            if (provinceSel) provinceSel.addEventListener('change', refreshForProvince);
            if (municipalitySel) municipalitySel.addEventListener('change', refreshBarangays);
        })();

        // =========================================================
        // Quick-view modal: a plain click on a card opens it; modified clicks
        // (new tab, etc.) fall through to the card's real link.
        // =========================================================
        (function () {
            const modalEl   = document.getElementById('detailModal');
            const content   = document.getElementById('detailModalContent');
            const title     = document.getElementById('detailModalTitle');
            const viewBtn   = document.getElementById('viewFullDetailsBtn');
            const editBtn   = document.getElementById('editEntityBtn');
            let controller  = null;

            document.querySelectorAll('.entity-card .entity-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                    e.preventDefault();
                    const card = link.closest('.entity-card');
                    showDetailModal(card.dataset.type, card.dataset.id);
                });
            });

            function showDetailModal(type, id) {
                const isBeneficiary = type === 'beneficiary';
                title.textContent = isBeneficiary ? 'Beneficiary details' : 'Proponent details';
                viewBtn.href = (isBeneficiary ? 'beneficiary-view.php' : 'proponent-view.php') + '?id=' + encodeURIComponent(id);
                if (editBtn) editBtn.href = (isBeneficiary ? 'beneficiary-form.php' : 'proponent-form.php') + '?id=' + encodeURIComponent(id);
                content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div></div>';

                // Drop any earlier request so a slow response can't replace this card's details.
                if (controller) controller.abort();
                controller = new AbortController();

                fetch('api/get-entity-details.php?' + new URLSearchParams({ type, id }), { signal: controller.signal })
                    .then(res => res.text().then(html => ({ ok: res.ok, html })))
                    .then(({ ok, html }) => {
                        content.innerHTML = ok || html ? html : '<div class="alert alert-danger">Failed to load details.</div>';
                    })
                    .catch(err => {
                        if (err.name !== 'AbortError') {
                            content.innerHTML = '<div class="alert alert-danger">Failed to load details.</div>';
                        }
                    });

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        })();
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
