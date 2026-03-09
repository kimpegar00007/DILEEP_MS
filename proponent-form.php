<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin', 'encoder']);

$proponentModel = new Proponent();
$errors = [];
$success = '';
$proponent = null;
$isEdit = false;

if (isset($_GET['id'])) {
    $isEdit = true;
    $proponent = $proponentModel->findById($_GET['id']);
    if (!$proponent) {
        header('Location: proponents.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'proponent_type' => $_POST['proponent_type'] ?? '',
        'date_received' => $_POST['date_received'] ?: null,
        'noted_findings' => trim($_POST['noted_findings'] ?? ''),
        'control_number' => !empty(trim($_POST['control_number'] ?? '')) ? trim($_POST['control_number']) : null,
        'number_of_copies' => intval($_POST['number_of_copies'] ?? 0),
        'date_copies_received' => $_POST['date_copies_received'] ?: null,
        'district' => trim($_POST['district'] ?? ''),
        'proponent_name' => trim($_POST['proponent_name'] ?? ''),
        'project_title' => trim($_POST['project_title'] ?? ''),
        'amount' => floatval($_POST['amount'] ?? 0),
        'number_of_associations' => intval($_POST['number_of_associations'] ?? 0),
        'association_names' => $_POST['association_names'] ?? [],
        'association_addresses' => $_POST['association_addresses'] ?? [],
        'total_beneficiaries' => intval($_POST['total_beneficiaries'] ?? 0),
        'male_beneficiaries' => intval($_POST['male_beneficiaries'] ?? 0),
        'female_beneficiaries' => intval($_POST['female_beneficiaries'] ?? 0),
        'type_of_beneficiaries' => trim($_POST['type_of_beneficiaries'] ?? ''),
        'category' => $_POST['category'] ?? '',
        'recipient_barangays' => trim($_POST['recipient_barangays'] ?? ''),
        'letter_of_intent_date' => $_POST['letter_of_intent_date'] ?: null,
        'date_forwarded_to_ro6' => $_POST['date_forwarded_to_ro6'] ?: null,
        'rpmt_findings' => trim($_POST['rpmt_findings'] ?? ''),
        'date_complied_by_proponent' => $_POST['date_complied_by_proponent'] ?: null,
        'date_complied_by_proponent_nofo' => $_POST['date_complied_by_proponent_nofo'] ?: null,
        'date_forwarded_to_nofo' => $_POST['date_forwarded_to_nofo'] ?: null,
        'date_approved' => $_POST['date_approved'] ?: null,
        'date_check_release' => $_POST['date_check_release'] ?: null,
        'check_number' => !empty(trim($_POST['check_number'] ?? '')) ? trim($_POST['check_number']) : null,
        'check_date_issued' => $_POST['check_date_issued'] ?: null,
        'or_number' => !empty(trim($_POST['or_number'] ?? '')) ? trim($_POST['or_number']) : null,
        'or_date_issued' => $_POST['or_date_issued'] ?: null,
        'date_turnover' => $_POST['date_turnover'] ?: null,
        'date_implemented' => $_POST['date_implemented'] ?: null,
        'date_liquidated' => $_POST['date_liquidated'] ?: null,
        'date_monitoring' => $_POST['date_monitoring'] ?: null,
        'source_of_funds' => trim($_POST['source_of_funds'] ?? ''),
        'latitude' => !empty(trim($_POST['latitude'] ?? '')) ? floatval(trim($_POST['latitude'])) : null,
        'longitude' => !empty(trim($_POST['longitude'] ?? '')) ? floatval(trim($_POST['longitude'])) : null,
        'status' => $_POST['status'] ?? 'pending'
    ];
    
    if (empty($data['proponent_type'])) {
        $errors[] = 'Proponent type is required';
    } elseif (!in_array($data['proponent_type'], ['LGU-associated', 'Non-LGU-associated'])) {
        $errors[] = 'Invalid proponent type. Must be either LGU-associated or Non-LGU-associated';
    }
    if (empty($data['proponent_name'])) $errors[] = 'Proponent name is required';
    if (empty($data['project_title'])) $errors[] = 'Project title is required';
    if ($data['amount'] <= 0) $errors[] = 'Amount must be greater than zero';
    if ($data['total_beneficiaries'] <= 0) $errors[] = 'Total beneficiaries must be greater than zero';
    if (empty($data['category'])) $errors[] = 'Category is required';
    
    if ($data['male_beneficiaries'] + $data['female_beneficiaries'] > $data['total_beneficiaries']) {
        $errors[] = 'Male + Female beneficiaries cannot exceed total beneficiaries';
    }
    
    if ($data['latitude'] !== null && ($data['latitude'] < 9.0 || $data['latitude'] > 12.0)) {
        $errors[] = 'Latitude must be between 9.0 and 12.0 for Negros Occidental';
    }
    if ($data['longitude'] !== null && ($data['longitude'] < 122.0 || $data['longitude'] > 124.0)) {
        $errors[] = 'Longitude must be between 122.0 and 124.0 for Negros Occidental';
    }
    if (($data['latitude'] !== null && $data['longitude'] === null) || ($data['latitude'] === null && $data['longitude'] !== null)) {
        $errors[] = 'Both latitude and longitude must be provided together';
    }
    
    if (empty($errors)) {
        try {
            if ($isEdit) {
                if ($proponentModel->update($_GET['id'], $data)) {
                    $proponentModel->saveAssociations($_GET['id'], $data['association_names'], $data['association_addresses']);
                    $success = 'Proponent updated successfully!';
                    $proponent = $proponentModel->findById($_GET['id']);
                } else {
                    $dbError = $proponentModel->getLastError();
                    $errors[] = 'Failed to update proponent' . ($dbError ? ': ' . $dbError : '');
                }
            } else {
                $id = $proponentModel->create($data);
                if ($id) {
                    $proponentModel->saveAssociations($id, $data['association_names'], $data['association_addresses']);
                    header('Location: proponents.php?success=created');
                    exit;
                } else {
                    $dbError = $proponentModel->getLastError();
                    $errors[] = 'Failed to create proponent' . ($dbError ? ': ' . $dbError : '');
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to save proponent: ' . $e->getMessage();
            error_log("Proponent save exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Proponent - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .liquidation-info {
            background-color: #fff3cd;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid var(--dole-warning);
        }
    </style>
</head>
<body>
    <?php $currentPage = 'proponents'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-people"></i> <?php echo $isEdit ? 'Edit' : 'Add New'; ?> Proponent</h2>
                    <a href="proponents.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
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

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-section">
                                <h5><i class="bi bi-info-circle"></i> Basic Information</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Proponent Type <span class="text-danger">*</span></label>
                                        <select name="proponent_type" id="proponent_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="LGU-associated" <?php echo ($proponent['proponent_type'] ?? '') === 'LGU-associated' ? 'selected' : ''; ?>>LGU-associated</option>
                                            <option value="Non-LGU-associated" <?php echo ($proponent['proponent_type'] ?? '') === 'Non-LGU-associated' ? 'selected' : ''; ?>>Non-LGU-associated</option>
                                        </select>
                                        <small class="text-muted">LGU: 10 days liquidation | Non-LGU: 60 days</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Received (DILP)</label>
                                        <input type="date" name="date_received" class="form-control"
                                               value="<?php echo $proponent['date_received'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Control Number</label>
                                        <input type="text" name="control_number" class="form-control"
                                               value="<?php echo htmlspecialchars($proponent['control_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Number of Proposal Copies</label>
                                        <input type="number" name="number_of_copies" class="form-control" min="0"
                                               value="<?php echo $proponent['number_of_copies'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Copies Received</label>
                                        <input type="date" name="date_copies_received" class="form-control"
                                               value="<?php echo $proponent['date_copies_received'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">District</label>
                                        <input type="text" name="district" class="form-control"
                                               value="<?php echo htmlspecialchars($proponent['district'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-building"></i> Proponent Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Name of Proponent/ACP <span class="text-danger">*</span></label>
                                        <input type="text" name="proponent_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($proponent['proponent_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Project Title <span class="text-danger">*</span></label>
                                        <input type="text" name="project_title" class="form-control" required
                                               value="<?php echo htmlspecialchars($proponent['project_title'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                                        <input type="number" name="amount" class="form-control" step="0.01" required
                                               value="<?php echo $proponent['amount'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <select name="category" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <option value="Formation" <?php echo ($proponent['category'] ?? '') === 'Formation' ? 'selected' : ''; ?>>Formation</option>
                                            <option value="Enhancement" <?php echo ($proponent['category'] ?? '') === 'Enhancement' ? 'selected' : ''; ?>>Enhancement</option>
                                            <option value="Restoration" <?php echo ($proponent['category'] ?? '') === 'Restoration' ? 'selected' : ''; ?>>Restoration</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Number of Associations</label>
                                        <input type="number" name="number_of_associations" id="number_of_associations" class="form-control" min="0"
                                               value="<?php echo $proponent['number_of_associations'] ?? ''; ?>">
                                    </div>                                  
                                </div>
                                <div id="associations-container">
                                    <?php
                                    $existingAssociations = [];
                                    if ($isEdit && $proponent) {
                                        $existingAssociations = $proponentModel->getAssociations($proponent['id']);
                                    }
                                    if (!empty($existingAssociations)):
                                        foreach ($existingAssociations as $index => $assoc):
                                    ?>
                                    <div class="row association-row mb-2" data-index="<?php echo $index; ?>">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Name of Association #<?php echo $index + 1; ?></label>
                                            <input type="text" name="association_names[]" class="form-control" 
                                                   placeholder="Enter association name"
                                                   value="<?php echo htmlspecialchars($assoc['association_name']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Association Address #<?php echo $index + 1; ?></label>
                                            <input type="text" name="association_addresses[]" class="form-control" 
                                                   placeholder="Enter association address"
                                                   value="<?php echo htmlspecialchars($assoc['association_address']); ?>">
                                        </div>
                                    </div>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-people"></i> Beneficiary Information</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Total Beneficiaries <span class="text-danger">*</span></label>
                                        <input type="number" name="total_beneficiaries" id="total_beneficiaries" class="form-control" min="1" required
                                               value="<?php echo $proponent['total_beneficiaries'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Male Beneficiaries</label>
                                        <input type="number" name="male_beneficiaries" id="male_beneficiaries" class="form-control" min="0"
                                               value="<?php echo $proponent['male_beneficiaries'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Female Beneficiaries</label>
                                        <input type="number" name="female_beneficiaries" id="female_beneficiaries" class="form-control" min="0"
                                               value="<?php echo $proponent['female_beneficiaries'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Type of Beneficiaries</label>
                                        <input type="text" name="type_of_beneficiaries" class="form-control"
                                               placeholder="e.g., Farmers, Fisherfolk, Women"
                                               value="<?php echo htmlspecialchars($proponent['type_of_beneficiaries'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Recipient Barangays/ACPs</label>
                                        <input type="text" name="recipient_barangays" class="form-control"
                                               value="<?php echo htmlspecialchars($proponent['recipient_barangays'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-calendar-check"></i> Process Dates</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Letter of Intent - Date Received</label>
                                        <input type="date" name="letter_of_intent_date" class="form-control"
                                               value="<?php echo $proponent['letter_of_intent_date'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Forwarded to RO6 for RPMT</label>
                                        <input type="date" name="date_forwarded_to_ro6" class="form-control"
                                               value="<?php echo $proponent['date_forwarded_to_ro6'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Complied by Proponent/ACP</label>
                                        <input type="date" name="date_complied_by_proponent" class="form-control"
                                               value="<?php echo $proponent['date_complied_by_proponent'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">RPMT Findings</label>
                                        <textarea name="rpmt_findings" class="form-control" rows="2"><?php echo htmlspecialchars($proponent['rpmt_findings'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Complied by Proponent/ACP/NOFO</label>
                                        <input type="date" name="date_complied_by_proponent_nofo" class="form-control"
                                               value="<?php echo $proponent['date_complied_by_proponent_nofo'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Forwarded to NOFO</label>
                                        <input type="date" name="date_forwarded_to_nofo" class="form-control"
                                               value="<?php echo $proponent['date_forwarded_to_nofo'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Approved</label>
                                        <input type="date" name="date_approved" class="form-control"
                                               value="<?php echo $proponent['date_approved'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-cash-stack"></i> Financial Information</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date of Check Release</label>
                                        <input type="date" name="date_check_release" class="form-control"
                                               value="<?php echo $proponent['date_check_release'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Check Number</label>
                                        <input type="text" name="check_number" class="form-control"
                                               value="<?php echo htmlspecialchars($proponent['check_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Check Date Issued</label>
                                        <input type="date" name="check_date_issued" class="form-control"
                                               value="<?php echo $proponent['check_date_issued'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Official Receipt (OR) Number</label>
                                        <input type="text" name="or_number" class="form-control"
                                               value="<?php echo htmlspecialchars($proponent['or_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">OR Date Issued</label>
                                        <input type="date" name="or_date_issued" class="form-control"
                                               value="<?php echo $proponent['or_date_issued'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Source of Funds</label>
                                        <select name="source_of_funds" class="form-select">
                                            <option value="">Select Source</option>
                                            <option value="DOLE" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'DOLE') ? 'selected' : ''; ?>>DOLE</option>
                                            <option value="GAA" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'GAA') ? 'selected' : ''; ?>>GAA (General Appropriations Act)</option>
                                            <option value="LGU" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'LGU') ? 'selected' : ''; ?>>LGU Counterpart</option>
                                            <option value="NGO" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'NGO') ? 'selected' : ''; ?>>NGO/Private Sector</option>
                                            <option value="TUPAD" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'TUPAD') ? 'selected' : ''; ?>>TUPAD</option>
                                            <option value="SPES" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'SPES') ? 'selected' : ''; ?>>SPES</option>
                                            <option value="Other" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-clipboard-check"></i> Implementation & Monitoring</h5>
                                <?php if ($isEdit && $proponent['liquidation_deadline']): ?>
                                <div class="liquidation-info mb-3">
                                    <strong><i class="bi bi-exclamation-triangle"></i> Liquidation Deadline:</strong>
                                    <?php echo date('F d, Y', strtotime($proponent['liquidation_deadline'])); ?>
                                    (<?php echo $proponent['proponent_type'] === 'LGU-associated' ? '10' : '60'; ?> days from turnover)
                                </div>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Date of Turn-over</label>
                                        <input type="date" name="date_turnover" id="date_turnover" class="form-control"
                                               value="<?php echo $proponent['date_turnover'] ?? ''; ?>">
                                        <small class="text-muted">Triggers liquidation deadline and monitoring date</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Date Implemented</label>
                                        <input type="date" name="date_implemented" class="form-control"
                                               value="<?php echo $proponent['date_implemented'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Date Liquidated</label>
                                        <input type="date" name="date_liquidated" class="form-control"
                                               value="<?php echo $proponent['date_liquidated'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Date of Monitoring</label>
                                        <input type="date" name="date_monitoring" id="date_monitoring" class="form-control"
                                               value="<?php echo $proponent['date_monitoring'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-geo-alt"></i> Location (For Map Visualization)</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Latitude</label>
                                        <input type="text" name="latitude" class="form-control" 
                                               placeholder="e.g., 10.5"
                                               value="<?php echo $proponent['latitude'] ?? ''; ?>">
                                        <small class="text-muted">Decimal degrees format</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Longitude</label>
                                        <input type="text" name="longitude" class="form-control" 
                                               placeholder="e.g., 123.0"
                                               value="<?php echo $proponent['longitude'] ?? ''; ?>">
                                        <small class="text-muted">Decimal degrees format</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-flag"></i> Status & Comments</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Current Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select" required>
                                            <option value="pending" <?php echo ($proponent['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo ($proponent['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="implemented" <?php echo ($proponent['status'] ?? '') === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                            <option value="liquidated" <?php echo ($proponent['status'] ?? '') === 'liquidated' ? 'selected' : ''; ?>>Liquidated</option>
                                            <option value="monitored" <?php echo ($proponent['status'] ?? '') === 'monitored' ? 'selected' : ''; ?>>Monitored</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Noted Findings/Comments</label>
                                        <textarea name="noted_findings" class="form-control" rows="3"><?php echo htmlspecialchars($proponent['noted_findings'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?> Proponent
                                </button>
                                <a href="proponents.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <script>
        document.getElementById('male_beneficiaries').addEventListener('input', validateBeneficiaries);
        document.getElementById('female_beneficiaries').addEventListener('input', validateBeneficiaries);
        document.getElementById('total_beneficiaries').addEventListener('input', validateBeneficiaries);
        
        function validateBeneficiaries() {
            const total = parseInt(document.getElementById('total_beneficiaries').value) || 0;
            const male = parseInt(document.getElementById('male_beneficiaries').value) || 0;
            const female = parseInt(document.getElementById('female_beneficiaries').value) || 0;
            
            if (male + female > total) {
                document.getElementById('male_beneficiaries').setCustomValidity('Male + Female cannot exceed Total');
                document.getElementById('female_beneficiaries').setCustomValidity('Male + Female cannot exceed Total');
            } else {
                document.getElementById('male_beneficiaries').setCustomValidity('');
                document.getElementById('female_beneficiaries').setCustomValidity('');
            }
        }
        
        const proponentTypeSelect = document.getElementById('proponent_type');
        const dateTurnoverInput = document.getElementById('date_turnover');
        const dateMonitoringInput = document.getElementById('date_monitoring');
        
        function updateLiquidationAndMonitoringPreview() {
            // Clear existing previews
            var lp = document.getElementById('liquidation-preview');
            if (lp) lp.remove();
            var mp = document.getElementById('monitoring-preview');
            if (mp) mp.remove();

            if (!proponentTypeSelect || !dateTurnoverInput || !dateMonitoringInput) return;

            var proponentType = proponentTypeSelect.value;
            var dateTurnover = dateTurnoverInput.value;

            // Show helper when date is selected but type is missing
            if (dateTurnover && !proponentType) {
                var hint = document.createElement('small');
                hint.id = 'liquidation-preview';
                hint.className = 'text-warning d-block mt-1';
                hint.style.cssText = 'font-weight:500;';
                hint.innerHTML = '<i class="bi bi-exclamation-circle"></i> Select a proponent type to calculate liquidation deadline';
                dateTurnoverInput.parentElement.appendChild(hint);
                return;
            }

            if (!dateTurnover || !proponentType) return;

            var turnoverDate = new Date(dateTurnover + 'T00:00:00');
            var days = proponentType === 'LGU-associated' ? 10 : 60;
            var deadlineDate = new Date(turnoverDate);
            deadlineDate.setDate(deadlineDate.getDate() + days);

            var monitoringDate = new Date(turnoverDate);
            monitoringDate.setMonth(monitoringDate.getMonth() + 6);

            // Auto-fill monitoring date
            dateMonitoringInput.value = monitoringDate.toISOString().split('T')[0];

            // Liquidation deadline helper
            var liquidationEl = document.createElement('small');
            liquidationEl.id = 'liquidation-preview';
            liquidationEl.className = 'text-info d-block mt-1';
            liquidationEl.style.cssText = 'font-weight:600; font-size:0.85rem;';
            liquidationEl.innerHTML = '<i class="bi bi-calendar-check"></i> Liquidation deadline: <strong>' + 
                deadlineDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) +
                '</strong> (' + days + ' days from turnover)';
            dateTurnoverInput.parentElement.appendChild(liquidationEl);

            // Monitoring date helper
            var monitoringEl = document.createElement('small');
            monitoringEl.id = 'monitoring-preview';
            monitoringEl.className = 'text-info d-block mt-1';
            monitoringEl.style.cssText = 'font-weight:600; font-size:0.85rem;';
            monitoringEl.innerHTML = '<i class="bi bi-calendar-check"></i> Monitoring date: <strong>' + 
                monitoringDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) +
                '</strong> (6 months from turnover)';
            dateMonitoringInput.parentElement.appendChild(monitoringEl);
        }

        // Attach event listeners
        if (proponentTypeSelect) {
            proponentTypeSelect.addEventListener('change', updateLiquidationAndMonitoringPreview);
            proponentTypeSelect.addEventListener('input', updateLiquidationAndMonitoringPreview);
        }
        if (dateTurnoverInput) {
            dateTurnoverInput.addEventListener('change', updateLiquidationAndMonitoringPreview);
            dateTurnoverInput.addEventListener('input', updateLiquidationAndMonitoringPreview);
        }

        // Run on page load for edit mode
        updateLiquidationAndMonitoringPreview();
        
        const numAssociationsInput = document.getElementById('number_of_associations');
        const associationsContainer = document.getElementById('associations-container');
        
        if (numAssociationsInput) {
            numAssociationsInput.addEventListener('input', renderAssociationFields);
        }
        
        function renderAssociationFields() {
            const count = parseInt(numAssociationsInput.value) || 0;
            const existingRows = associationsContainer.querySelectorAll('.association-row');
            const existingData = [];
            
            existingRows.forEach(function(row) {
                const nameInput = row.querySelector('input[name="association_names[]"]');
                const addressInput = row.querySelector('input[name="association_addresses[]"]');
                existingData.push({
                    name: nameInput ? nameInput.value : '',
                    address: addressInput ? addressInput.value : ''
                });
            });
            
            associationsContainer.innerHTML = '';
            
            for (let i = 0; i < count; i++) {
                const row = document.createElement('div');
                row.className = 'row association-row mb-2';
                row.setAttribute('data-index', i);
                
                const nameValue = (existingData[i] && existingData[i].name) ? existingData[i].name : '';
                const addressValue = (existingData[i] && existingData[i].address) ? existingData[i].address : '';
                
                row.innerHTML = 
                    '<div class="col-md-6 mb-2">' +
                        '<label class="form-label">Name of Association #' + (i + 1) + '</label>' +
                        '<input type="text" name="association_names[]" class="form-control" ' +
                               'placeholder="Enter association name" ' +
                               'value="' + nameValue.replace(/"/g, '&quot;') + '">' +
                    '</div>' +
                    '<div class="col-md-6 mb-2">' +
                        '<label class="form-label">Association Address #' + (i + 1) + '</label>' +
                        '<input type="text" name="association_addresses[]" class="form-control" ' +
                               'placeholder="Enter association address" ' +
                               'value="' + addressValue.replace(/"/g, '&quot;') + '">' +
                    '</div>';
                
                associationsContainer.appendChild(row);
            }
        }
    </script>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>DILP.validation.init('form[method="POST"]');</script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
