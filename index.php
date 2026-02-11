<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();

$beneficiaryModel = new Beneficiary();
$proponentModel = new Proponent();

// Get statistics
$beneficiaryStats = $beneficiaryModel->getStatistics();
$proponentStats = $proponentModel->getStatistics();

// Get map data
$beneficiaryMapData = $beneficiaryModel->getMapData();
$proponentMapData = $proponentModel->getMapData();
$mapData = array_merge($beneficiaryMapData, $proponentMapData);

// Get overdue liquidations
$overdueLiquidations = $proponentModel->getOverdueLiquidations();
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
                    <div class="col-sm-6 col-lg-3">
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

                    <div class="col-sm-6 col-lg-3">
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

                    <div class="col-sm-6 col-lg-3">
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

                    <div class="col-sm-6 col-lg-3">
                        <div class="card stat-card bg-warning text-dark h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="stat-text-wrap">
                                        <h6 class="card-title text-uppercase mb-1">Total Amount</h6>
                                        <h2 class="stat-number mb-0">₱<?php echo number_format($beneficiaryStats['total_amount'] + $proponentStats['total_amount'], 2); ?></h2>
                                        <small class="stat-detail">Individual + Group Projects</small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
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
        _defaults: { stats: true, status: true, overdue: true, map: true },

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
        { target: '#map', title: 'Project Map', description: 'Interactive map showing project locations across Negros Occidental. Click markers for details.' },
        { target: '.sidebar', title: 'Navigation', description: 'Use the sidebar to navigate between Beneficiaries, Proponents, Reports, and more.' }
    ];
    
    // Initialize tour with steps when needed
    document.getElementById('startTourBtn').addEventListener('click', function() {
        DILP.tour.init(dashboardTourSteps);
        DILP.tour.start();
    });
    </script>
</body>
</html>
