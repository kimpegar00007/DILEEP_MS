<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';
require_once 'models/Proponent.php';
require_once 'models/FieldworkSchedule.php';

$auth = new Auth();
$auth->requireLogin();

$beneficiaryModel = new Beneficiary();
$proponentModel = new Proponent();
$fieldworkModel = new FieldworkSchedule();

// Get statistics
$beneficiaryStats = $beneficiaryModel->getStatistics();
$proponentStats = $proponentModel->getStatistics();
$fieldworkStats = $fieldworkModel->getStatistics();

// Get map data
$beneficiaryMapData = $beneficiaryModel->getMapData();
$proponentMapData = $proponentModel->getMapData();
$mapData = array_merge($beneficiaryMapData, $proponentMapData);

// Get overdue liquidations
$overdueLiquidations = $proponentModel->getOverdueLiquidations();

// Get chart data
$beneficiaryMunicipalityData = $beneficiaryModel->getMunicipalityDistribution();
$beneficiaryProjectTypeData = $beneficiaryModel->getProjectTypeDistribution();
$beneficiaryMonthlyTrends = $beneficiaryModel->getMonthlyTrends();
$proponentDistrictData = $proponentModel->getDistrictDistribution();
$proponentCategoryData = $proponentModel->getCategoryDistribution();
$proponentFundingData = $proponentModel->getFundingSourceBreakdown();
$proponentMonthlyTrends = $proponentModel->getMonthlyTrends();
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
        
        .card canvas {
            max-width: 100%;
            height: auto !important;
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

        @media (min-width: 1200px) {
            .col-xl-2-4 {
                flex: 0 0 auto;
                width: 20%;
            }
        }

        @media (max-width: 1199px) {
            .col-xl-2-4 {
                flex: 0 0 auto;
                width: 33.333333%;
            }
        }

        @media (max-width: 767px) {
            .col-xl-2-4 {
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
                    <h2>Dashboard Overview</h2>
                    <div class="d-flex gap-2 no-print">
                        <button class="btn btn-outline-primary btn-sm" id="startTourBtn" onclick="DILP.tour.start()" aria-label="Start guided tour" title="Start a guided tour of the dashboard">
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

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4" id="widget-stats">
                    <div class="col-sm-6 col-xl-2-4">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Total Beneficiaries</h6>
                                        <h2 class="stat-number mb-0"><?php echo number_format($beneficiaryStats['total']); ?></h2>
                                        <small class="stat-detail">Male: <?php echo number_format($beneficiaryStats['male_count']); ?> | Female: <?php echo number_format($beneficiaryStats['female_count']); ?></small>
                                    </div>
                                    <i class="bi bi-person stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-2-4">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Total Proponents</h6>
                                        <h2 class="stat-number mb-0"><?php echo number_format($proponentStats['total']); ?></h2>
                                        <small class="stat-detail">LGU: <?php echo number_format($proponentStats['lgu_count']); ?> | Non-LGU: <?php echo number_format($proponentStats['non_lgu_count']); ?></small>
                                    </div>
                                    <i class="bi bi-people stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-2-4">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Beneficiaries (Groups)</h6>
                                        <h2 class="stat-number mb-0"><?php echo number_format($proponentStats['total_beneficiaries'] ?? 0); ?></h2>
                                        <small class="stat-detail">Male: <?php echo number_format($proponentStats['total_male'] ?? 0); ?> | Female: <?php echo number_format($proponentStats['total_female'] ?? 0); ?></small>
                                    </div>
                                    <i class="bi bi-people-fill stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-2-4">
                        <div class="card stat-card bg-purple text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Fieldwork Schedule</h6>
                                        <h2 class="stat-number mb-0"><?php echo number_format($fieldworkStats['total']); ?></h2>
                                        <small class="stat-detail">Ongoing: <?php echo number_format($fieldworkStats['ongoing']); ?> | Completed: <?php echo number_format($fieldworkStats['completed']); ?></small>
                                    </div>
                                    <i class="bi bi-calendar-check stat-icon flex-shrink-0"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-2-4">
                        <div class="card stat-card bg-warning text-dark h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Total Amount (Individual + Group Projects)</h6>
                                        <h2 class="stat-number mb-0">₱<?php echo number_format($beneficiaryStats['total_amount'] + $proponentStats['total_amount'], 2); ?></h2>
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
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-person"></i> Individual Beneficiaries Status</h5>
                            </div>
                            <div class="card-body d-flex align-items-center">
                                <div class="row text-center w-100">
                                    <div class="col">
                                        <h4 class="text-secondary mb-1"><?php echo $beneficiaryStats['pending']; ?></h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-primary mb-1"><?php echo $beneficiaryStats['approved']; ?></h4>
                                        <small class="text-muted">Approved</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-success mb-1"><?php echo $beneficiaryStats['implemented']; ?></h4>
                                        <small class="text-muted">Implemented</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-info mb-1"><?php echo $beneficiaryStats['monitored']; ?></h4>
                                        <small class="text-muted">Monitored</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-12 col-md-6">
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-people"></i> Group Proponents Status</h5>
                            </div>
                            <div class="card-body d-flex align-items-center">
                                <div class="row text-center w-100">
                                    <div class="col">
                                        <h4 class="text-secondary mb-1"><?php echo $proponentStats['pending']; ?></h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-primary mb-1"><?php echo $proponentStats['approved']; ?></h4>
                                        <small class="text-muted">Approved</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-success mb-1"><?php echo $proponentStats['implemented']; ?></h4>
                                        <small class="text-muted">Implemented</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-warning mb-1"><?php echo $proponentStats['liquidated']; ?></h4>
                                        <small class="text-muted">Liquidated</small>
                                    </div>
                                    <div class="col">
                                        <h4 class="text-info mb-1"><?php echo $proponentStats['monitored']; ?></h4>
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
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Top 10 Municipalities (Individual)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="municipalityChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Proponent District Distribution -->
                    <div class="col-lg-6">
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-pie-chart-fill"></i> District Distribution (Groups)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="districtChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Project Type Distribution -->
                    <div class="col-lg-6">
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-diagram-3-fill"></i> Worker Type Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="projectTypeChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Funding Source Breakdown -->
                    <div class="col-lg-6">
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Funding Source Breakdown</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="fundingChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="col-lg-6">
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-tags-fill"></i> Project Category Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trends -->
                    <div class="col-lg-6">
                        <div class="card stat-card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Monthly Approval Trends (Last 12 Months)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendsChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Liquidations Alert -->
                <div id="widget-overdue">
                <?php if (count($overdueLiquidations) > 0): ?>
                <div class="alert alert-danger alert-overdue mb-4" role="alert">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Overdue Liquidations</h5>
                    <p>There are <strong><?php echo count($overdueLiquidations); ?></strong> proponent(s) with overdue liquidation deadlines:</p>
                    <ul class="mb-0">
                        <?php foreach (array_slice($overdueLiquidations, 0, 5) as $overdue): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($overdue['proponent_name']); ?></strong> - 
                            Deadline: <?php echo date('F d, Y', strtotime($overdue['liquidation_deadline'])); ?>
                            (<?php echo abs((strtotime($overdue['liquidation_deadline']) - time()) / 86400); ?> days overdue)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($overdueLiquidations) > 5): ?>
                    <p class="mt-2 mb-0"><a href="proponents.php?filter=overdue" class="alert-link">View all overdue liquidations</a></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                </div>

                <!-- Map Visualization -->
                <div class="card stat-card mb-4" id="widget-map">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Project Distribution Map - Negros Occidental</h5>
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
        // Chart.js Configuration and Initialization
        Chart.defaults.font.family = "'Inter', 'Segoe UI', 'Roboto', sans-serif";
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = true;

        // Color palette for charts
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

        const colorPalette = [
            chartColors.primary, chartColors.success, chartColors.info, 
            chartColors.warning, chartColors.danger, chartColors.purple,
            chartColors.orange, chartColors.teal, chartColors.pink, chartColors.secondary
        ];

        // 1. Municipality Distribution Chart (Horizontal Bar)
        const municipalityData = <?php echo json_encode($beneficiaryMunicipalityData); ?>;
        if (municipalityData && municipalityData.length > 0) {
            const municipalityCtx = document.getElementById('municipalityChart').getContext('2d');
            new Chart(municipalityCtx, {
                type: 'bar',
                data: {
                    labels: municipalityData.map(item => item.municipality),
                    datasets: [{
                        label: 'Number of Beneficiaries',
                        data: municipalityData.map(item => item.count),
                        backgroundColor: chartColors.primary,
                        borderColor: chartColors.primary,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Beneficiaries: ' + context.parsed.x;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        // 2. District Distribution Chart (Doughnut)
        const districtData = <?php echo json_encode($proponentDistrictData); ?>;
        if (districtData && districtData.length > 0) {
            const districtCtx = document.getElementById('districtChart').getContext('2d');
            new Chart(districtCtx, {
                type: 'doughnut',
                data: {
                    labels: districtData.map(item => item.district),
                    datasets: [{
                        label: 'Projects',
                        data: districtData.map(item => item.count),
                        backgroundColor: colorPalette,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { padding: 10, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Project Type Distribution Chart (Pie)
        const projectTypeData = <?php echo json_encode($beneficiaryProjectTypeData); ?>;
        if (projectTypeData && projectTypeData.length > 0) {
            const projectTypeCtx = document.getElementById('projectTypeChart').getContext('2d');
            new Chart(projectTypeCtx, {
                type: 'pie',
                data: {
                    labels: projectTypeData.map(item => item.type_of_worker),
                    datasets: [{
                        label: 'Count',
                        data: projectTypeData.map(item => item.count),
                        backgroundColor: colorPalette,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 10, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 4. Funding Source Breakdown Chart (Bar)
        const fundingData = <?php echo json_encode($proponentFundingData); ?>;
        if (fundingData && fundingData.length > 0) {
            const fundingCtx = document.getElementById('fundingChart').getContext('2d');
            
            // Generate colors based on funding source
            const fundingColors = fundingData.map(item => {
                if (item.source_of_funds === 'Not Specified') return chartColors.secondary;
                if (item.source_of_funds === 'DOLE') return chartColors.primary;
                if (item.source_of_funds === 'GAA') return chartColors.success;
                if (item.source_of_funds === 'LGU') return chartColors.info;
                if (item.source_of_funds === 'NGO') return chartColors.warning;
                if (item.source_of_funds === 'TUPAD') return chartColors.purple;
                if (item.source_of_funds === 'SPES') return chartColors.teal;
                return chartColors.orange;
            });
            
            new Chart(fundingCtx, {
                type: 'bar',
                data: {
                    labels: fundingData.map(item => item.source_of_funds),
                    datasets: [{
                        label: 'Total Amount (₱)',
                        data: fundingData.map(item => parseFloat(item.total_amount)),
                        backgroundColor: fundingColors,
                        borderColor: fundingColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const count = fundingData[context.dataIndex].count;
                                    return [
                                        'Amount: ₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2}),
                                        'Projects: ' + count
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString('en-PH');
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // Display message when no data is available
            const fundingCanvas = document.getElementById('fundingChart');
            const fundingCtx = fundingCanvas.getContext('2d');
            fundingCtx.font = '14px Inter, sans-serif';
            fundingCtx.fillStyle = '#6c757d';
            fundingCtx.textAlign = 'center';
            fundingCtx.fillText('No funding source data available', fundingCanvas.width / 2, fundingCanvas.height / 2);
        }

        // 5. Category Distribution Chart (Doughnut)
        const categoryData = <?php echo json_encode($proponentCategoryData); ?>;
        if (categoryData && categoryData.length > 0) {
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.map(item => item.category),
                    datasets: [{
                        label: 'Projects',
                        data: categoryData.map(item => item.count),
                        backgroundColor: colorPalette,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { padding: 10, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 6. Monthly Trends Chart (Line)
        const beneficiaryTrends = <?php echo json_encode($beneficiaryMonthlyTrends); ?>;
        const proponentTrends = <?php echo json_encode($proponentMonthlyTrends); ?>;
        
        if ((beneficiaryTrends && beneficiaryTrends.length > 0) || (proponentTrends && proponentTrends.length > 0)) {
            const allMonths = new Set();
            beneficiaryTrends.forEach(item => allMonths.add(item.month));
            proponentTrends.forEach(item => allMonths.add(item.month));
            const sortedMonths = Array.from(allMonths).sort();

            const beneficiaryMap = {};
            const proponentMap = {};
            beneficiaryTrends.forEach(item => beneficiaryMap[item.month] = parseInt(item.count));
            proponentTrends.forEach(item => proponentMap[item.month] = parseInt(item.count));

            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: sortedMonths.map(month => {
                        const date = new Date(month + '-01');
                        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                    }),
                    datasets: [
                        {
                            label: 'Individual Beneficiaries',
                            data: sortedMonths.map(month => beneficiaryMap[month] || 0),
                            borderColor: chartColors.primary,
                            backgroundColor: chartColors.primary + '20',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Group Proponents',
                            data: sortedMonths.map(month => proponentMap[month] || 0),
                            borderColor: chartColors.success,
                            backgroundColor: chartColors.success + '20',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { padding: 15 }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' approved';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }
    </script>
    <script>
        // Initialize map centered on Negros Occidental
        var map = L.map('map').setView([10.5, 123.0], 9);

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        // Custom markers
        var beneficiaryIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var proponentIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        // Add markers from PHP data
        var mapData = <?php echo json_encode($mapData); ?>;
        
        console.log('Total map data items:', mapData.length);
        
        var validMarkers = 0;
        var invalidMarkers = 0;
        
        mapData.forEach(function(item) {
            var lat = parseFloat(item.latitude);
            var lng = parseFloat(item.longitude);
            
            if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0 && 
                lat >= 9.0 && lat <= 12.0 && lng >= 122.0 && lng <= 124.0) {
                
                try {
                    var icon = item.type === 'beneficiary' ? beneficiaryIcon : proponentIcon;
                    var popupContent = '<div style="min-width: 200px;">' +
                        '<h6 class="mb-2">' + item.name + '</h6>' +
                        '<p class="mb-1"><strong>Project:</strong> ' + (item.project_title || item.project_name) + '</p>' +
                        '<p class="mb-1"><strong>Location:</strong> ' + (item.barangay ? item.barangay + ', ' : '') + (item.municipality || item.district) + '</p>' +
                        '<p class="mb-1"><strong>Amount:</strong> ₱' + parseFloat(item.amount_worth || item.amount).toLocaleString('en-PH', {minimumFractionDigits: 2}) + '</p>';
                    
                    if (item.type === 'proponent') {
                        popupContent += '<p class="mb-1"><strong>Beneficiaries:</strong> ' + item.total_beneficiaries + '</p>';
                    }
                    
                    popupContent += '<p class="mb-0"><span class="badge bg-' + getStatusColor(item.status) + '">' + capitalizeFirst(item.status) + '</span></p>' +
                        '</div>';
                    
                    L.marker([lat, lng], {icon: icon})
                        .addTo(map)
                        .bindPopup(popupContent);
                    
                    validMarkers++;
                } catch (e) {
                    console.error('Error adding marker:', e, item);
                    invalidMarkers++;
                }
            } else {
                console.warn('Invalid coordinates for item:', item.name, 'Lat:', lat, 'Lng:', lng);
                invalidMarkers++;
            }
        });
        
        console.log('Valid markers added:', validMarkers);
        console.log('Invalid/skipped markers:', invalidMarkers);

        function getStatusColor(status) {
            switch(status) {
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

    // Dashboard Tour Steps (initialized on-demand when user clicks "Start Tour" button)
    const dashboardTourSteps = [
        { target: '.stat-card.bg-primary', title: 'Beneficiary Overview', description: 'View total individual beneficiaries with gender breakdown at a glance.' },
        { target: '.stat-card.bg-success', title: 'Proponent Overview', description: 'Track group proponents categorized by LGU and Non-LGU types.' },
        { target: '.stat-card.bg-warning', title: 'Financial Summary', description: 'See the combined total amount for all individual and group projects.' },
        { target: '#widget-status', title: 'Status Overview', description: 'Monitor the status distribution of both individual beneficiaries and group proponents.' },
        { target: '#widget-charts', title: 'Data Visualization', description: 'Interactive charts showing municipality distribution, project types, funding sources, and monthly trends.' },
        { target: '#municipalityChart', title: 'Municipality Analysis', description: 'Bar chart displaying the top 10 municipalities with the most individual beneficiaries.' },
        { target: '#trendsChart', title: 'Monthly Trends', description: 'Line chart tracking approval trends over the last 12 months for both individuals and groups.' },
        { target: '#map', title: 'Project Map', description: 'Interactive map showing project locations across Negros Occidental. Click markers for details.' },
        { target: '.sidebar', title: 'Navigation', description: 'Use the sidebar to navigate between Beneficiaries, Proponents, Reports, and more.' }
    ];
    
    // Initialize tour with steps when needed
    document.getElementById('startTourBtn').addEventListener('click', function() {
        DILP.tour.init(dashboardTourSteps);
        DILP.tour.start();
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
</body>
</html>
