<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword)) {
        $errors[] = 'Current password is required';
    }
    if (empty($newPassword)) {
        $errors[] = 'New password is required';
    }
    if (empty($confirmPassword)) {
        $errors[] = 'Password confirmation is required';
    }
    
    if (!empty($newPassword) && strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters';
    }
    
    if (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        if ($auth->changePassword($_SESSION['user_id'], $oldPassword, $newPassword)) {
            $success = 'Password changed successfully!';
        } else {
            $errors[] = 'Current password is incorrect';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - DOLE DILP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .password-header {
            background: linear-gradient(135deg, var(--dole-primary), var(--dole-secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--dole-border-radius);
            margin-bottom: 2rem;
        }
        .security-tips {
            background-color: #e7f3ff;
            border-left: 4px solid var(--dole-primary);
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1.5rem;
        }
        .security-tips h6 { color: var(--dole-primary); margin-bottom: 0.75rem; }
        .security-tips ul { margin-bottom: 0; padding-left: 1.25rem; }
        .security-tips li { font-size: 0.9rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <?php $currentPage = ''; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-key"></i> Change Password</h2>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Profile
                    </a>
                </div>

                <div class="password-header">
                    <h4 class="mb-2">Secure Your Account</h4>
                    <p class="mb-0">Update your password regularly to keep your account secure</p>
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

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-lock"></i> Password Change Form</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="passwordForm">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <input type="password" name="old_password" class="form-control" required>
                                        <small class="text-muted">Enter your current password to verify your identity</small>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" name="new_password" id="newPassword" class="form-control" 
                                               minlength="8" required>
                                        <small class="text-muted">Minimum 8 characters</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <input type="password" name="confirm_password" class="form-control" 
                                               minlength="8" required>
                                        <small class="text-muted">Re-enter your new password</small>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Change Password
                                        </button>
                                        <a href="profile.php" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="security-tips">
                            <h6><i class="bi bi-shield-check"></i> Password Security Tips</h6>
                            <ul>
                                <li>Use at least 8 characters</li>
                                <li>Mix uppercase and lowercase letters</li>
                                <li>Include numbers and symbols</li>
                                <li>Avoid using personal information</li>
                                <li>Don't reuse old passwords</li>
                                <li>Change regularly (every 90 days)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            DILP.passwordStrength.init('#newPassword');
            DILP.validation.init('#passwordForm');
        });
    </script>
</body>
</html>
