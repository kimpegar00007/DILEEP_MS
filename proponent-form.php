<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin', 'encoder']);

$sessionProvince = $auth->getProvince();
$isSuperAdmin    = $auth->isSuperAdmin();

$proponentModel = new Proponent();
$errors = [];
$success = '';
$proponent = null;
$isEdit = false;
$beneficiaryTypeOptions = [
    'Marginalized and Landless Farmers',
    'Marginalized Fisherfolk',
    'Self-employed with Insufficient Income',
    'Parents/Guardians of Child Laborers',
    'Displaced Workers',
    'Among others'
];
$workerTypeOptions = [
    'Disadvantaged Workers',
    'Indigenous People (IPs)',
    'Parents/Guardians of Child Laborers',
    'TESDA graduates',
    'Micro-establishment\'s beneficiaries of NWPC and RTWPB\'s Productive Improvement Trainings',
    'Labor Organizations and Workers\' Association under BLR\'s WODP Plus',
    'Micro-entrepreneur under the BWC\'s TAV',
    'Others'
];

function proponentNormalizeOptions($allowedOptions, $values) {
    if (!is_array($values)) {
        $values = $values === '' ? [] : explode(',', $values);
    }

    $values = array_map('trim', $values);
    return array_values(array_intersect($allowedOptions, $values));
}

function normalizeBeneficiaryNames($values) {
    if (!is_array($values)) {
        $values = $values === '' ? [] : explode(',', $values);
    }

    $values = array_map('trim', $values);
    return array_values(array_filter($values, function ($name) {
        return $name !== '';
    }));
}

if (isset($_GET['id'])) {
    $isEdit = true;
    $proponent = $proponentModel->findById($_GET['id']);
    if (!$proponent) {
        header('Location: proponents.php');
        exit;
    }
}

// Determine the province to pre-fill the form with
$formProvince = '';
if ($isEdit && $proponent) {
    $formProvince = $proponent['province'] ?? '';
} elseif (!$isSuperAdmin && $sessionProvince) {
    $formProvince = $sessionProvince;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedBeneficiaryTypes = proponentNormalizeOptions($beneficiaryTypeOptions, $_POST['type_of_beneficiaries'] ?? []);
    $selectedWorkerTypes = proponentNormalizeOptions($workerTypeOptions, $_POST['type_of_workers'] ?? []);
    $beneficiaryNames = normalizeBeneficiaryNames($_POST['beneficiary_full_names'] ?? $_POST['beneficiary_full_name'] ?? []);

    $data = [
        'proponent_type' => $_POST['proponent_type'] ?? '',
        'date_received' => $_POST['date_received'] ?: null,
        'noted_findings' => trim($_POST['noted_findings'] ?? ''),
        'control_number' => !empty(trim($_POST['control_number'] ?? '')) ? trim($_POST['control_number']) : null,
        'number_of_copies' => intval($_POST['number_of_copies'] ?? 0),
        'date_copies_received' => $_POST['date_copies_received'] ?: null,
        'district' => trim($_POST['district'] ?? ''),
        'province' => trim($_POST['province'] ?? ''),
        'proponent_name' => trim($_POST['proponent_name'] ?? ''),
        'project_title' => trim($_POST['project_title'] ?? ''),
        'amount' => floatval($_POST['amount'] ?? 0),
        'number_of_associations' => intval($_POST['number_of_associations'] ?? 0),
        'association_names' => $_POST['association_names'] ?? [],
        'association_addresses' => $_POST['association_addresses'] ?? [],
        'total_beneficiaries' => intval($_POST['total_beneficiaries'] ?? 0),
        'beneficiary_full_name' => implode(', ', $beneficiaryNames),
        'male_beneficiaries' => intval($_POST['male_beneficiaries'] ?? 0),
        'female_beneficiaries' => intval($_POST['female_beneficiaries'] ?? 0),
        'type_of_beneficiaries' => implode(', ', $selectedBeneficiaryTypes),
        'type_of_workers' => implode(', ', $selectedWorkerTypes),
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
    } elseif (!in_array($data['proponent_type'], ['LGU-associated', 'Non-LGU-associated', 'By Administration', 'Others'])) {
        $errors[] = 'Invalid proponent type';
    }
    if (empty($data['proponent_name'])) $errors[] = 'Proponent name is required';
    if (empty($data['project_title'])) $errors[] = 'Project title is required';
    if ($data['amount'] <= 0) $errors[] = 'Amount must be greater than zero';
    if ($data['total_beneficiaries'] <= 0) $errors[] = 'Total beneficiaries must be greater than zero';
    if (empty($data['category'])) $errors[] = 'Category is required';
    
    if ($data['male_beneficiaries'] + $data['female_beneficiaries'] > $data['total_beneficiaries']) {
        $errors[] = 'Male + Female beneficiaries cannot exceed total beneficiaries';
    }
    
    if ($data['total_beneficiaries'] > 0 && count($beneficiaryNames) !== $data['total_beneficiaries']) {
        $errors[] = 'Please provide a full name for each beneficiary or adjust the total beneficiaries.';
    }
    
    $provinceRanges = [
        'Negros Occidental' => ['lat' => [9.0, 12.0], 'lng' => [122.0, 124.0]],
        'Negros Oriental' => ['lat' => [9.0, 10.5], 'lng' => [122.5, 123.5]],
        'Siquijor' => ['lat' => [9.0, 9.5], 'lng' => [123.0, 123.8]]
    ];
    
    if ($data['latitude'] !== null && !empty($data['province']) && isset($provinceRanges[$data['province']])) {
        $range = $provinceRanges[$data['province']];
        if ($data['latitude'] < $range['lat'][0] || $data['latitude'] > $range['lat'][1]) {
            $errors[] = "Latitude must be between {$range['lat'][0]} and {$range['lat'][1]} for {$data['province']}";
        }
    }
    if ($data['longitude'] !== null && !empty($data['province']) && isset($provinceRanges[$data['province']])) {
        $range = $provinceRanges[$data['province']];
        if ($data['longitude'] < $range['lng'][0] || $data['longitude'] > $range['lng'][1]) {
            $errors[] = "Longitude must be between {$range['lng'][0]} and {$range['lng'][1]} for {$data['province']}";
        }
    }
    if (($data['latitude'] !== null && $data['longitude'] === null) || ($data['latitude'] === null && $data['longitude'] !== null)) {
        $errors[] = 'Both latitude and longitude must be provided together';
    }
    
    if (empty($errors)) {
        $excludeId = $isEdit ? (int) $_GET['id'] : null;
        $duplicate = $proponentModel->checkProponentDuplicate(
            $data['proponent_name'], $data['project_title'], $excludeId
        );
        if ($duplicate) {
            $errors[] = 'Duplicate detected: a proponent with the same name and project title already exists in this province (Record #' . $duplicate['id'] . ' — ' . htmlspecialchars($duplicate['proponent_name']) . ', ' . htmlspecialchars($duplicate['project_title']) . '). Please verify before saving.';
        }
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formBeneficiaryTypes = proponentNormalizeOptions($beneficiaryTypeOptions, $_POST['type_of_beneficiaries'] ?? []);
    $formWorkerTypes = proponentNormalizeOptions($workerTypeOptions, $_POST['type_of_workers'] ?? []);
    $formBeneficiaryNames = normalizeBeneficiaryNames($_POST['beneficiary_full_names'] ?? $_POST['beneficiary_full_name'] ?? []);
} else {
    $formBeneficiaryTypes = proponentNormalizeOptions($beneficiaryTypeOptions, $proponent['type_of_beneficiaries'] ?? '');
    $formWorkerTypes = proponentNormalizeOptions($workerTypeOptions, $proponent['type_of_workers'] ?? '');
    $formBeneficiaryNames = normalizeBeneficiaryNames($proponent['beneficiary_full_name'] ?? '');
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
        .province-locked-notice {
            background: linear-gradient(135deg, rgba(27,122,61,0.08), rgba(27,122,61,0.04));
            border: 1px solid rgba(27,122,61,0.20);
            border-left: 4px solid var(--dole-primary);
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-size: 0.82rem;
            color: var(--dole-secondary);
            display: flex; align-items: center; gap: 0.5rem;
            margin-top: 0.4rem;
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
                    <a href="unified-beneficiaries-proponents.php" class="btn btn-secondary">
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
                                            <option value="By Administration" <?php echo ($proponent['proponent_type'] ?? '') === 'By Administration' ? 'selected' : ''; ?>>By Administration</option>
                                            <option value="Others" <?php echo ($proponent['proponent_type'] ?? '') === 'Others' ? 'selected' : ''; ?>>Others</option>
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
                                    <div class="col-md-4 mb-3" style="display: none;">
                                        <label class="form-label">Number of Proposal Copies</label>
                                        <input type="number" name="number_of_copies" class="form-control" min="0"
                                               value="<?php echo $proponent['number_of_copies'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3" style="display: none;">
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
                                        <label class="form-label">Full Name(s) of Beneficiaries</label>
                                        <div id="beneficiary_name_fields"></div>
                                        <small class="text-muted">Fields are generated automatically based on the Total Beneficiaries value.</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Recipient Barangays/ACPs</label>
                                        <input type="text" name="recipient_barangays" class="form-control"
                                               value="<?php echo htmlspecialchars($proponent['recipient_barangays'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Type of Beneficiaries</label>
                                        <div class="row g-2">
                                            <?php foreach ($beneficiaryTypeOptions as $index => $option): ?>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="type_of_beneficiaries[]"
                                                           id="beneficiary_type_<?php echo $index; ?>"
                                                           value="<?php echo htmlspecialchars($option); ?>"
                                                           <?php echo in_array($option, $formBeneficiaryTypes, true) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="beneficiary_type_<?php echo $index; ?>">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kinds Beneficiaries</label>
                                        <div class="row g-2">
                                            <?php foreach ($workerTypeOptions as $index => $option): ?>
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="type_of_workers[]"
                                                           id="worker_type_<?php echo $index; ?>"
                                                           value="<?php echo htmlspecialchars($option); ?>"
                                                           <?php echo in_array($option, $formWorkerTypes, true) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="worker_type_<?php echo $index; ?>">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-calendar-check"></i> Process Dates</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">DILP Application - Date Received</label>
                                        <input type="date" name="letter_of_intent_date" class="form-control"
                                               value="<?php echo $proponent['letter_of_intent_date'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Forwarded to NIR for RPMT</label>
                                        <input type="date" name="date_forwarded_to_ro6" class="form-control"
                                               value="<?php echo $proponent['date_forwarded_to_ro6'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3" style="display: none;">
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
                                        <label class="form-label">Date Complied by Proponent</label>
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
                                <h5><i class="bi bi-arrow-return-left"></i> Application Return History
                                    <?php if ($isEdit): 
                                        $returnCount = $proponentModel->getReturnCount($proponent['id']);
                                        if ($returnCount > 0): ?>
                                        <span class="badge bg-warning text-dark ms-2"><?php echo $returnCount; ?> Return(s)</span>
                                    <?php endif; endif; ?>
                                </h5>
                                
                                <?php if ($isEdit): 
                                    $returns = $proponentModel->getReturns($proponent['id']);
                                    if (!empty($returns)): ?>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date Returned</th>
                                                <th>Reason</th>
                                                <th>Returned By</th>
                                                <th style="width: 80px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($returns as $return): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($return['return_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($return['reason']); ?></td>
                                                <td><?php echo htmlspecialchars($return['returned_by_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteReturn(<?php echo $return['id']; ?>)" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted mb-3"><i class="bi bi-info-circle"></i> No return history recorded.</p>
                                <?php endif; ?>
                                
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="mb-2"><i class="bi bi-plus-circle"></i> Add New Return Entry</h6>
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <label class="form-label small">Return Date</label>
                                                <input type="date" id="new_return_date" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-7">
                                                <label class="form-label small">Reason for Return</label>
                                                <input type="text" id="new_return_reason" class="form-control form-control-sm" 
                                                       placeholder="Enter reason for application return">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-warning w-100" onclick="addReturn()">
                                                    <i class="bi bi-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <p class="text-muted"><i class="bi bi-info-circle"></i> Save the proponent first to add return history entries.</p>
                                <?php endif; ?>
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
                                            <option value="GAA" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'GAA') ? 'selected' : ''; ?>>GAA (General Appropriation Act)</option>
                                            <option value="Centrally Managed Fund" <?php echo (isset($proponent['source_of_funds']) && $proponent['source_of_funds'] === 'Centrally Managed Fund') ? 'selected' : ''; ?>>Centrally Managed Fund/Central Office</option>
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
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-info-circle"></i>
                                    Coordinates are auto-filled when you select a Barangay, or use the button to re-fetch them.
                                </p>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Province</label>
                                        <?php if ($isSuperAdmin): ?>
                                            <select name="province" id="geo_province" class="form-select">
                                                <option value="">Select Province</option>
                                            </select>
                                            <input type="hidden" id="province_value" value="<?php echo htmlspecialchars($formProvince); ?>">
                                        <?php else: ?>
                                            <!-- Province is always submitted as hidden; display-only select is purely visual -->
                                            <input type="hidden" name="province" value="<?php echo htmlspecialchars($formProvince); ?>">
                                            <input type="text" class="form-control bg-light"
                                                   value="<?php echo htmlspecialchars($formProvince ?: 'Not assigned'); ?>" disabled>
                                            <div class="province-locked-notice">
                                                <i class="bi bi-lock-fill"></i>
                                                Province is locked to your assigned region.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Municipality/City</label>
                                        <select id="geo_municipality" class="form-select" disabled>
                                            <option value="">Select Province first</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Barangay</label>
                                        <select id="geo_barangay" class="form-select" disabled>
                                            <option value="">Select Municipality first</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3 d-flex align-items-end">
                                        <button type="button" id="btn-geocode" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-geo-alt-fill"></i> Auto-fill Coordinates
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Latitude</label>
                                        <input type="text" name="latitude" id="latitude" class="form-control"
                                               placeholder="e.g., 10.5"
                                               value="<?php echo $proponent['latitude'] ?? ''; ?>">
                                        <small class="text-muted">Decimal degrees format</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Longitude</label>
                                        <input type="text" name="longitude" id="longitude" class="form-control"
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
    <script src="assets/js/location-autofill.js"></script>
    <script>
        const existingBeneficiaryNames = <?php echo json_encode($formBeneficiaryNames); ?>;

        document.getElementById('male_beneficiaries').addEventListener('input', validateBeneficiaries);
        document.getElementById('female_beneficiaries').addEventListener('input', validateBeneficiaries);
        document.getElementById('total_beneficiaries').addEventListener('input', validateBeneficiaries);
        document.getElementById('total_beneficiaries').addEventListener('input', renderBeneficiaryNameFields);
        
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

        function getCurrentBeneficiaryValues() {
            const fields = document.querySelectorAll('input[name="beneficiary_full_names[]"]');
            return Array.from(fields).map(function(field) {
                return field.value.trim();
            });
        }

        function renderBeneficiaryNameFields() {
            const countValue = parseInt(document.getElementById('total_beneficiaries').value, 10);
            const currentValues = getCurrentBeneficiaryValues();
            const fieldCount = Math.max(1, Number.isInteger(countValue) && countValue > 0 ? countValue : Math.max(existingBeneficiaryNames.length, currentValues.length, 1));
            const container = document.getElementById('beneficiary_name_fields');

            container.innerHTML = '';

            for (let i = 0; i < fieldCount; i++) {
                const value = typeof currentValues[i] !== 'undefined' && currentValues[i] !== ''
                    ? currentValues[i]
                    : (existingBeneficiaryNames[i] || '');

                const fieldWrapper = document.createElement('div');
                fieldWrapper.className = 'mb-2';

                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = 'Beneficiary Full Name #' + (i + 1);

                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'beneficiary_full_names[]';
                input.className = 'form-control';
                input.value = value;
                input.placeholder = 'Enter beneficiary full name';

                fieldWrapper.appendChild(label);
                fieldWrapper.appendChild(input);
                container.appendChild(fieldWrapper);
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
        renderBeneficiaryNameFields();
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

        // ── Province / location bootstrap ──────────────────────────────────
        const IS_SUPER_ADMIN     = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;
        const SESSION_PROVINCE   = <?php echo json_encode($sessionProvince ?? ''); ?>;
        const FORM_PROVINCE      = <?php echo json_encode($formProvince); ?>;
        const SAVED_MUNICIPALITY = <?php echo json_encode($proponent['municipality'] ?? ''); ?>;
        const SAVED_BARANGAY     = <?php echo json_encode($proponent['barangay'] ?? ''); ?>;

        if (IS_SUPER_ADMIN) {
            // Super-admin: full free province selection via shared DILPLocation lib
            DILPLocation.initLocationAutoFill({
                provinceSelect:     '#geo_province',
                municipalitySelect: '#geo_municipality',
                barangaySelect:     '#geo_barangay',
                latitudeInput:      '#latitude',
                longitudeInput:     '#longitude',
                geocodeButton:      '#btn-geocode',
                provinceValueInput: '#province_value',
            });
        } else {
            // Provincial user: province is locked — load municipalities directly
            _bootstrapLockedProvince();
        }

        async function _bootstrapLockedProvince() {
            if (!FORM_PROVINCE) return;

            // 1. Resolve province code
            let provinceCode = null;
            try {
                const resp   = await fetch('api/get-locations.php?action=provinces', { headers: { 'Accept': 'application/json' } });
                const result = await resp.json();
                if (!result.success) return;
                const match = result.data.find(p => p.name === FORM_PROVINCE);
                if (match) provinceCode = match.code;
            } catch (e) {
                console.error('Province lookup failed', e);
            }
            if (!provinceCode) return;

            // 2. Load municipalities
            const muniSelect = document.getElementById('geo_municipality');
            try {
                const resp   = await fetch(`api/get-locations.php?action=cities&province_code=${encodeURIComponent(provinceCode)}`, { headers: { 'Accept': 'application/json' } });
                const result = await resp.json();
                if (!result.success) return;

                muniSelect.innerHTML = '<option value="">Select Municipality/City</option>';
                result.data.forEach(city => {
                    const opt = document.createElement('option');
                    opt.value        = city.name;
                    opt.textContent  = city.name;
                    opt.dataset.code = city.code;
                    if (city.name === SAVED_MUNICIPALITY) opt.selected = true;
                    muniSelect.appendChild(opt);
                });
                muniSelect.disabled = false;
            } catch (e) {
                console.error('Municipality fetch failed', e);
                return;
            }

            // 3. Load barangays for saved municipality (if any)
            if (SAVED_MUNICIPALITY && muniSelect.value) {
                const cityCode = muniSelect.selectedOptions[0]?.dataset.code;
                if (cityCode) await _loadBarangays(cityCode, SAVED_BARANGAY);
            }

            // 4. Wire municipality change → barangay reload
            muniSelect.addEventListener('change', async function () {
                const bgySelect = document.getElementById('geo_barangay');
                if (!this.value) {
                    bgySelect.innerHTML = '<option value="">Select Municipality first</option>';
                    bgySelect.disabled  = true;
                    return;
                }
                const cityCode = this.selectedOptions[0]?.dataset.code;
                if (cityCode) await _loadBarangays(cityCode, '');
            });

            // 5. Manual geocode button
            document.getElementById('btn-geocode')?.addEventListener('click', function () {
                _autoGeocode();
            });
        }

        async function _loadBarangays(cityCode, savedBarangay) {
            const bgySelect = document.getElementById('geo_barangay');
            bgySelect.innerHTML = '<option value="">Loading barangays...</option>';
            bgySelect.disabled  = true;
            try {
                const resp   = await fetch(`api/get-locations.php?action=barangays&city_code=${encodeURIComponent(cityCode)}`, { headers: { 'Accept': 'application/json' } });
                const result = await resp.json();
                if (!result.success) {
                    bgySelect.innerHTML = '<option value="">Error loading barangays</option>';
                    return;
                }
                bgySelect.innerHTML = '<option value="">Select Barangay</option>';
                result.data.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value       = b.name;
                    opt.textContent = b.name;
                    if (b.name === savedBarangay) opt.selected = true;
                    bgySelect.appendChild(opt);
                });
                bgySelect.disabled = false;

                // Auto-geocode when barangay restored or changed
                if (savedBarangay && bgySelect.value) _autoGeocode();

                bgySelect.addEventListener('change', function () {
                    _autoGeocode();
                });
            } catch (e) {
                console.error('Barangay fetch failed', e);
                bgySelect.innerHTML = '<option value="">Error loading barangays</option>';
            }
        }

        async function _autoGeocode() {
            const province     = IS_SUPER_ADMIN
                ? (document.getElementById('geo_province')?.value || '')
                : FORM_PROVINCE;
            const municipality = document.getElementById('geo_municipality')?.value || '';
            const barangay     = document.getElementById('geo_barangay')?.value || '';
            const latInput     = document.getElementById('latitude');
            const lngInput     = document.getElementById('longitude');

            if (!municipality) return;

            const btn = document.getElementById('btn-geocode');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Fetching…'; }

            try {
                const params = new URLSearchParams({ municipality });
                if (province)  params.append('province',  province);
                if (barangay)  params.append('barangay',  barangay);

                const resp   = await fetch(`api/geocode.php?${params}`, { headers: { 'Accept': 'application/json' } });
                const result = await resp.json();

                if (result.success) {
                    if (latInput) latInput.value = result.latitude.toFixed(8);
                    if (lngInput) lngInput.value = result.longitude.toFixed(8);
                }
            } catch (e) {
                console.warn('Auto-geocode failed:', e);
            } finally {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Auto-fill Coordinates'; }
            }
        }
        
        // Application Return History Functions
        async function addReturn() {
            const returnDate = document.getElementById('new_return_date').value;
            const reason = document.getElementById('new_return_reason').value;
            const proponentId = <?php echo $isEdit ? (int)$proponent['id'] : 0; ?>;
            
            if (!returnDate) {
                alert('Please select a return date.');
                return;
            }
            
            if (!reason.trim()) {
                alert('Please enter a reason for the return.');
                return;
            }
            
            try {
                const response = await fetch('api/proponent-returns.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        proponent_id: proponentId,
                        return_date: returnDate,
                        reason: reason
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to add return entry.');
                }
            } catch (error) {
                console.error('Error adding return:', error);
                alert('Failed to add return entry.');
            }
        }
        
        async function deleteReturn(returnId) {
            if (!confirm('Are you sure you want to delete this return entry?')) {
                return;
            }
            
            try {
                const response = await fetch(`api/proponent-returns.php?id=${returnId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to delete return entry.');
                }
            } catch (error) {
                console.error('Error deleting return:', error);
                alert('Failed to delete return entry.');
            }
        }
    </script>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>DILP.validation.init('form[method="POST"]');</script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
