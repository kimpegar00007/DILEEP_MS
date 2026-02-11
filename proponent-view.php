<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();

$proponentModel = new Proponent();

if (!isset($_GET['id'])) {
    header('Location: proponents.php');
    exit;
}

$proponent = $proponentModel->findById($_GET['id']);

if (!$proponent) {
    header('Location: proponents.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proponent - DOLE DILEEP Monitoring System</title>
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
        .liquidation-alert {
            background-color: #fff3cd; border-left: 4px solid var(--dole-warning);
            padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem;
        }
        .liquidation-alert.overdue { background-color: #f8d7da; border-left-color: var(--dole-danger); }
        #map { height: 400px; border-radius: var(--dole-border-radius); box-shadow: var(--dole-box-shadow); }
        .header-info {
            background: linear-gradient(135deg, var(--dole-primary), var(--dole-secondary));
            color: white; padding: 2rem; border-radius: var(--dole-border-radius); margin-bottom: 2rem;
        }
        .header-info h2 { margin-bottom: 0.5rem; }
        .header-info .subtitle { font-size: 0.95rem; opacity: 0.9; }
        .timeline-item {
            padding: 1rem; border-left: 3px solid var(--dole-primary);
            background-color: white; border-radius: 5px; margin-bottom: 0.75rem;
        }
        .timeline-item.completed { border-left-color: var(--dole-success); }
        .timeline-item.pending { border-left-color: var(--dole-warning); }
    </style>
</head>
<body>
    <?php $currentPage = 'proponents'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2><i class="bi bi-people"></i> Proponent Details</h2>
                    <div class="btn-group" role="group">
                        <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                        <a href="proponent-form.php?id=<?php echo $proponent['id']; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <a href="proponents.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="header-info">
                    <h2>
                        <?php echo htmlspecialchars($proponent['proponent_name']); ?>
                    </h2>
                    <div class="subtitle">
                        <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($proponent['project_title']); ?>
                    </div>
                    <div class="subtitle mt-2">
                        <span class="status-badge bg-<?php 
                            $statusColors = ['pending' => 'secondary', 'approved' => 'primary', 'implemented' => 'success', 'liquidated' => 'warning', 'monitored' => 'info'];
                            echo $statusColors[$proponent['status']] ?? 'secondary';
                        ?>">
                            <?php echo ucfirst($proponent['status']); ?>
                        </span>
                        <span class="status-badge bg-<?php echo $proponent['proponent_type'] === 'LGU-associated' ? 'info' : 'secondary'; ?> ms-2">
                            <?php echo htmlspecialchars($proponent['proponent_type']); ?>
                        </span>
                    </div>
                </div>

                <?php if ($proponent['liquidation_deadline']): ?>
                <div class="liquidation-alert <?php echo strtotime($proponent['liquidation_deadline']) < time() && !$proponent['date_liquidated'] ? 'overdue' : ''; ?>">
                    <strong><i class="bi bi-exclamation-triangle"></i> Liquidation Deadline:</strong>
                    <?php echo date('F d, Y', strtotime($proponent['liquidation_deadline'])); ?>
                    <?php if (strtotime($proponent['liquidation_deadline']) < time() && !$proponent['date_liquidated']): ?>
                    <span class="badge bg-danger ms-2">OVERDUE</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="view-section">
                            <h5><i class="bi bi-info-circle"></i> Basic Information</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Proponent/ACP Name</label>
                                    <p><?php echo htmlspecialchars($proponent['proponent_name']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Project Title</label>
                                    <p><?php echo htmlspecialchars($proponent['project_title']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Proponent Type</label>
                                    <p><?php echo htmlspecialchars($proponent['proponent_type']); ?></p>
                                </div>
                            </div>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Control Number</label>
                                    <p><?php echo htmlspecialchars($proponent['control_number'] ?: 'Not assigned'); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Date Received (DILP)</label>
                                    <p><?php echo $proponent['date_received'] ? date('F d, Y', strtotime($proponent['date_received'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>District</label>
                                    <p><?php echo htmlspecialchars($proponent['district'] ?: 'Not specified'); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-cash-stack"></i> Financial Information</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Project Amount</label>
                                    <p><strong>₱<?php echo number_format($proponent['amount'], 2); ?></strong></p>
                                </div>
                                <div class="view-item">
                                    <label>Source of Funds</label>
                                    <p><?php echo htmlspecialchars($proponent['source_of_funds'] ?: 'Not specified'); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Category</label>
                                    <p><?php echo htmlspecialchars($proponent['category']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-people"></i> Beneficiary Information</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Total Beneficiaries</label>
                                    <p><?php echo number_format($proponent['total_beneficiaries']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Male Beneficiaries</label>
                                    <p><?php echo number_format($proponent['male_beneficiaries']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Female Beneficiaries</label>
                                    <p><?php echo number_format($proponent['female_beneficiaries']); ?></p>
                                </div>
                            </div>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Number of Associations</label>
                                    <p><?php echo $proponent['number_of_associations'] ?: 'Not specified'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Type of Beneficiaries</label>
                                    <p><?php echo htmlspecialchars($proponent['type_of_beneficiaries'] ?: 'Not specified'); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Recipient Barangays/ACPs</label>
                                    <p><?php echo htmlspecialchars($proponent['recipient_barangays'] ?: 'Not specified'); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-calendar-check"></i> Process Timeline</h5>
                            <div class="timeline-item <?php echo $proponent['letter_of_intent_date'] ? 'completed' : 'pending'; ?>">
                                <strong>Letter of Intent - Date Received</strong>
                                <p><?php echo $proponent['letter_of_intent_date'] ? date('F d, Y', strtotime($proponent['letter_of_intent_date'])) : 'Not set'; ?></p>
                            </div>
                            <div class="timeline-item <?php echo $proponent['date_copies_received'] ? 'completed' : 'pending'; ?>">
                                <strong>Proposal Copies Received</strong>
                                <p><?php echo $proponent['date_copies_received'] ? date('F d, Y', strtotime($proponent['date_copies_received'])) . ' (' . $proponent['number_of_copies'] . ' copies)' : 'Not set'; ?></p>
                            </div>
                            <div class="timeline-item <?php echo $proponent['date_forwarded_to_ro6'] ? 'completed' : 'pending'; ?>">
                                <strong>Date Forwarded to RO6 for RPMT</strong>
                                <p><?php echo $proponent['date_forwarded_to_ro6'] ? date('F d, Y', strtotime($proponent['date_forwarded_to_ro6'])) : 'Not set'; ?></p>
                            </div>
                            <div class="timeline-item <?php echo $proponent['date_complied_by_proponent'] ? 'completed' : 'pending'; ?>">
                                <strong>Date Complied by Proponent/ACP</strong>
                                <p><?php echo $proponent['date_complied_by_proponent'] ? date('F d, Y', strtotime($proponent['date_complied_by_proponent'])) : 'Not set'; ?></p>
                            </div>
                            <div class="timeline-item <?php echo $proponent['date_complied_by_proponent_nofo'] ? 'completed' : 'pending'; ?>">
                                <strong>Date Complied by Proponent/ACP/NOFO</strong>
                                <p><?php echo $proponent['date_complied_by_proponent_nofo'] ? date('F d, Y', strtotime($proponent['date_complied_by_proponent_nofo'])) : 'Not set'; ?></p>
                            </div>
                            <div class="timeline-item <?php echo $proponent['date_forwarded_to_nofo'] ? 'completed' : 'pending'; ?>">
                                <strong>Date Forwarded to NOFO</strong>
                                <p><?php echo $proponent['date_forwarded_to_nofo'] ? date('F d, Y', strtotime($proponent['date_forwarded_to_nofo'])) : 'Not set'; ?></p>
                            </div>
                            <div class="timeline-item <?php echo $proponent['date_approved'] ? 'completed' : 'pending'; ?>">
                                <strong>Date Approved</strong>
                                <p><?php echo $proponent['date_approved'] ? date('F d, Y', strtotime($proponent['date_approved'])) : 'Not set'; ?></p>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-cash-coin"></i> Check & Financial Release</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Date of Check Release</label>
                                    <p><?php echo $proponent['date_check_release'] ? date('F d, Y', strtotime($proponent['date_check_release'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Check Number</label>
                                    <p><?php echo htmlspecialchars($proponent['check_number'] ?: 'Not assigned'); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Check Date Issued</label>
                                    <p><?php echo $proponent['check_date_issued'] ? date('F d, Y', strtotime($proponent['check_date_issued'])) : 'Not set'; ?></p>
                                </div>
                            </div>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Official Receipt (OR) Number</label>
                                    <p><?php echo htmlspecialchars($proponent['or_number'] ?: 'Not assigned'); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>OR Date Issued</label>
                                    <p><?php echo $proponent['or_date_issued'] ? date('F d, Y', strtotime($proponent['or_date_issued'])) : 'Not set'; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-clipboard-check"></i> Implementation & Liquidation</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Date of Turn-over</label>
                                    <p><?php echo $proponent['date_turnover'] ? date('F d, Y', strtotime($proponent['date_turnover'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Date Implemented</label>
                                    <p><?php echo $proponent['date_implemented'] ? date('F d, Y', strtotime($proponent['date_implemented'])) : 'Not set'; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Date Liquidated</label>
                                    <p><?php echo $proponent['date_liquidated'] ? date('F d, Y', strtotime($proponent['date_liquidated'])) : 'Not set'; ?></p>
                                </div>
                            </div>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Date of Monitoring</label>
                                    <p><?php echo $proponent['date_monitoring'] ? date('F d, Y', strtotime($proponent['date_monitoring'])) : 'Not set'; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="view-section">
                            <h5><i class="bi bi-file-text"></i> RPMT Findings</h5>
                            <div class="view-item">
                                <label>RPMT Findings</label>
                                <p><?php echo nl2br(htmlspecialchars($proponent['rpmt_findings'] ?: 'Not provided')); ?></p>
                            </div>
                            <div class="view-item mt-3">
                                <label>Noted Findings/Comments</label>
                                <p><?php echo nl2br(htmlspecialchars($proponent['noted_findings'] ?: 'Not provided')); ?></p>
                            </div>
                        </div>

                        <?php if ($proponent['latitude'] && $proponent['longitude']): ?>
                        <div class="view-section">
                            <h5><i class="bi bi-geo-alt-fill"></i> Project Location Map</h5>
                            <div id="map"></div>
                            <div class="view-row mt-3">
                                <div class="view-item">
                                    <label>Latitude</label>
                                    <p><?php echo htmlspecialchars($proponent['latitude']); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Longitude</label>
                                    <p><?php echo htmlspecialchars($proponent['longitude']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="view-section">
                            <h5><i class="bi bi-info-circle"></i> Record Information</h5>
                            <div class="view-row">
                                <div class="view-item">
                                    <label>Record ID</label>
                                    <p><?php echo $proponent['id']; ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Created Date</label>
                                    <p><?php echo date('F d, Y \a\t H:i', strtotime($proponent['created_at'])); ?></p>
                                </div>
                                <div class="view-item">
                                    <label>Last Updated</label>
                                    <p><?php echo date('F d, Y \a\t H:i', strtotime($proponent['updated_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 no-print">
                    <a href="proponents.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                    <a href="proponent-form.php?id=<?php echo $proponent['id']; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($proponent['latitude'] && $proponent['longitude']): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([<?php echo $proponent['latitude']; ?>, <?php echo $proponent['longitude']; ?>], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        var proponentIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var marker = L.marker([<?php echo $proponent['latitude']; ?>, <?php echo $proponent['longitude']; ?>], {icon: proponentIcon})
            .addTo(map)
            .bindPopup('<div><strong><?php echo htmlspecialchars($proponent['proponent_name']); ?></strong><br>' +
                       'Project: <?php echo htmlspecialchars($proponent['project_title']); ?><br>' +
                       'Amount: ₱<?php echo number_format($proponent['amount'], 2); ?><br>' +
                       'Beneficiaries: <?php echo number_format($proponent['total_beneficiaries']); ?><br>' +
                       'Status: <?php echo ucfirst($proponent['status']); ?></div>');
    </script>
    <?php endif; ?>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
