<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();

$proponentModel = new Proponent();

$filters = [
    'proponent_type' => $_GET['proponent_type'] ?? '',
    'district' => $_GET['district'] ?? '',
    'status' => $_GET['status'] ?? '',
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$proponents = $proponentModel->getAll($filters);

$db = Database::getInstance()->getConnection();
$districts = $db->query("SELECT DISTINCT district FROM proponents WHERE district IS NOT NULL ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proponents - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
</head>
<body>
    <?php $currentPage = 'proponents'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-people"></i> Group Proponents</h2>
                    <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                    <a href="proponent-form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Proponent
                    </a>
                    <?php endif; ?>
                </div>

                <div class="card filters-card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Name, Project, or Control #" value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Proponent Type</label>
                                <select name="proponent_type" class="form-select">
                                    <option value="">All</option>
                                    <option value="LGU-associated" <?php echo $filters['proponent_type'] === 'LGU-associated' ? 'selected' : ''; ?>>LGU-associated</option>
                                    <option value="Non-LGU-associated" <?php echo $filters['proponent_type'] === 'Non-LGU-associated' ? 'selected' : ''; ?>>Non-LGU-associated</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">District</label>
                                <select name="district" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo htmlspecialchars($district); ?>" 
                                            <?php echo $filters['district'] === $district ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district); ?>
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
                                    <option value="liquidated" <?php echo $filters['status'] === 'liquidated' ? 'selected' : ''; ?>>Liquidated</option>
                                    <option value="monitored" <?php echo $filters['status'] === 'monitored' ? 'selected' : ''; ?>>Monitored</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                                <a href="proponents.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="proponentsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Control #</th>
                                        <th>Proponent Name</th>
                                        <th>Project Title</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Beneficiaries</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proponents as $proponent): ?>
                                    <tr>
                                        <td><?php echo $proponent['id']; ?></td>
                                        <td><?php echo htmlspecialchars($proponent['control_number']); ?></td>
                                        <td><?php echo htmlspecialchars($proponent['proponent_name']); ?></td>
                                        <td><?php echo htmlspecialchars($proponent['project_title']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $proponent['proponent_type'] === 'LGU-associated' ? 'info' : 'warning'; ?>">
                                                <?php echo htmlspecialchars($proponent['proponent_type']); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($proponent['amount'], 2); ?></td>
                                        <td><?php echo number_format($proponent['total_beneficiaries']); ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'secondary',
                                                'approved' => 'primary',
                                                'implemented' => 'success',
                                                'liquidated' => 'warning',
                                                'monitored' => 'info'
                                            ];
                                            $color = $statusColors[$proponent['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?> badge-status">
                                                <?php echo ucfirst($proponent['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons-container">
                                                <a href="proponent-view.php?id=<?php echo $proponent['id']; ?>" 
                                                   class="btn btn-sm btn-info action-btn" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($auth->hasRole(['admin', 'encoder'])): ?>
                                                <a href="proponent-form.php?id=<?php echo $proponent['id']; ?>" 
                                                   class="btn btn-sm btn-warning action-btn" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($auth->hasRole('admin')): ?>
                                                <button onclick="deleteProponent(<?php echo $proponent['id']; ?>)" 
                                                        class="btn btn-sm btn-danger action-btn" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
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
            $('#proponentsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ proponents"
                }
            });
        });

        function deleteProponent(id) {
            if (confirm('Are you sure you want to delete this proponent? This action cannot be undone.')) {
                window.location.href = 'proponent-delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
