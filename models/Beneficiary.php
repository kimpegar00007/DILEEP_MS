<?php
// models/Beneficiary.php
// Model for Individual Beneficiaries
// v2.1: Province-scoped — all reads/writes are automatically filtered by the
//       logged-in user's province. super_admin and regional_director bypass all
//       province filters (CROSS_PROVINCE_ROLES).

class Beneficiary {
    private $db;

    /** Roles that see all provinces (no province filter applied) */
    private const CROSS_PROVINCE_ROLES = ['super_admin', 'regional_director'];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // =========================================================
    // Province scope helper
    // =========================================================

    /**
     * Returns [sql_fragment, params] for province-scoped WHERE clauses.
     *
     * CROSS_PROVINCE_ROLES → no restriction  → ['', []]
     * Province user        → AND province = ? → [' AND province = ?', [$province]]
     * Edge case (no prov.) → safe empty set  → [' AND 1 = 0', []]
     */
    private function provinceScope(): array {
        $role     = $_SESSION['role']     ?? '';
        $province = $_SESSION['province'] ?? null;

        if (in_array($role, self::CROSS_PROVINCE_ROLES, true)) {
            return ['', []];
        }

        if ($province === null) {
            return [' AND 1 = 0', []];
        }

        return [' AND province = ?', [$province]];
    }

    // =========================================================
    // Write operations
    // =========================================================

    /**
     * Province is always sourced from the session — never from form input.
     * This prevents a user from assigning a record to another province.
     */
    public function create($data) {
        $province = $_SESSION['province'] ?? null;

        $sql = "INSERT INTO beneficiaries (
            last_name, first_name, middle_name, suffix, gender, barangay, municipality, province,
            contact_number, project_name, type_of_worker, type_of_beneficiaries, amount_worth, noted_findings,
            date_complied_by_proponent, date_forwarded_to_ro6, rpmt_findings, date_approved,
            date_forwarded_to_nofo, date_turnover, date_monitoring, latitude, longitude,
            status, source_of_funds, created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed', $this->db->errorInfo());
                return false;
            }

            $result = $stmt->execute([
                $data['last_name'], $data['first_name'], $data['middle_name'], $data['suffix'],
                $data['gender'], $data['barangay'], $data['municipality'], $province,
                $data['contact_number'], $data['project_name'], $data['type_of_worker'],
                $data['type_of_beneficiaries'] ?? null,
                $data['amount_worth'], $data['noted_findings'], $data['date_complied_by_proponent'],
                $data['date_forwarded_to_ro6'], $data['rpmt_findings'], $data['date_approved'],
                $data['date_forwarded_to_nofo'], $data['date_turnover'], $data['date_monitoring'],
                $data['latitude'], $data['longitude'], $data['status'],
                $data['source_of_funds'] ?? null,
                $_SESSION['user_id'] ?? null, $_SESSION['user_id'] ?? null,
            ]);

            if (!$result) {
                $this->logDatabaseError('Execute failed', $stmt->errorInfo());
                return false;
            }

            $insertId = $this->db->lastInsertId();
            $this->logActivity('create', $insertId, 'Created new beneficiary');
            return $insertId;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in create', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return false;
        }
    }

    /**
     * Province is intentionally excluded from SET — it is immutable after creation.
     * Province scope on WHERE prevents cross-province writes.
     */
    public function update($id, $data) {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "UPDATE beneficiaries SET
            last_name = ?, first_name = ?, middle_name = ?, suffix = ?, gender = ?,
            barangay = ?, municipality = ?, contact_number = ?, project_name = ?,
            type_of_worker = ?, type_of_beneficiaries = ?, amount_worth = ?, noted_findings = ?,
            date_complied_by_proponent = ?, date_forwarded_to_ro6 = ?, rpmt_findings = ?,
            date_approved = ?, date_forwarded_to_nofo = ?, date_turnover = ?,
            date_monitoring = ?, latitude = ?, longitude = ?, status = ?,
            source_of_funds = ?, updated_by = ?
        WHERE id = ?" . $pSql;

        try {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed in update', $this->db->errorInfo());
                return false;
            }

            $result = $stmt->execute(array_merge([
                $data['last_name'], $data['first_name'], $data['middle_name'], $data['suffix'],
                $data['gender'], $data['barangay'], $data['municipality'],
                $data['contact_number'], $data['project_name'], $data['type_of_worker'],
                $data['type_of_beneficiaries'] ?? null,
                $data['amount_worth'], $data['noted_findings'], $data['date_complied_by_proponent'],
                $data['date_forwarded_to_ro6'], $data['rpmt_findings'], $data['date_approved'],
                $data['date_forwarded_to_nofo'], $data['date_turnover'], $data['date_monitoring'],
                $data['latitude'], $data['longitude'], $data['status'],
                $data['source_of_funds'] ?? null,
                $_SESSION['user_id'] ?? null, $id,
            ], $pParams));

            if (!$result) {
                $this->logDatabaseError('Execute failed in update', $stmt->errorInfo());
                return false;
            }

            $this->logActivity('update', $id, 'Updated beneficiary');
            return $result;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in update', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return false;
        }
    }

    public function delete($id) {
        [$pSql, $pParams] = $this->provinceScope();

        try {
            $stmt = $this->db->prepare("DELETE FROM beneficiaries WHERE id = ?" . $pSql);
            if (!$stmt) {
                $this->logDatabaseError('Prepare failed in delete', $this->db->errorInfo());
                return false;
            }

            $result = $stmt->execute(array_merge([$id], $pParams));

            if (!$result) {
                $this->logDatabaseError('Execute failed in delete', $stmt->errorInfo());
                return false;
            }

            if ($result) {
                $this->logActivity('delete', $id, 'Deleted beneficiary');
            }

            return $result;
        } catch (PDOException $e) {
            $this->logDatabaseError('PDOException in delete', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return false;
        }
    }

    // =========================================================
    // Read operations
    // =========================================================

    public function checkBeneficiaryDuplicate($lastName, $firstName, $projectName, $excludeId = null) {
        [$pSql, $pParams] = $this->provinceScope();

        $sql    = "SELECT id, last_name, first_name, project_name, municipality, province
                   FROM beneficiaries
                   WHERE LOWER(TRIM(last_name))    = LOWER(TRIM(?))
                     AND LOWER(TRIM(first_name))   = LOWER(TRIM(?))
                     AND LOWER(TRIM(project_name)) = LOWER(TRIM(?))"
                  . $pSql;
        $params = array_merge([$lastName, $firstName, $projectName], $pParams);

        if ($excludeId !== null) {
            $sql     .= " AND id != ?";
            $params[] = (int) $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function findById($id) {
        [$pSql, $pParams] = $this->provinceScope();
        $stmt = $this->db->prepare("SELECT * FROM beneficiaries WHERE id = ?" . $pSql);
        $stmt->execute(array_merge([$id], $pParams));
        return $stmt->fetch();
    }

    public function getAll($filters = []) {
        [$pSql, $pParams] = $this->provinceScope();

        $sql    = "SELECT * FROM beneficiaries WHERE 1=1" . $pSql;
        $params = $pParams;

        // Province override: super_admin can filter by a specific province via filters
        if (!empty($filters['province']) && ($_SESSION['role'] ?? '') === 'super_admin') {
            $sql     .= " AND province = ?";
            $params[] = $filters['province'];
        }

        if (!empty($filters['municipality'])) {
            $sql     .= " AND municipality = ?";
            $params[] = $filters['municipality'];
        }

        if (!empty($filters['barangay'])) {
            $sql     .= " AND barangay = ?";
            $params[] = $filters['barangay'];
        }

        if (!empty($filters['status'])) {
            $sql     .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $allowedDateFields = [
            'date_complied_by_proponent', 'date_forwarded_to_ro6', 'date_approved',
            'date_forwarded_to_nofo', 'date_turnover', 'date_monitoring',
        ];

        $dateField = 'date_approved';
        if (!empty($filters['date_field']) && in_array($filters['date_field'], $allowedDateFields, true)) {
            $dateField = $filters['date_field'];
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $sql     .= " AND (({$dateField} IS NOT NULL AND {$dateField} BETWEEN ? AND ?) OR ({$dateField} IS NULL AND DATE(created_at) BETWEEN ? AND ?))";
            $params[] = $filters['date_from']; $params[] = $filters['date_to'];
            $params[] = $filters['date_from']; $params[] = $filters['date_to'];
        } elseif (!empty($filters['date_from'])) {
            $sql     .= " AND (({$dateField} IS NOT NULL AND {$dateField} >= ?) OR ({$dateField} IS NULL AND DATE(created_at) >= ?))";
            $params[] = $filters['date_from']; $params[] = $filters['date_from'];
        } elseif (!empty($filters['date_to'])) {
            $sql     .= " AND (({$dateField} IS NOT NULL AND {$dateField} <= ?) OR ({$dateField} IS NULL AND DATE(created_at) <= ?))";
            $params[] = $filters['date_to']; $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql     .= " AND (CONCAT(first_name, ' ', last_name) LIKE ? OR project_name LIKE ?)";
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term; $params[] = $term;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getMapData() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, project_name,
                barangay, municipality, province, amount_worth,
                CAST(latitude  AS DECIMAL(10,8)) as latitude,
                CAST(longitude AS DECIMAL(11,8)) as longitude,
                status, 'beneficiary' as type
                FROM beneficiaries
                WHERE status IN ('approved', 'implemented', 'monitored')
                AND latitude  IS NOT NULL AND longitude IS NOT NULL
                AND latitude  != 0        AND longitude != 0
                AND latitude  BETWEEN 9.0 AND 12.0
                AND longitude BETWEEN 122.0 AND 124.0"
                . $pSql;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getStatistics() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved'    THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
            SUM(CASE WHEN status = 'monitored'   THEN 1 ELSE 0 END) as monitored,
            SUM(CASE WHEN gender = 'Male'        THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN gender = 'Female'      THEN 1 ELSE 0 END) as female_count,
            SUM(amount_worth) as total_amount
        FROM beneficiaries WHERE 1=1" . $pSql;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetch();
    }

    public function getMunicipalityDistribution() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT municipality, COUNT(*) as count
                FROM beneficiaries
                WHERE municipality IS NOT NULL AND municipality != ''"
                . $pSql .
               " GROUP BY municipality ORDER BY count DESC LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getProjectTypeDistribution() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT type_of_worker, COUNT(*) as count
                FROM beneficiaries
                WHERE type_of_worker IS NOT NULL AND type_of_worker != ''"
                . $pSql .
               " GROUP BY type_of_worker ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getMonthlyTrends() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT
                DATE_FORMAT(date_approved, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(amount_worth) as total_amount
                FROM beneficiaries
                WHERE date_approved IS NOT NULL
                AND date_approved >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)"
                . $pSql .
               " GROUP BY DATE_FORMAT(date_approved, '%Y-%m') ORDER BY month ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getFundingSourceBreakdown() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT
                CASE
                    WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
                    ELSE source_of_funds
                END as source_of_funds,
                COUNT(*) as count,
                SUM(amount_worth) as total_amount
                FROM beneficiaries
                WHERE 1=1"
                . $pSql .
               " GROUP BY
                    CASE
                        WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified'
                        ELSE source_of_funds
                    END
                ORDER BY total_amount DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getCategoryDistribution() {
        // Beneficiaries don't have a category field, return empty array
        // This method exists for consistency with Proponent model
        return [];
    }

    // =========================================================
    // Logging helpers
    // =========================================================

    private function logActivity($action, $recordId, $description) {
        $stmt = $this->db->prepare(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
             VALUES (?, ?, 'beneficiaries', ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $action, $recordId, $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }

    private function logDatabaseError($context, $errorInfo) {
        $msg  = "[Beneficiary Model] {$context}\n";
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
