<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';

$auth = new Auth();
$auth->requireLogin();

$beneficiaryModel = new Beneficiary();

// Get filters from query string
$filters = [
    'municipality' => $_GET['municipality'] ?? '',
    'barangay' => $_GET['barangay'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Get beneficiaries
$beneficiaries = $beneficiaryModel->getAll($filters);

// Get unique municipalities and barangays for filters
$db = Database::getInstance()->getConnection();
$municipalities = $db->query("SELECT DISTINCT municipality FROM beneficiaries ORDER BY municipality")->fetchAll(PDO::FETCH_COLUMN);
$barangays = $db->query("SELECT DISTINCT barangay FROM beneficiaries ORDER BY barangay")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiaries - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
</head>
<body>
    <?php $currentPage = 'beneficiaries'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-person"></i> Individual Beneficiaries</h2>
                    <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                    <a href="beneficiary-form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Beneficiary
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Filters -->
                <div class="card filters-card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Name or Project" value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Municipality</label>
                                <select name="municipality" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($municipalities as $municipality): ?>
                                    <option value="<?php echo htmlspecialchars($municipality); ?>" 
                                            <?php echo $filters['municipality'] === $municipality ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($municipality); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Barangay</label>
                                <select name="barangay" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?php echo htmlspecialchars($barangay); ?>" 
                                            <?php echo $filters['barangay'] === $barangay ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($barangay); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="implemented" <?php echo $filters['status'] === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                    <option value="monitored" <?php echo $filters['status'] === 'monitored' ? 'selected' : ''; ?>>Monitored</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                                <a href="beneficiaries.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Beneficiaries Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="beneficiariesTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Gender</th>
                                        <th>Location</th>
                                        <th>Project Name</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($beneficiaries as $beneficiary): ?>
                                    <tr>
                                        <td><?php echo $beneficiary['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . 
                                                      ($beneficiary['middle_name'] ? substr($beneficiary['middle_name'], 0, 1) . '. ' : '') . 
                                                      $beneficiary['last_name'] . 
                                                      ($beneficiary['suffix'] ? ' ' . $beneficiary['suffix'] : '')); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($beneficiary['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($beneficiary['barangay'] . ', ' . $beneficiary['municipality']); ?></td>
                                        <td><?php echo htmlspecialchars($beneficiary['project_name']); ?></td>
                                        <td>₱<?php echo number_format($beneficiary['amount_worth'], 2); ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'secondary',
                                                'approved' => 'primary',
                                                'implemented' => 'success',
                                                'monitored' => 'info'
                                            ];
                                            $color = $statusColors[$beneficiary['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?> badge-status">
                                                <?php echo ucfirst($beneficiary['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="beneficiary-view.php?id=<?php echo $beneficiary['id']; ?>" 
                                               class="btn btn-sm btn-info action-btn" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                                            <a href="beneficiary-form.php?id=<?php echo $beneficiary['id']; ?>" 
                                               class="btn btn-sm btn-warning action-btn" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($auth->hasRole('admin')): ?>
                                            <button onclick="deleteBeneficiary(<?php echo $beneficiary['id']; ?>)" 
                                                    class="btn btn-sm btn-danger action-btn" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>
        $(document).ready(function() {
            $('#beneficiariesTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ beneficiaries"
                }
            });
        });

        function deleteBeneficiary(id) {
            if (confirm('Are you sure you want to delete this beneficiary? This action cannot be undone.')) {
                window.location.href = 'beneficiary-delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
