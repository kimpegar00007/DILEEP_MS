<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$db = Database::getInstance()->getConnection();
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';
        
        if (empty($username)) $errors[] = 'Username is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($fullName)) $errors[] = 'Full name is required';
        if (empty($password)) $errors[] = 'Password is required';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
        if (!in_array($role, ['admin', 'encoder', 'user'])) $errors[] = 'Invalid role';
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Username already exists';
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Email already exists';
        
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            if ($stmt->execute([$username, $email, $hashedPassword, $fullName, $role])) {
                $userId = $db->lastInsertId();
                $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, 'create', 'users', ?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], $userId, "Created new user: $username", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                $success = 'User created successfully!';
            } else {
                $errors[] = 'Failed to create user';
            }
        }
    } elseif ($action === 'update') {
        $userId = intval($_POST['user_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($fullName)) $errors[] = 'Full name is required';
        if (!in_array($role, ['admin', 'encoder', 'user'])) $errors[] = 'Invalid role';
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Email already exists';
        
        if (empty($errors)) {
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 8) {
                    $errors[] = 'Password must be at least 8 characters';
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET email = ?, full_name = ?, role = ?, is_active = ?, password = ? WHERE id = ?");
                    $stmt->execute([$email, $fullName, $role, $isActive, $hashedPassword, $userId]);
                }
            } else {
                $stmt = $db->prepare("UPDATE users SET email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$email, $fullName, $role, $isActive, $userId]);
            }
            
            if (empty($errors)) {
                $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address) VALUES (?, 'update', 'users', ?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], $userId, "Updated user ID: $userId", $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                $success = 'User updated successfully!';
            }
        }
    }
}

$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
</head>
<body>
    <?php $currentPage = 'users'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-people-fill"></i> User Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-plus-circle"></i> Add New User
                    </button>
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
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="usersTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php
                                            $roleColors = ['admin' => 'danger', 'encoder' => 'primary', 'user' => 'secondary'];
                                            $color = $roleColors[$user['role']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning action-btn" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" minlength="8" required>
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span>
                                <span class="help-icon" data-bs-toggle="tooltip" title="Admin: Full access. Encoder: Can add/edit records. User: View only.">?</span>
                            </label>
                            <select name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="encoder">Encoder</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" id="edit_username" class="form-control" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="8">
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="encoder">Encoder</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25
            });
        });

        function editUser(user) {
            $('#edit_user_id').val(user.id);
            $('#edit_username').val(user.username);
            $('#edit_email').val(user.email);
            $('#edit_full_name').val(user.full_name);
            $('#edit_role').val(user.role);
            $('#edit_is_active').prop('checked', user.is_active == 1);
            
            var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        }
    </script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            DILP.validation.init('#createUserModal form');
            DILP.validation.init('#editUserModal form');
            DILP.passwordStrength.init('#createUserModal input[name="password"]');
        });
    </script>
</body>
</html>
