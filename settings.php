<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin');

$db = Database::getInstance()->getConnection();

// Check Google Drive connection status
$gdriveConnected = false;
$gdriveEmail = '';
try {
    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gdrive_access_token'");
    if ($stmt && $row = $stmt->fetch()) {
        $gdriveConnected = !empty($row['setting_value']);
    }
    $stmt2 = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gdrive_user_email'");
    if ($stmt2 && $row2 = $stmt2->fetch()) {
        $gdriveEmail = $row2['setting_value'] ?? '';
    }
} catch (PDOException $e) {
    // Table doesn't exist yet — that's fine
}

// Check if Google credentials are configured
$googleClientId = getenv('GOOGLE_CLIENT_ID') ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$googleConfigured = !empty($googleClientId);

// Get record counts for display
$beneficiaryCount = $db->query("SELECT COUNT(*) FROM beneficiaries")->fetchColumn();
$proponentCount = $db->query("SELECT COUNT(*) FROM proponents")->fetchColumn();

// Get table list for backup info
$tablesStmt = $db->query("SHOW TABLES");
$tableCount = $tablesStmt->rowCount();

// Maintenance mode status
$maintenanceEnabled = false;
try {
    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    if ($stmt && $row = $stmt->fetch()) {
        $maintenanceEnabled = ($row['setting_value'] === '1');
    }
} catch (PDOException $e) {
    // Table may not exist yet — default to disabled
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .settings-section {
            background-color: white;
            padding: 1.5rem;
            border-radius: var(--dole-border-radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--dole-box-shadow);
        }
        .settings-section h5 {
            color: var(--dole-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        .export-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.25rem;
            text-align: center;
            transition: var(--dole-transition);
        }
        .export-card:hover {
            border-color: var(--dole-primary);
            box-shadow: 0 4px 12px rgba(0,102,204,0.1);
        }
        .export-card .export-icon {
            font-size: 2.5rem;
            color: var(--dole-primary);
            margin-bottom: 0.75rem;
        }
        .export-card .record-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dole-secondary);
        }
        .import-zone {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: var(--dole-transition);
            cursor: pointer;
        }
        .import-zone:hover, .import-zone.dragover {
            border-color: var(--dole-primary);
            background-color: rgba(0,102,204,0.03);
        }
        .import-zone .upload-icon {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 0.75rem;
        }
        .import-zone.dragover .upload-icon {
            color: var(--dole-primary);
        }
        .import-preview {
            max-height: 300px;
            overflow-y: auto;
        }
        .backup-option {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            transition: var(--dole-transition);
        }
        .backup-option:hover {
            border-color: var(--dole-primary);
            box-shadow: 0 4px 12px rgba(0,102,204,0.1);
        }
        .backup-option .backup-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        .gdrive-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .gdrive-status.connected {
            background-color: #d4edda;
            color: #155724;
        }
        .gdrive-status.disconnected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: var(--dole-primary);
            font-weight: 600;
        }
        .nav-tabs .nav-link i {
            margin-right: 0.35rem;
        }
    </style>
</head>
<body>
    <?php $currentPage = 'settings'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-gear"></i> Settings</h2>
                </div>

                <!-- Maintenance Mode Card -->
                <div class="settings-section">
                    <h5><i class="bi bi-tools"></i> Site Maintenance</h5>
                    <p class="text-muted mb-3">Enable maintenance mode to temporarily block access for regular users and encoders. Administrators will still be able to access the site.</p>
                    <div class="d-flex align-items-center gap-3">
                        <div id="maintenanceStatus" class="gdrive-status <?php echo $maintenanceEnabled ? 'connected' : 'disconnected'; ?>">
                            <?php echo $maintenanceEnabled ? '<i class="bi bi-shield-lock"></i> Maintenance ON' : '<i class="bi bi-shield-check"></i> Maintenance OFF'; ?>
                        </div>
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenanceToggle" <?php echo $maintenanceEnabled ? 'checked' : ''; ?> onchange="toggleMaintenance(this.checked)">
                                <label class="form-check-label" for="maintenanceToggle">Enable maintenance mode</label>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">When enabled, users with roles <code>users</code> and <code>encoder</code> will be redirected to the maintenance page.</small>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="export-tab" data-bs-toggle="tab" data-bs-target="#exportPane" type="button" role="tab" aria-controls="exportPane" aria-selected="true">
                            <i class="bi bi-download" aria-hidden="true"></i> Data Export
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#importPane" type="button" role="tab" aria-controls="importPane" aria-selected="false">
                            <i class="bi bi-upload" aria-hidden="true"></i> Data Import
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backupPane" type="button" role="tab" aria-controls="backupPane" aria-selected="false">
                            <i class="bi bi-shield-check" aria-hidden="true"></i> System Backup
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="settingsTabContent">

                    <!-- ==================== EXPORT TAB ==================== -->
                    <div class="tab-pane fade show active" id="exportPane" role="tabpanel" aria-labelledby="export-tab">
                        <div class="settings-section">
                            <h5><i class="bi bi-download"></i> Export Data</h5>
                            <p class="text-muted mb-4">Download complete datasets as CSV files. These can be opened in Excel, Google Sheets, or any spreadsheet application.</p>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="export-card">
                                        <div class="export-icon"><i class="bi bi-person"></i></div>
                                        <h6>Beneficiaries</h6>
                                        <div class="record-count"><?php echo number_format($beneficiaryCount); ?></div>
                                        <small class="text-muted d-block mb-3">Total Records</small>
                                        <div class="row g-2 mt-2 text-start">
                                            <div class="col-6">
                                                <label class="form-label small mb-1">From</label>
                                                <input type="date" id="exportDateFromBeneficiaries" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small mb-1">To</label>
                                                <input type="date" id="exportDateToBeneficiaries" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1 mb-3">Leave empty to export all records</small>
                                        <div id="lastExportBeneficiaries" class="text-muted small mb-3" style="font-style: italic;"></div>
                                        <button type="button" class="btn btn-primary" onclick="exportData('beneficiaries')">
                                            <i class="bi bi-file-earmark-arrow-down"></i> Export Beneficiaries
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="export-card">
                                        <div class="export-icon"><i class="bi bi-people"></i></div>
                                        <h6>Proponents</h6>
                                        <div class="record-count"><?php echo number_format($proponentCount); ?></div>
                                        <small class="text-muted d-block mb-3">Total Records</small>
                                        <div class="row g-2 mt-2 text-start">
                                            <div class="col-6">
                                                <label class="form-label small mb-1">From</label>
                                                <input type="date" id="exportDateFromProponents" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small mb-1">To</label>
                                                <input type="date" id="exportDateToProponents" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1 mb-3">Leave empty to export all records</small>
                                        <div id="lastExportProponents" class="text-muted small mb-3" style="font-style: italic;"></div>
                                        <button type="button" class="btn btn-primary" onclick="exportData('proponents')">
                                            <i class="bi bi-file-earmark-arrow-down"></i> Export Proponents
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== IMPORT TAB ==================== -->
                    <div class="tab-pane fade" id="importPane" role="tabpanel" aria-labelledby="import-tab">
                        <div class="settings-section">
                            <h5><i class="bi bi-upload"></i> Import Data</h5>
                            <p class="text-muted mb-4">Upload CSV files to import records into the database. The CSV must include a header row matching the expected column names.</p>

                            <div class="row g-4">
                                <!-- Beneficiaries Import -->
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-white">
                                            <h6 class="mb-0"><i class="bi bi-person"></i> Import Beneficiaries</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="import-zone" id="importZoneBeneficiaries" onclick="document.getElementById('importFileBeneficiaries').click()">
                                                <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                                <p class="mb-1">Click to select or drag & drop a CSV file</p>
                                                <small class="text-muted">Maximum file size: 5MB</small>
                                                <input type="file" id="importFileBeneficiaries" accept=".csv" class="d-none" onchange="handleFileSelect(this, 'beneficiaries')">
                                            </div>
                                            <div id="importPreviewBeneficiaries" class="mt-3" style="display:none;">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong id="importFileNameBeneficiaries"></strong>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearImport('beneficiaries')">
                                                        <i class="bi bi-x-circle"></i> Clear
                                                    </button>
                                                </div>
                                                <div class="alert alert-info py-2 mb-2">
                                                    <small><i class="bi bi-info-circle"></i> <span id="importStatsBeneficiaries"></span></small>
                                                </div>
                                                <div class="import-preview">
                                                    <table class="table table-sm table-bordered" id="importTableBeneficiaries">
                                                        <thead class="table-light"></thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <button type="button" class="btn btn-success mt-3 w-100" id="importBtnBeneficiaries" onclick="confirmImport('beneficiaries')">
                                                    <i class="bi bi-database-add"></i> Import Records
                                                </button>
                                            </div>
                                            <div class="mt-3">
                                                <small class="text-muted">
                                                    <strong>Required columns:</strong> last_name, first_name, gender, barangay, municipality, project_name, amount_worth
                                                </small>
                                            </div>
                                            <div id="lastImportBeneficiaries" class="text-muted small mt-2" style="font-style: italic;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Proponents Import -->
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-white">
                                            <h6 class="mb-0"><i class="bi bi-people"></i> Import Proponents</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="import-zone" id="importZoneProponents" onclick="document.getElementById('importFileProponents').click()">
                                                <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                                <p class="mb-1">Click to select or drag & drop a CSV file</p>
                                                <small class="text-muted">Maximum file size: 5MB</small>
                                                <input type="file" id="importFileProponents" accept=".csv" class="d-none" onchange="handleFileSelect(this, 'proponents')">
                                            </div>
                                            <div id="importPreviewProponents" class="mt-3" style="display:none;">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong id="importFileNameProponents"></strong>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearImport('proponents')">
                                                        <i class="bi bi-x-circle"></i> Clear
                                                    </button>
                                                </div>
                                                <div class="alert alert-info py-2 mb-2">
                                                    <small><i class="bi bi-info-circle"></i> <span id="importStatsProponents"></span></small>
                                                </div>
                                                <div class="import-preview">
                                                    <table class="table table-sm table-bordered" id="importTableProponents">
                                                        <thead class="table-light"></thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <button type="button" class="btn btn-success mt-3 w-100" id="importBtnProponents" onclick="confirmImport('proponents')">
                                                    <i class="bi bi-database-add"></i> Import Records
                                                </button>
                                            </div>
                                            <div class="mt-3">
                                                <small class="text-muted">
                                                    <strong>Required columns:</strong> proponent_type, proponent_name, project_title, amount, total_beneficiaries, category
                                                </small>
                                            </div>
                                            <div id="lastImportProponents" class="text-muted small mt-2" style="font-style: italic;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== BACKUP TAB ==================== -->
                    <div class="tab-pane fade" id="backupPane" role="tabpanel" aria-labelledby="backup-tab">
                        <div class="settings-section">
                            <h5><i class="bi bi-shield-check"></i> System Backup</h5>
                            <p class="text-muted mb-4">Create a complete backup of the application database. The backup includes all tables, data, and structure.</p>

                            <div class="row g-4">
                                <!-- Local Download -->
                                <div class="col-md-6">
                                    <div class="backup-option text-center h-100">
                                        <div class="backup-icon text-primary"><i class="bi bi-hdd-fill"></i></div>
                                        <h6>Local Download</h6>
                                        <p class="text-muted small mb-3">Download a .sql backup file to your computer. Contains all <?php echo $tableCount; ?> database tables.</p>
                                        <button type="button" class="btn btn-primary" onclick="downloadBackup()">
                                            <i class="bi bi-download"></i> Download Backup
                                        </button>
                                    </div>
                                </div>

                                <!-- Google Drive -->
                                <div class="col-md-6">
                                    <div class="backup-option text-center h-100">
                                        <div class="backup-icon" style="color: #4285f4;"><i class="bi bi-google"></i></div>
                                        <h6>Google Drive</h6>
                                        <?php if (!$googleConfigured): ?>
                                            <p class="text-muted small mb-3">Google Drive integration is not configured. Add <code>GOOGLE_CLIENT_ID</code> and <code>GOOGLE_CLIENT_SECRET</code> to your <code>.env</code> file.</p>
                                            <button type="button" class="btn btn-secondary" disabled>
                                                <i class="bi bi-google"></i> Not Configured
                                            </button>
                                        <?php elseif ($gdriveConnected): ?>
                                            <div class="mb-2">
                                                <span class="gdrive-status connected">
                                                    <i class="bi bi-check-circle-fill"></i> Connected
                                                    <?php if ($gdriveEmail): ?>
                                                        (<?php echo htmlspecialchars($gdriveEmail); ?>)
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <p class="text-muted small mb-3">Save backup directly to your Google Drive account.</p>
                                            <button type="button" class="btn btn-primary" onclick="backupToGoogleDrive()" style="background: #4285f4; border-color: #4285f4;">
                                                <i class="bi bi-cloud-arrow-up"></i> Backup to Google Drive
                                            </button>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="disconnectGoogleDrive()">
                                                    <i class="bi bi-x-circle"></i> Disconnect
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted small mb-3">Connect your Google Drive account to enable cloud backups.</p>
                                            <button type="button" class="btn btn-primary" onclick="connectGoogleDrive()" style="background: #4285f4; border-color: #4285f4;">
                                                <i class="bi bi-google"></i> Connect Google Drive
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Import Confirmation Modal -->
    <div class="modal fade" id="importConfirmModal" tabindex="-1" aria-labelledby="importConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importConfirmLabel"><i class="bi bi-exclamation-triangle text-warning"></i> Confirm Import</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="importConfirmMessage"></p>
                    <div class="alert alert-warning py-2">
                        <small><i class="bi bi-info-circle"></i> New records will be <strong>added</strong> to the existing data. Existing records will not be overwritten.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="importConfirmBtn">
                        <i class="bi bi-database-add"></i> Yes, Import
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Confirmation Modal -->
    <div class="modal fade" id="backupConfirmModal" tabindex="-1" aria-labelledby="backupConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="backupConfirmLabel"><i class="bi bi-cloud-arrow-up" style="color: #4285f4;"></i> Backup to Google Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This will create a full database backup and upload it to your connected Google Drive account.</p>
                    <p class="text-muted small">The backup file will be saved in a folder named <strong>DILP_Backups</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="gdriveBackupBtn" onclick="executeGoogleDriveBackup()" style="background: #4285f4; border-color: #4285f4;">
                        <i class="bi bi-cloud-arrow-up"></i> Start Backup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ==================== EXPORT ====================
        function exportData(table) {
            var capTable = table.charAt(0).toUpperCase() + table.slice(1);
            var dateFrom = document.getElementById('exportDateFrom' + capTable).value;
            var dateTo = document.getElementById('exportDateTo' + capTable).value;

            // Validate: if only one date is set, warn the user
            if ((dateFrom && !dateTo) || (!dateFrom && dateTo)) {
                showToast('warning', 'Date Range Incomplete', 'Please select both a "From" and "To" date, or leave both empty to export all records.');
                return;
            }

            // Validate: from must not be after to
            if (dateFrom && dateTo && dateFrom > dateTo) {
                showToast('error', 'Invalid Date Range', 'The "From" date cannot be after the "To" date.');
                return;
            }

            if (typeof DILP !== 'undefined' && DILP.loading) {
                DILP.loading.show('Exporting ' + table + ' data...');
            }

            var url = 'api/settings-export.php?table=' + encodeURIComponent(table);
            if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
            if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);

            window.location.href = url;
            setTimeout(function() {
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }
                var msg = dateFrom
                    ? 'Your filtered ' + table + ' CSV file is downloading.'
                    : 'Your ' + table + ' CSV file is downloading (all records).';
                showToast('success', 'Export Started', msg);
                
                // Save last export date
                var now = new Date();
                localStorage.setItem('lastExport_' + table, now.toISOString());
                updateLastExportDisplay(table);
            }, 1500);
        }
        
        function updateLastExportDisplay(table) {
            var capTable = table.charAt(0).toUpperCase() + table.slice(1);
            var lastExport = localStorage.getItem('lastExport_' + table);
            var displayEl = document.getElementById('lastExport' + capTable);
            
            if (lastExport && displayEl) {
                var date = new Date(lastExport);
                var formattedDate = formatDateTime(date);
                displayEl.textContent = 'Last exported: ' + formattedDate;
            } else if (displayEl) {
                displayEl.textContent = '';
            }
        }

        // ==================== IMPORT ====================
        var importFiles = {};
        var importRowCounts = {};

        // Drag and drop support
        document.querySelectorAll('.import-zone').forEach(function(zone) {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                zone.classList.add('dragover');
            });
            zone.addEventListener('dragleave', function() {
                zone.classList.remove('dragover');
            });
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                zone.classList.remove('dragover');
                var table = zone.id.replace('importZone', '').toLowerCase();
                // Capitalize first letter to match ID convention
                table = zone.id === 'importZoneBeneficiaries' ? 'beneficiaries' : 'proponents';
                var input = zone.querySelector('input[type="file"]');
                input.files = e.dataTransfer.files;
                handleFileSelect(input, table);
            });
        });

        function handleFileSelect(input, table) {
            var file = input.files[0];
            if (!file) return;

            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                showToast('error', 'Invalid File', 'Please select a CSV file.');
                input.value = '';
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showToast('error', 'File Too Large', 'Maximum file size is 5MB.');
                input.value = '';
                return;
            }

            importFiles[table] = file;
            var capTable = table.charAt(0).toUpperCase() + table.slice(1);

            // Parse CSV for preview
            var reader = new FileReader();
            reader.onload = function(e) {
                var text = e.target.result;
                var rows = parseCSV(text);

                if (rows.length < 2) {
                    showToast('error', 'Empty File', 'The CSV file must contain a header row and at least one data row.');
                    clearImport(table);
                    return;
                }

                var headers = rows[0];
                var dataRows = rows.slice(1).filter(function(r) { return r.some(function(c) { return c.trim() !== ''; }); });
                importRowCounts[table] = dataRows.length;

                document.getElementById('importFileName' + capTable).textContent = file.name;
                document.getElementById('importStats' + capTable).textContent =
                    dataRows.length + ' records found, ' + headers.length + ' columns detected';

                // Build preview table (first 5 rows)
                var thead = document.querySelector('#importTable' + capTable + ' thead');
                var tbody = document.querySelector('#importTable' + capTable + ' tbody');
                thead.innerHTML = '<tr>' + headers.map(function(h) { return '<th>' + escapeHtml(h) + '</th>'; }).join('') + '</tr>';
                tbody.innerHTML = '';

                var previewRows = dataRows.slice(0, 5);
                previewRows.forEach(function(row) {
                    tbody.innerHTML += '<tr>' + row.map(function(c) { return '<td>' + escapeHtml(c) + '</td>'; }).join('') + '</tr>';
                });

                if (dataRows.length > 5) {
                    tbody.innerHTML += '<tr><td colspan="' + headers.length + '" class="text-center text-muted"><em>... and ' + (dataRows.length - 5) + ' more rows</em></td></tr>';
                }

                document.getElementById('importZone' + capTable).style.display = 'none';
                document.getElementById('importPreview' + capTable).style.display = 'block';
            };
            reader.readAsText(file);
        }

        function clearImport(table) {
            var capTable = table.charAt(0).toUpperCase() + table.slice(1);
            document.getElementById('importFile' + capTable).value = '';
            document.getElementById('importZone' + capTable).style.display = '';
            document.getElementById('importPreview' + capTable).style.display = 'none';
            delete importFiles[table];
            delete importRowCounts[table];
        }

        function confirmImport(table) {
            var count = importRowCounts[table] || 0;
            var capTable = table.charAt(0).toUpperCase() + table.slice(1);
            document.getElementById('importConfirmMessage').innerHTML =
                'Are you sure you want to import <strong>' + count + '</strong> records into the <strong>' + capTable + '</strong> table?';

            document.getElementById('importConfirmBtn').onclick = function() {
                bootstrap.Modal.getInstance(document.getElementById('importConfirmModal')).hide();
                executeImport(table);
            };

            new bootstrap.Modal(document.getElementById('importConfirmModal')).show();
        }

        function executeImport(table) {
            var file = importFiles[table];
            if (!file) return;

            if (typeof DILP !== 'undefined' && DILP.loading) {
                DILP.loading.show('Importing data...');
            }

            var formData = new FormData();
            formData.append('file', file);
            formData.append('table', table);

            fetch('api/settings-import.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }

                if (data.success) {
                    showToast('success', 'Import Successful', data.message);
                    
                    // Save last import date
                    var now = new Date();
                    localStorage.setItem('lastImport_' + table, now.toISOString());
                    
                    clearImport(table);
                    // Refresh page after short delay to update counts
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    var errorMsg = data.message || 'Import failed.';
                    if (data.errors && data.errors.length > 0) {
                        errorMsg += '\n' + data.errors.slice(0, 5).join('\n');
                    }
                    showToast('error', 'Import Failed', errorMsg);
                }
            })
            .catch(function(err) {
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }
                showToast('error', 'Import Error', 'An unexpected error occurred. Please try again.');
                console.error('Import error:', err);
            });
        }

        // ==================== BACKUP ====================
        function downloadBackup() {
            if (typeof DILP !== 'undefined' && DILP.loading) {
                DILP.loading.show('Generating database backup...');
            }
            window.location.href = 'api/settings-backup.php?action=download';
            setTimeout(function() {
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }
                showToast('success', 'Backup Started', 'Your database backup file is downloading.');
            }, 2000);
        }

        // ==================== GOOGLE DRIVE ====================
        function connectGoogleDrive() {
            window.location.href = 'api/settings-gdrive-auth.php?action=connect';
        }

        function disconnectGoogleDrive() {
            if (!confirm('Are you sure you want to disconnect Google Drive?')) return;

            fetch('api/settings-gdrive-auth.php?action=disconnect')
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast('success', 'Disconnected', 'Google Drive has been disconnected.');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to disconnect.');
                    }
                })
                .catch(function() {
                    showToast('error', 'Error', 'An unexpected error occurred.');
                });
        }

        function backupToGoogleDrive() {
            new bootstrap.Modal(document.getElementById('backupConfirmModal')).show();
        }

        function executeGoogleDriveBackup() {
            bootstrap.Modal.getInstance(document.getElementById('backupConfirmModal')).hide();

            if (typeof DILP !== 'undefined' && DILP.loading) {
                DILP.loading.show('Uploading backup to Google Drive...');
            }

            fetch('api/settings-gdrive-backup.php', { method: 'POST' })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (typeof DILP !== 'undefined' && DILP.loading) {
                        DILP.loading.hide();
                    }
                    if (data.success) {
                        showToast('success', 'Backup Complete', data.message);
                    } else {
                        showToast('error', 'Backup Failed', data.message || 'Failed to upload backup.');
                    }
                })
                .catch(function() {
                    if (typeof DILP !== 'undefined' && DILP.loading) {
                        DILP.loading.hide();
                    }
                    showToast('error', 'Error', 'An unexpected error occurred during backup.');
                });
        }

        function updateLastImportDisplay(table) {
            var capTable = table.charAt(0).toUpperCase() + table.slice(1);
            var lastImport = localStorage.getItem('lastImport_' + table);
            var displayEl = document.getElementById('lastImport' + capTable);
            
            if (lastImport && displayEl) {
                var date = new Date(lastImport);
                var formattedDate = formatDateTime(date);
                displayEl.textContent = 'Last imported: ' + formattedDate;
            } else if (displayEl) {
                displayEl.textContent = '';
            }
        }

        // ==================== UTILITIES ====================
        function formatDateTime(date) {
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var month = months[date.getMonth()];
            var day = date.getDate();
            var year = date.getFullYear();
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            
            return month + ' ' + day + ', ' + year + ' at ' + hours + ':' + minutes + ' ' + ampm;
        }
        
        function showToast(type, title, message) {
            if (typeof DILP !== 'undefined' && DILP.toast) {
                DILP.toast[type](title, message);
            } else {
                alert(title + ': ' + message);
            }
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text || ''));
            return div.innerHTML;
        }

        function parseCSV(text) {
            var rows = [];
            var row = [];
            var field = '';
            var inQuotes = false;

            for (var i = 0; i < text.length; i++) {
                var c = text[i];
                var next = text[i + 1];

                if (inQuotes) {
                    if (c === '"' && next === '"') {
                        field += '"';
                        i++;
                    } else if (c === '"') {
                        inQuotes = false;
                    } else {
                        field += c;
                    }
                } else {
                    if (c === '"') {
                        inQuotes = true;
                    } else if (c === ',') {
                        row.push(field);
                        field = '';
                    } else if (c === '\r' && next === '\n') {
                        row.push(field);
                        field = '';
                        rows.push(row);
                        row = [];
                        i++;
                    } else if (c === '\n') {
                        row.push(field);
                        field = '';
                        rows.push(row);
                        row = [];
                    } else {
                        field += c;
                    }
                }
            }

            if (field || row.length > 0) {
                row.push(field);
                rows.push(row);
            }

            return rows;
        }

        // ==================== MAINTENANCE TOGGLE ====================
        function toggleMaintenance(enabled) {
            var confirmMsg = enabled
                ? 'Enable maintenance mode? Users and encoders will be prevented from accessing the site.'
                : 'Disable maintenance mode? The site will be available to all users.';

            if (!confirm(confirmMsg)) {
                // Revert checkbox
                document.getElementById('maintenanceToggle').checked = !enabled;
                return;
            }

            var form = new URLSearchParams();
            form.append('value', enabled ? '1' : '0');

            if (typeof DILP !== 'undefined' && DILP.loading) {
                DILP.loading.show(enabled ? 'Enabling maintenance...' : 'Disabling maintenance...');
            }

            fetch('api/toggle-maintenance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: form.toString()
            })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }
                if (data.success) {
                    var statusEl = document.getElementById('maintenanceStatus');
                    if (enabled) {
                        statusEl.classList.remove('disconnected');
                        statusEl.classList.add('connected');
                        statusEl.innerHTML = '<i class="bi bi-shield-lock"></i> Maintenance ON';
                        showToast('success', 'Enabled', 'Maintenance mode enabled. Non-admin users will be blocked.');
                    } else {
                        statusEl.classList.remove('connected');
                        statusEl.classList.add('disconnected');
                        statusEl.innerHTML = '<i class="bi bi-shield-check"></i> Maintenance OFF';
                        showToast('success', 'Disabled', 'Maintenance mode disabled. Site is available to all users.');
                    }
                } else {
                    showToast('error', 'Error', data.message || 'Failed to update maintenance mode.');
                    document.getElementById('maintenanceToggle').checked = !enabled;
                }
            })
            .catch(function(err) {
                if (typeof DILP !== 'undefined' && DILP.loading) {
                    DILP.loading.hide();
                }
                console.error('Toggle maintenance error', err);
                showToast('error', 'Error', 'Unexpected error while toggling maintenance mode.');
                document.getElementById('maintenanceToggle').checked = !enabled;
            });
        }

        // Handle URL params for Google Drive callback messages
        $(document).ready(function() {
            // Initialize last export/import date displays
            updateLastExportDisplay('beneficiaries');
            updateLastExportDisplay('proponents');
            updateLastImportDisplay('beneficiaries');
            updateLastImportDisplay('proponents');
            
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('gdrive') === 'connected') {
                showToast('success', 'Google Drive Connected', 'Your Google Drive account has been linked successfully.');
                // Switch to backup tab
                var backupTab = new bootstrap.Tab(document.getElementById('backup-tab'));
                backupTab.show();
                // Clean URL
                window.history.replaceState({}, document.title, 'settings.php');
            } else if (urlParams.get('gdrive') === 'error') {
                showToast('error', 'Connection Failed', urlParams.get('message') || 'Failed to connect Google Drive.');
                var backupTab = new bootstrap.Tab(document.getElementById('backup-tab'));
                backupTab.show();
                window.history.replaceState({}, document.title, 'settings.php');
            }

            // Restore active tab from hash
            var hash = window.location.hash;
            if (hash) {
                var tabTrigger = document.querySelector('[data-bs-target="' + hash + '"]');
                if (tabTrigger) {
                    new bootstrap.Tab(tabTrigger).show();
                }
            }
        });
    </script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
</body>
</html>
