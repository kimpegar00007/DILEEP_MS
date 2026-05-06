<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/Beneficiary.php';
require_once 'models/Proponent.php';

$auth = new Auth();
$auth->requireLogin();

$beneficiaryModel = new Beneficiary();
$proponentModel = new Proponent();

// Get filters from query string
$beneficiaryFilters = [
    'municipality' => $_GET['municipality'] ?? '',
    'barangay' => $_GET['barangay'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$proponentFilters = [
    'proponent_type' => $_GET['proponent_type'] ?? '',
    'district' => $_GET['district'] ?? '',
    'status' => $_GET['status'] ?? '',
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Get data
$beneficiaries = $beneficiaryModel->getAll($beneficiaryFilters);
$proponents = $proponentModel->getAll($proponentFilters);

// Get unique values for filters
$db = Database::getInstance()->getConnection();
$municipalities = $db->query("SELECT DISTINCT municipality FROM beneficiaries ORDER BY municipality")->fetchAll(PDO::FETCH_COLUMN);
$barangays = $db->query("SELECT DISTINCT barangay FROM beneficiaries ORDER BY barangay")->fetchAll(PDO::FETCH_COLUMN);
$districts = $db->query("SELECT DISTINCT district FROM proponents WHERE district IS NOT NULL ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);

// Get current view from query string or default to beneficiaries
$currentView = $_GET['view'] ?? 'beneficiaries';
if (!in_array($currentView, ['beneficiaries', 'proponents'])) {
    $currentView = 'beneficiaries';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiaries & Proponents - DOLE DILEEP Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        /* Unified Page Specific Styles */
        .unified-header {
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .view-toggle {
            display: inline-flex;
            background: rgba(0, 0, 0, 0.06);
            border-radius: 50px;
            padding: 4px;
            gap: 4px;
        }
        
        .view-toggle button {
            background: transparent;
            border: none;
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 2;
        }
        
        .view-toggle button.active {
            background: white;
            color: var(--dole-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }
        
        .view-toggle button:hover:not(.active) {
            color: var(--dole-primary);
            background: rgba(255, 255, 255, 0.6);
        }
        
        .table-view-link {
            font-size: 0.78rem;
            color: #adb5bd;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.4rem;
            transition: color 0.2s ease;
        }
        
        .table-view-link:hover {
            color: var(--dole-primary);
        }
        
        .filter-section {
            background: var(--dole-light);
            border-radius: var(--dole-border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--dole-box-shadow);
            transition: all 0.3s ease;
        }
        
        .filter-section.hidden {
            opacity: 0;
            transform: translateY(-10px);
            display: none;
        }
        
        .filter-section.visible {
            opacity: 1;
            transform: translateY(0);
            display: block;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .entity-card {
            background: white;
            border-radius: var(--dole-border-radius);
            box-shadow: var(--dole-box-shadow);
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .entity-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--dole-primary), var(--dole-accent));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .entity-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .entity-card:hover::before {
            transform: scaleX(1);
        }
        
        .entity-card .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .entity-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dole-dark);
            margin: 0;
            line-height: 1.3;
        }
        
        .entity-card .card-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .entity-card .card-content {
            margin-bottom: 1rem;
        }
        
        .entity-card .info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .entity-card .info-row i {
            color: var(--dole-primary);
            width: 16px;
            text-align: center;
        }
        
        .entity-card .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .entity-card .amount {
            font-weight: 600;
            color: var(--dole-primary);
            font-size: 1.1rem;
        }
        
        .skeleton-card {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: var(--dole-border-radius);
            height: 200px;
            margin-bottom: 1.5rem;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .add-button-fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--dole-primary), var(--dole-secondary));
            border: none;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 20px rgba(27, 122, 61, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .add-button-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(27, 122, 61, 0.4);
        }
        
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .choice-modal .modal-content {
            border: none;
            border-radius: var(--dole-border-radius);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .choice-option {
            padding: 2rem;
            border: 2px solid #e9ecef;
            border-radius: var(--dole-border-radius);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .choice-option:hover {
            border-color: var(--dole-primary);
            background: rgba(27, 122, 61, 0.05);
            transform: translateY(-2px);
        }
        
        .choice-option i {
            font-size: 3rem;
            color: var(--dole-primary);
            margin-bottom: 1rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .view-toggle button {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
            
            .add-button-fab {
                bottom: 1rem;
                right: 1rem;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1025px) {
            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 1400px) {
            .cards-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php $currentPage = 'unified'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main">
                <!-- Unified Header -->
                <div class="unified-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-2">Beneficiaries & Proponents</h1>
                            <p class="mb-0 opacity-90">Manage individual beneficiaries and group proponents in one unified interface</p>
                        </div>
                        <div>
                            <div class="view-toggle">
                                <button type="button" <?php echo $currentView === 'beneficiaries' ? 'class="active"' : ''; ?> data-view="beneficiaries">
                                    <i class="bi bi-person"></i> Beneficiaries
                                </button>
                                <button type="button" <?php echo $currentView === 'proponents' ? 'class="active"' : ''; ?> data-view="proponents">
                                    <i class="bi bi-people"></i> Proponents
                                </button>
                            </div>
                            <div class="text-end">
                                <?php if ($currentView === 'beneficiaries'): ?>
                                <a href="beneficiaries.php" class="table-view-link">
                                    <i class="bi bi-table"></i> View as table <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem;"></i>
                                </a>
                                <?php else: ?>
                                <a href="proponents.php" class="table-view-link">
                                    <i class="bi bi-table"></i> View as table <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem;"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Beneficiary Filters -->
                <div class="filter-section <?php echo $currentView === 'beneficiaries' ? 'visible' : 'hidden'; ?>" id="beneficiaryFilters">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Beneficiaries</h5>
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="view" value="beneficiaries">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Name or Project" value="<?php echo htmlspecialchars($beneficiaryFilters['search']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Municipality</label>
                            <select name="municipality" class="form-select">
                                <option value="">All</option>
                                <?php foreach ($municipalities as $municipality): ?>
                                <option value="<?php echo htmlspecialchars($municipality); ?>" 
                                        <?php echo $beneficiaryFilters['municipality'] === $municipality ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($municipality); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Barangay</label>
                            <select name="barangay" class="form-select">
                                <option value="">All</option>
                                <?php foreach ($barangays as $barangay): ?>
                                <option value="<?php echo htmlspecialchars($barangay); ?>" 
                                        <?php echo $beneficiaryFilters['barangay'] === $barangay ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($barangay); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="pending" <?php echo $beneficiaryFilters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $beneficiaryFilters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="implemented" <?php echo $beneficiaryFilters['status'] === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                <option value="monitored" <?php echo $beneficiaryFilters['status'] === 'monitored' ? 'selected' : ''; ?>>Monitored</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="unified-beneficiaries-proponents.php?view=beneficiaries" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Proponent Filters -->
                <div class="filter-section <?php echo $currentView === 'proponents' ? 'visible' : 'hidden'; ?>" id="proponentFilters">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Proponents</h5>
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="view" value="proponents">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Name, Project, or Control #" value="<?php echo htmlspecialchars($proponentFilters['search']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Proponent Type</label>
                            <select name="proponent_type" class="form-select">
                                <option value="">All</option>
                                <option value="LGU-associated" <?php echo $proponentFilters['proponent_type'] === 'LGU-associated' ? 'selected' : ''; ?>>LGU-associated</option>
                                <option value="Non-LGU-associated" <?php echo $proponentFilters['proponent_type'] === 'Non-LGU-associated' ? 'selected' : ''; ?>>Non-LGU-associated</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">District</label>
                            <select name="district" class="form-select">
                                <option value="">All</option>
                                <?php foreach ($districts as $district): ?>
                                <option value="<?php echo htmlspecialchars($district); ?>" 
                                        <?php echo $proponentFilters['district'] === $district ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All</option>
                                <option value="Formation" <?php echo $proponentFilters['category'] === 'Formation' ? 'selected' : ''; ?>>Formation</option>
                                <option value="Enhancement" <?php echo $proponentFilters['category'] === 'Enhancement' ? 'selected' : ''; ?>>Enhancement</option>
                                <option value="Restoration" <?php echo $proponentFilters['category'] === 'Restoration' ? 'selected' : ''; ?>>Restoration</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="pending" <?php echo $proponentFilters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $proponentFilters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="implemented" <?php echo $proponentFilters['status'] === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                <option value="liquidated" <?php echo $proponentFilters['status'] === 'liquidated' ? 'selected' : ''; ?>>Liquidated</option>
                                <option value="monitored" <?php echo $proponentFilters['status'] === 'monitored' ? 'selected' : ''; ?>>Monitored</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="unified-beneficiaries-proponents.php?view=proponents" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Cards Container -->
                <div id="cardsContainer">
                    <?php if ($currentView === 'beneficiaries'): ?>
                        <div class="cards-grid" id="beneficiaryCards">
                            <?php foreach ($beneficiaries as $beneficiary): ?>
                            <div class="entity-card" data-type="beneficiary" data-id="<?php echo $beneficiary['id']; ?>">
                                <div class="card-header">
                                    <div>
                                        <div class="card-title">
                                            <?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . 
                                                      ($beneficiary['middle_name'] ? substr($beneficiary['middle_name'], 0, 1) . '. ' : '') . 
                                                      $beneficiary['last_name'] . 
                                                      ($beneficiary['suffix'] ? ' ' . $beneficiary['suffix'] : '')); ?>
                                        </div>
                                        <div class="card-subtitle">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($beneficiary['barangay'] . ', ' . $beneficiary['municipality']); ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-<?php 
                                        $statusColors = ['pending' => 'secondary', 'approved' => 'primary', 'implemented' => 'success', 'monitored' => 'info'];
                                        echo $statusColors[$beneficiary['status']] ?? 'secondary';
                                    ?> badge-status">
                                        <?php echo ucfirst($beneficiary['status']); ?>
                                    </span>
                                </div>
                                <div class="card-content">
                                    <div class="info-row">
                                        <i class="bi bi-briefcase"></i>
                                        <span><?php echo htmlspecialchars($beneficiary['project_name']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-person"></i>
                                        <span><?php echo htmlspecialchars($beneficiary['gender']); ?></span>
                                    </div>
                                    <?php if ($beneficiary['contact_number']): ?>
                                    <div class="info-row">
                                        <i class="bi bi-telephone"></i>
                                        <span><?php echo htmlspecialchars($beneficiary['contact_number']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="amount">₱<?php echo number_format($beneficiary['amount_worth'], 2); ?></div>
                                    <div>
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="cards-grid" id="proponentCards">
                            <?php foreach ($proponents as $proponent): ?>
                            <div class="entity-card" data-type="proponent" data-id="<?php echo $proponent['id']; ?>">
                                <div class="card-header">
                                    <div>
                                        <div class="card-title">
                                            <?php echo htmlspecialchars($proponent['proponent_name']); ?>
                                        </div>
                                        <div class="card-subtitle">
                                            <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($proponent['project_title']); ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-<?php 
                                        $statusColors = ['pending' => 'secondary', 'approved' => 'primary', 'implemented' => 'success', 'liquidated' => 'warning', 'monitored' => 'info'];
                                        echo $statusColors[$proponent['status']] ?? 'secondary';
                                    ?> badge-status">
                                        <?php echo ucfirst($proponent['status']); ?>
                                    </span>
                                </div>
                                <div class="card-content">
                                    <div class="info-row">
                                        <i class="bi bi-tag"></i>
                                        <span><?php echo htmlspecialchars($proponent['proponent_type']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i class="bi bi-people"></i>
                                        <span><?php echo number_format($proponent['total_beneficiaries']); ?> beneficiaries</span>
                                    </div>
                                    <?php if ($proponent['district']): ?>
                                    <div class="info-row">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?php echo htmlspecialchars($proponent['district']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($proponent['control_number']): ?>
                                    <div class="info-row">
                                        <i class="bi bi-hash"></i>
                                        <span><?php echo htmlspecialchars($proponent['control_number']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="amount">₱<?php echo number_format($proponent['amount'], 2); ?></div>
                                    <div>
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Empty State -->
                <?php if (($currentView === 'beneficiaries' && empty($beneficiaries)) || ($currentView === 'proponents' && empty($proponents))): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #6c757d;"></i>
                    <h4 class="mt-3">No <?php echo $currentView === 'beneficiaries' ? 'Beneficiaries' : 'Proponents'; ?> Found</h4>
                    <p class="text-muted">Try adjusting your filters or add a new <?php echo $currentView === 'beneficiaries' ? 'beneficiary' : 'proponent'; ?>.</p>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add Button FAB -->
    <button class="add-button-fab" data-bs-toggle="modal" data-bs-target="#addChoiceModal">
        <i class="bi bi-plus"></i>
    </button>

    <!-- Add Choice Modal -->
    <div class="modal fade" id="addChoiceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content choice-modal">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Add New</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="choice-option" onclick="window.location.href='beneficiary-form.php'">
                                <i class="bi bi-person-plus"></i>
                                <h6>Add Beneficiary</h6>
                                <p class="text-muted mb-0">Add an individual beneficiary</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="choice-option" onclick="window.location.href='proponent-form.php'">
                                <i class="bi bi-people"></i>
                                <h6>Add Proponent</h6>
                                <p class="text-muted mb-0">Add a group proponent</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalTitle">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailModalContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="viewFullDetailsBtn">View Full Details</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
    <script>
        $(document).ready(function() {
            // View toggle functionality
            $('.view-toggle button').click(function() {
                const view = $(this).data('view');
                
                // Update active state
                $('.view-toggle button').removeClass('active');
                $(this).addClass('active');
                
                // Update URL without page reload
                const url = new URL(window.location);
                url.searchParams.set('view', view);
                window.history.pushState({}, '', url);
                
                // Switch filters
                $('#beneficiaryFilters').toggleClass('visible hidden', view === 'beneficiaries');
                $('#proponentFilters').toggleClass('visible hidden', view === 'proponents');
                
                // Show loading state
                showSkeletonCards();
                
                // Load new content
                loadCards(view);
            });
            
            // Card click handler
            $('.entity-card').click(function() {
                const type = $(this).data('type');
                const id = $(this).data('id');
                showDetailModal(type, id);
            });
            
            // View full details button
            $('#viewFullDetailsBtn').click(function() {
                const type = $('#detailModal').data('current-type');
                const id = $('#detailModal').data('current-id');
                
                if (type === 'beneficiary') {
                    window.location.href = 'beneficiary-view.php?id=' + id;
                } else {
                    window.location.href = 'proponent-view.php?id=' + id;
                }
            });
        });
        
        function showSkeletonCards() {
            const skeletonHtml = Array(8).fill('<div class="skeleton-card"></div>').join('');
            $('#cardsContainer').html('<div class="cards-grid">' + skeletonHtml + '</div>');
        }
        
        function loadCards(view) {
            // Simulate loading delay for demo
            setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.set('view', view);
                window.location.reload();
            }, 500);
        }
        
        function showDetailModal(type, id) {
            $('#detailModal').data('current-type', type);
            $('#detailModal').data('current-id', id);
            
            // Set title
            const title = type === 'beneficiary' ? 'Beneficiary Details' : 'Proponent Details';
            $('#detailModalTitle').text(title);
            
            // Show loading
            $('#detailModalContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
            
            // Load content via AJAX
            $.ajax({
                url: 'api/get-entity-details.php',
                method: 'GET',
                data: { type: type, id: id },
                success: function(response) {
                    $('#detailModalContent').html(response);
                },
                error: function() {
                    $('#detailModalContent').html('<div class="alert alert-danger">Failed to load details.</div>');
                }
            });
            
            // Show modal
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
