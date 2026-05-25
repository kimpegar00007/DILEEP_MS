<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

// Only super_admin can access this page
if (!$auth->isSuperAdmin()) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get system statistics
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$beneficiaryCount = $db->query("SELECT COUNT(*) FROM beneficiaries")->fetchColumn();
$proponentCount = $db->query("SELECT COUNT(*) FROM proponents")->fetchColumn();

// Get database size
$dbName = 'dilp_monitoring';
$stmt = $db->prepare("
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.TABLES 
    WHERE table_schema = ?
");
$stmt->execute([$dbName]);
$dbSize = $stmt->fetchColumn() ?: 0;

// Get maintenance mode status
$maintenanceMode = false;
try {
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $maintenanceMode = !empty($row['setting_value']) && $row['setting_value'] === '1';
} catch (PDOException $e) {
    // Table or setting may not exist yet — treat as disabled
    $maintenanceMode = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Administration - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .admin-card {
            background: white;
            border-radius: var(--dole-border-radius);
            box-shadow: var(--dole-box-shadow);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .admin-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .admin-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(27,122,61,0.08), rgba(27,122,61,0.04));
            border-left: 4px solid var(--dole-primary);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dole-primary);
            margin-bottom: 0.25rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .danger-zone {
            background: #fff5f5;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .danger-zone-title {
            color: #dc3545;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .maintenance-card {
            border: 2px solid #ffc107;
            background: #fffbf0;
        }
        .maintenance-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .maintenance-status-badge.enabled {
            background-color: #dc3545;
            color: white;
        }
        .maintenance-status-badge.disabled {
            background-color: #28a745;
            color: white;
        }
        .form-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
            cursor: pointer;
        }
        .form-switch .form-check-input:checked {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php $currentPage = 'system-admin'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-shield-lock"></i> System Administration</h2>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <strong>Super Admin Access:</strong> This page provides system-level administrative functions. Use with caution.
                </div>

                <!-- System Statistics -->
                <div class="admin-card">
                    <h5 class="mb-4"><i class="bi bi-bar-chart"></i> System Statistics</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($userCount); ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($beneficiaryCount); ?></div>
                                <div class="stat-label">Beneficiaries</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($proponentCount); ?></div>
                                <div class="stat-label">Proponents</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($dbSize, 2); ?> MB</div>
                                <div class="stat-label">Database Size</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Mode -->
                <div class="admin-card maintenance-card">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="admin-card-icon" style="background: linear-gradient(135deg, #ffc107, #ff9800); color: white; margin: 0 auto;">
                                <i class="bi bi-tools"></i>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <h5 class="mb-0">Maintenance Mode</h5>
                                <span class="maintenance-status-badge <?php echo $maintenanceMode ? 'enabled' : 'disabled'; ?>" id="maintenanceStatusBadge">
                                    <i class="bi <?php echo $maintenanceMode ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'; ?>"></i>
                                    <span id="maintenanceStatusText"><?php echo $maintenanceMode ? 'Enabled' : 'Disabled'; ?></span>
                                </span>
                            </div>
                            <p class="text-muted mb-0">
                                When enabled, only administrators can access the system. Regular users will see a maintenance page.
                            </p>
                        </div>
                        <div class="col-md-3 text-end">
                            <div class="form-check form-switch d-flex justify-content-end align-items-center">
                                <input class="form-check-input" type="checkbox" role="switch" id="maintenanceToggle" 
                                       <?php echo $maintenanceMode ? 'checked' : ''; ?>
                                       onchange="toggleMaintenanceMode(this.checked)">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Management -->
                <div class="admin-card">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="admin-card-icon" style="background: linear-gradient(135deg, #0066cc, #004999); color: white; margin: 0 auto;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h5 class="mb-2">User Management</h5>
                            <p class="text-muted mb-0">
                                Create, edit, and manage user accounts. Assign roles and provinces to control access levels.
                            </p>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="users.php" class="btn btn-primary">
                                <i class="bi bi-person-gear"></i> Manage Users
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Organizational Chart -->
                <div class="admin-card">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="admin-card-icon" style="background: linear-gradient(135deg, var(--dole-primary), var(--dole-secondary)); color: white; margin: 0 auto;">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h5 class="mb-2">Organizational Chart</h5>
                            <p class="text-muted mb-0">
                                Update the DILEEP-NOCFO organizational structure displayed on the About page.
                            </p>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="org-chart-admin.php" class="btn btn-primary">
                                <i class="bi bi-pencil-square"></i> Edit Org Chart
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="admin-card">
                    <div class="danger-zone">
                        <div class="danger-zone-title">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>Danger Zone</span>
                        </div>
                        <p class="text-muted mb-3">
                            These actions are irreversible and will permanently affect the database. Proceed with extreme caution.
                        </p>
                        <div class="row align-items-center">
                            <div class="col-md-9">
                                <h6 class="mb-1">Reset All Records</h6>
                                <small class="text-muted">
                                    Permanently delete all beneficiaries and proponents, and reset auto-increment IDs to 1.
                                </small>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="reset-records.php" class="btn btn-danger">
                                    <i class="bi bi-trash"></i> Reset Records
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <script>
        function toggleMaintenanceMode(enabled) {
            const toggle = document.getElementById('maintenanceToggle');
            const badge = document.getElementById('maintenanceStatusBadge');
            const statusText = document.getElementById('maintenanceStatusText');
            
            // Show loading state
            if (typeof DILP !== 'undefined' && DILP.loading) {
                DILP.loading.show(enabled ? 'Enabling maintenance mode...' : 'Disabling maintenance mode...');
            }
            
            // Disable toggle during request
            toggle.disabled = true;
            
            // Make AJAX request
            fetch('api/toggle-maintenance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'value=' + (enabled ? '1' : '0')
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }
                
                if (data.success) {
                    // Update UI
                    if (enabled) {
                        badge.classList.remove('disabled');
                        badge.classList.add('enabled');
                        statusText.textContent = 'Enabled';
                        badge.querySelector('i').className = 'bi bi-exclamation-triangle-fill';
                    } else {
                        badge.classList.remove('enabled');
                        badge.classList.add('disabled');
                        statusText.textContent = 'Disabled';
                        badge.querySelector('i').className = 'bi bi-check-circle-fill';
                    }
                    
                    // Show success notification
                    if (typeof DILP !== 'undefined' && DILP.toast) {
                        DILP.toast.success('Success', data.message);
                    } else {
                        alert(data.message);
                    }
                } else {
                    // Revert toggle on error
                    toggle.checked = !enabled;
                    
                    // Show error notification
                    if (typeof DILP !== 'undefined' && DILP.toast) {
                        DILP.toast.error('Error', data.message || 'Failed to toggle maintenance mode');
                    } else {
                        alert('Error: ' + (data.message || 'Failed to toggle maintenance mode'));
                    }
                }
                
                // Re-enable toggle
                toggle.disabled = false;
            })
            .catch(error => {
                // Hide loading
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }
                
                // Revert toggle on error
                toggle.checked = !enabled;
                toggle.disabled = false;
                
                // Show error notification
                if (typeof DILP !== 'undefined' && DILP.toast) {
                    DILP.toast.error('Error', 'An unexpected error occurred. Please try again.');
                } else {
                    alert('Error: An unexpected error occurred. Please try again.');
                }
                
                console.error('Maintenance mode toggle error:', error);
            });
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
