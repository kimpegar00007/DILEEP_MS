<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
// App metadata (attempt to read from a VERSION file if present)
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
        .about-hero {
            background: linear-gradient(135deg, var(--dole-primary) 0%, var(--dole-secondary) 100%);
            color: white;
            padding: 3rem 0;
            border-radius: var(--dole-border-radius);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            min-height: 200px;
            display: flex;
            align-items: center;
        }
        
        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.1;
        }
        
        .about-hero-content {
            position: relative;
            z-index: 1;
            width: 100%;
        }
        
        .about-hero .display-4 {
            margin-bottom: 1rem;
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .about-hero .lead {
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }
        
        @media (max-width: 768px) {
            .about-hero .display-4 {
                font-size: 2rem;
            }
            
            .about-hero {
                padding: 2rem 0;
                min-height: auto;
            }
            
            .about-hero .lead {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 992px) {
            .about-hero .lead {
                font-size: 1.1rem;
            }
        }
        
        .developer-card {
            background: white;
            border-radius: var(--dole-border-radius);
            box-shadow: var(--dole-box-shadow);
            padding: 2rem;
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .developer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .developer-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--dole-primary);
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(27, 122, 61, 0.2);
        }
        
        .developer-name {
            color: var(--dole-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .developer-alias {
            color: var(--dole-accent);
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .developer-bio {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .tech-badge {
            background: rgba(27, 122, 61, 0.1);
            color: var(--dole-primary);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid rgba(27, 122, 61, 0.2);
            transition: all 0.2s ease;
        }
        
        .tech-badge:hover {
            background: var(--dole-primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .feature-card {
            background: white;
            border-radius: var(--dole-border-radius);
            padding: 1.5rem;
            height: 100%;
            transition: all 0.2s ease;
            border-left: 4px solid var(--dole-primary);
        }
        
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(27, 122, 61, 0.1);
            color: var(--dole-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .version-info {
            background: rgba(27, 122, 61, 0.05);
            border-radius: var(--dole-border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #495057;
        }
        
        .contact-item i {
            color: var(--dole-primary);
            width: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .about-hero {
                padding: 2rem 0;
            }
            
            .developer-avatar {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <?php $currentPage = 'about'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 py-4" id="mainContent" role="main" aria-describedby="aboutDesc">
                <!-- Hero Section -->
                <div class="about-hero">
                    <div class="about-hero-content p-4">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h1 class="display-4 fw-bold mb-4">About the DILEEP Monitoring System</h1>
                                <p id="aboutDesc" class="lead mb-0">A comprehensive web-based platform for monitoring and managing Department of Labor and Employment - DILEEP program beneficiaries and proponents across Negros Occidental.</p>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex flex-column align-items-lg-end align-items-start text-lg-end text-start gap-3 mt-3 mt-lg-0">
                                    <div class="text-center text-lg-end" aria-hidden="false">
                                        <div class="h6 mb-1 opacity-75">Version</div>
                                        <div class="h4 mb-0"><?php echo htmlspecialchars($appVersion); ?></div>
                                    </div>
                                    <div class="text-center text-lg-end">
                                        <div class="h6 mb-1 opacity-75">Released</div>
                                        <div class="h4 mb-0"><?php echo htmlspecialchars($release_year); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Developer Section -->
                <div class="row mb-4">
                    <div class="col-lg-8 mx-auto">
                        <div class="developer-card">
                            <img src="assets/m1k0yw0rkz.png" alt="Elziakim Pegar — developer" class="developer-avatar">
                            <h2 class="developer-name">m1k0yw0rkz</h2>
                            <div class="developer-alias">Elziakim Pegar</div>
                            <p class="developer-bio">
                                "Programmers are tools for converting caffeine into code"
                            </p>
                            <!--
                            <div class="row text-start">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3"><i class="bi bi-person-badge me-2"></i>Role & Expertise</h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>System Architecture & Design</li>
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Full-Stack Development</li>
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Database Design & Optimization</li>
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>UI/UX Design</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3"><i class="bi bi-envelope me-2"></i>Contact</h5>
                                    <div class="contact-item">
                                        <i class="bi bi-envelope"></i>
                                        <span>m1k0yw0rkz@example.com</span>
                                    </div>
                                    <div class="contact-item">
                                        <i class="bi bi-github"></i>
                                        <span>github.com/m1k0yw0rkz</span>
                                    </div>
                                    <div class="contact-item">
                                        <i class="bi bi-linkedin"></i>
                                        <span>linkedin.com/in/elziakim-pegar</span>
                                    </div>
                                </div>
                            </div>
                            -->
                            <div class="tech-stack">
                                <span class="tech-badge">PHP</span>
                                <span class="tech-badge">MySQL</span>
                                <span class="tech-badge">Bootstrap 5</span>
                                <span class="tech-badge">JavaScript</span>
                                <span class="tech-badge">HTML5</span>
                                <span class="tech-badge">CSS3</span>
                                <span class="tech-badge">Leaflet.js</span>
                                <span class="tech-badge">Chart.js</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Features -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <h5 class="text-primary">Beneficiary Management</h5>
                            <p class="text-muted">Comprehensive tracking of individual beneficiaries with detailed profiles, project information, and status monitoring.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-eye-fill"></i>
                            </div>
                            <h5 class="text-primary">Proponent Oversight</h5>
                            <p class="text-muted">Streamlined management of group proponents including LGUs and non-LGU organizations with liquidation tracking.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <h5 class="text-primary">Geographic Visualization</h5>
                            <p class="text-muted">Interactive mapping system showing project distribution across municipalities and cities in Negros Occidental.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-file-text-fill"></i>
                            </div>
                            <h5 class="text-primary">Reporting & Analytics</h5>
                            <p class="text-muted">Advanced reporting capabilities with statistical analysis, trend monitoring, and exportable reports.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-shield-fill-check"></i>
                            </div>
                            <h5 class="text-primary">Security & Access Control</h5>
                            <p class="text-muted">Role-based authentication system ensuring data security and appropriate access levels for different user types.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <h5 class="text-primary">Real-time Notifications</h5>
                            <p class="text-muted">Automated alert system for important deadlines, overdue liquidations, and system updates.</p>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="version-info">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>System Information</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <strong>Framework:</strong> Custom PHP
                                    </div>
                                    <div class="mb-2">
                                        <strong>Database:</strong> MySQL 8.0
                                    </div>
                                    <div class="mb-2">
                                        <strong>Frontend:</strong> Bootstrap 5.3
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <strong>Maps:</strong> Leaflet.js
                                    </div>
                                    <div class="mb-2">
                                        <strong>Icons:</strong> Bootstrap Icons
                                    </div>
                                    <div class="mb-2">
                                        <strong>Charts:</strong> Chart.js
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3"><i class="bi bi-gear me-2"></i>Technical Features</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <strong>Responsive Design:</strong> ✓
                                    </div>
                                    <div class="mb-2">
                                        <strong>Accessibility:</strong> WCAG 2.1
                                    </div>
                                    <div class="mb-2">
                                        <strong>Browser Support:</strong> Modern
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <strong>Session Management:</strong> ✓
                                    </div>
                                    <div class="mb-2">
                                        <strong>Data Validation:</strong> ✓
                                    </div>
                                    <div class="mb-2">
                                        <strong>Error Handling:</strong> ✓
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/notification-script.php'; ?>
    <?php include 'includes/ux-utilities.php'; ?>
</body>
</html>
