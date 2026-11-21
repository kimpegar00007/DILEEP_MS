<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/SharedBudget.php';

$auth = new Auth();
$auth->requireLogin();

$isCrossProvince = $auth->isCrossProvince();
$canWrite        = $auth->hasRole(['admin', 'encoder']);
$canDelete       = $auth->hasRole('admin');

$model = new SharedBudget();

// Cross-province roles may filter by province; everyone else is scoped inside the model.
$provinceFilter = $isCrossProvince ? trim($_GET['province'] ?? '') : '';
$search         = trim($_GET['search'] ?? '');
$budgets        = $model->getAll(['province' => $provinceFilter, 'search' => $search]);

$totalBudget = array_sum(array_column($budgets, 'total_budget'));
$mismatches  = count(array_filter($budgets, function ($b) { return $b['mismatch']; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Budgets - DOLE DILEEP Monitoring System</title>
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
                    <h2 class="mb-0"><i class="bi bi-pie-chart"></i> Shared Budgets</h2>
                    <?php if ($canWrite): ?>
                    <a href="shared-budget-form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Shared Budget</a>
                    <?php endif; ?>
                </div>

                <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show">Shared budget deleted. Its records were kept.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <p class="text-muted">Livelihood projects where several Proponents and Beneficiaries share one budget, each with their own share.</p>

                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="card"><div class="card-body">
                        <div class="text-muted small">Shared budgets</div><div class="fs-4 fw-semibold"><?php echo count($budgets); ?></div>
                    </div></div></div>
                    <div class="col-md-4"><div class="card"><div class="card-body">
                        <div class="text-muted small">Combined total budget</div><div class="fs-4 fw-semibold">&#8369;<?php echo number_format($totalBudget, 2); ?></div>
                    </div></div></div>
                    <div class="col-md-4"><div class="card"><div class="card-body">
                        <div class="text-muted small">Shares not matching the total</div>
                        <div class="fs-4 fw-semibold <?php echo $mismatches ? 'text-danger' : 'text-success'; ?>"><?php echo $mismatches; ?></div>
                    </div></div></div>
                </div>

                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search title or source of funds" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <?php if ($isCrossProvince): ?>
                    <div class="col-md-3">
                        <select name="province" class="form-select">
                            <option value="">All Provinces</option>
                            <?php foreach (Auth::PROVINCES as $p): ?>
                            <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $provinceFilter === $p ? 'selected' : ''; ?>><?php echo htmlspecialchars($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filter</button></div>
                </form>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Province</th>
                                    <th class="text-end">Total Budget</th>
                                    <th class="text-end">Allocated</th>
                                    <th class="text-center">Proponents</th>
                                    <th class="text-center">Beneficiaries</th>
                                    <th>Check</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$budgets): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No shared budgets yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($budgets as $b): ?>
                                <tr>
                                    <td><a href="shared-budget-view.php?id=<?php echo (int) $b['id']; ?>" class="fw-semibold text-decoration-none"><?php echo htmlspecialchars($b['title']); ?></a></td>
                                    <td><?php echo htmlspecialchars($b['province']); ?></td>
                                    <td class="text-end">&#8369;<?php echo number_format($b['total_budget'], 2); ?></td>
                                    <td class="text-end">&#8369;<?php echo number_format($b['allocated'], 2); ?></td>
                                    <td class="text-center"><?php echo (int) $b['proponent_count']; ?></td>
                                    <td class="text-center"><?php echo (int) $b['beneficiary_count']; ?></td>
                                    <td>
                                        <?php if ($b['mismatch']): ?>
                                        <span class="badge bg-danger" title="Shares add up to &#8369;<?php echo number_format($b['allocated'], 2); ?>"><i class="bi bi-exclamation-triangle"></i> Shares don't match</span>
                                        <?php else: ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Balanced</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="shared-budget-view.php?id=<?php echo (int) $b['id']; ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                        <?php if ($canWrite): ?>
                                        <a href="shared-budget-form.php?id=<?php echo (int) $b['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                        <a href="shared-budget-delete.php?id=<?php echo (int) $b['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
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
