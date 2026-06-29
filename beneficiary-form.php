<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin', 'encoder']);

$beneficiaryModel = new Beneficiary();
$errors = [];
$success = '';
$beneficiary = null;
$isEdit = false;

// Province logic: provincial users have province locked; cross-province roles (super_admin, regional_director) pick freely
$sessionProvince = $auth->getProvince();
$isSuperAdmin    = $auth->isSuperAdmin();
$isCrossProvince = $auth->isCrossProvince();

// Beneficiary and worker type options (updated May 2026)
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

function normalizeCheckboxTypes($allowedOptions, $values) {
    if (!is_array($values)) {
        $values = $values === '' ? [] : explode(',', $values);
    }
    $values = array_map('trim', $values);
    return array_values(array_intersect($allowedOptions, $values));
}

if (isset($_GET['id'])) {
    $isEdit = true;
    $beneficiary = $beneficiaryModel->findById($_GET['id']);
    if (!$beneficiary) {
        header('Location: beneficiaries.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedBeneficiaryTypes = normalizeCheckboxTypes($beneficiaryTypeOptions, $_POST['type_of_beneficiaries'] ?? []);
    $selectedWorkerTypes = normalizeCheckboxTypes($workerTypeOptions, $_POST['type_of_worker'] ?? []);

    $data = [
        'last_name'                  => trim($_POST['last_name'] ?? ''),
        'first_name'                 => trim($_POST['first_name'] ?? ''),
        'middle_name'                => trim($_POST['middle_name'] ?? ''),
        'suffix'                     => trim($_POST['suffix'] ?? ''),
        'gender'                     => $_POST['gender'] ?? '',
        'barangay'                   => trim($_POST['barangay'] ?? ''),
        'municipality'               => trim($_POST['municipality'] ?? ''),
        'province'                   => trim($_POST['province'] ?? ''),
        'contact_number'             => trim($_POST['contact_number'] ?? ''),
        'project_name'               => trim($_POST['project_name'] ?? ''),
        'type_of_worker'             => implode(', ', $selectedWorkerTypes) ?: null,
        'type_of_beneficiaries'      => implode(', ', $selectedBeneficiaryTypes) ?: null,
        'amount_worth'               => floatval($_POST['amount_worth'] ?? 0),
        'noted_findings'             => trim($_POST['noted_findings'] ?? ''),
        'date_complied_by_proponent' => $_POST['date_complied_by_proponent'] ?: null,
        'date_forwarded_to_ro6'      => $_POST['date_forwarded_to_ro6'] ?: null,
        'rpmt_findings'              => trim($_POST['rpmt_findings'] ?? ''),
        'date_approved'              => $_POST['date_approved'] ?: null,
        'date_forwarded_to_nofo'     => $_POST['date_forwarded_to_nofo'] ?: null,
        'date_turnover'              => $_POST['date_turnover'] ?: null,
        'date_monitoring'            => $_POST['date_monitoring'] ?: null,
        'latitude'                   => !empty(trim($_POST['latitude'] ?? '')) ? floatval(trim($_POST['latitude'])) : null,
        'longitude'                  => !empty(trim($_POST['longitude'] ?? '')) ? floatval(trim($_POST['longitude'])) : null,
        'status'                     => $_POST['status'] ?? 'pending',
        'source_of_funds'            => trim($_POST['source_of_funds'] ?? '') ?: null,
        'proponent_id'               => !empty($_POST['proponent_id']) ? (int) $_POST['proponent_id'] : null,
    ];

    if (empty($data['last_name']))    $errors[] = 'Last name is required';
    if (empty($data['first_name']))   $errors[] = 'First name is required';
    if (empty($data['gender']))       $errors[] = 'Gender is required';
    if (empty($data['barangay']))     $errors[] = 'Barangay is required';
    if (empty($data['municipality'])) $errors[] = 'Municipality is required';
    if (empty($data['province'])) {
        $errors[] = 'Province is required';
    } elseif (!in_array($data['province'], Auth::PROVINCES, true)) {
        // Hard guard: reject any province value not in the canonical enum.
        // Applies uniformly to provincial users and cross-province roles.
        $errors[] = 'Invalid province selected';
    }
    if (empty($data['project_name'])) $errors[] = 'Project name is required';
    if ($data['amount_worth'] <= 0)   $errors[] = 'Amount must be greater than zero';
    if (empty($data['proponent_id'])) $errors[] = 'Linked proponent is required. Search and select an existing proponent, or use "Create new proponent" if none exists yet.';

    $provinceRanges = [
        'Negros Occidental' => ['lat' => [9.0, 12.0], 'lng' => [122.0, 124.0]],
        'Negros Oriental'   => ['lat' => [9.0, 10.5], 'lng' => [122.5, 123.5]],
        'Siquijor'          => ['lat' => [9.0,  9.5], 'lng' => [123.0, 123.8]],
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
        $duplicate = $beneficiaryModel->checkBeneficiaryDuplicate(
            $data['last_name'], $data['first_name'], $data['project_name'], $excludeId
        );
        if ($duplicate) {
            $errors[] = 'Duplicate detected: a beneficiary with the same full name and project name already exists in this province (Record #' . $duplicate['id'] . ' — ' . htmlspecialchars($duplicate['first_name'] . ' ' . $duplicate['last_name']) . ', ' . htmlspecialchars($duplicate['project_name']) . '). Please verify before saving.';
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            if ($beneficiaryModel->update($_GET['id'], $data)) {
                $success = 'Beneficiary updated successfully!';
                $beneficiary = $beneficiaryModel->findById($_GET['id']);
            } else {
                $errors[] = 'Failed to update beneficiary';
            }
        } else {
            $id = $beneficiaryModel->create($data);
            if ($id) {
                header('Location: beneficiaries.php?success=created');
                exit;
            } else {
                $errors[] = 'Failed to create beneficiary. Please check the error logs or contact the administrator.';
                error_log("Beneficiary creation failed for user: " . ($_SESSION['user_id'] ?? 'unknown') . " with data: " . json_encode($data));
            }
        }
    }
}

// Determine initial province value for the form
$formProvince = '';
if ($isEdit && $beneficiary) {
    $formProvince = $beneficiary['province'] ?? '';
} elseif (!$isCrossProvince && $sessionProvince) {
    $formProvince = $sessionProvince;
}

// Normalise checked types for form display
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formBeneficiaryTypes = normalizeCheckboxTypes($beneficiaryTypeOptions, $_POST['type_of_beneficiaries'] ?? []);
    $formWorkerTypes = normalizeCheckboxTypes($workerTypeOptions, $_POST['type_of_worker'] ?? []);
} else {
    $formBeneficiaryTypes = normalizeCheckboxTypes($beneficiaryTypeOptions, $beneficiary['type_of_beneficiaries'] ?? '');
    $formWorkerTypes = normalizeCheckboxTypes($workerTypeOptions, $beneficiary['type_of_worker'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Beneficiary - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        .province-locked-notice {
            background: linear-gradient(135deg, rgba(27,122,61,0.08), rgba(27,122,61,0.04));
            border: 1px solid rgba(27,122,61,0.20);
            border-left: 4px solid var(--dole-primary);
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-size: 0.82rem;
            color: var(--dole-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.4rem;
        }
    </style>
</head>
<body>
    <?php $currentPage = 'beneficiaries'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-person"></i> <?php echo $isEdit ? 'Edit' : 'Add New'; ?> Beneficiary</h2>
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
                            <!-- ── Personal Information ───────────────────── -->
                            <div class="form-section">
                                <h5><i class="bi bi-person-badge"></i> Personal Information</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($beneficiary['last_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($beneficiary['first_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="middle_name" class="form-control"
                                               value="<?php echo htmlspecialchars($beneficiary['middle_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Suffix</label>
                                        <input type="text" name="suffix" class="form-control" placeholder="Jr., Sr., III"
                                               value="<?php echo htmlspecialchars($beneficiary['suffix'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-select" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male"   <?php echo ($beneficiary['gender'] ?? '') === 'Male'   ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($beneficiary['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>

                                    <!-- Province -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Province <span class="text-danger">*</span></label>
                                        <?php if (!$isCrossProvince && $sessionProvince): ?>
                                            <!-- Locked: provincial user -->
                                            <input type="hidden" name="province" value="<?php echo htmlspecialchars($sessionProvince); ?>">
                                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($sessionProvince); ?>" disabled>
                                            <div class="province-locked-notice">
                                                <i class="bi bi-lock-fill"></i>
                                                Province is set to your assigned area and cannot be changed.
                                            </div>
                                        <?php else: ?>
                                            <!-- Cross-province role (super_admin, regional_director): free pick -->
                                            <select name="province" id="province" class="form-select" required>
                                                <option value="">Select Province</option>
                                            </select>
                                            <input type="hidden" id="province_value" value="<?php echo htmlspecialchars($formProvince); ?>">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Municipality -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">City/Municipality <span class="text-danger">*</span></label>
                                        <select name="municipality" id="municipality" class="form-select" required>
                                            <option value="">Select City/Municipality</option>
                                        </select>
                                        <input type="hidden" id="municipality_value" value="<?php echo htmlspecialchars($beneficiary['municipality'] ?? ''); ?>">
                                    </div>

                                    <!-- Barangay -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Barangay <span class="text-danger">*</span></label>
                                        <select name="barangay" id="barangay" class="form-select" required disabled>
                                            <option value="">Select City/Municipality first</option>
                                        </select>
                                        <input type="hidden" id="barangay_value" value="<?php echo htmlspecialchars($beneficiary['barangay'] ?? ''); ?>">
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" name="contact_number" class="form-control"
                                               value="<?php echo htmlspecialchars($beneficiary['contact_number'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- ── Project Information ────────────────────── -->
                            <div class="form-section">
                                <h5><i class="bi bi-briefcase"></i> Project Information</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Project Name/Title <span class="text-danger">*</span></label>
                                        <input type="text" name="project_name" class="form-control" required
                                               placeholder="e.g., Banana Cue Vending, Cooked Viand Vending"
                                               value="<?php echo htmlspecialchars($beneficiary['project_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Amount Worth (₱) <span class="text-danger">*</span></label>
                                        <input type="number" name="amount_worth" class="form-control" step="0.01" required
                                               value="<?php echo $beneficiary['amount_worth'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Source of Funds</label>
                                        <select name="source_of_funds" class="form-select">
                                            <option value="">Select Source</option>
                                            <option value="GAA" <?php echo (($beneficiary['source_of_funds'] ?? '') === 'GAA') ? 'selected' : ''; ?>>GAA (General Appropriation Act)</option>
                                            <option value="Centrally Managed Fund" <?php echo (($beneficiary['source_of_funds'] ?? '') === 'Centrally Managed Fund') ? 'selected' : ''; ?>>Centrally Managed Fund/Central Office</option>
                                            <option value="Other" <?php echo (($beneficiary['source_of_funds'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label" for="proponent_search">
                                            <i class="bi bi-link-45deg"></i> Linked Proponent <span class="text-danger">*</span>
                                        </label>
                                        <?php
                                        // Resolve previously selected proponent (edit, POST-failure, or restore flow).
                                        $linkedProponentId    = '';
                                        $linkedProponentLabel = '';

                                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['proponent_id'])) {
                                            $linkedProponentId = (int) $_POST['proponent_id'];
                                        } elseif (!empty($beneficiary['proponent_id'])) {
                                            $linkedProponentId = (int) $beneficiary['proponent_id'];
                                        }

                                        if ($linkedProponentId) {
                                            $linkedProponent = (new Proponent())->findById($linkedProponentId);
                                            if ($linkedProponent) {
                                                $linkedProponentLabel = $linkedProponent['proponent_name'] . ' — ' . $linkedProponent['project_title'];
                                            } else {
                                                // Linked proponent no longer accessible, clear stale link
                                                $linkedProponentId = '';
                                            }
                                        }
                                        ?>
                                        <div class="position-relative" id="proponent_combobox">
                                            <input type="hidden" name="proponent_id" id="proponent_id" value="<?php echo htmlspecialchars((string) $linkedProponentId); ?>">
                                            <div class="input-group">
                                                <input type="text"
                                                       id="proponent_search"
                                                       class="form-control"
                                                       autocomplete="off"
                                                       placeholder="Type to search proponents (min 2 characters)..."
                                                       value="<?php echo htmlspecialchars($linkedProponentLabel); ?>"
                                                       aria-required="true">
                                                <button type="button" id="proponent_clear_btn" class="btn btn-outline-secondary" title="Clear linked proponent">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div id="proponent_dropdown"
                                                 class="list-group position-absolute w-100 shadow-sm"
                                                 style="z-index: 1050; max-height: 260px; overflow-y: auto; display: none; top: 100%;"></div>
                                        </div>
                                        <small class="text-muted">
                                            A proponent is required. Search and select an existing one,
                                            or use the &quot;Create new proponent&quot; button shown in the dropdown when no match is found.
                                        </small>
                                    </div>
                                </div>

                                <!-- Kinds Beneficiaries — checkboxes -->
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Kinds Beneficiaries</label>
                                        <div class="row g-2">
                                            <?php foreach ($workerTypeOptions as $index => $option): ?>
                                            <div class="col-sm-6 col-md-4 col-lg-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="type_of_worker[]"
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

                                <!-- Type of Beneficiaries — checkboxes (mirrors proponents) -->
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Type of Beneficiaries</label>
                                        <div class="row g-2">
                                            <?php foreach ($beneficiaryTypeOptions as $index => $option): ?>
                                            <div class="col-sm-6 col-md-4 col-lg-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="type_of_beneficiaries[]"
                                                           id="ben_type_<?php echo $index; ?>"
                                                           value="<?php echo htmlspecialchars($option); ?>"
                                                           <?php echo in_array($option, $formBeneficiaryTypes, true) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="ben_type_<?php echo $index; ?>">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Process Tracking ───────────────────────── -->
                            <div class="form-section">
                                <h5><i class="bi bi-calendar-check"></i> Process Tracking</h5>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Noted Findings/Comments</label>
                                        <textarea name="noted_findings" class="form-control" rows="2"><?php echo htmlspecialchars($beneficiary['noted_findings'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Complied by Proponent/ACP</label>
                                        <input type="date" name="date_complied_by_proponent" class="form-control"
                                               value="<?php echo $beneficiary['date_complied_by_proponent'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><?php echo htmlspecialchars(Auth::getForwardedToRoLabel()); ?></label>
                                        <input type="date" name="date_forwarded_to_ro6" class="form-control"
                                               value="<?php echo $beneficiary['date_forwarded_to_ro6'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date Approved</label>
                                        <input type="date" name="date_approved" class="form-control"
                                               value="<?php echo $beneficiary['date_approved'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">RPMT Findings</label>
                                        <textarea name="rpmt_findings" class="form-control" rows="2"><?php echo htmlspecialchars($beneficiary['rpmt_findings'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><?php echo htmlspecialchars(Auth::getForwardedToOfficeLabel($formProvince)); ?></label>
                                        <input type="date" name="date_forwarded_to_nofo" class="form-control"
                                               value="<?php echo $beneficiary['date_forwarded_to_nofo'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date of Turn-over</label>
                                        <input type="date" name="date_turnover" class="form-control"
                                               value="<?php echo $beneficiary['date_turnover'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date of Monitoring</label>
                                        <input type="date" name="date_monitoring" class="form-control"
                                               value="<?php echo $beneficiary['date_monitoring'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- ── Location (Map Visualization) ──────────── -->
                            <div class="form-section">
                                <h5><i class="bi bi-geo-alt"></i> Location (For Map Visualization)</h5>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-info-circle"></i>
                                    Coordinates are auto-filled when you select a Barangay above, or use the button below to re-fetch them.
                                </p>
                                <div class="row">
                                    <div class="col-md-4 mb-3 d-flex align-items-end">
                                        <button type="button" id="btn-geocode" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-geo-alt-fill"></i> Auto-fill Coordinates
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Latitude
                                            <span class="help-icon" data-bs-toggle="tooltip" title="Decimal degrees (9.0–12.0 for Negros Occidental)">?</span>
                                        </label>
                                        <input type="text" name="latitude" id="latitude" class="form-control"
                                               placeholder="e.g., 10.5"
                                               value="<?php echo $beneficiary['latitude'] ?? ''; ?>">
                                        <small class="text-muted">Decimal degrees format (9.0 – 12.0)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Longitude
                                            <span class="help-icon" data-bs-toggle="tooltip" title="Decimal degrees (122.0–124.0 for Negros Occidental)">?</span>
                                        </label>
                                        <input type="text" name="longitude" id="longitude" class="form-control"
                                               placeholder="e.g., 123.0"
                                               value="<?php echo $beneficiary['longitude'] ?? ''; ?>">
                                        <small class="text-muted">Decimal degrees format (122.0 – 124.0)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Status ─────────────────────────────────── -->
                            <div class="form-section">
                                <h5><i class="bi bi-flag"></i> Status</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Current Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select" required>
                                            <option value="pending"     <?php echo ($beneficiary['status'] ?? 'pending') === 'pending'     ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved"    <?php echo ($beneficiary['status'] ?? '') === 'approved'    ? 'selected' : ''; ?>>Approved</option>
                                            <option value="implemented" <?php echo ($beneficiary['status'] ?? '') === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                            <option value="monitored"   <?php echo ($beneficiary['status'] ?? '') === 'monitored'   ? 'selected' : ''; ?>>Monitored</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?> Beneficiary
                                </button>
                                <button type="button" id="btn-clear-form" class="btn btn-outline-warning">
                                    <i class="bi bi-eraser"></i> Clear Form
                                </button>
                                <a href="beneficiaries.php" class="btn btn-secondary">
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
    <script src="assets/js/form-autosave.js"></script>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>DILP.validation.init('form[method="POST"]');</script>
    <script>DILPAutoSave.init({ storageKey: 'dilp_beneficiary_form' });</script>
    <script>
    (function () {
        'use strict';

        const IS_SUPER_ADMIN     = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;
        const IS_CROSS_PROVINCE  = <?php echo $isCrossProvince ? 'true' : 'false'; ?>;
        const SESSION_PROVINCE   = <?php echo json_encode($sessionProvince); ?>;
        const FORM_PROVINCE      = <?php echo json_encode($formProvince); ?>;
        const SAVED_MUNICIPALITY = <?php echo json_encode($beneficiary['municipality'] ?? ''); ?>;
        const SAVED_BARANGAY     = <?php echo json_encode($beneficiary['barangay'] ?? ''); ?>;

        // ── Bootstrap location autofill ────────────────────────────────────
        if (IS_CROSS_PROVINCE) {
            DILPLocation.initLocationAutoFill({
                provinceSelect:        '#province',
                municipalitySelect:    '#municipality',
                barangaySelect:        '#barangay',
                latitudeInput:         'input[name="latitude"]',
                longitudeInput:        'input[name="longitude"]',
                geocodeButton:         '#btn-geocode',
                provinceValueInput:    '#province_value',
                municipalityValueInput:'#municipality_value',
                barangayValueInput:    '#barangay_value',
            });
        } else {
            // Provincial user — province locked, bootstrap municipalities manually
            _bootstrapLockedProvince();
        }

        // ── Locked-province bootstrap ──────────────────────────────────────
        async function _bootstrapLockedProvince() {
            if (!SESSION_PROVINCE) return;

            try {
                const resp   = await fetch('api/get-locations.php?action=provinces', { headers: { 'Accept': 'application/json' } });
                const result = await resp.json();
                if (!result.success) return;

                const provinceObj = result.data.find(p => p.name === SESSION_PROVINCE);
                if (!provinceObj) return;

                // Load municipalities
                const muniResp   = await fetch(`api/get-locations.php?action=cities&province_code=${provinceObj.code}`, { headers: { 'Accept': 'application/json' } });
                const muniResult = await muniResp.json();
                if (!muniResult.success) return;

                const muniSelect = document.getElementById('municipality');
                if (!muniSelect) return;

                muniSelect.innerHTML = '<option value="">Select City/Municipality</option>';
                muniSelect.disabled  = false;

                muniResult.data.forEach(city => {
                    const opt = document.createElement('option');
                    opt.value        = city.name;
                    opt.textContent  = city.name;
                    opt.dataset.code = city.code;
                    if (city.name === SAVED_MUNICIPALITY) opt.selected = true;
                    muniSelect.appendChild(opt);
                });

                // Load barangays if municipality was pre-selected
                if (SAVED_MUNICIPALITY && muniSelect.value) {
                    const cityCode = muniSelect.selectedOptions[0]?.dataset.code;
                    if (cityCode) await _loadBarangays(cityCode, SAVED_BARANGAY);
                }

                // Wire municipality → barangay cascade
                muniSelect.addEventListener('change', async function () {
                    const cityCode  = this.selectedOptions[0]?.dataset.code;
                    const barSelect = document.getElementById('barangay');
                    if (!cityCode || !barSelect) return;
                    barSelect.innerHTML = '<option value="">Loading barangays...</option>';
                    barSelect.disabled  = true;
                    await _loadBarangays(cityCode, '');
                });

            } catch (err) {
                console.error('Failed to bootstrap locked-province location:', err);
            }
        }

        async function _loadBarangays(cityCode, savedBarangay) {
            const bResp   = await fetch(`api/get-locations.php?action=barangays&city_code=${cityCode}`, { headers: { 'Accept': 'application/json' } });
            const bResult = await bResp.json();
            const barSelect = document.getElementById('barangay');
            if (!barSelect || !bResult.success) return;

            barSelect.innerHTML = '<option value="">Select Barangay (optional)</option>';
            barSelect.disabled  = false;

            bResult.data.forEach(b => {
                const opt = document.createElement('option');
                opt.value       = b.name;
                opt.textContent = b.name;
                if (b.name === savedBarangay) opt.selected = true;
                barSelect.appendChild(opt);
            });

            // Auto-geocode once barangay list is ready (if saved barangay is set)
            if (savedBarangay && barSelect.value) {
                _autoGeocode();
            }

            // Wire barangay change → auto geocode
            barSelect.addEventListener('change', function () {
                _autoGeocode();
            });
        }

        // ── Auto-geocode ───────────────────────────────────────────────────
        async function _autoGeocode() {
            const province     = IS_CROSS_PROVINCE
                ? (document.getElementById('province')?.value || '')
                : SESSION_PROVINCE;
            const municipality = document.getElementById('municipality')?.value || '';
            const barangay     = document.getElementById('barangay')?.value || '';
            const latInput     = document.querySelector('input[name="latitude"]');
            const lngInput     = document.querySelector('input[name="longitude"]');

            if (!municipality) return;  // not enough info

            const btn = document.getElementById('btn-geocode');
            if (btn) {
                btn.disabled   = true;
                btn.innerHTML  = '<i class="bi bi-hourglass-split"></i> Fetching…';
            }

            try {
                const params = new URLSearchParams({ municipality });
                if (province) params.append('province', province);
                if (barangay) params.append('barangay', barangay);

                const resp   = await fetch(`api/geocode.php?${params}`, { headers: { 'Accept': 'application/json' } });
                const result = await resp.json();

                if (result.success) {
                    if (latInput) latInput.value = result.latitude.toFixed(8);
                    if (lngInput) lngInput.value = result.longitude.toFixed(8);
                }
            } catch (e) {
                console.warn('Auto-geocode failed:', e);
            } finally {
                if (btn) {
                    btn.disabled  = false;
                    btn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Auto-fill Coordinates';
                }
            }
        }

        // Manual geocode button (always attached)
        document.getElementById('btn-geocode')?.addEventListener('click', function () {
            _autoGeocode();
        });

        // For cross-province roles, the DILPLocation lib already wires auto-geocode via barangay change.
        // For locked-province, we wire it inside _loadBarangays above.

        // ── Beneficiary-form draft snapshot helpers (used by the proponent
        //    "create new" detour). Snapshots every form field into
        //    sessionStorage so the user returns to a fully-restored form.
        const DRAFT_KEY = 'dilp_beneficiary_draft';

        function _snapshotBeneficiaryForm(extra) {
            const form = document.querySelector('form[method="POST"]');
            if (!form) return;
            const snapshot = { values: {}, checkboxes: {}, extra: extra || {} };
            // Capture every named input/select/textarea (single + multi values)
            const data = new FormData(form);
            data.forEach(function (val, key) {
                if (key.endsWith('[]')) {
                    if (!snapshot.checkboxes[key]) snapshot.checkboxes[key] = [];
                    snapshot.checkboxes[key].push(val);
                } else {
                    snapshot.values[key] = val;
                }
            });
            // Also persist the visible proponent search label so the user
            // sees their previous query when they return.
            const searchEl = document.getElementById('proponent_search');
            if (searchEl) snapshot.values.__proponent_search = searchEl.value;
            try {
                sessionStorage.setItem(DRAFT_KEY, JSON.stringify(snapshot));
            } catch (e) {
                console.warn('Failed to snapshot beneficiary draft:', e);
            }
        }

        // ── Proponent combobox (searchable dropdown with visible results) ──
        (function initProponentCombobox() {
            const hiddenInput = document.getElementById('proponent_id');
            const searchInput = document.getElementById('proponent_search');
            const dropdown    = document.getElementById('proponent_dropdown');
            const clearBtn    = document.getElementById('proponent_clear_btn');
            const combobox    = document.getElementById('proponent_combobox');
            if (!hiddenInput || !searchInput || !dropdown || !combobox) return;

            let debounceTimer = null;
            let lastQuery     = null;

            function showDropdown() { dropdown.style.display = 'block'; }
            function hideDropdown() { dropdown.style.display = 'none'; }

            function _appendCreateCTA() {
                // Inline "Create new proponent" button shown after the empty-state
                // message or below any results. Snapshots the in-progress
                // beneficiary form, then redirects to proponent-form.php with
                // a return marker so the new proponent ID is auto-linked back.
                const wrap = document.createElement('div');
                wrap.className = 'list-group-item d-flex justify-content-between align-items-center bg-light';
                const span = document.createElement('span');
                span.className = 'text-muted small';
                span.textContent = 'Cannot find the right proponent?';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-primary';
                btn.innerHTML = '<i class="bi bi-plus-circle"></i> Create new proponent';
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    _snapshotBeneficiaryForm({ source: 'proponent_create_cta' });
                    window.location.href = 'proponent-form.php?return=beneficiary-form';
                });
                wrap.appendChild(span);
                wrap.appendChild(btn);
                dropdown.appendChild(wrap);
            }

            function renderItems(items) {
                dropdown.innerHTML = '';
                if (!items || items.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'list-group-item text-muted small';
                    empty.textContent = 'No matching proponents found.';
                    dropdown.appendChild(empty);
                    _appendCreateCTA();
                    showDropdown();
                    return;
                }
                items.forEach(function (item) {
                    const a = document.createElement('button');
                    a.type = 'button';
                    a.className = 'list-group-item list-group-item-action text-start';
                    a.textContent = item.label;
                    a.dataset.id = item.id;
                    a.addEventListener('mousedown', function (e) {
                        // mousedown so it fires before searchInput blur
                        e.preventDefault();
                        hiddenInput.value = item.id;
                        searchInput.value = item.label;
                        hideDropdown();
                    });
                    dropdown.appendChild(a);
                });
                _appendCreateCTA();
                showDropdown();
            }

            async function search(query) {
                if (query === lastQuery) { showDropdown(); return; }
                lastQuery = query;
                try {
                    const resp = await fetch('api/get-proponents.php?search=' + encodeURIComponent(query), {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await resp.json();
                    if (!json || !json.success) {
                        renderItems([]);
                        return;
                    }
                    renderItems(json.data || []);
                } catch (e) {
                    console.warn('Proponent search error:', e);
                    renderItems([]);
                }
            }

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const query = this.value.trim();

                // Clear linked id immediately when user edits text — they must re-select
                hiddenInput.value = '';

                if (query.length < 2) {
                    hideDropdown();
                    return;
                }
                debounceTimer = setTimeout(function () { search(query); }, 250);
            });

            searchInput.addEventListener('focus', function () {
                const query = this.value.trim();
                if (query.length >= 2) search(query);
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (!combobox.contains(e.target)) hideDropdown();
            });

            // Clear button
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    hiddenInput.value = '';
                    searchInput.value = '';
                    lastQuery = null;
                    hideDropdown();
                    searchInput.focus();
                });
            }

            // Reset combobox when the whole form is cleared via Clear Form button
            document.addEventListener('dilp:form-cleared', function () {
                hiddenInput.value = '';
                searchInput.value = '';
                lastQuery = null;
                hideDropdown();
            });
        })();

        // ── Restore beneficiary draft after returning from proponent-form ──
        //    Triggered when the URL carries ?restore=1 (optionally with
        //    link_proponent_id=<id>). Reads the snapshot from sessionStorage,
        //    re-applies every value/checkbox, clears the storage key, and
        //    (if a new proponent ID is supplied) resolves its label via the
        //    same API used by the combobox so the user sees the linkage.
        (async function restoreDraftIfRequested() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('restore') !== '1') return;

            let snapshot = null;
            try {
                const raw = sessionStorage.getItem(DRAFT_KEY);
                if (raw) snapshot = JSON.parse(raw);
            } catch (e) {
                console.warn('Failed to read beneficiary draft:', e);
            }

            if (snapshot) {
                const form = document.querySelector('form[method="POST"]');
                if (form) {
                    // Restore scalar values
                    Object.keys(snapshot.values || {}).forEach(function (name) {
                        if (name.startsWith('__')) return; // skip internal-only keys
                        const el = form.elements[name];
                        if (!el) return;
                        // Skip hidden proponent_id here — handled below by the
                        // explicit link_proponent_id query param so a freshly
                        // created proponent supersedes any stale snapshot value.
                        if (name === 'proponent_id') return;
                        if (el.length && el.tagName !== 'SELECT') {
                            // RadioNodeList (e.g. radios). Set by value.
                            for (let i = 0; i < el.length; i++) {
                                if (el[i].value === snapshot.values[name]) el[i].checked = true;
                            }
                        } else {
                            el.value = snapshot.values[name];
                        }
                    });
                    // Restore checkbox arrays
                    Object.keys(snapshot.checkboxes || {}).forEach(function (name) {
                        const wanted = new Set(snapshot.checkboxes[name]);
                        form.querySelectorAll('input[type="checkbox"][name="' + name + '"]').forEach(function (cb) {
                            cb.checked = wanted.has(cb.value);
                        });
                    });
                    // Restore the visible proponent search label so users can re-search
                    const searchEl = document.getElementById('proponent_search');
                    if (searchEl && snapshot.values && snapshot.values.__proponent_search) {
                        searchEl.value = snapshot.values.__proponent_search;
                    }
                }
                sessionStorage.removeItem(DRAFT_KEY);
            } else {
                console.warn('No beneficiary draft found in sessionStorage; rendering empty form.');
            }

            // Auto-link the freshly created proponent if its ID was passed back.
            // The label is supplied directly by proponent-form.php so we don't
            // depend on the proponent-search API to find it among many results.
            const newProponentId    = params.get('link_proponent_id');
            const newProponentLabel = params.get('link_proponent_label') || '';
            if (newProponentId && /^\d+$/.test(newProponentId)) {
                const hiddenInputEl = document.getElementById('proponent_id');
                const searchInputEl = document.getElementById('proponent_search');
                if (hiddenInputEl) hiddenInputEl.value = newProponentId;
                if (searchInputEl) {
                    searchInputEl.value = newProponentLabel || ('Proponent #' + newProponentId);
                }
            }

            // Clean the URL so a page refresh does not re-trigger the restore flow.
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('restore');
                url.searchParams.delete('link_proponent_id');
                url.searchParams.delete('link_proponent_label');
                window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ''));
            }
        })();

    })();
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
