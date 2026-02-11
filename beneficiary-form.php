<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole(['admin', 'encoder']);

$beneficiaryModel = new Beneficiary();
$errors = [];
$success = '';
$beneficiary = null;
$isEdit = false;

if (isset($_GET['id'])) {
    $isEdit = true;
    $beneficiary = $beneficiaryModel->findById($_GET['id']);
    if (!$beneficiary) {
        header('Location: beneficiaries.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'last_name' => trim($_POST['last_name'] ?? ''),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'middle_name' => trim($_POST['middle_name'] ?? ''),
        'suffix' => trim($_POST['suffix'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'barangay' => trim($_POST['barangay'] ?? ''),
        'municipality' => trim($_POST['municipality'] ?? ''),
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'project_name' => trim($_POST['project_name'] ?? ''),
        'type_of_worker' => trim($_POST['type_of_worker'] ?? ''),
        'amount_worth' => floatval($_POST['amount_worth'] ?? 0),
        'noted_findings' => trim($_POST['noted_findings'] ?? ''),
        'date_complied_by_proponent' => $_POST['date_complied_by_proponent'] ?: null,
        'date_forwarded_to_ro6' => $_POST['date_forwarded_to_ro6'] ?: null,
        'rpmt_findings' => trim($_POST['rpmt_findings'] ?? ''),
        'date_approved' => $_POST['date_approved'] ?: null,
        'date_forwarded_to_nofo' => $_POST['date_forwarded_to_nofo'] ?: null,
        'date_turnover' => $_POST['date_turnover'] ?: null,
        'date_monitoring' => $_POST['date_monitoring'] ?: null,
        'latitude' => !empty(trim($_POST['latitude'] ?? '')) ? floatval(trim($_POST['latitude'])) : null,
        'longitude' => !empty(trim($_POST['longitude'] ?? '')) ? floatval(trim($_POST['longitude'])) : null,
        'status' => $_POST['status'] ?? 'pending'
    ];
    
    if (empty($data['last_name'])) $errors[] = 'Last name is required';
    if (empty($data['first_name'])) $errors[] = 'First name is required';
    if (empty($data['gender'])) $errors[] = 'Gender is required';
    if (empty($data['barangay'])) $errors[] = 'Barangay is required';
    if (empty($data['municipality'])) $errors[] = 'Municipality is required';
    if (empty($data['project_name'])) $errors[] = 'Project name is required';
    if ($data['amount_worth'] <= 0) $errors[] = 'Amount must be greater than zero';
    
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
                    <a href="beneficiaries.php" class="btn btn-secondary">
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
                                            <option value="Male" <?php echo ($beneficiary['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($beneficiary['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">City/Municipality <span class="text-danger">*</span></label>
                                        <select name="municipality" id="municipality" class="form-select" required>
                                            <option value="">Select City/Municipality</option>
                                        </select>
                                        <input type="hidden" id="municipality_value" value="<?php echo htmlspecialchars($beneficiary['municipality'] ?? ''); ?>">
                                    </div>
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
                                        <label class="form-label">Type of Worker</label>
                                        <input type="text" name="type_of_worker" class="form-control"
                                               placeholder="e.g., Persons deprived of liberty (PDL), Ambulant Vendor"
                                               value="<?php echo htmlspecialchars($beneficiary['type_of_worker'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Amount Worth (₱) <span class="text-danger">*</span></label>
                                        <input type="number" name="amount_worth" class="form-control" step="0.01" required
                                               value="<?php echo $beneficiary['amount_worth'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

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
                                        <label class="form-label">Date Forwarded to RO6 for RPMT Evaluation</label>
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
                                        <label class="form-label">Date Forwarded of Approved Proposal to NOFO</label>
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

                            <div class="form-section">
                                <h5><i class="bi bi-geo-alt"></i> Location (For Map Visualization)</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Latitude <span class="help-icon" data-bs-toggle="tooltip" title="Enter latitude in decimal degrees (9.0-12.0 for Negros Occidental)">?</span></label>
                                        <input type="text" name="latitude" class="form-control" 
                                               placeholder="e.g., 10.5"
                                               value="<?php echo $beneficiary['latitude'] ?? ''; ?>">
                                        <small class="text-muted">Decimal degrees format (9.0 - 12.0)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Longitude <span class="help-icon" data-bs-toggle="tooltip" title="Enter longitude in decimal degrees (122.0-124.0 for Negros Occidental)">?</span></label>
                                        <input type="text" name="longitude" class="form-control" 
                                               placeholder="e.g., 123.0"
                                               value="<?php echo $beneficiary['longitude'] ?? ''; ?>">
                                        <small class="text-muted">Decimal degrees format (122.0 - 124.0)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h5><i class="bi bi-flag"></i> Status</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Current Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select" required>
                                            <option value="pending" <?php echo ($beneficiary['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo ($beneficiary['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="implemented" <?php echo ($beneficiary['status'] ?? '') === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                            <option value="monitored" <?php echo ($beneficiary['status'] ?? '') === 'monitored' ? 'selected' : ''; ?>>Monitored</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?> Beneficiary
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
    <?php include 'includes/ux-utilities.php'; ?>
    <script>DILP.validation.init('form[method="POST"]');</script>
    <script>
        let citiesData = [];
        let cityCodeMap = {};
        
        document.addEventListener('DOMContentLoaded', function() {
            loadCities();
        });
        
        async function loadCities() {
            try {
                const response = await fetch('api/get-locations.php?action=cities');
                const result = await response.json();
                
                if (result.success) {
                    citiesData = result.data;
                    const municipalitySelect = document.getElementById('municipality');
                    const savedMunicipality = document.getElementById('municipality_value').value;
                    
                    municipalitySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                    
                    result.data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.name;
                        option.textContent = city.name;
                        option.dataset.code = city.code;
                        
                        if (savedMunicipality && city.name === savedMunicipality) {
                            option.selected = true;
                        }
                        
                        municipalitySelect.appendChild(option);
                        cityCodeMap[city.name] = city.code;
                    });
                    
                    if (savedMunicipality) {
                        const selectedOption = municipalitySelect.querySelector(`option[value="${savedMunicipality}"]`);
                        if (selectedOption) {
                            const cityCode = selectedOption.dataset.code;
                            await loadBarangays(cityCode);
                        }
                    }
                } else {
                    console.error('Failed to load cities:', result.message);
                    alert('Failed to load cities data. Please refresh the page.');
                }
            } catch (error) {
                console.error('Error loading cities:', error);
                alert('Error loading cities data. Please check your connection.');
            }
        }
        
        document.getElementById('municipality').addEventListener('change', async function() {
            const selectedOption = this.options[this.selectedIndex];
            const cityCode = selectedOption.dataset.code;
            const barangaySelect = document.getElementById('barangay');
            
            if (cityCode) {
                barangaySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Loading...</option>';
                await loadBarangays(cityCode);
            } else {
                barangaySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select City/Municipality first</option>';
            }
        });
        
        async function loadBarangays(cityCode) {
            try {
                const response = await fetch(`api/get-locations.php?action=barangays&city_code=${cityCode}`);
                const result = await response.json();
                
                if (result.success) {
                    const barangaySelect = document.getElementById('barangay');
                    const savedBarangay = document.getElementById('barangay_value').value;
                    
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    
                    result.data.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay.name;
                        option.textContent = barangay.name;
                        
                        if (savedBarangay && barangay.name === savedBarangay) {
                            option.selected = true;
                        }
                        
                        barangaySelect.appendChild(option);
                    });
                    
                    barangaySelect.disabled = false;
                } else {
                    console.error('Failed to load barangays:', result.message);
                    alert('Failed to load barangays data.');
                }
            } catch (error) {
                console.error('Error loading barangays:', error);
                alert('Error loading barangays data.');
            }
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
