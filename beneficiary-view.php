<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';

$auth = new Auth();
$auth->requireLogin();

$beneficiaryModel = new Beneficiary();

if (!isset($_GET['id'])) {
    header('Location: beneficiaries.php');
    exit;
}

$beneficiary = $beneficiaryModel->findById($_GET['id']);

if (!$beneficiary) {
    header('Location: beneficiaries.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Beneficiary - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .view-section {
            background-color: var(--dole-light);
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;
        }
        .view-section h5 {
            color: var(--dole-primary); margin-bottom: 1rem;
            border-bottom: 2px solid var(--dole-primary); padding-bottom: 0.5rem;
        }
        .view-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem; margin-bottom: 1rem;
        }
        .view-item {
            padding: 0.75rem; background-color: white;
            border-radius: 5px; border-left: 3px solid var(--dole-primary);
        }
        .view-item label {
            font-weight: 600; color: #495057; font-size: 0.9rem;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .view-item p { margin: 0.5rem 0 0 0; color: #212529; font-size: 1rem; }
        .status-badge {
            display: inline-block; padding: 0.5rem 1rem;
            border-radius: 20px; font-weight: 500; font-size: 0.9rem;
        }
        #map { height: 400px; border-radius: var(--dole-border-radius); box-shadow: var(--dole-box-shadow); }
        .header-info {
            background: linear-gradient(135deg, var(--dole-primary), var(--dole-secondary));
            color: white; padding: 2rem; border-radius: var(--dole-border-radius); margin-bottom: 2rem;
        }
        .header-info h2 { margin-bottom: 0.5rem; }
        .header-info .subtitle { font-size: 0.95rem; opacity: 0.9; }
    </style>
</head>
<body>
    <?php $currentPage = 'beneficiaries'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2><i class="bi bi-person"></i> Beneficiary Details</h2>
                    <div class="btn-group" role="group">
                        <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                        <a href="beneficiary-form.php?id=<?php echo $beneficiary['id']; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <a href="beneficiaries.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="header-info">
                    <h2>
                        <?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . 
                                  ($beneficiary['middle_name'] ? substr($beneficiary['middle_name'], 0, 1) . '. ' : '') . 
                                  $beneficiary['last_name'] . 
                                  ($beneficiary['suffix'] ? ' ' . $beneficiary['suffix'] : '')); ?>
                    </h2>
                    <div class="subtitle">
                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($beneficiary['barangay'] . ', ' . $beneficiary['municipality']); ?>
                    </div>
                    <div class="subtitle mt-2">
                        <span class="status-badge bg-<?php 
                            $statusColors = ['pending' => 'secondary', 'approved' => 'primary', 'implemented' => 'success', 'monitored' => 'info'];
                            echo $statusColors[$beneficiary['status']] ?? 'secondary';
                        ?>">
                            <?php echo ucfirst($beneficiary['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="view-section">
                            <h5><i class="bi bi-person-badge"></i> Personal Information</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Full Name</label>
                                    <p><?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . 
                                              ($beneficiary['middle_name'] ? $beneficiary['middle_name'] . ' ' : '') . 
                                              $beneficiary['last_name'] . 
                                              ($beneficiary['suffix'] ? ' ' . $beneficiary['suffix'] : '')); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Gender</label>
                                    <p><?php echo htmlspecialchars($beneficiary['gender']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Contact Number</label>
                                    <p><?php echo htmlspecialchars($beneficiary['contact_number'] ?: 'Not provided'); ?></p>
                                </div>
                            </div>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Barangay</label>
                                    <p><?php echo htmlspecialchars($beneficiary['barangay']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Municipality</label>
                                    <p><?php echo htmlspecialchars($beneficiary['municipality']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-briefcase"></i> Project Information</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Project Name/Title</label>
                                    <p><?php echo htmlspecialchars($beneficiary['project_name']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Type of Worker</label>
                                    <p><?php echo htmlspecialchars($beneficiary['type_of_worker'] ?: 'Not specified'); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Amount Worth</label>
                                    <p><strong>₱<?php echo number_format($beneficiary['amount_worth'], 2); ?></strong></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-calendar-check"></i> Process Timeline</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Date Complied by Proponent/ACP</label>
                                    <p><?php echo $beneficiary['date_complied_by_proponent'] ? date('F d, Y', strtotime($beneficiary['date_complied_by_proponent'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Date Forwarded to RO6</label>
                                    <p><?php echo $beneficiary['date_forwarded_to_ro6'] ? date('F d, Y', strtotime($beneficiary['date_forwarded_to_ro6'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Date Approved</label>
                                    <p><?php echo $beneficiary['date_approved'] ? date('F d, Y', strtotime($beneficiary['date_approved'])) : 'Not set'; ?></p>
                                </div>
                            </div>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Date Forwarded to NOFO</label>
                                    <p><?php echo $beneficiary['date_forwarded_to_nofo'] ? date('F d, Y', strtotime($beneficiary['date_forwarded_to_nofo'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Date of Turn-over</label>
                                    <p><?php echo $beneficiary['date_turnover'] ? date('F d, Y', strtotime($beneficiary['date_turnover'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Date of Monitoring</label>
                                    <p><?php echo $beneficiary['date_monitoring'] ? date('F d, Y', strtotime($beneficiary['date_monitoring'])) : 'Not set'; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-file-text"></i> RPMT Findings</h5>
                            <div class="view-item">
                                <label>RPMT Findings</label>
                                <p><?php echo nl2br(htmlspecialchars($beneficiary['rpmt_findings'] ?: 'Not provided')); ?></p>
                            </div>
                            <div class="view-item mt-3">
                                <label>Noted Findings/Comments</label>
                                <p><?php echo nl2br(htmlspecialchars($beneficiary['noted_findings'] ?: 'Not provided')); ?></p>
                            </div>
                        </div>

                        <?php if ($beneficiary['latitude'] && $beneficiary['longitude']): ?>
                        <div class="view-section">
                            <h5><i class="bi bi-geo-alt-fill"></i> Project Location Map</h5>
                            <div id="map"></div>
                            <div class="view-row mt-3">
                                <div class="view-item">
                                    <label>Latitude</label>
                                    <p><?php echo htmlspecialchars($beneficiary['latitude']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Longitude</label>
                                    <p><?php echo htmlspecialchars($beneficiary['longitude']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="view-section">
                            <h5><i class="bi bi-info-circle"></i> Record Information</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Record ID</label>
                                    <p><?php echo $beneficiary['id']; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Created Date</label>
                                    <p><?php echo date('F d, Y \a\t H:i', strtotime($beneficiary['created_at'])); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Last Updated</label>
                                    <p><?php echo date('F d, Y \a\t H:i', strtotime($beneficiary['updated_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 no-print">
                    <a href="beneficiaries.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                    <a href="beneficiary-form.php?id=<?php echo $beneficiary['id']; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($beneficiary['latitude'] && $beneficiary['longitude']): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([<?php echo $beneficiary['latitude']; ?>, <?php echo $beneficiary['longitude']; ?>], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        var beneficiaryIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var marker = L.marker([<?php echo $beneficiary['latitude']; ?>, <?php echo $beneficiary['longitude']; ?>], {icon: beneficiaryIcon})
            .addTo(map)
            .bindPopup('<div><strong><?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . $beneficiary['last_name']); ?></strong><br>' +
                       'Project: <?php echo htmlspecialchars($beneficiary['project_name']); ?><br>' +
                       'Amount: ₱<?php echo number_format($beneficiary['amount_worth'], 2); ?><br>' +
                       'Status: <?php echo ucfirst($beneficiary['status']); ?></div>');
    </script>
    <?php endif; ?>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
