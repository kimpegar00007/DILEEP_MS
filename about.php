<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$isSuperAdmin = $auth->isSuperAdmin();
$isCrossProvince = $auth->isCrossProvince(); // super_admin or regional_director
$sessionProvince = $auth->getProvince();
$appName = 'DOLE DILEEP Monitoring System';
if (file_exists(__DIR__ . '/VERSION')) {
    $appVersion = trim(file_get_contents(__DIR__ . '/VERSION')) ?: '1.0.0';
} else {
    $appVersion = '1.0.0';
}
$release_year = date('Y');

// Fetch organizational chart data from database (phase9: province support)
$db = Database::getInstance()->getConnection();

// Detect whether phase8 and phase9 migration columns exist
$hasTierCol = false;
$hasProvinceCol = false;
try {
    $db->query("SELECT tier FROM org_chart LIMIT 1");
    $hasTierCol = true;
} catch (PDOException $e) {
    $hasTierCol = false;
}
try {
    $db->query("SELECT province FROM org_chart LIMIT 1");
    $hasProvinceCol = true;
} catch (PDOException $e) {
    $hasProvinceCol = false;
}

// Build query based on available columns and user role
if ($hasProvinceCol) {
    // Phase9: province column exists
    if ($isCrossProvince) {
        // Super-admin or regional_director: show all provinces
        if ($hasTierCol) {
            $stmt = $db->query("SELECT * FROM org_chart ORDER BY province ASC, tier ASC, sort_order ASC, id ASC");
        } else {
            $stmt = $db->query("SELECT * FROM org_chart ORDER BY province ASC, position_order ASC");
        }
    } else {
        // Provincial user: show only their province
        if ($hasTierCol) {
            $stmt = $db->prepare("SELECT * FROM org_chart WHERE province = ? ORDER BY tier ASC, sort_order ASC, id ASC");
        } else {
            $stmt = $db->prepare("SELECT * FROM org_chart WHERE province = ? ORDER BY position_order ASC");
        }
        $stmt->execute([$sessionProvince]);
    }
} else {
    // Legacy: no province column (pre-phase9)
    if ($hasTierCol) {
        $stmt = $db->query("SELECT * FROM org_chart ORDER BY tier ASC, sort_order ASC, id ASC");
    } else {
        $stmt = $db->query("SELECT * FROM org_chart ORDER BY position_order ASC");
    }
}
$orgChartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group org chart data by province for display
$orgChartByProvince = [];
foreach ($orgChartData as $row) {
    $province = $row['province'] ?? 'Negros Occidental'; // Default for legacy data
    if (!isset($orgChartByProvince[$province])) {
        $orgChartByProvince[$province] = [];
    }
    $orgChartByProvince[$province][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DILEEP Monitoring System — web-based platform for managing beneficiaries and proponents under DOLE DILEEP in Negros Occidental.">
    <title>About - <?php echo htmlspecialchars($appName); ?></title>
    <meta name="theme-color" content="#1B7A3D">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'includes/shared-styles.php'; ?>
    <?php include 'includes/notification-styles.php'; ?>
    <style>
        /* ── Page Header ─────────────────────────────────────── */
        .about-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
        }
        .about-page-header-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .about-page-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(27,122,61,0.25);
        }
        .about-page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1a2e1a;
            margin: 0;
            line-height: 1.2;
        }
        .about-page-subtitle {
            font-size: 0.8rem;
            color: #6c757d;
            margin: 0;
        }
        .about-version-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(27,122,61,0.08);
            border: 1px solid rgba(27,122,61,0.18);
            color: var(--dole-primary);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
        }

        /* ── Section Cards ──────────────────────────────────── */
        .about-section {
            background: #fff;
            border-radius: var(--dole-border-radius);
            box-shadow: var(--dole-box-shadow);
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
        }
        .section-heading {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.35rem;
        }
        .section-heading-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--dole-secondary), var(--dole-primary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .section-heading h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dole-primary);
            margin: 0;
        }
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, var(--dole-primary) 0%, rgba(27,122,61,0.06) 100%);
            border: none;
            border-radius: 2px;
            margin: 0.75rem 0 1.5rem;
        }

        /* ── Org Chart ──────────────────────────────────────── */
        .org-tree {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            padding: 0.5rem 0 1rem;
            overflow-x: auto;
        }

        /* Node */
        .org-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .org-card {
            background: #fff;
            border: 1.5px solid rgba(27,122,61,0.15);
            border-radius: 14px;
            padding: 1rem 1.35rem;
            text-align: center;
            min-width: 200px;
            max-width: 260px;
            position: relative;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
            cursor: default;
            animation: orgFadeIn 0.45s ease both;
            box-shadow: 0 3px 14px rgba(0,0,0,0.07);
        }
        .org-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(27,122,61,0.14);
        }

        /* Tier colours */
        .org-card.tier-0 {
            border-top: 4px solid #D4A017;
            background: linear-gradient(160deg, rgba(212,160,23,0.06) 0%, #fff 60%);
        }
        .org-card.tier-1 {
            border-top: 4px solid var(--dole-primary);
            background: linear-gradient(160deg, rgba(27,122,61,0.06) 0%, #fff 60%);
        }
        .org-card.tier-2 {
            border-top: 4px solid var(--dole-info);
            background: linear-gradient(160deg, rgba(30,107,184,0.05) 0%, #fff 60%);
        }
        .org-card.tier-3 {
            border-top: 3px solid rgba(27,122,61,0.40);
        }

        .org-card-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 auto 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
            font-weight: 700;
        }
        .tier-0 .org-card-avatar { background: linear-gradient(135deg,#c8900f,#D4A017); }
        .tier-1 .org-card-avatar { background: linear-gradient(135deg,var(--dole-secondary),var(--dole-primary)); }
        .tier-2 .org-card-avatar { background: linear-gradient(135deg,#1452a0,var(--dole-info)); }
        .tier-3 .org-card-avatar { background: linear-gradient(135deg,#4a7c5e,#6da87e); }

        .org-name {
            font-weight: 700;
            font-size: 0.88rem;
            color: #1a2e1a;
            line-height: 1.3;
            margin-bottom: 0.4rem;
        }
        .org-role-badge {
            display: inline-block;
            font-size: 0.69rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.22rem 0.6rem;
            border-radius: 20px;
            background: rgba(27,122,61,0.08);
            color: var(--dole-primary);
            border: 1px solid rgba(27,122,61,0.15);
        }
        .tier-0 .org-role-badge {
            background: rgba(212,160,23,0.10);
            color: #8a6200;
            border-color: rgba(212,160,23,0.25);
        }
        .tier-2 .org-role-badge {
            background: rgba(30,107,184,0.08);
            color: #1452a0;
            border-color: rgba(30,107,184,0.20);
        }

        /* Vertical connector line */
        .org-vline {
            width: 2px;
            height: 32px;
            background: linear-gradient(180deg, rgba(27,122,61,0.40) 0%, rgba(27,122,61,0.15) 100%);
            flex-shrink: 0;
        }

        /* Row of children */
        .org-children-row {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 1.25rem;
            position: relative;
            width: 100%;
        }

        /* Horizontal bar across children */
        .org-hbar-wrap {
            position: relative;
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 0;
        }
        .org-hbar {
            height: 2px;
            background: linear-gradient(90deg,
                rgba(27,122,61,0.08) 0%,
                rgba(27,122,61,0.35) 20%,
                rgba(27,122,61,0.35) 80%,
                rgba(27,122,61,0.08) 100%
            );
            position: absolute;
            top: 0;
        }

        /* Per-child drop line */
        .org-child-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .org-child-drop {
            width: 2px;
            height: 30px;
            background: linear-gradient(180deg, rgba(27,122,61,0.30) 0%, rgba(27,122,61,0.12) 100%);
        }

        /* Animation delays for staggered entrance */
        .org-card { animation-delay: 0s; }
        .org-children-row .org-child-wrap:nth-child(1) .org-card { animation-delay: 0.10s; }
        .org-children-row .org-child-wrap:nth-child(2) .org-card { animation-delay: 0.18s; }
        .org-children-row .org-child-wrap:nth-child(3) .org-card { animation-delay: 0.26s; }
        .org-children-row .org-child-wrap:nth-child(4) .org-card { animation-delay: 0.34s; }
        .org-children-row .org-child-wrap:nth-child(5) .org-card { animation-delay: 0.42s; }

        @keyframes orgFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Office info strip */
        .org-meta-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            padding: 0.75rem 1rem;
            background: linear-gradient(90deg, rgba(27,122,61,0.04) 0%, rgba(27,122,61,0.07) 50%, rgba(27,122,61,0.04) 100%);
            border: 1px solid rgba(27,122,61,0.10);
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.82rem;
            color: #495057;
        }
        .org-meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .org-meta-item i {
            color: var(--dole-primary);
            font-size: 0.95rem;
        }
        .org-meta-item strong {
            color: #212529;
        }

        /* Responsive org chart */
        @media (max-width: 768px) {
            .org-children-row {
                flex-direction: column;
                align-items: center;
                gap: 0;
            }
            .org-hbar { display: none; }
            .org-hbar-wrap { display: none; }
            .org-child-wrap { width: 100%; max-width: 280px; }
            .org-card { min-width: 160px; max-width: 100%; width: 100%; }
            .about-section { padding: 1.25rem 1rem; }
        }

        /* ── Tech Stack ─────────────────────────────────────── */
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
        }
        .tech-card {
            background: #fff;
            border: 1.5px solid rgba(27,122,61,0.10);
            border-radius: 12px;
            padding: 1.1rem 0.9rem;
            text-align: center;
            transition: transform 0.20s ease, box-shadow 0.20s ease, border-color 0.20s ease;
            animation: orgFadeIn 0.4s ease both;
        }
        .tech-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.09);
            border-color: rgba(27,122,61,0.28);
        }
        .tech-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            margin: 0 auto 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
        }
        .tech-name {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a2e1a;
            margin-bottom: 0.25rem;
        }
        .tech-desc {
            font-size: 0.73rem;
            color: #6c757d;
            line-height: 1.4;
        }
        .tech-tag {
            display: inline-block;
            font-size: 0.67rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            margin-top: 0.4rem;
        }

        /* Stagger tech card animations */
        .tech-card:nth-child(1)  { animation-delay: 0.05s; }
        .tech-card:nth-child(2)  { animation-delay: 0.10s; }
        .tech-card:nth-child(3)  { animation-delay: 0.15s; }
        .tech-card:nth-child(4)  { animation-delay: 0.20s; }
        .tech-card:nth-child(5)  { animation-delay: 0.25s; }
        .tech-card:nth-child(6)  { animation-delay: 0.30s; }
        .tech-card:nth-child(7)  { animation-delay: 0.35s; }
        .tech-card:nth-child(8)  { animation-delay: 0.40s; }

        /* ── Org Chart Edit Mode ──────────────────────────────── */
        .org-edit-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding: 0.6rem 1rem;
            background: rgba(27,122,61,0.06);
            border: 1px dashed rgba(27,122,61,0.25);
            border-radius: 10px;
        }
        body.org-editing .org-card {
            cursor: default;
            outline: 2px dashed rgba(27,122,61,0.25);
            outline-offset: 3px;
        }
        .org-card-actions {
            display: none;
            gap: 0.3rem;
            justify-content: center;
            margin-top: 0.5rem;
        }
        body.org-editing .org-card-actions {
            display: flex;
        }
        .org-card-actions .btn {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            line-height: 1.2;
        }
        .org-inline-input {
            font-size: 0.85rem;
            border: 1px solid rgba(27,122,61,0.4);
            border-radius: 6px;
            padding: 0.2rem 0.45rem;
            width: 100%;
            text-align: center;
            background: rgba(27,122,61,0.04);
        }
        .org-inline-input:focus {
            outline: none;
            border-color: var(--dole-primary);
            box-shadow: 0 0 0 2px rgba(27,122,61,0.15);
        }
        .org-saving-spinner {
            display: none;
            width: 14px; height: 14px;
            border: 2px solid rgba(27,122,61,0.2);
            border-top-color: var(--dole-primary);
            border-radius: 50%;
            animation: orgSpin 0.6s linear infinite;
            flex-shrink: 0;
        }
        @keyframes orgSpin { to { transform: rotate(360deg); } }
        .org-add-staff-btn {
            border: 2px dashed rgba(27,122,61,0.35);
            border-radius: 14px;
            padding: 0.75rem 1rem;
            min-width: 160px;
            text-align: center;
            cursor: pointer;
            color: var(--dole-primary);
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(27,122,61,0.03);
            transition: all 0.2s ease;
            display: none;
        }
        body.org-editing .org-add-staff-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .org-add-staff-btn:hover {
            background: rgba(27,122,61,0.08);
            border-color: var(--dole-primary);
        }
    </style>
</head>
<body>
    <?php $currentPage = 'about'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main" aria-describedby="aboutDesc">

                <!-- ── Compact Page Header ───────────────────────────── -->
                <div class="about-page-header">
                    <div class="about-page-header-left">
                        <div class="about-page-icon" aria-hidden="true">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div>
                            <h1 class="about-page-title" id="aboutDesc">About &amp; Org Chart</h1>
                            <p class="about-page-subtitle">DOLE NOCFO · DILEEP Team Structure</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="about-version-badge">
                            <i class="bi bi-tag-fill"></i> v<?php echo htmlspecialchars($appVersion); ?>
                        </span>
                        <span class="about-version-badge">
                            <i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($release_year); ?>
                        </span>
                    </div>
                </div>

                <!-- ── Org Chart Section ─────────────────────────────── -->
                <div class="about-section">
                    <div class="section-heading">
                        <div class="section-heading-icon" aria-hidden="true">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h2>DILEEP-NOCFO Organizational Structure</h2>
                        <?php if ($isSuperAdmin): ?>
                        <div class="ms-auto">
                            <a href="org-chart-admin.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square"></i> Manage Org Chart
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <hr class="section-divider">

                    <!-- Meta info strip -->
                    <div class="org-meta-strip" role="complementary" aria-label="Office details">
                        <div class="org-meta-item">
                            <i class="bi bi-building"></i>
                            <span><strong>Office:</strong> DOLE Field Office</span>
                        </div>
                        <div class="org-meta-item">
                            <i class="bi bi-briefcase"></i>
                            <span><strong>Program:</strong> DILEEP</span>
                        </div>
                        <div class="org-meta-item">
                            <i class="bi bi-geo-alt"></i>
                            <span><strong>Coverage:</strong> <?php echo $isCrossProvince ? 'All Provinces' : htmlspecialchars($sessionProvince); ?></span>
                        </div>
                        <div class="org-meta-item">
                            <i class="bi bi-calendar-check"></i>
                            <span><strong>Updated:</strong> <?php echo htmlspecialchars($release_year); ?></span>
                        </div>
                    </div>

                    <?php if ($isCrossProvince && count($orgChartByProvince) > 1): ?>
                    <!-- Multi-province view with tabs -->
                    <ul class="nav nav-tabs mb-3" id="provinceOrgTabs" role="tablist">
                        <?php $firstProvince = true; foreach ($orgChartByProvince as $province => $data): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $firstProvince ? 'active' : ''; ?>" 
                                    id="tab-<?php echo htmlspecialchars(str_replace(' ', '-', $province)); ?>" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#org-<?php echo htmlspecialchars(str_replace(' ', '-', $province)); ?>" 
                                    type="button" role="tab">
                                <i class="bi bi-geo-alt-fill me-1"></i>
                                <?php echo htmlspecialchars($province); ?>
                            </button>
                        </li>
                        <?php $firstProvince = false; endforeach; ?>
                    </ul>

                    <div class="tab-content" id="provinceOrgTabContent">
                        <?php $firstProvince = true; foreach ($orgChartByProvince as $province => $data): ?>
                        <div class="tab-pane fade <?php echo $firstProvince ? 'show active' : ''; ?>" 
                             id="org-<?php echo htmlspecialchars(str_replace(' ', '-', $province)); ?>" 
                             role="tabpanel">
                            <div class="org-tree" data-province="<?php echo htmlspecialchars($province); ?>" role="img" aria-label="<?php echo htmlspecialchars($province); ?> Organizational Chart">
                                <div class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Loading <?php echo htmlspecialchars($province); ?> org chart…
                                </div>
                            </div>
                        </div>
                        <?php $firstProvince = false; endforeach; ?>
                    </div>
                    <?php else: ?>
                    <!-- Single province view -->
                    <?php foreach ($orgChartByProvince as $province => $data): ?>
                    <div class="mb-3">
                        <h5 class="text-muted mb-3">
                            <i class="bi bi-geo-alt-fill me-2"></i>
                            <?php echo htmlspecialchars($province); ?>
                        </h5>
                        <div class="org-tree" data-province="<?php echo htmlspecialchars($province); ?>" role="img" aria-label="<?php echo htmlspecialchars($province); ?> Organizational Chart">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Loading org chart…
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div><!-- /.about-section (org chart) -->

                <!-- ── Tech Stack Section ────────────────────────────── -->
                <div class="about-section">
                    <div class="section-heading">
                        <div class="section-heading-icon" aria-hidden="true">
                            <i class="bi bi-stack"></i>
                        </div>
                        <h2>Tech Stack</h2>
                    </div>
                    <hr class="section-divider">

                    <div class="tech-grid">

                        <!-- PHP -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#4f5b93,#787cb5);">
                                <i class="bi bi-filetype-php"></i>
                            </div>
                            <div class="tech-name">PHP</div>
                            <div class="tech-desc">Server-side scripting & application logic</div>
                            <span class="tech-tag" style="background:rgba(79,91,147,0.10);color:#4f5b93;border:1px solid rgba(79,91,147,0.20);">Backend</span>
                        </div>

                        <!-- MySQL -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#00758f,#00a3c1);">
                                <i class="bi bi-database-fill"></i>
                            </div>
                            <div class="tech-name">MySQL</div>
                            <div class="tech-desc">Relational database for records & storage</div>
                            <span class="tech-tag" style="background:rgba(0,117,143,0.10);color:#00758f;border:1px solid rgba(0,117,143,0.20);">Database</span>
                        </div>

                        <!-- Bootstrap 5 -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#563d7c,#7952b3);">
                                <i class="bi bi-bootstrap-fill"></i>
                            </div>
                            <div class="tech-name">Bootstrap 5</div>
                            <div class="tech-desc">Responsive UI framework & grid system</div>
                            <span class="tech-tag" style="background:rgba(86,61,124,0.10);color:#563d7c;border:1px solid rgba(86,61,124,0.20);">Frontend</span>
                        </div>

                        <!-- Bootstrap Icons -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#1B7A3D,#3aaa64);">
                                <i class="bi bi-grid-1x2-fill"></i>
                            </div>
                            <div class="tech-name">Bootstrap Icons</div>
                            <div class="tech-desc">Open-source SVG icon library</div>
                            <span class="tech-tag" style="background:rgba(27,122,61,0.10);color:var(--dole-primary);border:1px solid rgba(27,122,61,0.20);">UI</span>
                        </div>

                        <!-- Chart.js -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#d63333,#ff6384);">
                                <i class="bi bi-bar-chart-fill"></i>
                            </div>
                            <div class="tech-name">Chart.js</div>
                            <div class="tech-desc">Animated canvas-based data visualizations</div>
                            <span class="tech-tag" style="background:rgba(214,51,51,0.10);color:#d63333;border:1px solid rgba(214,51,51,0.20);">Data Viz</span>
                        </div>

                        <!-- JavaScript -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#b8860b,#f0c030);">
                                <i class="bi bi-filetype-js"></i>
                            </div>
                            <div class="tech-name">JavaScript</div>
                            <div class="tech-desc">Interactive UI behavior & client logic</div>
                            <span class="tech-tag" style="background:rgba(184,134,11,0.10);color:#b8860b;border:1px solid rgba(184,134,11,0.20);">Frontend</span>
                        </div>

                        <!-- HTML5 / CSS3 -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#c0390b,#e8522a);">
                                <i class="bi bi-filetype-html"></i>
                            </div>
                            <div class="tech-name">HTML5 / CSS3</div>
                            <div class="tech-desc">Semantic markup & custom styling</div>
                            <span class="tech-tag" style="background:rgba(192,57,11,0.10);color:#c0390b;border:1px solid rgba(192,57,11,0.20);">Markup</span>
                        </div>

                        <!-- XAMPP -->
                        <div class="tech-card">
                            <div class="tech-icon" style="background:linear-gradient(135deg,#e67e22,#f39c12);">
                                <i class="bi bi-server"></i>
                            </div>
                            <div class="tech-name">XAMPP</div>
                            <div class="tech-desc">Local Apache + MySQL dev environment</div>
                            <span class="tech-tag" style="background:rgba(230,126,34,0.10);color:#c47209;border:1px solid rgba(230,126,34,0.20);">Dev Env</span>
                        </div>

                    </div><!-- /.tech-grid -->
                </div><!-- /.about-section (tech stack) -->

            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>

    <script>
    (function () {
        'use strict';

        const API = 'api/org-chart.php';
        let orgNodes = [];

        function initials(name) {
            if (!name || name === 'Vacant') return '?';
            return name.split(/\s+/).map(w => w[0]).join('').toUpperCase().slice(0, 2);
        }

        // ── Render ───────────────────────────────────────────────────────
        function renderOrgTree(treeElement, provinceNodes) {
            if (!treeElement) return;
            treeElement.innerHTML = '';

            // Group by tier, preserve sort_order
            const byTier = {};
            provinceNodes.forEach(n => {
                if (!byTier[n.tier]) byTier[n.tier] = [];
                byTier[n.tier].push(n);
            });

            const tiers = Object.keys(byTier).map(Number).sort((a, b) => a - b);
            // Track hbar/row elements per tier for repositioning
            const hbarElements = [];

            tiers.forEach((tier, idx) => {
                const nodes     = byTier[tier];
                const isLast    = (idx === tiers.length - 1);

                if (nodes.length === 1) {
                    // ── Single node ──────────────────────────────────────
                    treeElement.appendChild(makeNodeEl(nodes[0], tier));

                    if (!isLast) {
                        const vline = document.createElement('div');
                        vline.className = 'org-vline';
                        treeElement.appendChild(vline);
                    }
                } else {
                    // ── Multiple nodes (row layout) ──────────────────────
                    // If the previous tier was single, its vline is already appended.
                    // If this is the first tier and it has multiple nodes, no vline needed.

                    const rowId    = 'orgChildrenRow-' + tier + '-' + Math.random().toString(36).substr(2, 9);
                    const hbarId   = 'orgHbar-' + tier + '-' + Math.random().toString(36).substr(2, 9);
                    const wrapId   = 'orgHbarWrap-' + tier + '-' + Math.random().toString(36).substr(2, 9);

                    // Horizontal bar wrap
                    const hbarWrap = document.createElement('div');
                    hbarWrap.className = 'org-hbar-wrap';
                    hbarWrap.id        = wrapId;
                    const hbar         = document.createElement('div');
                    hbar.className     = 'org-hbar';
                    hbar.id            = hbarId;
                    hbarWrap.appendChild(hbar);
                    treeElement.appendChild(hbarWrap);

                    // Children row
                    const row = document.createElement('div');
                    row.className = 'org-children-row';
                    row.id        = rowId;

                    nodes.forEach(n => {
                        const wrap = document.createElement('div');
                        wrap.className = 'org-child-wrap';
                        const drop = document.createElement('div');
                        drop.className = 'org-child-drop';
                        wrap.appendChild(drop);
                        wrap.appendChild(makeNodeEl(n, tier));
                        row.appendChild(wrap);
                    });

                    treeElement.appendChild(row);
                    hbarElements.push({ rowId, hbarId, wrapId });

                    if (!isLast) {
                        const vline = document.createElement('div');
                        vline.className = 'org-vline';
                        treeElement.appendChild(vline);
                    }
                }
            });

            // Reposition all hbars after layout paint
            requestAnimationFrame(() => {
                hbarElements.forEach(({ rowId, hbarId, wrapId }) => {
                    positionHbar(rowId, hbarId, wrapId);
                });
            });
        }

        function makeNodeEl(n, tier) {
            const card = document.createElement('div');
            card.className = `org-card tier-${Math.min(tier, 3)}`;
            card.dataset.id = n.id;

            const avatar = document.createElement('div');
            avatar.className = 'org-card-avatar';
            avatar.textContent = initials(n.name);

            const nameEl = document.createElement('div');
            nameEl.className = 'org-name';
            nameEl.textContent = n.name;

            const roleEl = document.createElement('span');
            roleEl.className = 'org-role-badge';
            roleEl.textContent = n.role_label;

            card.appendChild(avatar);
            card.appendChild(nameEl);
            card.appendChild(roleEl);

            return card;
        }


        // ── Load from API ────────────────────────────────────────────────
        async function loadNodes() {
            try {
                const res  = await fetch(API);
                const json = await res.json();
                if (json.success) {
                    orgNodes = json.data;
                    
                    // Find all org-tree elements and render for each province
                    const orgTrees = document.querySelectorAll('.org-tree');
                    orgTrees.forEach(treeEl => {
                        const province = treeEl.dataset.province;
                        if (province) {
                            // Filter nodes by province
                            const provinceNodes = orgNodes.filter(n => 
                                (n.province || 'Negros Occidental') === province
                            );
                            renderOrgTree(treeEl, provinceNodes);
                        } else {
                            // Legacy: render all nodes (no province filtering)
                            renderOrgTree(treeEl, orgNodes);
                        }
                    });
                }
            } catch (e) {
                const orgTrees = document.querySelectorAll('.org-tree');
                orgTrees.forEach(treeEl => {
                    treeEl.innerHTML = '<div class="alert alert-warning">Could not load org chart data.</div>';
                });
            }
        }

        // ── Hbar positioning (generic, by ID) ────────────────────────────
        function positionHbar(rowId, hbarId, wrapId) {
            const row  = document.getElementById(rowId);
            const bar  = document.getElementById(hbarId);
            const wrap = document.getElementById(wrapId);
            if (!row || !bar || !wrap) return;

            const children = Array.from(row.querySelectorAll('.org-child-wrap'));
            if (children.length < 2 || window.innerWidth <= 768) {
                bar.style.display = 'none';
                return;
            }
            bar.style.display = 'block';

            const wrapRect = wrap.getBoundingClientRect();
            const first    = children[0].getBoundingClientRect();
            const last     = children[children.length - 1].getBoundingClientRect();
            bar.style.left  = (first.left  - wrapRect.left + first.width  / 2) + 'px';
            bar.style.width = (last.right - wrapRect.left - last.width / 2
                               - (first.left - wrapRect.left + first.width / 2)) + 'px';
        }

        function repositionAllHbars() {
            // Find all hbar wraps and recompute
            document.querySelectorAll('[id^="orgHbarWrap-"]').forEach(wrapEl => {
                const tier   = wrapEl.id.replace('orgHbarWrap-', '');
                positionHbar('orgChildrenRow-' + tier, 'orgHbar-' + tier, 'orgHbarWrap-' + tier);
            });
        }

        window.addEventListener('resize', repositionAllHbars);

        // ── Bootstrap ────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', loadNodes);
    })();
    </script>
</body>
</html>
