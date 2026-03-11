<?php
// Simple maintenance page — kept minimal and themed with shared styles
// This page should be accessible even when maintenance mode is active.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php if (file_exists(__DIR__ . '/includes/shared-styles.php')) include __DIR__ . '/includes/shared-styles.php'; ?>
    <style>
        body { background: var(--dole-bg, #f6f8fb); }
        .maintenance-card { max-width: 820px; margin: 6vh auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: var(--dole-box-shadow); }
        .maintenance-illustration { font-size: 4rem; color: var(--dole-primary); }
    </style>
</head>
<body>
    <div class="container">
        <div class="maintenance-card text-center">
            <div class="mb-3">
                <img src="assets/dilp-logo.png" alt="DILP" style="height:64px;" />
            </div>
            <div class="mb-3 maintenance-illustration"><i class="bi bi-tools"></i></div>
            <h1 class="h3 mb-2">We'll be right back</h1>
            <p class="text-muted">The site is currently undergoing scheduled maintenance. We apologize for the inconvenience. Administrators can still sign in below.</p>

            <div class="mt-4">
                <!-- If a non-admin user is currently logged in, redirect them to logout first so admin can sign in -->
                <a href="logout.php" class="btn btn-primary me-2"><i class="bi bi-box-arrow-in-right"></i> Admin Login</a>
                <a href="mailto:support@example.com" class="btn btn-outline-secondary"><i class="bi bi-envelope"></i> Contact Support</a>
            </div>

            <p class="text-muted small mt-4">If you believe this is an error, contact your system administrator.</p>
        </div>
    </div>
</body>
</html>
