<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$error = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if ($auth->login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DOLE DILP Monitoring System</title>
    <link rel="icon" href="assets/dileep-logo.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow: hidden;
            background: #e5f0e8;
        }

        /* ── Animated gradient mesh background ── */
        .login-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 40%, rgba(27, 122, 61, 0.35) 0%, transparent 70%),
                radial-gradient(ellipse 60% 80% at 85% 25%, rgba(212, 160, 23, 0.30) 0%, transparent 70%),
                radial-gradient(ellipse 70% 50% at 50% 90%, rgba(30, 107, 184, 0.28) 0%, transparent 70%),
                radial-gradient(ellipse 50% 40% at 30% 10%, rgba(240, 180, 41, 0.22) 0%, transparent 60%),
                linear-gradient(135deg, #e5f0e8 0%, #eef5e8 40%, #e0eef5 70%, #f0efe5 100%);
            animation: meshShift 12s ease-in-out infinite alternate;
        }

        @keyframes meshShift {
            0%   { background-position: 0% 0%, 100% 0%, 50% 100%, 30% 10%, 0% 0%; }
            25%  { background-position: 20% 30%, 80% 10%, 40% 80%, 50% 20%, 0% 0%; }
            50%  { background-position: 10% 50%, 90% 40%, 60% 70%, 20% 40%, 0% 0%; }
            75%  { background-position: 30% 20%, 70% 50%, 50% 90%, 40% 30%, 0% 0%; }
            100% { background-position: 5% 10%, 95% 30%, 45% 85%, 35% 15%, 0% 0%; }
        }

        /* ── Floating glass orbs ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 2px solid rgba(255, 255, 255, 0.30);
            box-shadow:
                0 8px 32px rgba(255, 255, 255, 0.20),
                inset 0 1px 0 rgba(255, 255, 255, 0.40);
        }

        .orb--1 {
            width: 140px; height: 140px;
            top: 15%; left: 8%;
            opacity: 0.50;
            animation: orbFloat1 8s ease-in-out infinite;
        }
        .orb--2 {
            width: 100px; height: 100px;
            bottom: 18%; right: 12%;
            opacity: 0.40;
            animation: orbFloat2 10s ease-in-out infinite;
        }
        .orb--3 {
            width: 70px; height: 70px;
            top: 50%; right: 25%;
            opacity: 0.35;
            animation: orbFloat3 7s ease-in-out infinite;
        }
        .orb--4 {
            width: 50px; height: 50px;
            bottom: 30%; left: 20%;
            opacity: 0.30;
            animation: orbFloat4 9s ease-in-out infinite;
        }

        @keyframes orbFloat1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(30px, -25px) scale(1.05); }
        }
        @keyframes orbFloat2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(-20px, 20px) scale(0.95); }
        }
        @keyframes orbFloat3 {
            0%, 100% { transform: translate(0, 0); }
            50%      { transform: translate(-15px, -30px); }
        }
        @keyframes orbFloat4 {
            0%, 100% { transform: translate(0, 0); }
            50%      { transform: translate(25px, 15px); }
        }

        /* ── Glass card ── */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 1rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(40px) saturate(250%);
            -webkit-backdrop-filter: blur(40px) saturate(250%);
            border: 1px solid rgba(255, 255, 255, 0.40);
            border-radius: 1.25rem;
            box-shadow:
                0 32px 80px rgba(0, 0, 0, 0.12),
                0 16px 64px rgba(255, 255, 255, 0.15),
                inset 0 3px 0 rgba(255, 255, 255, 0.60),
                inset 0 -1px 0 rgba(255, 255, 255, 0.30);
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-card:hover {
            transform: translateY(-3px);
            box-shadow:
                0 36px 90px rgba(0, 0, 0, 0.15),
                0 20px 70px rgba(255, 255, 255, 0.18),
                inset 0 3px 0 rgba(255, 255, 255, 0.60),
                inset 0 -1px 0 rgba(255, 255, 255, 0.30);
        }

        /* ── Header ── */
        .login-header {
            padding: 2rem 2rem 0.75rem;
            text-align: center;
        }

        .login-header img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 0.75rem;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.10));
        }

        .login-header h4 {
            font-size: 1.65rem;
            font-weight: 700;
            color: #1a2e1a;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .login-header p {
            color: rgba(55, 65, 81, 0.70);
            font-size: 0.9rem;
            font-weight: 400;
            margin-bottom: 0;
        }

        /* ── Body ── */
        .login-body {
            padding: 1.5rem 2rem 2rem;
        }

        /* ── Labels ── */
        .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.4rem;
        }

        /* ── Inputs ── */
        .glass-input {
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.40);
            border-radius: 0.75rem;
            color: #1a2e1a;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }

        .glass-input::placeholder {
            color: rgba(55, 65, 81, 0.45);
        }

        .glass-input:focus {
            outline: none;
            border-color: rgba(27, 122, 61, 0.50);
            background: rgba(255, 255, 255, 0.18);
            box-shadow: 0 0 0 3px rgba(27, 122, 61, 0.18);
        }

        .glass-input-group {
            position: relative;
        }

        .glass-input-group .input-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(55, 65, 81, 0.50);
            font-size: 1rem;
            pointer-events: none;
            z-index: 2;
        }

        .glass-input-group .glass-input {
            padding-left: 2.5rem;
            width: 100%;
        }

        .glass-input-group .password-toggle {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(55, 65, 81, 0.50);
            font-size: 1rem;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
            z-index: 2;
        }

        .glass-input-group .password-toggle:hover {
            color: rgba(27, 122, 61, 0.70);
            background: rgba(255, 255, 255, 0.10);
        }

        .glass-input-group .password-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(27, 122, 61, 0.18);
        }

        /* ── Sign-in button ── */
        .btn-signin {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #1B7A3D;
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-signin:hover {
            background-color: #145A2C;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(27, 122, 61, 0.35);
        }

        .btn-signin:active {
            transform: translateY(0);
        }

        /* Ripple effect on button */
        .btn-signin::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 0; height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            transform: translate(-50%, -50%);
            transition: width 0.5s ease, height 0.5s ease;
        }
        .btn-signin:active::before {
            width: 400px;
            height: 400px;
        }

        .btn-signin:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }

        .btn-signin .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 2px;
            margin-right: 0.5rem;
        }

        /* ── Alerts ── */
        .glass-alert {
            background: rgba(255, 255, 255, 0.30);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .glass-alert--warning {
            border-left: 3px solid #f59e0b;
            color: #92400e;
        }

        .glass-alert--danger {
            border-left: 3px solid #ef4444;
            color: #991b1b;
        }

        .glass-alert .btn-close {
            filter: none;
            font-size: 0.7rem;
            margin-left: auto;
            padding: 0.5rem;
        }

        /* ── Footer ── */
        .login-footer {
            padding: 0.85rem 2rem;
            text-align: center;
            font-size: 0.78rem;
            color: rgba(55, 65, 81, 0.55);
            border-top: 1px solid rgba(255, 255, 255, 0.25);
        }

        .login-secure {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: rgba(55, 65, 81, 0.55);
        }

        .login-secure i {
            margin-right: 0.25rem;
        }

        /* ── Responsive ── */
        @media (max-width: 576px) {
            .login-container { padding: 0.75rem; }
            .login-header { padding: 1.5rem 1.25rem 0.5rem; }
            .login-body { padding: 1.25rem; }
            .login-header h4 { font-size: 1.35rem; }
            .orb--1 { width: 90px; height: 90px; }
            .orb--2 { width: 65px; height: 65px; }
            .orb--3, .orb--4 { display: none; }
        }

        /* ── Reduced motion ── */
        @media (prefers-reduced-motion: reduce) {
            .login-bg { animation: none; }
            .orb { animation: none !important; }
            .login-card, .btn-signin { transition: none; }
        }

        /* ── Scrollbar accent ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: rgba(27, 122, 61, 0.25);
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Animated gradient mesh background -->
    <div class="login-bg" aria-hidden="true"></div>

    <!-- Floating glass orbs -->
    <div class="orb orb--1" aria-hidden="true"></div>
    <div class="orb orb--2" aria-hidden="true"></div>
    <div class="orb orb--3" aria-hidden="true"></div>
    <div class="orb orb--4" aria-hidden="true"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/dilp-logo.png" alt="DILP Logo">
                <h4>Welcome Back</h4>
                <p>Sign in to your account to continue</p>
            </div>

            <div class="login-body">
                <?php if (isset($_GET['timeout']) && $_GET['timeout'] === '1'): ?>
                <div class="glass-alert glass-alert--warning" role="alert">
                    <i class="bi bi-clock-history"></i>
                    <span>Your session expired due to inactivity. Please log in again.</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="glass-alert glass-alert--danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="glass-input-group">
                            <i class="bi bi-person input-icon" aria-hidden="true"></i>
                            <input type="text" class="glass-input" id="username" name="username"
                                   placeholder="Enter your username" required autofocus
                                   autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="glass-input-group">
                            <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                            <input type="password" class="glass-input" id="password" name="password"
                                   placeholder="Enter your password" required
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle" id="passwordToggle" 
                                    aria-label="Toggle password visibility" title="Show password">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-signin" id="loginBtn">
                        Sign In
                    </button>
                </form>

                <div class="login-secure">
                    <i class="bi bi-shield-lock"></i> Secure login &mdash; your session is protected
                </div>
            </div>

            <div class="login-footer">
                &copy; 2026 Department of Labor and Employment | m1k0yw0rkz
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        var form = document.getElementById('loginForm');
        var btn = document.getElementById('loginBtn');

        if (form && btn) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing In...';
            });
        }

        // Password visibility toggle
        var passwordToggle = document.getElementById('passwordToggle');
        var passwordInput = document.getElementById('password');
        var toggleIcon = document.getElementById('toggleIcon');

        if (passwordToggle && passwordInput && toggleIcon) {
            passwordToggle.addEventListener('click', function() {
                var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle icon
                if (type === 'text') {
                    toggleIcon.className = 'bi bi-eye-slash';
                    passwordToggle.setAttribute('title', 'Hide password');
                } else {
                    toggleIcon.className = 'bi bi-eye';
                    passwordToggle.setAttribute('title', 'Show password');
                }
            });
        }
    })();
    </script>
</body>
</html>
