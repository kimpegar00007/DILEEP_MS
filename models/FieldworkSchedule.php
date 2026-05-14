<?php
// models/FieldworkSchedule.php
// Model for Schedule of Activities / Fieldwork
// v2.0: Province-scoped — all reads/writes are automatically filtered by the
//       logged-in user's province. super_admin bypasses all province filters.
//       Province is injected at create time and immutable thereafter.
// v2.1: regional_director also bypasses province filters (cross-province access).
//       Added manual_override flag: prevents autoUpdateStatuses() from reverting
//       a manually-reset 'pending' status back to 'missed' for past-dated records.

class FieldworkSchedule {
    private $db;
    private $lastError = '';

    // Cross-province roles — these bypass province scoping
    private const CROSS_PROVINCE_ROLES = ['super_admin', 'regional_director'];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTable();
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function ensureTable() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS fieldwork_schedule (
                    id               INT PRIMARY KEY AUTO_INCREMENT,
                    title            VARCHAR(255) NOT NULL,
                    description      TEXT,
                    location         VARCHAR(500),
                    province         ENUM('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL,
                    assigned_user_id INT NOT NULL,
                    start_date       DATE NOT NULL,
                    end_date         DATE,
                    status           ENUM('pending','ongoing','completed','missed') DEFAULT 'pending',
                    manual_override  TINYINT(1) NOT NULL DEFAULT 0
                        COMMENT '1 = status was manually set; skip auto-update until next natural transition',
                    created_by       INT NOT NULL,
                    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (assigned_user_id) REFERENCES users(id),
                    FOREIGN KEY (created_by)       REFERENCES users(id)
                )
            ");

            // Add manual_override column to existing tables (idempotent)
            try {
                $this->db->exec("
                    ALTER TABLE fieldwork_schedule
                    ADD COLUMN manual_override TINYINT(1) NOT NULL DEFAULT 0
                        COMMENT '1 = status was manually set; skip auto-update until next natural transition'
                ");
            } catch (PDOException $e) {
                // Column already exists — safe to ignore
            }
        } catch (PDOException $e) {
            error_log('[FieldworkSchedule Model] Table check failed: ' . $e->getMessage());
        }
    }

    // =========================================================
    // Province scope helper
    // =========================================================

    /**
     * Returns [sql_fragment, params] for province-scoped WHERE clauses.
     * Prefixes column references with $alias when the query uses table aliases.
     *
     * super_admin / regional_director → no restriction  → ['', []]
     * Province user                   → AND <col> = ?   → [' AND <col> = ?', [$province]]
     * Edge case (non-cross, no province) → safe empty set → [' AND 1 = 0', []]
     */
    private function provinceScope(string $alias = ''): array {
        $role     = $_SESSION['role']     ?? '';
        $province = $_SESSION['province'] ?? null;

        // Both super_admin and regional_director see all provinces
        if (in_array($role, self::CROSS_PROVINCE_ROLES, true)) {
            return ['', []];
        }

        if ($province === null) {
            return [' AND 1 = 0', []];
        }

        $col = $alias ? "{$alias}.province" : 'province';
        return [" AND {$col} = ?", [$province]];
    }

    // =========================================================
    // Write operations
    // =========================================================

    /**
     * Province is always sourced from the session — never from form input.
     * Cross-province roles (super_admin, regional_director) store NULL province.
     */
    public function create($data) {
        $this->lastError = '';
        $role            = $_SESSION['role'] ?? '';
        $province        = in_array($role, self::CROSS_PROVINCE_ROLES, true)
                           ? null
                           : ($_SESSION['province'] ?? null);

        try {
            $sql = "INSERT INTO fieldwork_schedule (
                title, description, location, province, assigned_user_id,
                start_date, end_date, status, manual_override, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed', $this->db->errorInfo());
                return false;
            }

            $status = $this->computeStatus($data['start_date'], $data['end_date'] ?? null);

            $result = $stmt->execute([
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['location']    ?? ''),
                $province,
                (int) $data['assigned_user_id'],
                $data['start_date'],
                !empty($data['end_date']) ? $data['end_date'] : null,
                $status,
                $_SESSION['user_id'] ?? null,
            ]);

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed', $stmt->errorInfo());
                return false;
            }

            $insertId = (int) $this->db->lastInsertId();

            if ($insertId <= 0) {
                $findStmt = $this->db->prepare(
                    "SELECT id FROM fieldwork_schedule WHERE title = ? AND start_date = ? AND created_by = ? ORDER BY id DESC LIMIT 1"
                );
                $findStmt->execute([trim($data['title']), $data['start_date'], $_SESSION['user_id'] ?? null]);
                $row      = $findStmt->fetch();
                $insertId = $row ? (int) $row['id'] : 0;

                if ($insertId <= 0) {
                    $this->lastError = 'Insert succeeded but could not retrieve the new record ID';
                    return false;
                }
            }

            $this->logActivity('create', $insertId, 'Created fieldwork schedule: ' . trim($data['title']));
            return $insertId;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in create', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return false;
        }
    }

    /**
     * Province excluded from SET — immutable after creation.
     * Province scope on WHERE prevents cross-province writes.
     * Editing a record clears manual_override so auto-update resumes normally.
     */
    public function update($id, $data) {
        $this->lastError = '';
        [$pSql, $pParams] = $this->provinceScope();

        try {
            $sql = "UPDATE fieldwork_schedule SET
                title = ?, description = ?, location = ?, assigned_user_id = ?,
                start_date = ?, end_date = ?, status = ?, manual_override = 0
            WHERE id = ?" . $pSql;

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed in update', $this->db->errorInfo());
                return false;
            }

            $status = $data['status'] ?? $this->computeStatus($data['start_date'], $data['end_date'] ?? null);

            $result = $stmt->execute(array_merge([
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['location']    ?? ''),
                (int) $data['assigned_user_id'],
                $data['start_date'],
                !empty($data['end_date']) ? $data['end_date'] : null,
                $status,
                (int) $id,
            ], $pParams));

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed in update', $stmt->errorInfo());
                return false;
            }

            $this->logActivity('update', $id, 'Updated fieldwork schedule: ' . trim($data['title']));
            return true;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in update', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return false;
        }
    }

    /**
     * Manually set the status for an activity.
     *
     * Sets manual_override = 1 for any explicit manual status change so
     * autoUpdateStatuses() won't immediately revert it. The override is
     * cleared when the record naturally transitions (start_date reached).
     */
    public function updateStatus($id, $status) {
        $this->lastError = '';
        [$pSql, $pParams] = $this->provinceScope();

        $validStatuses = ['pending', 'ongoing', 'completed', 'missed'];
        if (!in_array($status, $validStatuses, true)) {
            $this->lastError = 'Invalid status: ' . $status;
            return false;
        }

        // Any manual status change sets override = 1
        // 'ongoing' via Start button also sets override to prevent immediate revert
        $manualOverride = 1;

        try {
            $stmt   = $this->db->prepare(
                "UPDATE fieldwork_schedule SET status = ?, manual_override = ? WHERE id = ?" . $pSql
            );
            $result = $stmt->execute(array_merge([$status, $manualOverride, (int) $id], $pParams));

            if ($result) {
                $this->logActivity('update', $id, 'Updated fieldwork status to: ' . $status);
            }

            return $result;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in updateStatus', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function delete($id) {
        $this->lastError = '';
        [$pSql, $pParams] = $this->provinceScope();

        try {
            $activity = $this->findById($id);
            $stmt     = $this->db->prepare("DELETE FROM fieldwork_schedule WHERE id = ?" . $pSql);
            $result   = $stmt->execute(array_merge([(int) $id], $pParams));

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed in delete', $stmt->errorInfo());
                return false;
            }

            $title = $activity ? $activity['title'] : 'ID ' . $id;
            $this->logActivity('delete', $id, 'Deleted fieldwork schedule: ' . $title);
            return true;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in delete', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // =========================================================
    // Read operations
    // =========================================================

    public function findById($id) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');
            $stmt = $this->db->prepare("
                SELECT fs.*, u.full_name as assigned_user_name, c.full_name as created_by_name
                FROM fieldwork_schedule fs
                LEFT JOIN users u ON fs.assigned_user_id = u.id
                LEFT JOIN users c ON fs.created_by = c.id
                WHERE fs.id = ?" . $pSql
            );
            $stmt->execute(array_merge([(int) $id], $pParams));
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in findById', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function getAll($filters = []) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');

            $sql    = "SELECT fs.*, u.full_name as assigned_user_name, c.full_name as created_by_name
                       FROM fieldwork_schedule fs
                       LEFT JOIN users u ON fs.assigned_user_id = u.id
                       LEFT JOIN users c ON fs.created_by = c.id
                       WHERE 1=1" . $pSql;
            $params = $pParams;

            if (!empty($filters['status'])) {
                $sql     .= " AND fs.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['assigned_user_id'])) {
                $sql     .= " AND fs.assigned_user_id = ?";
                $params[] = (int) $filters['assigned_user_id'];
            }

            if (!empty($filters['date_from'])) {
                $sql     .= " AND fs.start_date >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql     .= " AND fs.start_date <= ?";
                $params[] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $sql     .= " AND (fs.title LIKE ? OR fs.description LIKE ? OR fs.location LIKE ?)";
                $term     = '%' . $filters['search'] . '%';
                $params[] = $term; $params[] = $term; $params[] = $term;
            }

            $sql .= " ORDER BY fs.start_date ASC, fs.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getAll', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getCalendarEvents($startDate, $endDate) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');

            $sql = "SELECT fs.*, u.full_name as assigned_user_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE (fs.start_date <= ? AND (fs.end_date >= ? OR (fs.end_date IS NULL AND fs.start_date >= ?)))"
                    . $pSql .
                   " ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge([$endDate, $startDate, $startDate], $pParams));
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getCalendarEvents', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getUpcomingActivities($daysAhead = 3) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');

            $sql = "SELECT fs.*, u.full_name as assigned_user_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE fs.status IN ('pending', 'ongoing')
                    AND fs.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)"
                    . $pSql .
                   " ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge([$daysAhead], $pParams));
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getUpcomingActivities', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getOverdueActivities() {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');

            $sql = "SELECT fs.*, u.full_name as assigned_user_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE fs.status NOT IN ('completed', 'missed')
                    AND (
                        (fs.end_date IS NOT NULL AND fs.end_date < CURDATE())
                        OR (fs.end_date IS NULL AND fs.start_date < CURDATE() AND fs.status NOT IN ('completed', 'missed'))
                    )"
                    . $pSql .
                   " ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($pParams);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getOverdueActivities', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getStatistics() {
        try {
            [$pSql, $pParams] = $this->provinceScope();

            $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'ongoing'   THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed
            FROM fieldwork_schedule WHERE 1=1" . $pSql;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($pParams);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getStatistics', ['message' => $e->getMessage()]);
            return ['total' => 0, 'pending' => 0, 'ongoing' => 0, 'completed' => 0, 'missed' => 0];
        }
    }

    /**
     * Returns users available for fieldwork assignment.
     * Province-scoped: admins/encoders see only users in their province.
     * super_admin and regional_director see all active users.
     */
    public function getAllUsers() {
        try {
            [$pSql, $pParams] = $this->provinceScope();

            $sql  = "SELECT id, full_name, role, province FROM users WHERE is_active = 1" . $pSql . " ORDER BY full_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($pParams);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // =========================================================
    // Auto-status update (runs globally — not province-scoped)
    // This is a maintenance task; scoping it would leave other
    // provinces with stale statuses.
    //
    // IMPORTANT: Records with manual_override = 1 are excluded from
    // automatic status changes. manual_override is cleared when a
    // record naturally transitions (start_date <= today for pending→ongoing).
    // =========================================================

    public function autoUpdateStatuses() {
        try {
            $today = date('Y-m-d');

            // pending → ongoing: only if not manually overridden.
            // Clears manual_override so future auto-updates work normally.
            $this->db->prepare("
                UPDATE fieldwork_schedule SET status = 'ongoing', manual_override = 0
                WHERE status = 'pending'
                AND manual_override = 0
                AND start_date <= ? AND (end_date >= ? OR end_date IS NULL)
            ")->execute([$today, $today]);

            // non-completed/missed → missed when end_date has passed.
            // Respects manual_override: won't revert a manually-reset 'pending'.
            $this->db->prepare("
                UPDATE fieldwork_schedule SET status = 'missed'
                WHERE status NOT IN ('completed', 'missed')
                AND manual_override = 0
                AND end_date IS NOT NULL AND end_date < ?
            ")->execute([$today]);

            // No end_date but start_date passed → missed.
            // Respects manual_override.
            $this->db->prepare("
                UPDATE fieldwork_schedule SET status = 'missed'
                WHERE status NOT IN ('completed', 'missed')
                AND manual_override = 0
                AND end_date IS NULL AND start_date < ?
            ")->execute([$today]);

            return true;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in autoUpdateStatuses', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // =========================================================
    // Utility
    // =========================================================

    public function computeStatus($startDate, $endDate = null) {
        $today = date('Y-m-d');

        if (!empty($endDate) && $endDate < $today) return 'missed';

        if ($startDate <= $today && (empty($endDate) || $endDate >= $today)) {
            if ($startDate === $today) return 'ongoing';
            if ($startDate < $today)  return empty($endDate) ? 'missed' : 'ongoing';
        }

        return 'pending';
    }

    // =========================================================
    // Logging helpers
    // =========================================================

    private function logActivity($action, $recordId, $description) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, ?, 'fieldwork_schedule', ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action, $recordId, $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        } catch (PDOException $e) {
            error_log('[FieldworkSchedule Model] Failed to log activity: ' . $e->getMessage());
        }
    }

    private function logDatabaseError($context, $errorInfo) {
        $msg  = "[FieldworkSchedule Model] {$context}\n";
        if (is_array($errorInfo)) {
            foreach (['code', 'message', 'file', 'line'] as $k) {
                if (isset($errorInfo[$k])) $msg .= ucfirst($k) . ": {$errorInfo[$k]}\n";
            }
            if (isset($errorInfo[0], $errorInfo[1], $errorInfo[2])) {
                $msg .= "SQLSTATE: {$errorInfo[0]}\nDriver Code: {$errorInfo[1]}\nDriver Message: {$errorInfo[2]}\n";
            }
        }
        $msg .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        $msg .= "User ID: "   . ($_SESSION['user_id']  ?? 'unknown') . "\n";
        $msg .= "Province: "  . ($_SESSION['province'] ?? 'unknown') . "\n";
        $msg .= "IP: "        . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        error_log($msg);
    }
}
