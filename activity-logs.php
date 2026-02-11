<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$db = Database::getInstance()->getConnection();

$filters = [
    'action' => $_GET['action'] ?? '',
    'table_name' => $_GET['table_name'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$query = "SELECT al.*, u.username, u.full_name FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
$params = [];

if (!empty($filters['action'])) {
    $query .= " AND al.action = ?";
    $params[] = $filters['action'];
}

if (!empty($filters['table_name'])) {
    $query .= " AND al.table_name = ?";
    $params[] = $filters['table_name'];
}

if (!empty($filters['user_id'])) {
    $query .= " AND al.user_id = ?";
    $params[] = intval($filters['user_id']);
}

if (!empty($filters['date_from'])) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $filters['date_to'];
}

if (!empty($filters['search'])) {
    $query .= " AND (al.description LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
    $searchTerm = '%' . $filters['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY al.created_at DESC LIMIT 10000";

$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$stmt = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $db->query("SELECT DISTINCT table_name FROM activity_logs ORDER BY table_name");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $db->query("SELECT id, username, full_name FROM users ORDER BY full_name");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .filter-section {
            background-color: white;
            padding: 1.5rem;
            border-radius: var(--dole-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--dole-box-shadow);
        }
        .action-badge { font-size: 0.85rem; padding: 0.35rem 0.65rem; }
        .log-timestamp { font-size: 0.9rem; color: #6c757d; }
        .log-description { max-width: 300px; word-break: break-word; }
        .ip-address { font-family: 'Courier New', monospace; font-size: 0.85rem; }
    </style>
</head>
<body>
    <?php $currentPage = 'activity-logs'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-clock-history"></i> Activity Logs</h2>
                    <a href="activity-logs.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset Filters
                    </a>
                </div>

                <div class="filter-section">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Logs</h5>
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $act): ?>
                                <option value="<?php echo htmlspecialchars($act); ?>" 
                                    <?php echo $filters['action'] === $act ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($act); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Table</label>
                            <select name="table_name" class="form-select">
                                <option value="">All Tables</option>
                                <?php foreach ($tables as $table): ?>
                                <option value="<?php echo htmlspecialchars($table); ?>" 
                                    <?php echo $filters['table_name'] === $table ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($table); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">User</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                    <?php echo $filters['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Description..." 
                                   value="<?php echo htmlspecialchars($filters['search']); ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="activity-logs.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="logsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Table</th>
                                        <th>Record ID</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="log-timestamp">
                                            <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($log['full_name'] ?: 'System'); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['username'] ?: 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $actionColors = [
                                                'create' => 'success',
                                                'update' => 'info',
                                                'delete' => 'danger',
                                                'view' => 'secondary',
                                                'login' => 'primary',
                                                'logout' => 'warning'
                                            ];
                                            $color = $actionColors[$log['action']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?> action-badge">
                                                <?php echo ucfirst($log['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($log['table_name']); ?></code>
                                        </td>
                                        <td>
                                            <?php echo $log['record_id']; ?>
                                        </td>
                                        <td class="log-description">
                                            <?php echo htmlspecialchars($log['description'] ?: '-'); ?>
                                        </td>
                                        <td class="ip-address">
                                            <?php echo htmlspecialchars($log['ip_address'] ?: 'Unknown'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (empty($logs)): ?>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i> No activity logs found matching the selected filters.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Showing up to 10,000 most recent records. 
                        Use filters to narrow down results.
                    </small>
                </div>
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
            $('#logsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 50,
                columnDefs: [
                    { orderable: false, targets: 5 }
                ]
            });
        });
    </script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
</body>
</html>
