<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance()->getConnection();
$user = $auth->getUser();
$errors = [];
$success = '';

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userDetails = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$fullName, $email, $user['id']])) {
            $_SESSION['full_name'] = $fullName;
            $userDetails['full_name'] = $fullName;
            $userDetails['email'] = $email;
            $success = 'Profile updated successfully!';
        } else {
            $errors[] = 'Failed to update profile';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - DOLE DILP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--dole-primary), var(--dole-secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--dole-border-radius);
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 80px; height: 80px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin-bottom: 1rem;
        }
        .info-section {
            background-color: var(--dole-light);
            padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;
        }
        .info-item { margin-bottom: 1rem; }
        .info-item label {
            font-weight: 600; color: #495057; display: block;
            margin-bottom: 0.5rem; font-size: 0.9rem;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .info-item p { color: #212529; margin: 0; }
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
                    <h2><i class="bi bi-person-circle"></i> My Profile</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($userDetails['full_name']); ?></h3>
                    <p class="mb-0">
                        <span class="badge bg-light text-dark">
                            <?php echo ucfirst($userDetails['role']); ?>
                        </span>
                    </p>
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
                                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Username <span class="text-muted">(Cannot be changed)</span></label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userDetails['username']); ?>" disabled>
                                        <small class="text-muted">Your username is permanent and cannot be modified</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($userDetails['full_name']); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" required
                                               value="<?php echo htmlspecialchars($userDetails['email']); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($userDetails['role']); ?>" disabled>
                                        <small class="text-muted">Contact administrator to change your role</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Account Status</label>
                                        <div>
                                            <span class="badge bg-<?php echo $userDetails['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $userDetails['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Save Changes
                                        </button>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Account Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <label>Account Created</label>
                                    <p><?php echo date('F d, Y \a\t H:i', strtotime($userDetails['created_at'])); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Last Updated</label>
                                    <p><?php echo date('F d, Y \a\t H:i', strtotime($userDetails['updated_at'])); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Account ID</label>
                                    <p><?php echo $userDetails['id']; ?></p>
                                </div>
                                <hr>
                                <a href="change-password.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-key"></i> Change Password
                                </a>
                            </div>
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
            DILP.validation.init('form[method="POST"]');
        });
    </script>
</body>
</html>
