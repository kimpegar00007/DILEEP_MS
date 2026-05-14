<?php
// models/Proponent.php
// Model for Group Proponents
// v2.1: Province-scoped — all reads/writes are automatically filtered by the
//       logged-in user's province. super_admin and regional_director bypass all
//       province filters (CROSS_PROVINCE_ROLES).

class Proponent {
    private $db;
    private $lastError = '';

    /** Roles that see all provinces (no province filter applied) */
    private const CROSS_PROVINCE_ROLES = ['super_admin', 'regional_director'];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureBeneficiaryDetailColumns();
    }

    private function ensureBeneficiaryDetailColumns() {
        try {
            $columns = [
                'beneficiary_full_name' => "ALTER TABLE proponents ADD COLUMN beneficiary_full_name VARCHAR(255) DEFAULT NULL AFTER total_beneficiaries",
                'type_of_workers'       => "ALTER TABLE proponents ADD COLUMN type_of_workers VARCHAR(255) DEFAULT NULL AFTER type_of_beneficiaries",
            ];

            foreach ($columns as $column => $sql) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'proponents' AND COLUMN_NAME = ?
                ");
                $stmt->execute([$column]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $this->db->exec($sql);
                }
            }
        } catch (PDOException $e) {
            error_log('[Proponent Model] Failed to ensure beneficiary detail columns: ' . $e->getMessage());
        }
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

    public function getLastError() {
        return $this->lastError;
    }

    // =========================================================
    // Write operations
    // =========================================================

    /**
     * Province is always sourced from the session — never from form input.
     */
    public function create($data) {
        $this->lastError = '';
        $province        = $_SESSION['province'] ?? null;

        try {
            $data['liquidation_deadline'] = $this->calculateLiquidationDeadline(
                $data['date_turnover'], $data['proponent_type']
            );

            $sql = "INSERT INTO proponents (
                proponent_type, date_received, noted_findings, control_number, number_of_copies,
                date_copies_received, district, province, proponent_name, project_title, amount,
                number_of_associations, total_beneficiaries, beneficiary_full_name,
                male_beneficiaries, female_beneficiaries, type_of_beneficiaries, type_of_workers,
                category, recipient_barangays, letter_of_intent_date, date_forwarded_to_ro6,
                rpmt_findings, date_complied_by_proponent, date_complied_by_proponent_nofo,
                date_forwarded_to_nofo, date_approved, date_check_release, check_number,
                check_date_issued, or_number, or_date_issued, date_turnover, date_implemented,
                date_liquidated, liquidation_deadline, date_monitoring, source_of_funds,
                latitude, longitude, status, created_by, updated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed', $this->db->errorInfo());
                return false;
            }

            $params = [
                $data['proponent_type'],    $data['date_received'],       $data['noted_findings'],
                $data['control_number'],    $data['number_of_copies'],    $data['date_copies_received'],
                $data['district'],          $province,                    $data['proponent_name'],
                $data['project_title'],     $data['amount'],              $data['number_of_associations'],
                $data['total_beneficiaries'], $data['beneficiary_full_name'] ?? '',
                $data['male_beneficiaries'],  $data['female_beneficiaries'],
                $data['type_of_beneficiaries'], $data['type_of_workers'] ?? '',
                $data['category'],          $data['recipient_barangays'], $data['letter_of_intent_date'],
                $data['date_forwarded_to_ro6'], $data['rpmt_findings'],
                $data['date_complied_by_proponent'], $data['date_complied_by_proponent_nofo'],
                $data['date_forwarded_to_nofo'],     $data['date_approved'],
                $data['date_check_release'],  $data['check_number'],      $data['check_date_issued'],
                $data['or_number'],           $data['or_date_issued'],    $data['date_turnover'],
                $data['date_implemented'],    $data['date_liquidated'],   $data['liquidation_deadline'],
                $data['date_monitoring'],     $data['source_of_funds'],
                $data['latitude'],            $data['longitude'],         $data['status'],
                $_SESSION['user_id'] ?? null, $_SESSION['user_id'] ?? null,
            ];

            $result = $stmt->execute($params);

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed', $stmt->errorInfo());
                return false;
            }

            $insertId = (int) $this->db->lastInsertId();

            if ($insertId <= 0) {
                $findStmt = $this->db->prepare(
                    "SELECT id FROM proponents WHERE proponent_name = ? AND project_title = ? ORDER BY id DESC LIMIT 1"
                );
                $findStmt->execute([$data['proponent_name'], $data['project_title']]);
                $row      = $findStmt->fetch();
                $insertId = $row ? (int) $row['id'] : 0;

                if ($insertId <= 0) {
                    $this->lastError = 'Insert succeeded but could not retrieve the new record ID';
                    return false;
                }
            }

            $this->logActivity('create', $insertId, 'Created new proponent');
            return $insertId;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in create', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return false;
        } catch (Exception $e) {
            $this->lastError = 'Error: ' . $e->getMessage();
            error_log('[Proponent Model] Exception in create: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Province is intentionally excluded from SET — it is immutable after creation.
     * Province scope on WHERE prevents cross-province writes.
     */
    public function update($id, $data) {
        $this->lastError = '';
        [$pSql, $pParams] = $this->provinceScope();

        try {
            $data['liquidation_deadline'] = $this->calculateLiquidationDeadline(
                $data['date_turnover'], $data['proponent_type']
            );

            $sql = "UPDATE proponents SET
                proponent_type = ?, date_received = ?, noted_findings = ?, control_number = ?,
                number_of_copies = ?, date_copies_received = ?, district = ?,
                proponent_name = ?, project_title = ?, amount = ?, number_of_associations = ?,
                total_beneficiaries = ?, beneficiary_full_name = ?,
                male_beneficiaries = ?, female_beneficiaries = ?,
                type_of_beneficiaries = ?, type_of_workers = ?, category = ?,
                recipient_barangays = ?, letter_of_intent_date = ?, date_forwarded_to_ro6 = ?,
                rpmt_findings = ?, date_complied_by_proponent = ?, date_complied_by_proponent_nofo = ?,
                date_forwarded_to_nofo = ?, date_approved = ?, date_check_release = ?,
                check_number = ?, check_date_issued = ?, or_number = ?, or_date_issued = ?,
                date_turnover = ?, date_implemented = ?, date_liquidated = ?,
                liquidation_deadline = ?, date_monitoring = ?, source_of_funds = ?,
                latitude = ?, longitude = ?, status = ?, updated_by = ?
            WHERE id = ?" . $pSql;

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Prepare failed: ' . implode(' | ', $this->db->errorInfo());
                $this->logDatabaseError('Prepare failed in update', $this->db->errorInfo());
                return false;
            }

            $result = $stmt->execute(array_merge([
                $data['proponent_type'],    $data['date_received'],        $data['noted_findings'],
                $data['control_number'],    $data['number_of_copies'],     $data['date_copies_received'],
                $data['district'],
                $data['proponent_name'],    $data['project_title'],        $data['amount'],
                $data['number_of_associations'], $data['total_beneficiaries'],
                $data['beneficiary_full_name'] ?? '',
                $data['male_beneficiaries'],  $data['female_beneficiaries'],
                $data['type_of_beneficiaries'], $data['type_of_workers'] ?? '',
                $data['category'],            $data['recipient_barangays'],
                $data['letter_of_intent_date'], $data['date_forwarded_to_ro6'], $data['rpmt_findings'],
                $data['date_complied_by_proponent'], $data['date_complied_by_proponent_nofo'],
                $data['date_forwarded_to_nofo'],     $data['date_approved'],
                $data['date_check_release'],  $data['check_number'],        $data['check_date_issued'],
                $data['or_number'],           $data['or_date_issued'],      $data['date_turnover'],
                $data['date_implemented'],    $data['date_liquidated'],     $data['liquidation_deadline'],
                $data['date_monitoring'],     $data['source_of_funds'],
                $data['latitude'],            $data['longitude'],           $data['status'],
                $_SESSION['user_id'] ?? null, $id,
            ], $pParams));

            if (!$result) {
                $this->lastError = 'Execute failed: ' . implode(' | ', $stmt->errorInfo());
                $this->logDatabaseError('Execute failed in update', $stmt->errorInfo());
                return false;
            }

            $this->logActivity('update', $id, 'Updated proponent');
            return $result;
        } catch (PDOException $e) {
            $this->lastError = 'DB Error: ' . $e->getMessage();
            $this->logDatabaseError('PDOException in update', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line'    => $e->getLine(),
            ]);
            return false;
        } catch (Exception $e) {
            $this->lastError = 'Error: ' . $e->getMessage();
            error_log('[Proponent Model] Exception in update: ' . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        [$pSql, $pParams] = $this->provinceScope();

        try {
            $stmt = $this->db->prepare("DELETE FROM proponents WHERE id = ?" . $pSql);
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
                $this->logActivity('delete', $id, 'Deleted proponent');
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

    public function checkProponentDuplicate($proponentName, $projectTitle, $excludeId = null) {
        [$pSql, $pParams] = $this->provinceScope();

        $sql    = "SELECT id, proponent_name, project_title, province
                   FROM proponents
                   WHERE LOWER(TRIM(proponent_name)) = LOWER(TRIM(?))
                     AND LOWER(TRIM(project_title))  = LOWER(TRIM(?))"
                  . $pSql;
        $params = array_merge([$proponentName, $projectTitle], $pParams);

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
        $stmt = $this->db->prepare("SELECT * FROM proponents WHERE id = ?" . $pSql);
        $stmt->execute(array_merge([$id], $pParams));
        return $stmt->fetch();
    }

    public function getAll($filters = []) {
        [$pSql, $pParams] = $this->provinceScope();

        $sql    = "SELECT * FROM proponents WHERE 1=1" . $pSql;
        $params = $pParams;

        // Province override: super_admin can filter by a specific province via filters
        if (!empty($filters['province']) && ($_SESSION['role'] ?? '') === 'super_admin') {
            $sql     .= " AND province = ?";
            $params[] = $filters['province'];
        }

        if (!empty($filters['proponent_type'])) {
            $sql     .= " AND proponent_type = ?";
            $params[] = $filters['proponent_type'];
        }

        if (!empty($filters['district'])) {
            $sql     .= " AND district = ?";
            $params[] = $filters['district'];
        }

        if (!empty($filters['status'])) {
            $sql     .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql     .= " AND category = ?";
            $params[] = $filters['category'];
        }

        $allowedDateFields = [
            'letter_of_intent_date', 'date_forwarded_to_ro6', 'date_complied_by_proponent',
            'date_complied_by_proponent_nofo', 'date_forwarded_to_nofo', 'date_approved',
            'date_check_release', 'check_date_issued', 'or_date_issued', 'date_turnover',
            'date_implemented', 'date_liquidated', 'date_monitoring',
        ];

        $dateField           = 'date_approved';
        $isSourceOfFundsFilter = false;

        if (!empty($filters['date_field'])) {
            if ($filters['date_field'] === 'source_of_funds') {
                $isSourceOfFundsFilter = true;
            } elseif (in_array($filters['date_field'], $allowedDateFields, true)) {
                $dateField = $filters['date_field'];
            }
        }

        if ($isSourceOfFundsFilter) {
            $sql .= " AND (source_of_funds IS NOT NULL AND source_of_funds != '')";
            if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                $sql .= " AND (DATE(created_at) BETWEEN ? AND ?)";
                $params[] = $filters['date_from']; $params[] = $filters['date_to'];
            } elseif (!empty($filters['date_from'])) {
                $sql .= " AND (DATE(created_at) >= ?)";
                $params[] = $filters['date_from'];
            } elseif (!empty($filters['date_to'])) {
                $sql .= " AND (DATE(created_at) <= ?)";
                $params[] = $filters['date_to'];
            }
        } else {
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
        }

        if (!empty($filters['search'])) {
            $sql     .= " AND (proponent_name LIKE ? OR project_title LIKE ? OR control_number LIKE ?)";
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term; $params[] = $term; $params[] = $term;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getMapData() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT id, proponent_name as name, project_title, district, province, amount,
                CAST(latitude  AS DECIMAL(10,8)) as latitude,
                CAST(longitude AS DECIMAL(11,8)) as longitude,
                status, proponent_type, 'proponent' as type, total_beneficiaries
                FROM proponents
                WHERE status IN ('approved', 'implemented', 'liquidated', 'monitored')
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
            SUM(CASE WHEN proponent_type = 'LGU-associated'     THEN 1 ELSE 0 END) as lgu_count,
            SUM(CASE WHEN proponent_type = 'Non-LGU-associated' THEN 1 ELSE 0 END) as non_lgu_count,
            SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved'    THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
            SUM(CASE WHEN status = 'liquidated'  THEN 1 ELSE 0 END) as liquidated,
            SUM(CASE WHEN status = 'monitored'   THEN 1 ELSE 0 END) as monitored,
            SUM(total_beneficiaries)  as total_beneficiaries,
            SUM(male_beneficiaries)   as total_male,
            SUM(female_beneficiaries) as total_female,
            SUM(amount) as total_amount
        FROM proponents WHERE 1=1" . $pSql;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetch();
    }

    public function getOverdueLiquidations() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT * FROM proponents
                WHERE date_liquidated IS NULL
                AND liquidation_deadline < CURDATE()
                AND status != 'liquidated'"
                . $pSql .
               " ORDER BY liquidation_deadline ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getDistrictDistribution() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT district, COUNT(*) as count, SUM(amount) as total_amount
                FROM proponents
                WHERE district IS NOT NULL AND district != ''"
                . $pSql .
               " GROUP BY district ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getCategoryDistribution() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT category, COUNT(*) as count
                FROM proponents
                WHERE category IS NOT NULL AND category != ''"
                . $pSql .
               " GROUP BY category ORDER BY count DESC";

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
                SUM(amount) as total_amount
                FROM proponents
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

    public function getMonthlyTrends() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT
                DATE_FORMAT(date_approved, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                SUM(total_beneficiaries) as total_beneficiaries
                FROM proponents
                WHERE date_approved IS NOT NULL
                AND date_approved >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)"
                . $pSql .
               " GROUP BY DATE_FORMAT(date_approved, '%Y-%m') ORDER BY month ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    public function getWorkerTypeDistribution() {
        [$pSql, $pParams] = $this->provinceScope();

        $sql = "SELECT type_of_workers as type_of_worker, COUNT(*) as count
                FROM proponents
                WHERE type_of_workers IS NOT NULL AND type_of_workers != ''"
                . $pSql .
               " GROUP BY type_of_workers ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($pParams);
        return $stmt->fetchAll();
    }

    // =========================================================
    // Associations (no province scope needed — scoped via proponent FK)
    // =========================================================

    public function saveAssociations($proponentId, $names, $addresses) {
        try {
            try {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS proponent_associations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        proponent_id INT NOT NULL,
                        association_name VARCHAR(255) NOT NULL,
                        association_address VARCHAR(500) DEFAULT NULL,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (proponent_id) REFERENCES proponents(id) ON DELETE CASCADE
                    )
                ");
            } catch (PDOException $e) {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS proponent_associations (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        proponent_id INT NOT NULL,
                        association_name VARCHAR(255) NOT NULL,
                        association_address VARCHAR(500) DEFAULT NULL,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                error_log('[Proponent Model] proponent_associations created without FK: ' . $e->getMessage());
            }

            $stmt = $this->db->prepare("DELETE FROM proponent_associations WHERE proponent_id = ?");
            $stmt->execute([$proponentId]);

            if (!empty($names) && is_array($names)) {
                $ins = $this->db->prepare(
                    "INSERT INTO proponent_associations (proponent_id, association_name, association_address, sort_order) VALUES (?, ?, ?, ?)"
                );
                foreach ($names as $index => $name) {
                    $name = trim($name);
                    if (empty($name)) continue;
                    $address = isset($addresses[$index]) ? trim($addresses[$index]) : '';
                    $ins->execute([$proponentId, $name, $address, $index]);
                }
            }

            return true;
        } catch (PDOException $e) {
            $this->logDatabaseError('Failed to save associations', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getAssociations($proponentId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM proponent_associations WHERE proponent_id = ? ORDER BY sort_order ASC"
            );
            $stmt->execute([$proponentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // =========================================================
    // Returns (no province scope — scoped via proponent FK)
    // =========================================================

    public function getReturns($proponentId) {
        try {
            $sql = "SELECT pr.*, u.full_name as returned_by_name
                    FROM proponent_returns pr
                    LEFT JOIN users u ON pr.returned_by = u.id
                    WHERE pr.proponent_id = ?
                    ORDER BY pr.return_date DESC, pr.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proponentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addReturn($proponentId, $returnDate, $reason, $returnedBy = null) {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS proponent_returns (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    proponent_id INT NOT NULL,
                    return_date DATE NOT NULL,
                    reason TEXT,
                    returned_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $stmt = $this->db->prepare(
                "INSERT INTO proponent_returns (proponent_id, return_date, reason, returned_by) VALUES (?, ?, ?, ?)"
            );
            $result = $stmt->execute([$proponentId, $returnDate, $reason, $returnedBy]);

            if ($result) {
                $this->logActivity('return', $proponentId, 'Application returned: ' . substr($reason, 0, 100));
            }

            return $result;
        } catch (PDOException $e) {
            $this->logDatabaseError('Failed to add return', [
                'code' => $e->getCode(), 'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function deleteReturn($returnId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM proponent_returns WHERE id = ?");
            return $stmt->execute([$returnId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getReturnCount($proponentId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM proponent_returns WHERE proponent_id = ?");
            $stmt->execute([$proponentId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // =========================================================
    // Internal helpers
    // =========================================================

    private function calculateLiquidationDeadline($dateTurnover, $proponentType) {
        if (empty($dateTurnover)) return null;
        $date     = new DateTime($dateTurnover);
        $interval = ($proponentType === 'LGU-associated') ? 10 : 60;
        $date->add(new DateInterval('P' . $interval . 'D'));
        return $date->format('Y-m-d');
    }

    private function logActivity($action, $recordId, $description) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, ?, 'proponents', ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action, $recordId, $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        } catch (PDOException $e) {
            error_log('[Proponent Model] Failed to log activity: ' . $e->getMessage());
        }
    }

    private function logDatabaseError($context, $errorInfo) {
        $msg  = "[Proponent Model] {$context}\n";
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
