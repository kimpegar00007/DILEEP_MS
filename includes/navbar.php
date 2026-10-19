<?php
// navbar.php requires $auth to be available in the including scope.
// Every page already instantiates it before including navbar.
$_navRole     = $_SESSION['role']     ?? '';
$_navProvince = $_SESSION['province'] ?? null;
$_navIsSuperAdmin = ($_navRole === 'super_admin');
?>
<!-- CSRF: token for every POST form and non-GET fetch (checked in Auth::isLoggedIn) -->
<meta name="csrf-token" content="<?php echo htmlspecialchars(Auth::csrfToken()); ?>">
<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function addTokenToForm(form) {
        if (!form || (form.method || '').toLowerCase() !== 'post') return;
        var input = form.querySelector('input[name="csrf_token"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            form.appendChild(input);
        }
        input.value = token;
    }

    // Normal submits (capture phase, before page handlers) and programmatic form.submit()
    document.addEventListener('submit', function (e) { addTokenToForm(e.target); }, true);
    var nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () { addTokenToForm(this); return nativeSubmit.call(this); };
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(addTokenToForm);
    });

    // Same-origin fetch() calls that change data get the X-CSRF-Token header
    var nativeFetch = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || (input && input.method) || 'GET').toUpperCase();
        var url = typeof input === 'string' ? input : (input && input.url) || '';
        var sameOrigin = new URL(url, window.location.href).origin === window.location.origin;
        if (sameOrigin && ['GET', 'HEAD', 'OPTIONS'].indexOf(method) === -1) {
            var headers = new Headers(init.headers || (input && input.headers) || {});
            headers.set('X-CSRF-Token', token);
            init = Object.assign({}, init, { headers: headers });
        }
        return nativeFetch.call(this, input, init);
    };
})();
</script>

<!-- Skip to Content - Accessibility -->
<a href="#mainContent" class="skip-to-content">Skip to main content</a>

<!-- Navigation – Glass Morphism Header -->
<nav class="navbar navbar-expand-lg navbar-glass" role="navigation" aria-label="Main navigation">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/dilp-logo.png" alt="DILP Logo" class="navbar-logo">
            <!-- Partner logos: Bagong Pilipinas and DOLE -->
            <img src="assets/Bagong_Pilipinas_logo.png" alt="Bagong Pilipinas Logo" class="navbar-logo partner-logo" style="height:40px; width:auto; margin-left:8px;">
            <img src="assets/dole.png" alt="DOLE Logo" class="navbar-logo partner-logo" style="height:40px; width:auto; margin-left:8px;">
            <span class="navbar-brand-text">DILEEP System<span class="navbar-brand-sub" title="Department of Labor and Employment - Integrated Livelihood and Emergency Employment Program - Monitoring System">Department of Labor and Employment - Integrated Livelihood and Emergency Employment Program - Monitoring System</span></span>
        </a>
        <!-- Bell sits outside the collapsible menu so it stays visible on phones -->
        <ul class="navbar-nav flex-row align-items-center navbar-bell order-lg-last ms-lg-2 me-2 me-lg-0">
            <?php include __DIR__ . '/notification-bell.php'; ?>
        </ul>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">

                <!-- Province / role identity badge -->
                <li class="nav-item d-none d-md-flex align-items-center me-2">
                    <?php if ($_navIsSuperAdmin): ?>
                        <span class="navbar-province-badge badge-super-admin" title="Super Admin — all provinces">
                            <i class="bi bi-globe2" aria-hidden="true"></i> Super Admin
                        </span>
                    <?php elseif ($_navProvince): ?>
                        <span class="navbar-province-badge" title="Your data is scoped to <?php echo htmlspecialchars($_navProvince); ?>">
                            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($_navProvince); ?>
                        </span>
                    <?php endif; ?>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle navbar-user-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                        <span class="navbar-avatar"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
                        <span class="navbar-username"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end navbar-dropdown">
                        <?php if ($_navProvince || $_navIsSuperAdmin): ?>
                        <!-- Province identity shown in dropdown for mobile / confirmation -->
                        <li class="dropdown-header navbar-dropdown-province">
                            <?php if ($_navIsSuperAdmin): ?>
                                <i class="bi bi-globe2"></i> Super Admin
                            <?php else: ?>
                                <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($_navProvince); ?>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
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
