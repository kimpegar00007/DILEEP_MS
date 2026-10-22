<?php
// models/FieldworkSchedule.php
// Model for Schedule of Activities / Fieldwork
// v2.0: Province-scoped — all reads/writes are automatically filtered by the
//       logged-in user's province. super_admin bypasses all province filters.
//       Province is injected at create time and immutable thereafter.
// v2.1: regional_director also bypasses province filters (cross-province access).
//       Added manual_override flag: prevents autoUpdateStatuses() from reverting
//       a manually-reset 'pending' status back to 'missed' for past-dated records.
// v2.2: Nothing is auto-marked 'missed' any more. A past-due pending/ongoing activity
//       stays as it is and is flagged overdue (is_overdue / getOverdueActivities()) until
//       a person confirms it as completed or missed, or reschedules it, through the
//       Smart Activity Reminder. "Today" is always PHP's date in Asia/Manila.
// v2.3: Saves queue email alerts for the assignee (FieldworkNotifier); reminders and
//       overdue notices are queued by cron/fieldwork-notifications.php.

require_once __DIR__ . '/../includes/FieldworkNotifier.php';

class FieldworkSchedule {
    private $db;
    private $lastError = '';
    private $lastErrorStatus = 500;
    private $notifier = null;

    public const STATUSES = ['pending', 'ongoing', 'completed', 'missed'];

    // autoUpdateStatuses() only needs to run once per request.
    private static $statusesUpdated = false;

    // Cross-province roles — these bypass province scoping
    private const CROSS_PROVINCE_ROLES = ['super_admin', 'regional_director'];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTable();
    }

    public function getLastError() {
        return $this->lastError;
    }

    /** HTTP status that fits the last error: 404 not found, 422 bad input, 500 database. */
    public function getLastErrorStatus() {
        return $this->lastErrorStatus;
    }

    private function notifier(): FieldworkNotifier {
        return $this->notifier ??= new FieldworkNotifier();
    }

    private function fail(string $message, int $status): bool {
        $this->lastError       = $message;
        $this->lastErrorStatus = $status;
        return false;
    }

    /** Today's date in Philippine time, used instead of CURDATE() (the DB server's zone differs). */
    private function today(): string {
        return (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
    }

    /** SQL fragment that is 1 when an activity is past its last day and nobody has closed it. */
    private const OVERDUE_SQL = "(fs.status IN ('pending', 'ongoing') AND COALESCE(fs.end_date, fs.start_date) < ?)";

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
     * Works out the province an activity belongs to, from the assigned user.
     *
     * - Provincial users: always their session province, and the assignee must be an
     *   active user of that same province.
     * - Cross-province roles: the assignee's province, so the assignee's office can see it.
     *   NULL only when the assignee is cross-province too (then only cross-province roles see it).
     *
     * Returns [true, province] or [false, error message].
     */
    private function resolveProvinceForAssignee(int $assignedUserId): array {
        $stmt = $this->db->prepare("SELECT province FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$assignedUserId]);
        $assignee = $stmt->fetch();
        if (!$assignee) {
            return [false, 'Assigned user not found or inactive'];
        }

        $role = $_SESSION['role'] ?? '';
        if (in_array($role, self::CROSS_PROVINCE_ROLES, true)) {
            return [true, $assignee['province']];
        }

        $sessionProvince = $_SESSION['province'] ?? null;
        if ($sessionProvince === null || $assignee['province'] !== $sessionProvince) {
            return [false, 'You can only assign activities to users in your province'];
        }
        return [true, $sessionProvince];
    }

    /**
     * Status to store and the matching manual_override flag. An empty status means
     * "auto-detect" from the dates. A status that differs from the date-based one is a
     * manual choice, so it gets override = 1 (that only matters for 'pending', which
     * autoUpdateStatuses() would otherwise move to 'ongoing').
     */
    private function resolveStatus(string $status, string $startDate, ?string $endDate): array {
        $computed = $this->computeStatus($startDate, $endDate);
        if ($status === '' || $status === $computed) {
            return [$computed, 0];
        }
        return [$status, 1];
    }

    public function create($data) {
        $this->lastError = '';
        [$ok, $province] = $this->resolveProvinceForAssignee((int) $data['assigned_user_id']);
        if (!$ok) {
            return $this->fail($province, 422);
        }

        try {
            $sql = "INSERT INTO fieldwork_schedule (
                title, description, location, province, assigned_user_id,
                start_date, end_date, status, manual_override, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed', $this->db->errorInfo());
                return $this->fail('Failed to create activity', 500);
            }

            $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
            [$status, $override] = $this->resolveStatus((string) ($data['status'] ?? ''), $data['start_date'], $endDate);

            $result = $stmt->execute([
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['location']    ?? ''),
                $province,
                (int) $data['assigned_user_id'],
                $data['start_date'],
                $endDate,
                $status,
                $override,
                $_SESSION['user_id'] ?? null,
            ]);

            if (!$result) {
                $this->logDatabaseError('Execute failed', $stmt->errorInfo());
                return $this->fail('Failed to create activity', 500);
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
                    return $this->fail('Activity was saved but its ID could not be read back', 500);
                }
            }

            $this->logActivity('create', $insertId, 'Created fieldwork schedule: ' . trim($data['title']));
            $this->notifier()->onCreated($insertId);
            return $insertId;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in create', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return $this->fail('Failed to create activity', 500);
        }
    }

    /**
     * Province follows the assigned user (see resolveProvinceForAssignee()); for
     * provincial users that is always their own province, so it never changes.
     * Province scope on WHERE prevents cross-province writes.
     *
     * Status: an empty status means auto-detect. When the dates move (a reschedule) and
     * the form still carries the old status, the status is worked out again from the new
     * dates, so a missed or overdue activity moved to next week becomes 'pending'.
     * A completed activity keeps its status.
     */
    public function update($id, $data) {
        $this->lastError = '';
        [$pSql, $pParams] = $this->provinceScope();

        $existing = $this->findById($id);
        if (!$existing) {
            return $this->fail('Activity not found', 404);
        }

        [$ok, $province] = $this->resolveProvinceForAssignee((int) $data['assigned_user_id']);
        if (!$ok) {
            return $this->fail($province, 422);
        }

        try {
            $sql = "UPDATE fieldwork_schedule SET
                title = ?, description = ?, location = ?, assigned_user_id = ?, province = ?,
                start_date = ?, end_date = ?, status = ?, manual_override = ?
            WHERE id = ?" . $pSql;

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed in update', $this->db->errorInfo());
                return $this->fail('Failed to update activity', 500);
            }

            $endDate      = !empty($data['end_date']) ? $data['end_date'] : null;
            $status       = (string) ($data['status'] ?? '');
            $datesChanged = $data['start_date'] !== $existing['start_date']
                         || $endDate !== ($existing['end_date'] ?: null);
            if ($datesChanged && $status === $existing['status'] && $status !== 'completed') {
                $status = '';
            }
            [$status, $override] = $this->resolveStatus($status, $data['start_date'], $endDate);

            $result = $stmt->execute(array_merge([
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['location']    ?? ''),
                (int) $data['assigned_user_id'],
                $province,
                $data['start_date'],
                $endDate,
                $status,
                $override,
                (int) $id,
            ], $pParams));

            if (!$result) {
                $this->logDatabaseError('Execute failed in update', $stmt->errorInfo());
                return $this->fail('Failed to update activity', 500);
            }

            $this->logActivity('update', $id, 'Updated fieldwork schedule: ' . trim($data['title']));
            $this->notifier()->onUpdated($existing);
            return true;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in update', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return $this->fail('Failed to update activity', 500);
        }
    }

    /**
     * Manually set the status for an activity (table menu and the Smart Activity Reminder).
     *
     * Sets manual_override = 1, so a manual 'pending' is not moved to 'ongoing' by
     * autoUpdateStatuses(). Editing the activity's dates resets it (see update()).
     */
    public function updateStatus($id, $status) {
        $this->lastError = '';
        [$pSql, $pParams] = $this->provinceScope();

        if (!in_array($status, self::STATUSES, true)) {
            return $this->fail('Invalid status', 422);
        }
        $existing = $this->findById($id);
        if (!$existing) {
            return $this->fail('Activity not found', 404);
        }

        try {
            $stmt   = $this->db->prepare(
                "UPDATE fieldwork_schedule SET status = ?, manual_override = 1 WHERE id = ?" . $pSql
            );
            $result = $stmt->execute(array_merge([$status, (int) $id], $pParams));

            if ($result) {
                $this->logActivity('update', $id, 'Updated fieldwork status to: ' . $status);
                $this->notifier()->onStatusChanged((int) $id, (string) $existing['status'], $status);
            }

            return $result;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in updateStatus', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
            ]);
            return $this->fail('Failed to update status', 500);
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
                $this->logDatabaseError('Execute failed in delete', $stmt->errorInfo());
                return $this->fail('Failed to delete activity', 500);
            }

            // Nothing deleted: the activity doesn't exist or is outside the user's province.
            if ($stmt->rowCount() === 0) {
                return $this->fail('Activity not found', 404);
            }

            $title = $activity ? $activity['title'] : 'ID ' . $id;
            $this->logActivity('delete', $id, 'Deleted fieldwork schedule: ' . $title);
            return true;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in delete', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
            ]);
            return $this->fail('Failed to delete activity', 500);
        }
    }

    // =========================================================
    // Read operations
    // =========================================================

    public function findById($id) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');
            $stmt = $this->db->prepare("
                SELECT fs.*, u.full_name as assigned_user_name, c.full_name as created_by_name,
                       " . self::OVERDUE_SQL . " AS is_overdue
                FROM fieldwork_schedule fs
                LEFT JOIN users u ON fs.assigned_user_id = u.id
                LEFT JOIN users c ON fs.created_by = c.id
                WHERE fs.id = ?" . $pSql
            );
            $stmt->execute(array_merge([$this->today(), (int) $id], $pParams));
            $row = $stmt->fetch();
            if ($row) {
                $row['is_overdue'] = (bool) $row['is_overdue'];
            }
            return $row;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in findById', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Filters: status (one of STATUSES, or 'overdue'), assigned_user_id, date_from,
     * date_to (both on start_date) and search (title, description, location).
     *
     * Order: overdue first (oldest first), then today and upcoming (soonest first),
     * then finished or past ones (most recent first).
     */
    public function getAll($filters = []) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');
            $today = $this->today();

            $sql    = "SELECT fs.*, u.full_name as assigned_user_name, c.full_name as created_by_name,
                              " . self::OVERDUE_SQL . " AS is_overdue
                       FROM fieldwork_schedule fs
                       LEFT JOIN users u ON fs.assigned_user_id = u.id
                       LEFT JOIN users c ON fs.created_by = c.id
                       WHERE 1=1" . $pSql;
            $params = array_merge([$today], $pParams);

            if (($filters['status'] ?? '') === 'overdue') {
                $sql     .= " AND " . self::OVERDUE_SQL;
                $params[] = $today;
            } elseif (!empty($filters['status'])) {
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

            $sql .= " ORDER BY fs.start_date ASC, fs.id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // 0 = overdue, 1 = today or upcoming, 2 = closed or past.
            $group = function (array $r) use ($today): int {
                if ($r['is_overdue']) return 0;
                $lastDay = $r['end_date'] ?: $r['start_date'];
                return ($lastDay >= $today && !in_array($r['status'], ['completed', 'missed'], true)) ? 1 : 2;
            };
            $groups = [];
            foreach ($rows as &$r) {
                $r['is_overdue'] = (bool) $r['is_overdue'];
                $groups[$r['id']] = $group($r);
            }
            unset($r);
            usort($rows, function ($a, $b) use ($groups) {
                $ga = $groups[$a['id']];
                $gb = $groups[$b['id']];
                if ($ga !== $gb) return $ga <=> $gb;
                $cmp = strcmp($a['start_date'], $b['start_date']) ?: ($a['id'] <=> $b['id']);
                return $ga === 2 ? -$cmp : $cmp;
            });

            return $rows;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getAll', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getCalendarEvents($startDate, $endDate) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');

            $sql = "SELECT fs.*, u.full_name as assigned_user_name,
                           " . self::OVERDUE_SQL . " AS is_overdue
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE fs.start_date <= ? AND COALESCE(fs.end_date, fs.start_date) >= ?"
                    . $pSql .
                   " ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge([$this->today(), $endDate, $startDate], $pParams));
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['is_overdue'] = (bool) $r['is_overdue'];
            }
            unset($r);
            return $rows;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getCalendarEvents', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getUpcomingActivities($daysAhead = 3) {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');
            $today = $this->today();

            $sql = "SELECT fs.*, u.full_name as assigned_user_name
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE fs.status IN ('pending', 'ongoing')
                    AND fs.start_date BETWEEN ? AND DATE_ADD(?, INTERVAL ? DAY)"
                    . $pSql .
                   " ORDER BY fs.start_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge([$today, $today, (int) $daysAhead], $pParams));
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getUpcomingActivities', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Activities past their last day that nobody has marked completed or missed.
     * These feed the Smart Activity Reminder and the bell's "Overdue Fieldwork" items.
     */
    public function getOverdueActivities() {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');
            $today = $this->today();

            $sql = "SELECT fs.*, u.full_name as assigned_user_name,
                           DATEDIFF(?, COALESCE(fs.end_date, fs.start_date)) AS days_overdue
                    FROM fieldwork_schedule fs
                    LEFT JOIN users u ON fs.assigned_user_id = u.id
                    WHERE " . self::OVERDUE_SQL
                    . $pSql .
                   " ORDER BY COALESCE(fs.end_date, fs.start_date) ASC, fs.id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge([$today, $today], $pParams));
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['days_overdue'] = (int) $r['days_overdue'];
            }
            unset($r);
            return $rows;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getOverdueActivities', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getStatistics() {
        try {
            [$pSql, $pParams] = $this->provinceScope('fs');

            $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN fs.status = 'pending'   THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN fs.status = 'ongoing'   THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN fs.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN fs.status = 'missed'    THEN 1 ELSE 0 END) as missed,
                SUM(CASE WHEN " . self::OVERDUE_SQL . " THEN 1 ELSE 0 END) as overdue
            FROM fieldwork_schedule fs WHERE 1=1" . $pSql;

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge([$this->today()], $pParams));
            $row = $stmt->fetch() ?: [];
            foreach (['total', 'pending', 'ongoing', 'completed', 'missed', 'overdue'] as $k) {
                $row[$k] = (int) ($row[$k] ?? 0);
            }
            return $row;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in getStatistics', ['message' => $e->getMessage()]);
            return ['total' => 0, 'pending' => 0, 'ongoing' => 0, 'completed' => 0, 'missed' => 0, 'overdue' => 0];
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
    // Only pending → ongoing is automatic. Nothing is marked 'missed'
    // automatically: past-due activities stay pending/ongoing and are
    // reported as overdue until a person confirms what happened.
    // Records with manual_override = 1 (a manual 'pending') are skipped.
    // =========================================================

    public function autoUpdateStatuses() {
        if (self::$statusesUpdated) {
            return true;
        }
        self::$statusesUpdated = true;

        try {
            $today = $this->today();

            $this->db->prepare("
                UPDATE fieldwork_schedule SET status = 'ongoing'
                WHERE status = 'pending'
                AND manual_override = 0
                AND start_date <= ? AND COALESCE(end_date, start_date) >= ?
            ")->execute([$today, $today]);

            return true;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in autoUpdateStatuses', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // =========================================================
    // Utility
    // =========================================================

    /**
     * Date-based status: 'ongoing' while today falls inside the activity's dates,
     * otherwise 'pending' (not started yet, or past and still waiting for someone to
     * confirm it as completed or missed). Never returns completed or missed.
     */
    public function computeStatus($startDate, $endDate = null) {
        $today   = $this->today();
        $lastDay = !empty($endDate) ? $endDate : $startDate;

        return ($startDate <= $today && $lastDay >= $today) ? 'ongoing' : 'pending';
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
