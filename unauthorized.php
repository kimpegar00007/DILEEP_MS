<?php
// Shown by Auth::requireRole() when a signed-in user lacks the role a page needs.
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - DOLE DILP Monitoring System</title>
    <link rel="icon" href="assets/dileep-logo.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
</head>
<body>
    <?php $currentPage = ''; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="card shadow-sm mx-auto mt-4" style="max-width: 640px;">
                    <div class="card-body text-center p-5">
                        <div class="display-4 text-warning mb-3"><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
                        <h2 class="h4 mb-2">You don't have access to this page</h2>
                        <p class="text-muted mb-4">
                            Your account (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['role'] ?? ''))); ?>)
                            can't open that page. If you need access, ask a system administrator to change your role.
                        </p>
                        <a href="index.php" class="btn btn-primary"><i class="bi bi-house"></i> Back to Dashboard</a>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
</body>
</html>
