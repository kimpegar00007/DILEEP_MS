<!-- Sidebar Navigation -->
<nav class="col-md-2 d-md-block sidebar p-3 no-print" role="navigation" aria-label="Sidebar navigation">
    <div class="position-sticky sidebar-inner">
        <div class="sidebar-brand">
            <span class="sidebar-brand-icon"><i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i></span>
            <span class="sidebar-brand-text">Navigation</span>
        </div>
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>" href="index.php" aria-current="<?php echo ($currentPage ?? '') === 'dashboard' ? 'page' : 'false'; ?>">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'beneficiaries' ? 'active' : ''; ?>" href="beneficiaries.php" aria-current="<?php echo ($currentPage ?? '') === 'beneficiaries' ? 'page' : 'false'; ?>">
                    <i class="bi bi-person" aria-hidden="true"></i> <span>Beneficiaries</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'proponents' ? 'active' : ''; ?>" href="proponents.php" aria-current="<?php echo ($currentPage ?? '') === 'proponents' ? 'page' : 'false'; ?>">
                    <i class="bi bi-people" aria-hidden="true"></i> <span>Proponents</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'reports' ? 'active' : ''; ?>" href="reports.php" aria-current="<?php echo ($currentPage ?? '') === 'reports' ? 'page' : 'false'; ?>">
                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i> <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'fieldwork-schedule' ? 'active' : ''; ?>" href="fieldwork-schedule.php" aria-current="<?php echo ($currentPage ?? '') === 'fieldwork-schedule' ? 'page' : 'false'; ?>">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i> <span>Fieldwork Schedule</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'about' ? 'active' : ''; ?>" href="about.php" aria-current="<?php echo ($currentPage ?? '') === 'about' ? 'page' : 'false'; ?>">
                    <i class="bi bi-info-circle" aria-hidden="true"></i> <span>About</span>
                </a>
            </li>
            <?php if ($auth->hasRole('admin')): ?>
            <li class="sidebar-divider"></li>
            <li class="sidebar-section-label">Administration</li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'users' ? 'active' : ''; ?>" href="users.php" aria-current="<?php echo ($currentPage ?? '') === 'users' ? 'page' : 'false'; ?>">
                    <i class="bi bi-people-fill" aria-hidden="true"></i> <span>User Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'activity-logs' ? 'active' : ''; ?>" href="activity-logs.php" aria-current="<?php echo ($currentPage ?? '') === 'activity-logs' ? 'page' : 'false'; ?>">
                    <i class="bi bi-clock-history" aria-hidden="true"></i> <span>Activity Logs</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage ?? '') === 'settings' ? 'active' : ''; ?>" href="settings.php" aria-current="<?php echo ($currentPage ?? '') === 'settings' ? 'page' : 'false'; ?>">
                    <i class="bi bi-gear" aria-hidden="true"></i> <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-accent" aria-hidden="true"></div>
    </div>
</nav>
