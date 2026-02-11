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

$reportType = $_GET['type'] ?? 'beneficiaries';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$municipality = $_GET['municipality'] ?? '';
$district = $_GET['district'] ?? '';
$status = $_GET['status'] ?? '';

$reportData = [];
$reportGenerated = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generate'])) {
    $reportGenerated = true;
    
    $filters = [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'status' => $status
    ];
    
    if ($reportType === 'beneficiaries') {
        $filters['municipality'] = $municipality;
        $reportData = $beneficiaryModel->getAll($filters);
    } else {
        $filters['district'] = $district;
        $reportData = $proponentModel->getAll($filters);
    }
}

$db = Database::getInstance()->getConnection();
$municipalities = $db->query("SELECT DISTINCT municipality FROM beneficiaries ORDER BY municipality")->fetchAll(PDO::FETCH_COLUMN);
$districts = $db->query("SELECT DISTINCT district FROM proponents WHERE district IS NOT NULL ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
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
        .summary-item h3 { font-size: 2rem; margin-bottom: 0.5rem; }
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
                        <form method="GET" action="" class="row g-3">
                            <input type="hidden" name="generate" value="1">
                            
                            <div class="col-md-3">
                                <label class="form-label">Report Type</label>
                                <select name="type" id="reportType" class="form-select" required>
                                    <option value="beneficiaries" <?php echo $reportType === 'beneficiaries' ? 'selected' : ''; ?>>Individual Beneficiaries</option>
                                    <option value="proponents" <?php echo $reportType === 'proponents' ? 'selected' : ''; ?>>Group Proponents</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            
                            <div class="col-md-2" id="municipalityFilter">
                                <label class="form-label">Municipality</label>
                                <select name="municipality" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($municipalities as $mun): ?>
                                    <option value="<?php echo htmlspecialchars($mun); ?>" <?php echo $municipality === $mun ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mun); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2" id="districtFilter" style="display: none;">
                                <label class="form-label">District</label>
                                <select name="district" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($districts as $dist): ?>
                                    <option value="<?php echo htmlspecialchars($dist); ?>" <?php echo $district === $dist ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dist); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="implemented" <?php echo $status === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                    <option value="monitored" <?php echo $status === 'monitored' ? 'selected' : ''; ?>>Monitored</option>
                                </select>
                            </div>
                            
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-file-earmark-bar-graph"></i> Generate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($reportGenerated): ?>
                <div class="report-summary">
                    <div class="row">
                        <div class="col-md-4 summary-item">
                            <h3><?php echo count($reportData); ?></h3>
                            <p>Total Records</p>
                        </div>
                        <div class="col-md-4 summary-item">
                            <h3>₱<?php echo number_format(array_sum(array_column($reportData, $reportType === 'beneficiaries' ? 'amount_worth' : 'amount')), 2); ?></h3>
                            <p>Total Amount</p>
                        </div>
                        <div class="col-md-4 summary-item">
                            <h3><?php echo $reportType === 'beneficiaries' ? 'Beneficiaries' : 'Proponents'; ?></h3>
                            <p>Report Type</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php echo $reportType === 'beneficiaries' ? 'Individual Beneficiaries' : 'Group Proponents'; ?> Report
                            <?php if ($dateFrom && $dateTo): ?>
                            <small class="text-muted">(<?php echo date('M d, Y', strtotime($dateFrom)); ?> - <?php echo date('M d, Y', strtotime($dateTo)); ?>)</small>
                            <?php endif; ?>
                        </h5>
                        <div class="no-print d-flex gap-2">
                            <button onclick="DILP.export.toPDF('#reportTable', '<?php echo $reportType === 'beneficiaries' ? 'Beneficiaries Report' : 'Proponents Report'; ?>')" class="btn btn-sm btn-danger" title="Export as PDF">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </button>
                            <button onclick="DILP.export.toExcel('#reportTable', '<?php echo $reportType; ?>_report')" class="btn btn-sm btn-success" title="Export as Excel">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </button>
                            <button onclick="DILP.export.toCSV('#reportTable', '<?php echo $reportType; ?>_report')" class="btn btn-sm btn-info" title="Export as CSV">
                                <i class="bi bi-filetype-csv"></i> CSV
                            </button>
                            <button onclick="window.print()" class="btn btn-sm btn-primary" title="Print">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php if ($reportType === 'beneficiaries'): ?>
                            <table id="reportTable" class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Gender</th>
                                        <th>Location</th>
                                        <th>Project Name</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date Approved</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $item): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($item['barangay'] . ', ' . $item['municipality']); ?></td>
                                        <td><?php echo htmlspecialchars($item['project_name']); ?></td>
                                        <td>₱<?php echo number_format($item['amount_worth'], 2); ?></td>
                                        <td><?php echo ucfirst($item['status']); ?></td>
                                        <td><?php echo $item['date_approved'] ? date('M d, Y', strtotime($item['date_approved'])) : '-'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <table id="reportTable" class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Control #</th>
                                        <th>Proponent Name</th>
                                        <th>Project Title</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Beneficiaries</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $item): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['control_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['proponent_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['project_title']); ?></td>
                                        <td><?php echo htmlspecialchars($item['proponent_type']); ?></td>
                                        <td>₱<?php echo number_format($item['amount'], 2); ?></td>
                                        <td><?php echo number_format($item['total_beneficiaries']); ?></td>
                                        <td><?php echo ucfirst($item['status']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Select report parameters above and click "Generate" to create a report.
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
    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                pageLength: 50,
                dom: 'Bfrtip',
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries"
                }
            });
            
            $('#reportType').on('change', function() {
                if ($(this).val() === 'beneficiaries') {
                    $('#municipalityFilter').show();
                    $('#districtFilter').hide();
                } else {
                    $('#municipalityFilter').hide();
                    $('#districtFilter').show();
                }
            });
            
            if ($('#reportType').val() === 'proponents') {
                $('#municipalityFilter').hide();
                $('#districtFilter').show();
            }
        });
    </script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
</body>
</html>
