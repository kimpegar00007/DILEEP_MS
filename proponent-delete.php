<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$db = Database::getInstance()->getConnection();
$proponentModel = new Proponent();

if (!isset($_GET['id'])) {
    header('Location: proponents.php');
    exit;
}

$proponentId = intval($_GET['id']);
$proponent = $proponentModel->findById($proponentId);

if (!$proponent) {
    header('Location: proponents.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmDelete = $_POST['confirm_delete'] ?? '';
    
    if ($confirmDelete !== 'yes') {
        $errors[] = 'You must confirm the deletion';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("DELETE FROM proponents WHERE id = ?");
            if ($stmt->execute([$proponentId])) {
                $logStmt = $db->prepare(
                    "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) 
                     VALUES (?, 'delete', 'proponents', ?, ?, ?)"
                );
                $logStmt->execute([
                    $_SESSION['user_id'],
                    $proponentId,
                    "Deleted proponent: " . $proponent['proponent_name'],
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                header('Location: proponents.php?deleted=1');
                exit;
            } else {
                $errors[] = 'Failed to delete proponent';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Proponent - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <style>
        .delete-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        .delete-warning h4 { color: #856404; margin-bottom: 1rem; }
        .delete-warning ul { margin-bottom: 0; color: #856404; }
        .delete-warning li { margin-bottom: 0.5rem; }
        .proponent-info {
            background-color: var(--dole-light);
            padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;
        }
        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem; margin-bottom: 1rem;
        }
        .info-item {
            padding: 0.75rem; background-color: white;
            border-radius: 5px; border-left: 3px solid var(--dole-danger);
        }
        .info-item label {
            font-weight: 600; color: #495057; font-size: 0.85rem;
            text-transform: uppercase; letter-spacing: 0.5px;
            display: block; margin-bottom: 0.5rem;
        }
        .info-item p { margin: 0; color: #212529; }
        .confirmation-box {
            background-color: #f8d7da; border: 2px solid #f5c6cb;
            padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;
        }
        .confirmation-box h5 { color: #721c24; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php $currentPage = 'proponents'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-trash"></i> Delete Proponent</h2>
                    <a href="proponents.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>

                <div class="delete-warning">
                    <h4><i class="bi bi-exclamation-triangle"></i> Warning: Permanent Deletion</h4>
                    <ul>
                        <li>This action cannot be undone</li>
                        <li>All associated data will be permanently deleted</li>
                        <li>This deletion will be logged in the activity logs</li>
                        <li>Only administrators can delete proponents</li>
                    </ul>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Error!</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="proponent-info">
                    <h5 class="mb-3"><i class="bi bi-info-circle"></i> Proponent Information</h5>
                    <div class="info-row">
                        <div class="info-item">
                            <label>Proponent/ACP Name</label>
                            <p><?php echo htmlspecialchars($proponent['proponent_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Project Title</label>
                            <p><?php echo htmlspecialchars($proponent['project_title']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Proponent Type</label>
                            <p><?php echo htmlspecialchars($proponent['proponent_type']); ?></p>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <label>Project Amount</label>
                            <p><strong>₱<?php echo number_format($proponent['amount'], 2); ?></strong></p>
                        </div>
                        <div class="info-item">
                            <label>Total Beneficiaries</label>
                            <p><?php echo number_format($proponent['total_beneficiaries']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Current Status</label>
                            <p>
                                <?php
                                $statusColors = ['pending' => 'secondary', 'approved' => 'primary', 'implemented' => 'success', 'liquidated' => 'warning', 'monitored' => 'info'];
                                $color = $statusColors[$proponent['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>">
                                    <?php echo ucfirst($proponent['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="confirmation-box">
                            <h5><i class="bi bi-shield-exclamation"></i> Confirm Deletion</h5>
                            <p class="mb-0">
                                Are you absolutely sure you want to delete this proponent record? 
                                This action is <strong>permanent and irreversible</strong>.
                            </p>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="confirm_delete" 
                                           id="confirmDelete" value="yes" required>
                                    <label class="form-check-label" for="confirmDelete">
                                        I understand that deleting this proponent record is permanent and cannot be undone
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-trash"></i> Delete Proponent
                                </button>
                                <a href="proponent-view.php?id=<?php echo $proponent['id']; ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-4">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Record ID: <?php echo $proponent['id']; ?> | 
                        Created: <?php echo date('F d, Y', strtotime($proponent['created_at'])); ?>
                    </small>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/ux-utilities.php'; ?>
</body>
</html>
