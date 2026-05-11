<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$appName = 'DOLE DILEEP Monitoring System';
if (file_exists(__DIR__ . '/VERSION')) {
    $appVersion = trim(file_get_contents(__DIR__ . '/VERSION')) ?: '1.0.0';
} else {
    $appVersion = '1.0.0';
}
$release_year = date('Y');
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
                    </div>
                    <hr class="section-divider">

                    <!-- Meta info strip -->
                    <div class="org-meta-strip" role="complementary" aria-label="Office details">
                        <div class="org-meta-item">
                            <i class="bi bi-building"></i>
                            <span><strong>Office:</strong> DOLE Negros Occidental Field Office</span>
                        </div>
                        <div class="org-meta-item">
                            <i class="bi bi-briefcase"></i>
                            <span><strong>Program:</strong> DILEEP</span>
                        </div>
                        <div class="org-meta-item">
                            <i class="bi bi-geo-alt"></i>
                            <span><strong>Region:</strong> Negros Occidental</span>
                        </div>
                        <div class="org-meta-item">
                            <i class="bi bi-calendar-check"></i>
                            <span><strong>Updated:</strong> <?php echo htmlspecialchars($release_year); ?></span>
                        </div>
                    </div>

                    <!-- Org Tree -->
                    <div class="org-tree" role="img" aria-label="DILEEP-NOCFO Organizational Chart">

                        <!-- Tier 0: Regional Director -->
                        <div class="org-node">
                            <div class="org-card tier-0">
                                <div class="org-card-avatar">RB</div>
                                <div class="org-name">ATTY. ROY L. BUENAFE</div>
                                <span class="org-role-badge">Regional Director</span>
                            </div>
                        </div>

                        <div class="org-vline"></div>

                        <!-- Tier 1: OIC -->
                        <div class="org-node">
                            <div class="org-card tier-1">
                                <div class="org-card-avatar">GP</div>
                                <div class="org-name">MS. GRETCHEN I. PASIOLAN</div>
                                <span class="org-role-badge">OIC – DOLE NOCFO</span>
                            </div>
                        </div>

                        <div class="org-vline"></div>

                        <!-- Tier 2: Senior LEO / Focal -->
                        <div class="org-node">
                            <div class="org-card tier-2">
                                <div class="org-card-avatar">MD</div>
                                <div class="org-name">ENGR. MILSON DELOS REYES</div>
                                <span class="org-role-badge">Senior LEO / DILEEP Focal</span>
                            </div>
                        </div>

                        <!-- Horizontal bar spanning the 5 children -->
                        <div class="org-vline"></div>
                        <div class="org-hbar-wrap" id="orgHbarWrap">
                            <div class="org-hbar" id="orgHbar"></div>
                        </div>

                        <!-- Tier 3: Staff row -->
                        <div class="org-children-row" id="orgChildrenRow">

                            <div class="org-child-wrap">
                                <div class="org-child-drop"></div>
                                <div class="org-card tier-3">
                                    <div class="org-card-avatar">KA</div>
                                    <div class="org-name">MS. KAYZEL ARANETA</div>
                                    <span class="org-role-badge">LDS</span>
                                </div>
                            </div>

                            <div class="org-child-wrap">
                                <div class="org-child-drop"></div>
                                <div class="org-card tier-3">
                                    <div class="org-card-avatar">JC</div>
                                    <div class="org-name">MS. JONA CEPRIANO</div>
                                    <span class="org-role-badge">LDS</span>
                                </div>
                            </div>

                            <div class="org-child-wrap">
                                <div class="org-child-drop"></div>
                                <div class="org-card tier-3">
                                    <div class="org-card-avatar">YG</div>
                                    <div class="org-name">MS. YZABEL GANE</div>
                                    <span class="org-role-badge">TUPAD Coordinator</span>
                                </div>
                            </div>

                            <div class="org-child-wrap">
                                <div class="org-child-drop"></div>
                                <div class="org-card tier-3">
                                    <div class="org-card-avatar">IJ</div>
                                    <div class="org-name">MS. IELIZ JOVER</div>
                                    <span class="org-role-badge">TUPAD Coordinator</span>
                                </div>
                            </div>

                            <div class="org-child-wrap">
                                <div class="org-child-drop"></div>
                                <div class="org-card tier-3">
                                    <div class="org-card-avatar">EP</div>
                                    <div class="org-name">MR. ELZIAKIM PEGAR</div>
                                    <span class="org-role-badge">IT Specialist</span>
                                </div>
                            </div>

                        </div><!-- /.org-children-row -->
                    </div><!-- /.org-tree -->
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

        /**
         * Draw the horizontal connector bar that spans across the five
         * child cards in the org chart. Measured after layout is stable.
         */
        function positionOrgHbar() {
            const row   = document.getElementById('orgChildrenRow');
            const bar   = document.getElementById('orgHbar');
            const wrap  = document.getElementById('orgHbarWrap');
            if (!row || !bar || !wrap) return;

            const children = row.querySelectorAll('.org-child-wrap');
            if (children.length < 2) return;

            // On mobile the children stack vertically — hide bar
            if (window.innerWidth <= 768) {
                bar.style.display = 'none';
                return;
            }
            bar.style.display = 'block';

            const wrapRect  = wrap.getBoundingClientRect();
            const firstRect = children[0].getBoundingClientRect();
            const lastRect  = children[children.length - 1].getBoundingClientRect();

            const left  = firstRect.left  - wrapRect.left  + firstRect.width  / 2;
            const right = lastRect.right  - wrapRect.left  - lastRect.width   / 2;

            bar.style.left  = left  + 'px';
            bar.style.width = (right - left) + 'px';
        }

        // Run on load and on resize
        window.addEventListener('load',   positionOrgHbar);
        window.addEventListener('resize', positionOrgHbar);
        document.addEventListener('DOMContentLoaded', positionOrgHbar);
    })();
    </script>
</body>
</html>
