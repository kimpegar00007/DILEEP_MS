<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('super_admin'); // Restricted to super_admin only

$db = Database::getInstance()->getConnection();
$errors = [];
$success = '';

/**
 * Check if a table exists using information_schema.
 * Uses information_schema instead of "SHOW TABLES LIKE ?" to ensure reliable
 * behavior with native prepared statements (PDO::ATTR_EMULATE_PREPARES = false).
 */
function resetTableExists(PDO $db, string $tableName): bool {
    try {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
        );
        $stmt->execute([$tableName]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('[reset-records] resetTableExists failed for ' . $tableName . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Return the row count of a table, or 0 if it does not exist or the query fails.
 */
function resetTableCount(PDO $db, string $tableName): int {
    if (!resetTableExists($db, $tableName)) {
        return 0;
    }
    try {
        $stmt = $db->query(
            "SELECT COUNT(*) FROM `" . str_replace('`', '``', $tableName) . "`"
        );
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[reset-records] resetTableCount failed for ' . $tableName . ': ' . $e->getMessage());
        return 0;
    }
}

/**
 * Build the counts array, falling back to zeros on any DB error so the page
 * still renders instead of throwing an uncaught exception (HTTP 500).
 */
function resetLoadCounts(PDO $db): array {
    $tables = ['beneficiaries', 'proponents', 'proponent_associations', 'proponent_returns'];
    $result = array_fill_keys($tables, 0);
    foreach ($tables as $table) {
        $result[$table] = resetTableCount($db, $table);
    }
    return $result;
}

$counts = resetLoadCounts($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmDelete    = $_POST['confirm_delete'] ?? '';
    $confirmationText = trim($_POST['confirmation_text'] ?? '');

    if ($confirmDelete !== 'yes') {
        $errors[] = 'You must confirm that this reset is permanent.';
    }

    if ($confirmationText !== 'ERASE DATA') {
        $errors[] = 'Type ERASE DATA exactly to proceed.';
    }

    if (empty($errors)) {
        try {
            $beforeCounts = $counts;
            $db->beginTransaction();

            // Delete child tables first to satisfy FK constraints
            foreach (['proponent_returns', 'proponent_associations'] as $tableName) {
                if (resetTableExists($db, $tableName)) {
                    $db->exec("DELETE FROM `" . str_replace('`', '``', $tableName) . "`");
                }
            }

            // Delete parent tables
            foreach (['proponents', 'beneficiaries'] as $tableName) {
                if (resetTableExists($db, $tableName)) {
                    $db->exec("DELETE FROM `" . str_replace('`', '``', $tableName) . "`");
                }
            }

            $logStmt = $db->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, 'reset', 'system', 0, ?, ?)"
            );
            $logStmt->execute([
                $_SESSION['user_id'] ?? null,
                'Erased proponent and beneficiary records. Proponents: ' . $beforeCounts['proponents'] . ', Beneficiaries: ' . $beforeCounts['beneficiaries'] . '.',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $db->commit();

            // Reset AUTO_INCREMENT counters outside the transaction (DDL cannot be rolled back).
            // Wrap separately so a failure here does not mask the successful data deletion.
            foreach (['proponent_returns', 'proponent_associations', 'proponents', 'beneficiaries'] as $tableName) {
                if (resetTableExists($db, $tableName)) {
                    try {
                        $db->exec(
                            "ALTER TABLE `" . str_replace('`', '``', $tableName) . "` AUTO_INCREMENT = 1"
                        );
                    } catch (PDOException $e) {
                        error_log('[reset-records] AUTO_INCREMENT reset failed for ' . $tableName . ': ' . $e->getMessage());
                    }
                }
            }

            $success = 'Proponent and beneficiary records were erased successfully. The next encoded ID will start at 1.';
            $counts  = resetLoadCounts($db);

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = 'Reset failed: ' . $e->getMessage();
            error_log('[reset-records] Reset failed: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Records - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .reset-warning {
            background-color: #f8d7da;
            border-left: 5px solid var(--dole-danger);
            border-radius: var(--dole-border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .reset-count-card {
            background: white;
            border-radius: var(--dole-border-radius);
            box-shadow: var(--dole-box-shadow);
            padding: 1.5rem;
            height: 100%;
            border-top: 4px solid var(--dole-primary);
        }
        .reset-count-card .count-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dole-primary);
        }
        .confirmation-panel {
            border: 2px solid var(--dole-danger);
            border-radius: var(--dole-border-radius);
            padding: 1.5rem;
            background: white;
        }
    </style>
</head>
<body>
    <?php $currentPage = 'reset-records'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-exclamation-octagon"></i> Reset Proponent and Beneficiary Records</h2>
                    <a href="settings.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Settings
                    </a>
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

                <div class="reset-warning">
                    <h4 class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Permanent Data Reset</h4>
                    <p class="mb-2">This will permanently erase all records from proponents and beneficiaries, including proponent associations and return history.</p>
                    <ul class="mb-0">
                        <li>User accounts, settings, fieldwork schedules, and activity logs will remain.</li>
                        <li>After successful deletion, the next encoded proponent and beneficiary ID will start at 1.</li>
                        <li>This action cannot be undone from this screen.</li>
                    </ul>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-xl-3">
                        <div class="reset-count-card">
                            <div class="text-muted">Beneficiaries</div>
                            <div class="count-value"><?php echo number_format($counts['beneficiaries']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="reset-count-card">
                            <div class="text-muted">Proponents</div>
                            <div class="count-value"><?php echo number_format($counts['proponents']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="reset-count-card">
                            <div class="text-muted">Associations</div>
                            <div class="count-value"><?php echo number_format($counts['proponent_associations']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="reset-count-card">
                            <div class="text-muted">Return Entries</div>
                            <div class="count-value"><?php echo number_format($counts['proponent_returns']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="confirmation-panel">
                    <h5 class="text-danger mb-3"><i class="bi bi-shield-exclamation"></i> Confirm Reset</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Type <strong>ERASE DATA</strong> to continue</label>
                            <input type="text" name="confirmation_text" class="form-control" autocomplete="off" required>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="confirm_delete" id="confirmDelete" value="yes" required>
                            <label class="form-check-label" for="confirmDelete">
                                I understand this will permanently erase all proponent and beneficiary records.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3"></i> Erase Records and Reset IDs
                        </button>
                    </form>
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
