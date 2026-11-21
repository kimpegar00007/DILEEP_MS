<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/SharedBudget.php';

$auth = new Auth();
$auth->requireLogin();

$canWrite  = $auth->hasRole(['admin', 'encoder']);
$canDelete = $auth->hasRole('admin');

$model  = new SharedBudget();
$budget = isset($_GET['id']) ? $model->findById($_GET['id']) : false;   // province-scoped
if (!$budget) {
    header('Location: shared-budgets.php');
    exit;
}

$proponents    = $model->getProponents($budget['id']);
$beneficiaries = $model->getBeneficiaries($budget['id']);
$proponentNames = [];
foreach ($proponents as $p) {
    $proponentNames[$p['id']] = $p['proponent_name'];
}
$success = $_GET['success'] ?? '';

function sbv_status_badge(string $s): string {
    $map = ['pending' => 'secondary', 'approved' => 'info', 'implemented' => 'primary', 'liquidated' => 'warning', 'monitored' => 'success'];
    return '<span class="badge bg-' . ($map[$s] ?? 'secondary') . '">' . htmlspecialchars(ucfirst($s)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($budget['title']); ?> - Shared Budget - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
</head>
<body>
    <?php $currentPage = 'shared-budgets'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h2 class="mb-0"><i class="bi bi-pie-chart"></i> <?php echo htmlspecialchars($budget['title']); ?></h2>
                    <div class="d-flex gap-2">
                        <?php if ($canWrite): ?><a href="shared-budget-form.php?id=<?php echo (int) $budget['id']; ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a><?php endif; ?>
                        <?php if ($canDelete): ?><a href="shared-budget-delete.php?id=<?php echo (int) $budget['id']; ?>" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</a><?php endif; ?>
                        <a href="shared-budgets.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
                    </div>
                </div>

                <?php if ($success === 'created' || $success === 'updated'): ?>
                <div class="alert alert-success alert-dismissible fade show">Shared budget <?php echo $success; ?> successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <?php if ($budget['mismatch']): ?>
                <div class="alert alert-danger d-flex gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>The shares don't match the total budget.</strong>
                        The shares add up to &#8369;<?php echo number_format($budget['allocated'], 2); ?> but the approved total is
                        &#8369;<?php echo number_format($budget['total_budget'], 2); ?>
                        (<?php echo $budget['difference'] > 0 ? '&#8369;' . number_format($budget['difference'], 2) . ' not yet assigned' : '&#8369;' . number_format(-$budget['difference'], 2) . ' over'; ?>).
                        This usually happens when an amount is edited in a Proponent or Individual form<?php echo $canWrite ? ' &mdash; use Edit to rebalance the shares' : ''; ?>.
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="card h-100"><div class="card-body">
                        <div class="text-muted small">Total budget</div><div class="fs-4 fw-semibold">&#8369;<?php echo number_format($budget['total_budget'], 2); ?></div>
                    </div></div></div>
                    <div class="col-md-3"><div class="card h-100"><div class="card-body">
                        <div class="text-muted small">Allocated in shares</div>
                        <div class="fs-4 fw-semibold <?php echo $budget['mismatch'] ? 'text-danger' : 'text-success'; ?>">&#8369;<?php echo number_format($budget['allocated'], 2); ?></div>
                    </div></div></div>
                    <div class="col-md-3"><div class="card h-100"><div class="card-body">
                        <div class="text-muted small">Province</div><div class="fs-5 fw-semibold"><?php echo htmlspecialchars($budget['province']); ?></div>
                    </div></div></div>
                    <div class="col-md-3"><div class="card h-100"><div class="card-body">
                        <div class="text-muted small">Source of funds</div><div class="fs-5 fw-semibold"><?php echo htmlspecialchars($budget['source_of_funds'] ?: '—'); ?></div>
                    </div></div></div>
                </div>

                <?php if (!empty($budget['notes'])): ?>
                <div class="card mb-4"><div class="card-body"><strong>Notes</strong><div><?php echo nl2br(htmlspecialchars($budget['notes'])); ?></div></div></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><strong><i class="bi bi-building"></i> Proponents (<?php echo count($proponents); ?>)</strong></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th>Proponent</th><th>Project</th><th>Type</th><th>Status</th><th class="text-end">Share</th><th></th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($proponents as $p): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($p['proponent_name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['project_title']); ?></td>
                                    <td><?php echo htmlspecialchars($p['proponent_type']); ?></td>
                                    <td><?php echo sbv_status_badge($p['status']); ?></td>
                                    <td class="text-end">&#8369;<?php echo number_format($p['amount'], 2); ?></td>
                                    <td class="text-end"><a href="proponent-view.php?id=<?php echo (int) $p['id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><strong><i class="bi bi-people"></i> Individuals (<?php echo count($beneficiaries); ?>)</strong></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th>Individual</th><th>Project</th><th>Linked proponent</th><th>Status</th><th class="text-end">Share</th><th></th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($beneficiaries as $b): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars(trim($b['first_name'] . ' ' . $b['last_name'])); ?></td>
                                    <td><?php echo htmlspecialchars($b['project_name']); ?></td>
                                    <td><?php echo htmlspecialchars($proponentNames[$b['proponent_id']] ?? '—'); ?></td>
                                    <td><?php echo sbv_status_badge($b['status']); ?></td>
                                    <td class="text-end">&#8369;<?php echo number_format($b['amount_worth'], 2); ?></td>
                                    <td class="text-end"><a href="beneficiary-view.php?id=<?php echo (int) $b['id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light"><tr>
                                <th colspan="4" class="text-end">Total of all shares</th>
                                <th class="text-end">&#8369;<?php echo number_format($budget['allocated'], 2); ?></th><th></th>
                            </tr></tfoot>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
