<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Dashboard.php';

$auth = new Auth();
// The bare site address (dileepsys.com/) always opens the public landing page, signed in or
// not. The app's own links name index.php, so the dashboard is still reached through them.
// Logged-out visitors who ask for index.php get the landing page too.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
if (basename($requestPath) !== 'index.php' || !$auth->isLoggedIn()) {
    header('Location: landing.php');
    exit;
}
$auth->requireLogin();

// -----------------------------------------------------------------------
// Province config — baked into JS for map centering
// -----------------------------------------------------------------------
$isSuperAdmin    = $auth->isSuperAdmin();
$isCrossProvince = $auth->isCrossProvince(); // true for super_admin + regional_director
$sessionProvince = $auth->getProvince();     // null for cross-province roles

// Province center coordinates [lat, lng, zoom]
$provinceCenters = [
    'Negros Occidental' => [10.67, 123.00, 9],
    'Negros Oriental'   => [9.50,  123.00, 9],
    'Siquijor'          => [9.20,  123.50, 11],
];

// Determine initial map center:
//   • Province user → their province center
//   • super_admin   → regional overview (all 3 provinces visible)
if ($sessionProvince !== null && isset($provinceCenters[$sessionProvince])) {
    $mapCenter = $provinceCenters[$sessionProvince];
} else {
    // Regional center covering Negros Occ, Negros Or, and Siquijor
    $mapCenter = [10.0, 123.0, 8];
}

// Every number, chart, map point and the overdue alert come from one place, the same one
// api/dashboard-stats.php uses when filters are applied (models/Dashboard.php).
$dashboardModel = new Dashboard();
$dashboardData  = $dashboardModel->getData();

$beneficiaryStats = $dashboardData['beneficiaryStats'];
$proponentStats   = $dashboardData['proponentStats'];
$fieldworkStats   = $dashboardData['fieldworkStats'];

$mapAreaLabel = $sessionProvince ?? 'All Provinces';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOLE DILEEP Monitoring System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        #map {
            height: 600px;
            border-radius: 10px;
            box-shadow: var(--dole-box-shadow);
        }
        .alert-overdue { border-left: 4px solid var(--dole-danger); }
        .leaflet-popup-content-wrapper { border-radius: 8px; }
        
        .dash-card .card-body { padding: 1.25rem; }

        /* Chart.js sizes the canvas to this box (responsive, no fixed aspect ratio) */
        .chart-box {
            position: relative;
            width: 100%;
            height: 300px;
        }

        /* Dimmed while filtered data loads; the old values stay readable */
        .is-loading {
            opacity: 0.55;
            pointer-events: none;
            transition: opacity 0.2s;
        }
        
        #widget-charts .card-body {
            min-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        
        @media (max-width: 768px) {
            #widget-charts .card-body {
                min-height: 280px;
            }
        }
        
        @media print {
            #widget-charts {
                page-break-inside: avoid;
            }
            .card {
                break-inside: avoid;
            }
            .fab-scroll-to-map {
                display: none !important;
            }
        }

        /* FAB Styles */
        .fab-scroll-to-map {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #ca0a0a 100%);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(255, 0, 0, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            animation: fabPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes fabPulse {
            0%, 100% {
                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
            }
            50% {
                box-shadow: 0 4px 24px rgba(13, 110, 253, 0.8);
            }
        }

        .fab-scroll-to-map:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.6);
            animation: none;
        }

        .fab-scroll-to-map:active {
            transform: scale(0.95);
        }

        .fab-scroll-to-map.show {
            opacity: 1;
            visibility: visible;
        }

        .bg-purple {
            background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%) !important;
        }

        .bg-teal {
            background: linear-gradient(135deg, #0f766e 0%, #20c997 100%) !important;
        }

        /* Stat cards: wrap long labels instead of cutting them off */
        #widget-stats .card-title,
        #widget-stats .stat-detail { white-space: normal; }

        /* Six stat cards across on xl screens */
        @media (min-width: 1200px) {
            .col-xl-stat {
                flex: 0 0 auto;
                width: 16.666667%;
            }

            #widget-stats .card-body { padding: 1rem; }
            #widget-stats .stat-text-wrap { margin-right: 0; }
            #widget-stats .stat-number { font-size: 1.25rem; }
            /* Peso total scales with the card so nine-digit amounts stay on one line */
            #widget-stats .stat-number[data-prefix] {
                font-size: clamp(0.75rem, calc(1.55vw - 6.5px), 1.25rem);
                white-space: nowrap;
            }
        }

        @media (min-width: 1200px) and (max-width: 1599px) {
            #widget-stats .stat-icon { display: none; }
        }

        @media (min-width: 1600px) {
            /* Icon is back, so leave it room */
            #widget-stats .stat-number[data-prefix] {
                font-size: clamp(0.75rem, calc(1.55vw - 10px), 1.25rem);
            }
        }

        @media (max-width: 1199px) {
            .col-xl-stat {
                flex: 0 0 auto;
                width: 33.333333%;
            }
        }

        @media (max-width: 767px) {
            .col-xl-stat {
                flex: 0 0 auto;
                width: 50%;
            }
        }

        @media (max-width: 768px) {
            .fab-scroll-to-map {
                bottom: 1.5rem;
                right: 1.5rem;
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .fab-scroll-to-map {
                bottom: 1rem;
                right: 1rem;
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <?php $currentPage = 'dashboard'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <?php
                    // Prepare a friendly, time-aware greeting using Philippine time (UTC+8) and the logged-in user's full name.
                    $userInfo = $auth->getUser();
                    $fullName = $userInfo['full_name'] ?? ($_SESSION['full_name'] ?? 'User');
                    date_default_timezone_set('Asia/Manila');
                    $hour = (int) date('H');
                    if ($hour >= 5 && $hour < 12) {
                        $timeGreeting = 'Good Morning';
                    } elseif ($hour >= 12 && $hour < 17) {
                        $timeGreeting = 'Good Afternoon';
                    } elseif ($hour >= 17 && $hour < 21) {
                        $timeGreeting = 'Good Evening';
                    } else {
                        $timeGreeting = 'Good Night';
                    }
                    ?>
                    <h2 id="dashboardGreeting">Hello, <?php echo htmlspecialchars($fullName); ?> — <?php echo $timeGreeting; ?>!</h2>
                    <div class="d-flex gap-2 no-print">
                        <button class="btn btn-outline-primary btn-sm" id="startTourBtn" aria-label="Start guided tour" title="Start a guided tour of the dashboard">
                            <i class="bi bi-play-circle"></i> Start Tour
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Customize dashboard">
                                <i class="bi bi-gear"></i> Customize
                            </button>
                        <ul class="dropdown-menu dropdown-menu-end p-3" style="min-width: 250px;">
                            <li><h6 class="dropdown-header px-0">Show/Hide Widgets</h6></li>
                            <li>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="toggleStats" checked onchange="DILP.dashboard.toggle('stats', this.checked)">
                                    <label class="form-check-label" for="toggleStats">Statistics Cards</label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="toggleStatus" checked onchange="DILP.dashboard.toggle('status', this.checked)">
                                    <label class="form-check-label" for="toggleStatus">Status Overview</label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="toggleOverdue" checked onchange="DILP.dashboard.toggle('overdue', this.checked)">
                                    <label class="form-check-label" for="toggleOverdue">Overdue Alerts</label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="toggleMap" checked onchange="DILP.dashboard.toggle('map', this.checked)">
                                    <label class="form-check-label" for="toggleMap">Project Map</label>
                                </div>
                            </li>
                            <li>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="toggleCharts" checked onchange="DILP.dashboard.toggle('charts', this.checked)">
                                    <label class="form-check-label" for="toggleCharts">Data Charts</label>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="btn btn-sm btn-outline-primary w-100" onclick="DILP.dashboard.resetPrefs()">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset to Default
                                </button>
                            </li>
                        </ul>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Filters -->
                <div class="card mb-4 no-print" id="dashboard-filters">
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label class="form-label mb-0 fw-semibold"><i class="bi bi-funnel"></i> Filter Data:</label>
                            </div>
                            <?php if ($isCrossProvince): ?>
                            <div class="col-auto">
                                <select id="filter-province" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">All Provinces</option>
                                    <option value="Negros Occidental">Negros Occidental</option>
                                    <option value="Negros Oriental">Negros Oriental</option>
                                    <option value="Siquijor">Siquijor</option>
                                </select>
                            </div>
                            <?php else: ?>
                            <!-- Province filter hidden: province users are scoped to their own province -->
                            <input type="hidden" id="filter-province" value="<?php echo htmlspecialchars($sessionProvince ?? ''); ?>">
                            <?php endif; ?>
                            <div class="col-auto">
                                <select id="filter-year" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">All Years</option>
                                    <?php 
                                    $currentYear = (int) date('Y');
                                    for ($y = $currentYear; $y >= $currentYear - 5; $y--): 
                                    ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="filter-approved-only">
                                    <label class="form-check-label" for="filter-approved-only" title="Approved, implemented, liquidated and monitored records">Approved &amp; later</label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary btn-sm" id="applyFiltersBtn" onclick="applyDashboardFilters()">
                                    <i class="bi bi-check-lg"></i> Apply
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetDashboardFilters()">
                                    <i class="bi bi-x-lg"></i> Reset
                                </button>
                            </div>
                            <div class="col-auto" id="filter-status" style="display: none;">
                                <span class="badge bg-info"><i class="bi bi-funnel-fill"></i> Filters Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4" id="widget-stats">
                    <div class="col-sm-6 col-xl-stat">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Total Proponents</h6>
                                        <h2 class="stat-number mb-0" data-stat="proponents" data-value="<?php echo (int) $proponentStats['total']; ?>" data-decimals="0"><?php echo number_format($proponentStats['total']); ?></h2>
                                        <small class="stat-detail" data-stat-detail="proponents">LGU: <?php echo number_format($proponentStats['lgu_count']); ?> | Non-LGU: <?php echo number_format($proponentStats['non_lgu_count']); ?></small>
                                    </div>
                                    <i class="bi bi-people stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-stat">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Total Beneficiaries</h6>
                                        <h2 class="stat-number mb-0" data-stat="beneficiaries" data-value="<?php echo (int) $beneficiaryStats['total']; ?>" data-decimals="0"><?php echo number_format($beneficiaryStats['total']); ?></h2>
                                        <small class="stat-detail" data-stat-detail="beneficiaries">Male: <?php echo number_format($beneficiaryStats['male_count']); ?> | Female: <?php echo number_format($beneficiaryStats['female_count']); ?></small>
                                    </div>
                                    <i class="bi bi-person stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-stat">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Beneficiaries (Groups)</h6>
                                        <h2 class="stat-number mb-0" data-stat="groupMembers" data-value="<?php echo (int) ($proponentStats['total_beneficiaries'] ?? 0); ?>" data-decimals="0"><?php echo number_format($proponentStats['total_beneficiaries'] ?? 0); ?></h2>
                                        <small class="stat-detail" data-stat-detail="groupMembers">Male: <?php echo number_format($proponentStats['total_male'] ?? 0); ?> | Female: <?php echo number_format($proponentStats['total_female'] ?? 0); ?></small>
                                    </div>
                                    <i class="bi bi-people-fill stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-stat">
                        <div class="card stat-card bg-purple text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Fieldwork Schedule</h6>
                                        <h2 class="stat-number mb-0" data-stat="fieldwork" data-value="<?php echo (int) $fieldworkStats['total']; ?>" data-decimals="0"><?php echo number_format($fieldworkStats['total']); ?></h2>
                                        <small class="stat-detail" data-stat-detail="fieldwork">Ongoing: <?php echo number_format($fieldworkStats['ongoing']); ?> | Completed: <?php echo number_format($fieldworkStats['completed']); ?></small>
                                    </div>
                                    <i class="bi bi-calendar-check stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-stat">
                        <div class="card stat-card bg-teal text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">PCL (Parents of Child Laborers)</h6>
                                        <?php $pclTotal = (int) ($beneficiaryStats['pcl_count'] ?? 0) + (int) ($proponentStats['pcl_count'] ?? 0); ?>
                                        <h2 class="stat-number mb-0" data-stat="pcl" data-value="<?php echo $pclTotal; ?>" data-decimals="0"><?php echo number_format($pclTotal); ?></h2>
                                        <small class="stat-detail" data-stat-detail="pcl">Individual: <?php echo number_format($beneficiaryStats['pcl_count'] ?? 0); ?> | Groups: <?php echo number_format($proponentStats['pcl_count'] ?? 0); ?></small>
                                    </div>
                                    <i class="bi bi-house-heart stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-stat">
                        <div class="card stat-card bg-warning text-dark h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Total Amount (Individual + Group Projects)</h6>
                                        <?php $totalAmount = (float) $beneficiaryStats['total_amount'] + (float) $proponentStats['total_amount']; ?>
                                        <h2 class="stat-number mb-0" data-stat="totalAmount" data-value="<?php echo htmlspecialchars($totalAmount); ?>" data-decimals="2" data-prefix="₱"><?php echo '₱' . number_format($totalAmount, 2); ?></h2>
                                    </div>
                                    <i class="bi bi-cash-stack stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Status Overview -->
                <div class="row g-3 mb-4" id="widget-status">
                    <div class="col-sm-12 col-md-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-person"></i> Individual Beneficiaries Status</h5>
                            </div>
                            <div class="card-body d-flex align-items-center">
                                <div class="row text-center w-100">
                                    <div class="col">
                                        <h4 class="text-secondary mb-1 stat-count" data-stat="beneficiaryStats.pending" data-value="<?php echo (int) $beneficiaryStats['pending']; ?>" data-decimals="0"><?php echo number_format($beneficiaryStats['pending']); ?></h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-primary mb-1 stat-count" data-stat="beneficiaryStats.approved" data-value="<?php echo (int) $beneficiaryStats['approved']; ?>" data-decimals="0"><?php echo number_format($beneficiaryStats['approved']); ?></h4>
                                        <small class="text-muted">Approved</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-success mb-1 stat-count" data-stat="beneficiaryStats.implemented" data-value="<?php echo (int) $beneficiaryStats['implemented']; ?>" data-decimals="0"><?php echo number_format($beneficiaryStats['implemented']); ?></h4>
                                        <small class="text-muted">Implemented</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-info mb-1 stat-count" data-stat="beneficiaryStats.monitored" data-value="<?php echo (int) $beneficiaryStats['monitored']; ?>" data-decimals="0"><?php echo number_format($beneficiaryStats['monitored']); ?></h4>
                                        <small class="text-muted">Monitored</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-12 col-md-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-people"></i> Group Proponents Status</h5>
                            </div>
                            <div class="card-body d-flex align-items-center">
                                <div class="row text-center w-100">
                                    <div class="col">
                                        <h4 class="text-secondary mb-1 stat-count" data-stat="proponentStats.pending" data-value="<?php echo (int) $proponentStats['pending']; ?>" data-decimals="0"><?php echo number_format($proponentStats['pending']); ?></h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-primary mb-1 stat-count" data-stat="proponentStats.approved" data-value="<?php echo (int) $proponentStats['approved']; ?>" data-decimals="0"><?php echo number_format($proponentStats['approved']); ?></h4>
                                        <small class="text-muted">Approved</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-success mb-1 stat-count" data-stat="proponentStats.implemented" data-value="<?php echo (int) $proponentStats['implemented']; ?>" data-decimals="0"><?php echo number_format($proponentStats['implemented']); ?></h4>
                                        <small class="text-muted">Implemented</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-warning mb-1 stat-count" data-stat="proponentStats.liquidated" data-value="<?php echo (int) $proponentStats['liquidated']; ?>" data-decimals="0"><?php echo number_format($proponentStats['liquidated']); ?></h4>
                                        <small class="text-muted">Liquidated</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-info mb-1 stat-count" data-stat="proponentStats.monitored" data-value="<?php echo (int) $proponentStats['monitored']; ?>" data-decimals="0"><?php echo number_format($proponentStats['monitored']); ?></h4>
                                        <small class="text-muted">Monitored</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Visualization Charts -->
                <div class="row g-3 mb-4" id="widget-charts">
                    <!-- Beneficiary Municipality Distribution -->
                    <div class="col-lg-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Top 10 Municipalities (Individual)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-box"><canvas id="municipalityChart"></canvas></div>
                                <p class="chart-empty text-muted mb-0 d-none" data-empty-for="municipalityChart"><i class="bi bi-inbox"></i> No data for these filters.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Proponent District Distribution -->
                    <div class="col-lg-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-pie-chart-fill"></i> District Distribution (Groups)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-box"><canvas id="districtChart"></canvas></div>
                                <p class="chart-empty text-muted mb-0 d-none" data-empty-for="districtChart"><i class="bi bi-inbox"></i> No data for these filters.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Worker Type Distribution -->
                    <div class="col-lg-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-diagram-3-fill"></i> Worker Type Distribution (Combined)</h5>
                                <small class="text-muted">A record with several worker types counts once under each.</small>
                            </div>
                            <div class="card-body">
                                <div class="chart-box"><canvas id="projectTypeChart"></canvas></div>
                                <p class="chart-empty text-muted mb-0 d-none" data-empty-for="projectTypeChart"><i class="bi bi-inbox"></i> No data for these filters.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Funding Source Breakdown -->
                    <div class="col-lg-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Funding Source Breakdown (Combined)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-box"><canvas id="fundingChart"></canvas></div>
                                <p class="chart-empty text-muted mb-0 d-none" data-empty-for="fundingChart"><i class="bi bi-inbox"></i> No data for these filters.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="col-lg-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-tags-fill"></i> Project Category Distribution (Groups)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-box"><canvas id="categoryChart"></canvas></div>
                                <p class="chart-empty text-muted mb-0 d-none" data-empty-for="categoryChart"><i class="bi bi-inbox"></i> No data for these filters.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trends -->
                    <div class="col-lg-6">
                        <div class="card dash-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Monthly Approval Trends (<span id="trendsPeriod">Last 12 Months</span>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-box"><canvas id="trendsChart"></canvas></div>
                                <p class="chart-empty text-muted mb-0 d-none" data-empty-for="trendsChart"><i class="bi bi-inbox"></i> No data for these filters.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Liquidations Alert -->
                <div id="widget-overdue"></div>

                <!-- Map Visualization -->
                <div class="card dash-card mb-4" id="widget-map">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Project Distribution Map - <span id="mapAreaLabel"><?php echo htmlspecialchars($mapAreaLabel); ?></span></h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="map"></div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="row text-center">
                            <div class="col-6">
                                <span class="badge bg-primary me-2">●</span> Individual Beneficiaries
                            </div>
                            <div class="col-6">
                                <span class="badge bg-success me-2">●</span> Group Proponents
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- FAB: Scroll to Map Button -->
    <button class="fab-scroll-to-map" id="fabScrollToMap" aria-label="Scroll to map" title="View Project Map">
        <i class="bi bi-geo-alt-fill"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>
        // =========================================================
        // Dashboard data: same structure api/dashboard-stats.php returns
        // =========================================================
        const DASHBOARD_DATA = <?php echo json_encode($dashboardData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        // Province center config (mirrors PHP $provinceCenters)
        const PROVINCE_CENTERS = <?php echo json_encode($provinceCenters, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        // Auto-center on logged-in user's province; cross-province roles see the regional overview
        const MAP_CENTER = <?php echo json_encode(array_values($mapCenter), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatCount(value) {
            return Number(value || 0).toLocaleString('en-PH');
        }

        // =========================================================
        // Stat counters: animate on first load; a filter update cancels
        // a running animation so it cannot write the old number back.
        // =========================================================
        const DashboardCounters = (function() {
            function format(value, decimals, prefix) {
                const text = decimals > 0
                    ? value.toLocaleString('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
                    : Math.round(value).toLocaleString('en-PH');
                return (prefix || '') + text;
            }

            function options(el) {
                return {
                    decimals: parseInt(el.getAttribute('data-decimals') || '0', 10),
                    prefix: el.getAttribute('data-prefix') || ''
                };
            }

            function stop(el) {
                if (el._counterRaf) cancelAnimationFrame(el._counterRaf);
                el._counterRaf = null;
            }

            function animate(el) {
                const { decimals, prefix } = options(el);
                const target = parseFloat(el.getAttribute('data-value')) || 0;
                if (!target) {
                    el.textContent = format(0, decimals, prefix);
                    return;
                }

                const duration = 1200 + Math.min(800, Math.abs(target) / 100 * 200); // scale with size
                const startTime = performance.now();

                function tick(now) {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
                    el.textContent = format(target * eased, decimals, prefix);
                    el._counterRaf = progress < 1 ? requestAnimationFrame(tick) : null;
                }

                el._counterRaf = requestAnimationFrame(tick);
            }

            function set(el, value) {
                stop(el);
                const { decimals, prefix } = options(el);
                const number = parseFloat(value) || 0;
                el.setAttribute('data-value', String(number));
                el.textContent = format(number, decimals, prefix);
            }

            return { animate, set };
        })();

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.stat-number[data-value], .stat-count[data-value]').forEach(DashboardCounters.animate);
        });

        function renderStats(data) {
            const bs = data.beneficiaryStats || {};
            const ps = data.proponentStats || {};
            const fs = data.fieldworkStats || {};

            const cards = {
                proponents:    [ps.total, `LGU: ${formatCount(ps.lgu_count)} | Non-LGU: ${formatCount(ps.non_lgu_count)}`],
                beneficiaries: [bs.total, `Male: ${formatCount(bs.male_count)} | Female: ${formatCount(bs.female_count)}`],
                groupMembers:  [ps.total_beneficiaries, `Male: ${formatCount(ps.total_male)} | Female: ${formatCount(ps.total_female)}`],
                fieldwork:     [fs.total, `Ongoing: ${formatCount(fs.ongoing)} | Completed: ${formatCount(fs.completed)}`],
                pcl:           [(bs.pcl_count || 0) + (ps.pcl_count || 0), `Individual: ${formatCount(bs.pcl_count)} | Groups: ${formatCount(ps.pcl_count)}`],
                totalAmount:   [(parseFloat(bs.total_amount) || 0) + (parseFloat(ps.total_amount) || 0), null]
            };

            Object.entries(cards).forEach(([key, [value, detail]]) => {
                const numberEl = document.querySelector(`#widget-stats [data-stat="${key}"]`);
                if (numberEl) DashboardCounters.set(numberEl, value);
                const detailEl = document.querySelector(`#widget-stats [data-stat-detail="${key}"]`);
                if (detailEl && detail !== null) detailEl.textContent = detail;
            });

            // Status overview: data-stat="beneficiaryStats.pending", "proponentStats.liquidated", ...
            document.querySelectorAll('#widget-status [data-stat]').forEach(el => {
                const [group, field] = el.getAttribute('data-stat').split('.');
                DashboardCounters.set(el, (data[group] || {})[field]);
            });
        }

        // =========================================================
        // Charts: rebuilt from the rows on every render, with an
        // empty-state message when there is nothing to plot.
        // =========================================================
        Chart.defaults.font.family = "'Inter', 'Segoe UI', 'Roboto', sans-serif";
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        const chartColors = {
            primary: '#0d6efd',
            success: '#198754',
            info: '#0dcaf0',
            warning: '#ffc107',
            danger: '#dc3545',
            secondary: '#6c757d',
            purple: '#6f42c1',
            orange: '#fd7e14',
            teal: '#20c997',
            pink: '#d63384'
        };

        const basePalette = [
            chartColors.primary, chartColors.success, chartColors.info,
            chartColors.warning, chartColors.danger, chartColors.purple,
            chartColors.orange, chartColors.teal, chartColors.pink, chartColors.secondary
        ];

        // Past the ten base colours, spread extra hues around the wheel so no slice goes grey
        function paletteFor(count) {
            return Array.from({ length: count }, (_, i) =>
                i < basePalette.length ? basePalette[i] : `hsl(${Math.round((i * 137.5) % 360)}, 55%, 50%)`
            );
        }

        const fundingColorMap = {
            'Not Specified': chartColors.secondary,
            'DOLE': chartColors.primary,
            'GAA': chartColors.success,
            'LGU': chartColors.info,
            'NGO': chartColors.warning,
            'TUPAD': chartColors.purple,
            'SPES': chartColors.teal
        };

        function percentTooltip(context) {
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const value = context.parsed || 0;
            const percentage = total ? ((value / total) * 100).toFixed(1) : '0.0';
            return `${context.label || ''}: ${formatCount(value)} (${percentage}%)`;
        }

        const dashboardCharts = {};

        function renderChart(canvasId, isEmpty, buildConfig) {
            const canvas = document.getElementById(canvasId);
            const emptyEl = document.querySelector(`[data-empty-for="${canvasId}"]`);
            if (!canvas) return;

            if (dashboardCharts[canvasId]) {
                dashboardCharts[canvasId].destroy();
                delete dashboardCharts[canvasId];
            }

            canvas.parentElement.classList.toggle('d-none', isEmpty);
            if (emptyEl) emptyEl.classList.toggle('d-none', !isEmpty);
            if (!isEmpty) dashboardCharts[canvasId] = new Chart(canvas, buildConfig());
        }

        function renderCharts(data) {
            // 1. Top municipalities (individual), horizontal bar
            const municipalities = data.municipalityDistribution || [];
            renderChart('municipalityChart', municipalities.length === 0, () => ({
                type: 'bar',
                data: {
                    labels: municipalities.map(r => r.label),
                    datasets: [{
                        label: 'Number of Beneficiaries',
                        data: municipalities.map(r => r.count),
                        backgroundColor: chartColors.primary,
                        borderColor: chartColors.primary,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => 'Beneficiaries: ' + formatCount(ctx.parsed.x) } }
                    },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            }));

            // 2. District distribution (groups), doughnut
            const districts = data.districtDistribution || [];
            renderChart('districtChart', districts.length === 0, () => ({
                type: 'doughnut',
                data: {
                    labels: districts.map(r => r.label),
                    datasets: [{
                        label: 'Projects',
                        data: districts.map(r => r.count),
                        backgroundColor: paletteFor(districts.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'right', labels: { padding: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: percentTooltip } }
                    }
                }
            }));

            // 3. Worker types (combined). Records can name several types, so the counts
            //    overlap and are shown as bars, not as shares of a pie.
            const workerTypes = data.workerTypeDistribution || [];
            renderChart('projectTypeChart', workerTypes.length === 0, () => ({
                type: 'bar',
                data: {
                    labels: workerTypes.map(r => r.label),
                    datasets: [{
                        label: 'Records',
                        data: workerTypes.map(r => r.count),
                        backgroundColor: paletteFor(workerTypes.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => 'Records: ' + formatCount(ctx.parsed.x) } }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } },
                        // Some worker type names are long; keep the axis readable
                        y: { ticks: { callback: function(value) {
                            const label = this.getLabelForValue(value);
                            return label.length > 32 ? label.slice(0, 30) + '…' : label;
                        } } }
                    }
                }
            }));

            // 4. Funding sources (combined), bar of amounts
            const funding = data.fundingSourceBreakdown || [];
            const fundingColors = funding.map(r => fundingColorMap[r.label] || chartColors.orange);
            renderChart('fundingChart', funding.length === 0, () => ({
                type: 'bar',
                data: {
                    labels: funding.map(r => r.label),
                    datasets: [{
                        label: 'Total Amount (₱)',
                        data: funding.map(r => parseFloat(r.total_amount) || 0),
                        backgroundColor: fundingColors,
                        borderColor: fundingColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => [
                                    'Amount: ₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 }),
                                    'Projects: ' + formatCount(funding[ctx.dataIndex].count)
                                ]
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: value => '₱' + value.toLocaleString('en-PH') } }
                    }
                }
            }));

            // 5. Project category (groups), doughnut
            const categories = data.categoryDistribution || [];
            renderChart('categoryChart', categories.length === 0, () => ({
                type: 'doughnut',
                data: {
                    labels: categories.map(r => r.label),
                    datasets: [{
                        label: 'Projects',
                        data: categories.map(r => r.count),
                        backgroundColor: paletteFor(categories.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'right', labels: { padding: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: percentTooltip } }
                    }
                }
            }));

            // 6. Monthly approvals, line. Months arrive as "YYYY-MM" with zeros filled in.
            const trends = data.trends || { months: [], beneficiaries: [], proponents: [] };
            const trendsEmpty = ![...trends.beneficiaries, ...trends.proponents].some(v => v > 0);
            document.getElementById('trendsPeriod').textContent = data.filters && data.filters.year
                ? String(data.filters.year)
                : 'Last 12 Months';
            renderChart('trendsChart', trendsEmpty, () => ({
                type: 'line',
                data: {
                    labels: trends.months.map(m => {
                        const [year, month] = m.split('-').map(Number);
                        return new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                    }),
                    datasets: [
                        {
                            label: 'Individual Beneficiaries',
                            data: trends.beneficiaries,
                            borderColor: chartColors.primary,
                            backgroundColor: chartColors.primary + '20',
                            tension: 0.4,
                            cubicInterpolationMode: 'monotone', // smooth without dipping below zero
                            fill: true
                        },
                        {
                            label: 'Group Proponents',
                            data: trends.proponents,
                            borderColor: chartColors.success,
                            backgroundColor: chartColors.success + '20',
                            tension: 0.4,
                            cubicInterpolationMode: 'monotone', // smooth without dipping below zero
                            fill: true
                        }
                    ]
                },
                options: {
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { padding: 15 } },
                        tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${formatCount(ctx.parsed.y)} approved` } }
                    },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            }));
        }

        // =========================================================
        // Overdue liquidations alert
        // =========================================================
        function renderOverdue(data) {
            const box = document.getElementById('widget-overdue');
            const overdue = data.overdue || { total: 0, items: [] };
            if (!box) return;
            if (!overdue.total) {
                box.innerHTML = '';
                return;
            }

            const items = overdue.items.map(item =>
                `<li><strong>${escapeHtml(item.proponent_name)}</strong> - Deadline: ${escapeHtml(item.deadline_label)}` +
                ` (${formatCount(item.days_overdue)} ${item.days_overdue === 1 ? 'day' : 'days'} overdue)</li>`
            ).join('');

            const params = new URLSearchParams({ filter: 'overdue' });
            if (data.isCrossProvince && data.filters && data.filters.province) params.set('province', data.filters.province);

            const more = overdue.total > overdue.items.length
                ? ` (showing the ${overdue.items.length} oldest)`
                : '';

            box.innerHTML =
                '<div class="alert alert-danger alert-overdue mb-4" role="alert">' +
                    '<h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Overdue Liquidations</h5>' +
                    `<p>There ${overdue.total === 1 ? 'is' : 'are'} <strong>${formatCount(overdue.total)}</strong> ${overdue.total === 1 ? 'proponent' : 'proponents'} with an overdue liquidation deadline${more}:</p>` +
                    `<ul class="mb-0">${items}</ul>` +
                    `<p class="mt-2 mb-0"><a href="proponents.php?${params}" class="alert-link">View all overdue liquidations</a></p>` +
                '</div>';
        }

        // =========================================================
        // Project map
        // =========================================================
        const map = L.map('map').setView([MAP_CENTER[0], MAP_CENTER[1]], MAP_CENTER[2]);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        function markerIcon(color) {
            return L.icon({
                iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
        }
        const beneficiaryIcon = markerIcon('blue');
        const proponentIcon = markerIcon('green');
        const markerLayer = L.layerGroup().addTo(map);

        function getStatusColor(status) {
            switch (status) {
                case 'pending': return 'secondary';
                case 'approved': return 'primary';
                case 'implemented': return 'success';
                case 'liquidated': return 'warning';
                case 'monitored': return 'info';
                default: return 'secondary';
            }
        }

        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function mapPopup(item) {
            let html = '<div style="min-width: 200px;">' +
                '<h6 class="mb-2">' + escapeHtml(item.name) + '</h6>' +
                '<p class="mb-1"><strong>Project:</strong> ' + escapeHtml(item.project) + '</p>' +
                '<p class="mb-1"><strong>Location:</strong> ' + escapeHtml(item.location) + '</p>' +
                '<p class="mb-1"><strong>Amount:</strong> ₱' + (Number(item.amount) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + '</p>';
            if (item.type === 'proponent') {
                html += '<p class="mb-1"><strong>Beneficiaries:</strong> ' + escapeHtml(item.total_beneficiaries) + '</p>';
            }
            html += '<p class="mb-0"><span class="badge bg-' + getStatusColor(item.status) + '">' +
                escapeHtml(capitalizeFirst(item.status || '')) + '</span></p></div>';
            return html;
        }

        function renderMap(data, recenter) {
            markerLayer.clearLayers();
            (data.mapPoints || []).forEach(item => {
                L.marker([item.latitude, item.longitude], { icon: item.type === 'beneficiary' ? beneficiaryIcon : proponentIcon })
                    .bindPopup(mapPopup(item))
                    .addTo(markerLayer);
            });

            const province = data.filters && data.filters.province;
            document.getElementById('mapAreaLabel').textContent = province || 'All Provinces';

            if (recenter) {
                const center = (province && PROVINCE_CENTERS[province]) || MAP_CENTER;
                map.setView([center[0], center[1]], center[2]);
            }
        }

        // =========================================================
        // First render
        // =========================================================
        function renderDashboard(data, options = {}) {
            if (options.stats) renderStats(data);
            renderCharts(data);
            renderOverdue(data);
            renderMap(data, options.recenter);
        }

        renderDashboard(DASHBOARD_DATA);
    </script>
    <script>
    // Part 7: Dashboard Personalization
    DILP.dashboard = {
        _key: 'dilp_dashboard_prefs',
        _defaults: { stats: true, status: true, overdue: true, map: true, charts: true },

        init() {
            const prefs = this.getPrefs();
            Object.keys(prefs).forEach(widget => {
                this._applyVisibility(widget, prefs[widget]);
                const toggle = document.getElementById('toggle' + widget.charAt(0).toUpperCase() + widget.slice(1));
                if (toggle) toggle.checked = prefs[widget];
            });
        },

        getPrefs() {
            try {
                const saved = localStorage.getItem(this._key);
                return saved ? { ...this._defaults, ...JSON.parse(saved) } : { ...this._defaults };
            } catch { return { ...this._defaults }; }
        },

        toggle(widget, visible) {
            const prefs = this.getPrefs();
            prefs[widget] = visible;
            localStorage.setItem(this._key, JSON.stringify(prefs));
            this._applyVisibility(widget, visible);
        },

        _applyVisibility(widget, visible) {
            const el = document.getElementById('widget-' + widget);
            if (el) el.style.display = visible ? '' : 'none';
        },

        resetPrefs() {
            localStorage.removeItem(this._key);
            Object.keys(this._defaults).forEach(widget => {
                this._applyVisibility(widget, true);
                const toggle = document.getElementById('toggle' + widget.charAt(0).toUpperCase() + widget.slice(1));
                if (toggle) toggle.checked = true;
            });
            DILP.toast.success('Reset', 'Dashboard preferences restored to defaults.');
        }
    };
    DILP.dashboard.init();

    // Dashboard Tour Steps (run when the user clicks "Start Tour")
    const dashboardTourSteps = [
        { target: '.stat-card.bg-primary', title: 'Beneficiary Overview', description: 'View total individual beneficiaries with gender breakdown at a glance.' },
        { target: '.stat-card.bg-success', title: 'Proponent Overview', description: 'Track group proponents categorized by LGU and Non-LGU types.' },
        { target: '.stat-card.bg-teal', title: 'PCL Records', description: 'Count individual beneficiaries and group projects tagged as Parents/Guardians of Child Laborers.' },
        { target: '.stat-card.bg-warning', title: 'Financial Summary', description: 'See the combined total amount for all individual and group projects.' },
        { target: '#widget-status', title: 'Status Overview', description: 'Monitor the status distribution of both individual beneficiaries and group proponents.' },
        { target: '#widget-charts', title: 'Data Visualization', description: 'Interactive charts showing municipality distribution, project types, funding sources, and monthly trends.' },
        { target: '#municipalityChart', title: 'Municipality Analysis', description: 'Bar chart displaying the top 10 municipalities with the most individual beneficiaries.' },
        { target: '#trendsChart', title: 'Monthly Trends', description: 'Line chart tracking approval trends over the last 12 months for both individuals and groups.' },
        { target: '#map', title: 'Project Map', description: 'Interactive map showing where approved projects are located. Click markers for details.' },
        { target: '.sidebar', title: 'Navigation', description: 'Use the sidebar to navigate between Beneficiaries, Proponents, Reports, and more.' }
    ];
    
    document.getElementById('startTourBtn').addEventListener('click', function() {
        DILP.tour.start(dashboardTourSteps);
    });
    </script>
    <script>
    // FAB: Scroll to Map Functionality
    (function() {
        const fabBtn = document.getElementById('fabScrollToMap');
        const mapSection = document.getElementById('widget-map');
        
        if (!fabBtn || !mapSection) return;

        // Show/hide FAB based on scroll position
        function updateFabVisibility() {
            const mapRect = mapSection.getBoundingClientRect();
            const isMapVisible = mapRect.top < window.innerHeight && mapRect.bottom > 0;
            
            if (isMapVisible) {
                fabBtn.classList.remove('show');
            } else {
                fabBtn.classList.add('show');
            }
        }

        // Smooth scroll to map section
        function scrollToMap() {
            mapSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Event listeners
        window.addEventListener('scroll', updateFabVisibility, { passive: true });
        fabBtn.addEventListener('click', scrollToMap);

        // Initial check
        updateFabVisibility();
    })();
    </script>
    <script>
    // Dashboard Filter Functionality
    // The API returns the same structure the page was first rendered from, so a filter
    // redraws every widget: stat cards, status counts, charts, overdue alert and map.
    const DASHBOARD_LOADING_WIDGETS = ['widget-stats', 'widget-status', 'widget-charts', 'widget-overdue', 'widget-map'];

    function setDashboardLoading(isLoading) {
        DASHBOARD_LOADING_WIDGETS.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.classList.toggle('is-loading', isLoading);
                el.setAttribute('aria-busy', isLoading ? 'true' : 'false');
            }
        });
        const applyBtn = document.getElementById('applyFiltersBtn');
        if (applyBtn) {
            applyBtn.disabled = isLoading;
            applyBtn.innerHTML = isLoading
                ? '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Applying'
                : '<i class="bi bi-check-lg"></i> Apply';
        }
    }

    async function applyDashboardFilters() {
        const province = document.getElementById('filter-province').value;
        const year = document.getElementById('filter-year').value;
        const approvedOnly = document.getElementById('filter-approved-only').checked;
        const filterStatus = document.getElementById('filter-status');

        // Values stay on screen (dimmed) while loading, so a failed request leaves nothing broken
        setDashboardLoading(true);

        try {
            const params = new URLSearchParams();
            if (province) params.append('province', province);
            if (year) params.append('year', year);
            if (approvedOnly) params.append('approved_only', '1');

            const response = await fetch(`api/dashboard-stats.php?${params}`);
            let data = null;
            try {
                data = await response.json();
            } catch (parseError) {
                throw new Error(`The server returned an unexpected response (HTTP ${response.status}).`);
            }
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to apply filters.');
            }

            renderDashboard(data, { stats: true, recenter: true });

            // Province users always carry their own province, so only count what they picked
            const provinceIsFilter = data.isCrossProvince && !!province;
            filterStatus.style.display = (provinceIsFilter || year || approvedOnly) ? '' : 'none';

            if (typeof DILP !== 'undefined' && DILP.toast) {
                if (data.hasData === false) {
                    DILP.toast.warning('No Data Found', 'No records match the selected filters. Try adjusting your filter criteria.');
                } else {
                    DILP.toast.success('Filters Applied', 'Dashboard data has been filtered.');
                }
            }
        } catch (error) {
            console.error('Filter error:', error);
            if (typeof DILP !== 'undefined' && DILP.toast) {
                DILP.toast.error('Error', error.message || 'Failed to fetch filtered data.');
            }
        } finally {
            setDashboardLoading(false);
        }
    }

    function resetDashboardFilters() {
        const provinceSelect = document.getElementById('filter-province');
        // Province users have a hidden input holding their own province; keep it
        if (provinceSelect.tagName === 'SELECT') provinceSelect.value = '';
        document.getElementById('filter-year').value = '';
        document.getElementById('filter-approved-only').checked = false;
        document.getElementById('filter-status').style.display = 'none';

        // Reload page to reset all data
        location.reload();
    }
    </script>
</body>
</html>
