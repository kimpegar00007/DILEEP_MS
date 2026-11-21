<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/SharedBudget.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$model = new SharedBudget();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$budget = $id ? $model->findById($id) : false;   // province-scoped

if (!$budget) {
    header('Location: shared-budgets.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['confirm_delete'] ?? '') !== 'yes') {
        $errors[] = 'You must confirm the deletion.';
    } elseif ($model->delete($id)) {
        header('Location: shared-budgets.php?deleted=1');
        exit;
    } else {
        $errors[] = 'Nothing was deleted: ' . ($model->getLastError() ?: 'unknown error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Shared Budget - DOLE DILEEP Monitoring System</title>
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
                <h2 class="mb-4"><i class="bi bi-trash"></i> Delete Shared Budget</h2>

                <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                </ul></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>This removes only the budget grouping.</strong>
                            The <?php echo (int) $budget['proponent_count']; ?> proponent(s) and
                            <?php echo (int) $budget['beneficiary_count']; ?> beneficiary(ies) in it are <strong>kept</strong> as ordinary records
                            with their shares as their own amounts, so no money disappears from the totals.
                        </div>
                        <dl class="row mb-4">
                            <dt class="col-sm-3">Title</dt><dd class="col-sm-9"><?php echo htmlspecialchars($budget['title']); ?></dd>
                            <dt class="col-sm-3">Province</dt><dd class="col-sm-9"><?php echo htmlspecialchars($budget['province']); ?></dd>
                            <dt class="col-sm-3">Total budget</dt><dd class="col-sm-9">&#8369;<?php echo number_format($budget['total_budget'], 2); ?></dd>
                        </dl>
                        <form method="POST" action="">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_delete" value="yes" id="confirm_delete" required>
                                <label class="form-check-label" for="confirm_delete">Yes, delete this shared budget.</label>
                            </div>
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete Shared Budget</button>
                            <a href="shared-budget-view.php?id=<?php echo (int) $budget['id']; ?>" class="btn btn-secondary">Cancel</a>
                        </form>
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
