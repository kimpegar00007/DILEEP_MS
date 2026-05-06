<?php
session_start();
require_once '../config/database.php';
require_once '../models/Beneficiary.php';
require_once '../models/Proponent.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($type) || empty($id)) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Invalid request.</div>';
    exit;
}

if (!in_array($type, ['beneficiary', 'proponent'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Invalid entity type.</div>';
    exit;
}

try {
    if ($type === 'beneficiary') {
        $beneficiaryModel = new Beneficiary();
        $entity = $beneficiaryModel->findById($id);
        
        if (!$entity) {
            echo '<div class="alert alert-danger">Beneficiary not found.</div>';
            exit;
        }
        
        ?>
        <div class="entity-details">
            <div class="row mb-3">
                <div class="col-md-8">
                    <h6 class="text-primary mb-1">Personal Information</h6>
                    <div class="mb-2">
                        <strong>Name:</strong> <?php echo htmlspecialchars($entity['first_name'] . ' ' . 
                                  ($entity['middle_name'] ? $entity['middle_name'] . ' ' : '') . 
                                  $entity['last_name'] . 
                                  ($entity['suffix'] ? ' ' . $entity['suffix'] : '')); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Gender:</strong> <?php echo htmlspecialchars($entity['gender']); ?>
                    </div>
                    <?php if ($entity['contact_number']): ?>
                    <div class="mb-2">
                        <strong>Contact:</strong> <?php echo htmlspecialchars($entity['contact_number']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <span class="badge bg-<?php 
                            $statusColors = ['pending' => 'secondary', 'approved' => 'primary', 'implemented' => 'success', 'monitored' => 'info'];
                            echo $statusColors[$entity['status']] ?? 'secondary';
                        ?> badge-status fs-6">
                            <?php echo ucfirst($entity['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="text-primary mb-1">Location</h6>
                    <div class="mb-2">
                        <strong>Barangay:</strong> <?php echo htmlspecialchars($entity['barangay']); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Municipality:</strong> <?php echo htmlspecialchars($entity['municipality']); ?>
                    </div>
                    <?php if ($entity['province']): ?>
                    <div class="mb-2">
                        <strong>Province:</strong> <?php echo htmlspecialchars($entity['province']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="text-primary mb-1">Project Information</h6>
                    <div class="mb-2">
                        <strong>Project Name:</strong> <?php echo htmlspecialchars($entity['project_name']); ?>
                    </div>
                    <?php if ($entity['type_of_worker']): ?>
                    <div class="mb-2">
                        <strong>Type of Worker:</strong> <?php echo htmlspecialchars($entity['type_of_worker']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <strong>Amount Worth:</strong> <span class="text-success fw-bold">₱<?php echo number_format($entity['amount_worth'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($entity['date_approved'] || $entity['date_turnover'] || $entity['date_monitoring']): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="text-primary mb-1">Key Dates</h6>
                    <?php if ($entity['date_approved']): ?>
                    <div class="mb-2">
                        <strong>Date Approved:</strong> <?php echo date('F d, Y', strtotime($entity['date_approved'])); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($entity['date_turnover']): ?>
                    <div class="mb-2">
                        <strong>Date of Turn-over:</strong> <?php echo date('F d, Y', strtotime($entity['date_turnover'])); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($entity['date_monitoring']): ?>
                    <div class="mb-2">
                        <strong>Date of Monitoring:</strong> <?php echo date('F d, Y', strtotime($entity['date_monitoring'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-12">
                    <small class="text-muted">
                        <strong>Record ID:</strong> <?php echo $entity['id']; ?> | 
                        <strong>Created:</strong> <?php echo date('F d, Y', strtotime($entity['created_at'])); ?>
                    </small>
                </div>
            </div>
        </div>
        <?php
    } else {
        $proponentModel = new Proponent();
        $entity = $proponentModel->findById($id);
        
        if (!$entity) {
            echo '<div class="alert alert-danger">Proponent not found.</div>';
            exit;
        }
        
        ?>
        <div class="entity-details">
            <div class="row mb-3">
                <div class="col-md-8">
                    <h6 class="text-primary mb-1">Basic Information</h6>
                    <div class="mb-2">
                        <strong>Proponent Name:</strong> <?php echo htmlspecialchars($entity['proponent_name']); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Project Title:</strong> <?php echo htmlspecialchars($entity['project_title']); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Proponent Type:</strong> 
                        <span class="badge bg-<?php echo $entity['proponent_type'] === 'LGU-associated' ? 'info' : 'secondary'; ?>">
                            <?php echo htmlspecialchars($entity['proponent_type']); ?>
                        </span>
                    </div>
                    <?php if ($entity['control_number']): ?>
                    <div class="mb-2">
                        <strong>Control Number:</strong> <?php echo htmlspecialchars($entity['control_number']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($entity['district']): ?>
                    <div class="mb-2">
                        <strong>District:</strong> <?php echo htmlspecialchars($entity['district']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <span class="badge bg-<?php 
                            $statusColors = ['pending' => 'secondary', 'approved' => 'primary', 'implemented' => 'success', 'liquidated' => 'warning', 'monitored' => 'info'];
                            echo $statusColors[$entity['status']] ?? 'secondary';
                        ?> badge-status fs-6">
                            <?php echo ucfirst($entity['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-primary mb-1">Financial Information</h6>
                    <div class="mb-2">
                        <strong>Project Amount:</strong> <span class="text-success fw-bold">₱<?php echo number_format($entity['amount'], 2); ?></span>
                    </div>
                    <div class="mb-2">
                        <strong>Category:</strong> <?php echo htmlspecialchars($entity['category']); ?>
                    </div>
                    <?php if ($entity['source_of_funds']): ?>
                    <div class="mb-2">
                        <strong>Source of Funds:</strong> <?php echo htmlspecialchars($entity['source_of_funds']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary mb-1">Beneficiary Information</h6>
                    <div class="mb-2">
                        <strong>Total Beneficiaries:</strong> <?php echo number_format($entity['total_beneficiaries']); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Male:</strong> <?php echo number_format($entity['male_beneficiaries']); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Female:</strong> <?php echo number_format($entity['female_beneficiaries']); ?>
                    </div>
                    <?php if ($entity['type_of_beneficiaries']): ?>
                    <div class="mb-2">
                        <strong>Type:</strong> <?php echo htmlspecialchars($entity['type_of_beneficiaries']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($entity['date_approved'] || $entity['date_turnover'] || $entity['date_implemented']): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="text-primary mb-1">Key Dates</h6>
                    <?php if ($entity['date_approved']): ?>
                    <div class="mb-2">
                        <strong>Date Approved:</strong> <?php echo date('F d, Y', strtotime($entity['date_approved'])); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($entity['date_turnover']): ?>
                    <div class="mb-2">
                        <strong>Date of Turn-over:</strong> <?php echo date('F d, Y', strtotime($entity['date_turnover'])); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($entity['date_implemented']): ?>
                    <div class="mb-2">
                        <strong>Date Implemented:</strong> <?php echo date('F d, Y', strtotime($entity['date_implemented'])); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($entity['date_liquidated']): ?>
                    <div class="mb-2">
                        <strong>Date Liquidated:</strong> <?php echo date('F d, Y', strtotime($entity['date_liquidated'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-12">
                    <small class="text-muted">
                        <strong>Record ID:</strong> <?php echo $entity['id']; ?> | 
                        <strong>Created:</strong> <?php echo date('F d, Y', strtotime($entity['created_at'])); ?>
                    </small>
                </div>
            </div>
        </div>
        <?php
    }
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="alert alert-danger">An error occurred while loading details.</div>';
}
?>
