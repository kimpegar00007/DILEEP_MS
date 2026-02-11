<!-- Skip to Content - Accessibility -->
<a href="#mainContent" class="skip-to-content">Skip to main content</a>

<!-- Navigation – Glass Morphism Header -->
<nav class="navbar navbar-expand-lg navbar-glass" role="navigation" aria-label="Main navigation">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/dilp-logo.png" alt="DILP Logo" class="navbar-logo">
            <span class="navbar-brand-text">DOLE DILEEP <span class="navbar-brand-sub">Monitoring System</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php include __DIR__ . '/notification-bell.php'; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle navbar-user-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                        <span class="navbar-avatar"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
                        <span class="navbar-username"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end navbar-dropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person" aria-hidden="true"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="change-password.php"><i class="bi bi-key" aria-hidden="true"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item dropdown-item-logout" href="logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="navbar-accent-bar" aria-hidden="true"></div>
</nav>
