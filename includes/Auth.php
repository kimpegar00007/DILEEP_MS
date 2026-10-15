<?php
// includes/Auth.php
// Authentication and Authorization Handler
// v2.0: Multi-province support — province stored in session on login.
//       super_admin (province = NULL) bypasses all province filters and
//       inherits every role permission so no existing call sites need changes.
// v2.1: regional_director role — sees all provinces like super_admin but
//       cannot access System Admin panel or User Management.

class Auth {
    private $db;

    // Valid province values — mirrors the DB ENUM
    public const PROVINCES = [
        'Negros Occidental',
        'Negros Oriental',
        'Siquijor',
    ];

    // Roles that have cross-province (all-province) access
    // regional_director sees all provinces but is NOT a super_admin
    private const CROSS_PROVINCE_ROLES = ['super_admin', 'regional_director'];

    // Login throttling: this many failed attempts from one IP within the window blocks further tries.
    public const LOGIN_MAX_FAILURES   = 10;
    public const LOGIN_WINDOW_MINUTES = 15;

    // Session user re-checked against the DB once per request (see isLoggedIn()).
    private static $sessionChecked = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // =========================================================
    // Authentication
    // =========================================================

    /**
     * True when this IP has too many recent failed logins (counted from activity_logs).
     */
    public function isLoginThrottled(): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM activity_logs
                 WHERE action = 'login_failed' AND ip_address = ?
                 AND created_at >= DATE_SUB(NOW(), INTERVAL " . (int) self::LOGIN_WINDOW_MINUTES . " MINUTE)"
            );
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return (int) $stmt->fetchColumn() >= self::LOGIN_MAX_FAILURES;
        } catch (PDOException $e) {
            error_log('[Auth::isLoginThrottled] ' . $e->getMessage());
            return false;
        }
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, role, province, full_name, is_active
             FROM users WHERE username = ? AND is_active = 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // New session ID on login (prevents session fixation).
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']); // fresh CSRF token for the new session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            // regional_director also has NULL province (sees all provinces)
            $_SESSION['province']  = $user['province'];
            $_SESSION['full_name'] = $user['full_name'];

            $this->logActivity($user['id'], 'login', 'users', $user['id'], 'User logged in');
            self::$sessionChecked = true;
            return true;
        }

        $this->logActivity(null, 'login_failed', 'users', 0, 'Failed login for username: ' . mb_substr((string) $username, 0, 100));
        return false;
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity(
                $_SESSION['user_id'], 'logout', 'users',
                $_SESSION['user_id'], 'User logged out'
            );
        }

        session_destroy();
        return true;
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Once per request, reload role/province from the DB so deactivated users are
        // signed out and role or province changes apply without a new login.
        if (!self::$sessionChecked) {
            self::$sessionChecked = true;
            try {
                $stmt = $this->db->prepare(
                    "SELECT role, province, full_name FROM users WHERE id = ? AND is_active = 1"
                );
                $stmt->execute([$_SESSION['user_id']]);
                $row = $stmt->fetch();
                if (!$row) {
                    $_SESSION = [];
                    session_destroy();
                    return false;
                }
                $_SESSION['role']      = $row['role'];
                $_SESSION['province']  = $row['province'];
                $_SESSION['full_name'] = $row['full_name'];
            } catch (PDOException $e) {
                // Keep the session on a DB error rather than locking everyone out.
                error_log('[Auth::isLoggedIn] Session re-check failed: ' . $e->getMessage());
            }

            // Every page and API checks login first, so this covers all signed-in writes.
            $this->enforceCsrf();
        }

        return true;
    }

    // =========================================================
    // CSRF protection
    // =========================================================
    // One token per session. includes/navbar.php prints it in a <meta> tag and adds it
    // to every POST form (hidden "csrf_token" field) and every non-GET fetch()
    // (X-CSRF-Token header), so pages need no per-form changes.

    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function enforceCsrf(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $sent     = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = $_SESSION['csrf_token'] ?? '';
        if ($expected !== '' && is_string($sent) && hash_equals($expected, $sent)) {
            return;
        }

        error_log('[Auth] CSRF check failed for ' . ($_SERVER['REQUEST_URI'] ?? '?') . ' user ' . ($_SESSION['user_id'] ?? '?'));
        http_response_code(403);
        $message = 'Your session security token is missing or out of date. Reload the page and try again.';
        $isApi   = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message, 'error' => $message]);
        } else {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Request blocked</title>'
               . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
               . '<body class="bg-light"><div class="container py-5" style="max-width:640px"><div class="alert alert-warning">'
               . htmlspecialchars($message) . '</div><a class="btn btn-primary" href="javascript:history.back()">Go back</a> '
               . '<a class="btn btn-outline-secondary" href="index.php">Dashboard</a></div></body></html>';
        }
        exit;
    }

    // =========================================================
    // Session data helpers
    // =========================================================

    /**
     * Returns the full user array for the current session,
     * including the province key (null for super_admin / regional_director).
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id'        => $_SESSION['user_id'],
            'username'  => $_SESSION['username'],
            'role'      => $_SESSION['role'],
            'province'  => $this->getProvince(),
            'full_name' => $_SESSION['full_name'],
        ];
    }

    /**
     * Returns the logged-in user's province, or null for cross-province roles.
     *
     * Handles legacy sessions (logged in before the province migration)
     * by lazily re-fetching province + role from the DB and refreshing
     * the session transparently — no forced logout needed.
     */
    public function getProvince(): ?string {
        if (!$this->isLoggedIn()) {
            return null;
        }

        // Province already stored in session (normal path after migration)
        if (array_key_exists('province', $_SESSION)) {
            return $_SESSION['province'];
        }

        // Legacy session: province key was not set at login time.
        // Re-fetch from DB and refresh the session transparently.
        try {
            $stmt = $this->db->prepare(
                "SELECT province, role FROM users WHERE id = ? AND is_active = 1"
            );
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch();

            if ($row) {
                $_SESSION['province'] = $row['province'];
                $_SESSION['role']     = $row['role'];
                return $_SESSION['province'];
            }
        } catch (PDOException $e) {
            error_log('[Auth::getProvince] Failed to refresh province from DB: ' . $e->getMessage());
        }

        // Fallback: cannot determine province — store null and continue
        $_SESSION['province'] = null;
        return null;
    }

    // =========================================================
    // Role & province checks
    // =========================================================

    /**
     * Returns true if the current user has the super_admin role.
     * super_admin has cross-province access and ALL permissions including
     * system administration and user management.
     */
    public function isSuperAdmin(): bool {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'super_admin';
    }

    /**
     * Returns true if the current user has the regional_director role.
     * regional_director has cross-province read/write access but cannot
     * access System Admin panel or User Management.
     */
    public function isRegionalDirector(): bool {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'regional_director';
    }

    /**
     * Returns true if the user has cross-province (all-province) access.
     * Both super_admin and regional_director qualify.
     */
    public function isCrossProvince(): bool {
        return $this->isLoggedIn() &&
            in_array($_SESSION['role'] ?? '', self::CROSS_PROVINCE_ROLES, true);
    }

    /**
     * Returns true if the current user's role matches any of the given roles.
     *
     * super_admin passes every role check automatically — this ensures
     * zero existing call sites (requireRole('admin'), hasRole(['admin','encoder']),
     * etc.) need to be updated.
     *
     * regional_director inherits admin-level permissions (can do everything
     * an admin can do except system administration / user management).
     */
    public function hasRole($roles): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // super_admin inherits all permissions unconditionally
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $currentRole = $_SESSION['role'] ?? '';

        // regional_director inherits admin and encoder permissions
        if ($currentRole === 'regional_director') {
            $adminLevelRoles = ['admin', 'encoder', 'user', 'regional_director'];
            foreach ($roles as $r) {
                if (in_array($r, $adminLevelRoles, true)) {
                    return true;
                }
            }
        }

        return in_array($currentRole, $roles, true);
    }

    /**
     * Returns true if the current user may read/write data belonging
     * to the given province.
     *
     * - super_admin        : always true (no restriction)
     * - regional_director  : always true (sees all provinces)
     * - Other roles        : only true when their assigned province matches
     */
    public function hasProvinceAccess(string $province): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Both super_admin and regional_director have unrestricted province access
        if ($this->isCrossProvince()) {
            return true;
        }

        return $this->getProvince() === $province;
    }

    // =========================================================
    // Access enforcement
    // =========================================================

    /**
     * Path to a root-level page relative to the running script, so redirects from
     * api/*.php go to ../login.php instead of api/login.php.
     */
    private static function appPath(string $page): string {
        $root      = realpath(dirname(__DIR__));
        $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
        if (!$root || !$scriptDir || strpos($scriptDir, $root) !== 0) {
            return $page;
        }
        $sub   = trim(substr($scriptDir, strlen($root)), DIRECTORY_SEPARATOR);
        $depth = $sub === '' ? 0 : substr_count($sub, DIRECTORY_SEPARATOR) + 1;
        return str_repeat('../', $depth) . $page;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . self::appPath('login.php'));
            exit;
        }

        // Maintenance mode: only admin, regional_director, and super_admin may proceed.
        // hasRole('admin') already returns true for super_admin and regional_director,
        // so no separate check is needed here.
        try {
            if ($this->isMaintenanceModeEnabled()) {
                if (!$this->hasRole('admin')) {
                    header('Location: ' . self::appPath('maintenance.php'));
                    exit;
                }
            }
        } catch (Exception $e) {
            // Fail open — prevents accidental lockout on DB errors
        }
    }

    public function requireRole($roles) {
        $this->requireLogin();

        if (!$this->hasRole($roles)) {
            header('Location: ' . self::appPath('unauthorized.php'));
            exit;
        }
    }

    // =========================================================
    // Password management
    // =========================================================

    public function changePassword($userId, $oldPassword, $newPassword) {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && password_verify($oldPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare(
                "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$hashedPassword, $userId]);

            $this->logActivity($userId, 'update', 'users', $userId, 'Password changed');
            return true;
        }

        return false;
    }

    // =========================================================
    // Maintenance mode
    // =========================================================

    public function isMaintenanceModeEnabled(): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT setting_value FROM system_settings
                 WHERE setting_key = 'maintenance_mode' LIMIT 1"
            );
            $stmt->execute();
            $row = $stmt->fetch();
            return !empty($row['setting_value']) && $row['setting_value'] === '1';
        } catch (PDOException $e) {
            // Table or setting may not exist yet — treat as disabled
            return false;
        }
    }

    // =========================================================
    // Province-to-office label helpers
    // =========================================================

    // Maps province to its field office abbreviation
    private const PROVINCE_OFFICE_MAP = [
        'Negros Occidental' => 'NOCFO',
        'Negros Oriental'   => 'NORFO',
        'Siquijor'          => 'SFO',
    ];

    /**
     * Returns the field office abbreviation for a given province.
     * Falls back to 'NOFO' if province is unknown or null.
     */
    public static function getOfficeForProvince(?string $province): string {
        return self::PROVINCE_OFFICE_MAP[$province] ?? 'NOFO';
    }

    /**
     * Returns the "Date Forwarded to {office}" label for a given province.
     */
    public static function getForwardedToOfficeLabel(?string $province): string {
        $office = self::getOfficeForProvince($province);
        return "Date Forwarded of Approved Proposal to {$office}";
    }

    /**
     * Returns the "Date Forwarded to NIR" label for RPMT evaluation.
     */
    public static function getForwardedToRoLabel(): string {
        return 'Date Forwarded to NIR for RPMT Evaluation';
    }

    // =========================================================
    // Activity logging
    // =========================================================

    public function logActivity($userId, $action, $tableName, $recordId, $description) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_logs
                     (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId,
                $action,
                $tableName,
                $recordId,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        } catch (PDOException $e) {
            error_log('[Auth::logActivity] Failed to log activity: ' . $e->getMessage());
        }
    }
}
