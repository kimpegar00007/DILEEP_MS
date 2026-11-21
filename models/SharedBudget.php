<?php
// models/SharedBudget.php
// Model for budgets shared by several Proponents and Beneficiaries.
//
// How the money works: each party's peso share is stored in the amount column its
// record already has (proponents.amount / beneficiaries.amount_worth). The shares
// add up to shared_budgets.total_budget, so every existing total (dashboard, reports,
// CQPR, landing page) counts the budget exactly once with no change to those queries.
//
// Province-scoped like the other models: super_admin and regional_director see all
// provinces, everyone else only their own (fail-closed when they have none).

require_once __DIR__ . '/Proponent.php';
require_once __DIR__ . '/Beneficiary.php';

class SharedBudget {
    private $db;
    private $lastError = '';
    private $proponents;
    private $beneficiaries;

    /** Roles that see all provinces (no province filter applied) */
    private const CROSS_PROVINCE_ROLES = ['super_admin', 'regional_director'];

    public const PROPONENT_TYPES = ['LGU-associated', 'Non-LGU-associated', 'By Administration', 'Others'];
    public const CATEGORIES      = ['Formation', 'Enhancement', 'Restoration'];
    public const STATUSES_PROPONENT   = ['pending', 'approved', 'implemented', 'liquidated', 'monitored'];
    public const STATUSES_BENEFICIARY = ['pending', 'approved', 'implemented', 'monitored'];

    // Keep these two lists in sync with proponent-form.php and beneficiary-form.php.
    public const BENEFICIARY_TYPE_OPTIONS = [
        'Marginalized and Landless Farmers',
        'Marginalized Fisherfolk',
        'Self-employed with Insufficient Income',
        'Parents/Guardians of Child Laborers',
        'Displaced Workers',
        'Among others',
    ];
    public const WORKER_TYPE_OPTIONS = [
        'Disadvantaged Workers',
        'Indigenous People (IPs)',
        'Parents/Guardians of Child Laborers',
        'TESDA graduates',
        "Micro-establishment's beneficiaries of NWPC and RTWPB's Productive Improvement Trainings",
        "Labor Organizations and Workers' Association under BLR's WODP Plus",
        "Micro-entrepreneur under the BWC's TAV",
        'Others',
    ];

    private const PROVINCE_RANGES = [
        'Negros Occidental' => ['lat' => [9.0, 12.0], 'lng' => [122.0, 124.0]],
        'Negros Oriental'   => ['lat' => [9.0, 10.5], 'lng' => [122.5, 123.5]],
        'Siquijor'          => ['lat' => [9.0,  9.5], 'lng' => [123.0, 123.8]],
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Build the member models first: their constructors may run ALTER TABLE
        // (DDL commits implicitly), which must never happen inside our transaction.
        $this->proponents    = new Proponent();
        $this->beneficiaries = new Beneficiary();
        $this->ensureSchema();
    }

    public function getLastError() {
        return $this->lastError;
    }

    // =========================================================
    // Schema (self-healing, mirrors migrations/add_shared_budgets.sql)
    // =========================================================

    private function ensureSchema(): void {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS shared_budgets (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    province VARCHAR(100) NOT NULL,
                    total_budget DECIMAL(15,2) NOT NULL,
                    source_of_funds VARCHAR(255) DEFAULT NULL,
                    notes TEXT DEFAULT NULL,
                    created_by INT(11) DEFAULT NULL,
                    updated_by INT(11) DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_shared_budgets_province (province)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");

            // replaceAssociations() runs inside a transaction and must not create tables there.
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
            }

            foreach (['proponents', 'beneficiaries'] as $table) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'shared_budget_id'
                ");
                $stmt->execute([$table]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $this->db->exec("ALTER TABLE `{$table}` ADD COLUMN shared_budget_id INT(11) UNSIGNED NULL DEFAULT NULL");
                    $this->db->exec("ALTER TABLE `{$table}` ADD INDEX idx_{$table}_shared_budget_id (shared_budget_id)");
                }
            }
        } catch (PDOException $e) {
            error_log('[SharedBudget Model] Failed to ensure schema: ' . $e->getMessage());
        }
    }

    // =========================================================
    // Province scope helper
    // =========================================================

    /**
     * Returns [sql_fragment, params] for province-scoped WHERE clauses.
     * CROSS_PROVINCE_ROLES → no restriction; provincial user → their province;
     * provincial user without a province → empty set (fail closed).
     */
    private function provinceScope(string $prefix = ''): array {
        $role     = $_SESSION['role']     ?? '';
        $province = $_SESSION['province'] ?? null;

        if (in_array($role, self::CROSS_PROVINCE_ROLES, true)) {
            return ['', []];
        }
        if ($province === null) {
            return [' AND 1 = 0', []];
        }
        return [" AND {$prefix}province = ?", [$province]];
    }

    /**
     * Province for a NEW budget: provincial users always get their session province;
     * cross-province roles choose, and the value must be one of Auth::PROVINCES.
     */
    private function resolveProvince($requested): ?string {
        $role = $_SESSION['role'] ?? '';
        $province = in_array($role, self::CROSS_PROVINCE_ROLES, true)
            ? ($requested ?: null)
            : ($_SESSION['province'] ?? null);

        return ($province !== null && in_array($province, Auth::PROVINCES, true)) ? $province : null;
    }

    // =========================================================
    // Read operations
    // =========================================================

    /** Subquery fragments that add up the shares and count the members of budget alias "sb". */
    private function summarySelect(): string {
        return "sb.*,
            (SELECT COALESCE(SUM(p.amount), 0) FROM proponents p WHERE p.shared_budget_id = sb.id)
          + (SELECT COALESCE(SUM(b.amount_worth), 0) FROM beneficiaries b WHERE b.shared_budget_id = sb.id) AS allocated,
            (SELECT COUNT(*) FROM proponents p WHERE p.shared_budget_id = sb.id) AS proponent_count,
            (SELECT COUNT(*) FROM beneficiaries b WHERE b.shared_budget_id = sb.id) AS beneficiary_count";
    }

    private function decorate(array $row): array {
        $row['allocated']    = (float) $row['allocated'];
        $row['total_budget'] = (float) $row['total_budget'];
        $row['difference']   = round($row['total_budget'] - $row['allocated'], 2);
        $row['mismatch']     = abs($row['difference']) >= 0.005;
        return $row;
    }

    public function findById($id) {
        [$pSql, $pParams] = $this->provinceScope('sb.');
        $stmt = $this->db->prepare("SELECT " . $this->summarySelect() . " FROM shared_budgets sb WHERE sb.id = ?" . $pSql);
        $stmt->execute(array_merge([(int) $id], $pParams));
        $row = $stmt->fetch();
        return $row ? $this->decorate($row) : false;
    }

    public function getAll(array $filters = []): array {
        [$pSql, $pParams] = $this->provinceScope('sb.');
        $sql    = "SELECT " . $this->summarySelect() . " FROM shared_budgets sb WHERE 1 = 1" . $pSql;
        $params = $pParams;

        if (!empty($filters['province']) && in_array($filters['province'], Auth::PROVINCES, true)) {
            $sql    .= " AND sb.province = ?";
            $params[] = $filters['province'];
        }
        if (!empty($filters['search'])) {
            $sql    .= " AND (sb.title LIKE ? OR sb.source_of_funds LIKE ?)";
            $like    = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY sb.created_at DESC, sb.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'decorate'], $stmt->fetchAll());
    }

    /** Full proponent rows of a budget (the budget itself is province-checked by the caller via findById). */
    public function getProponents($budgetId): array {
        [$pSql, $pParams] = $this->provinceScope();
        $stmt = $this->db->prepare("SELECT * FROM proponents WHERE shared_budget_id = ?" . $pSql . " ORDER BY id");
        $stmt->execute(array_merge([(int) $budgetId], $pParams));
        return $stmt->fetchAll();
    }

    public function getBeneficiaries($budgetId): array {
        [$pSql, $pParams] = $this->provinceScope();
        $stmt = $this->db->prepare("SELECT * FROM beneficiaries WHERE shared_budget_id = ?" . $pSql . " ORDER BY id");
        $stmt->execute(array_merge([(int) $budgetId], $pParams));
        return $stmt->fetchAll();
    }

    public function getAssociations($proponentId): array {
        return $this->proponents->getAssociations($proponentId);
    }

    // =========================================================
    // Write operations
    // =========================================================

    /**
     * Create a budget with all of its members in ONE transaction: either the budget,
     * every Proponent and every Beneficiary are saved, or nothing is.
     *
     * @param array $budget        title, province, total_budget, source_of_funds, notes
     * @param array $proponents    each: key, data (Proponent::create columns), association_names/addresses
     * @param array $beneficiaries each: data (Beneficiary::create columns), proponent_key
     * @return int|false new budget id
     */
    public function create(array $budget, array $proponents, array $beneficiaries) {
        $this->lastError = '';
        $province = $this->resolveProvince($budget['province'] ?? null);
        if ($province === null) {
            $this->lastError = 'Invalid or missing province.';
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO shared_budgets (title, province, total_budget, source_of_funds, notes, created_by, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $budget['title'], $province, $budget['total_budget'],
                $budget['source_of_funds'] ?: null, $budget['notes'] ?: null,
                $_SESSION['user_id'] ?? null, $_SESSION['user_id'] ?? null,
            ]);
            $budgetId = (int) $this->db->lastInsertId();

            $this->saveMembers($budgetId, $province, $proponents, $beneficiaries);
            $this->logActivity('create', $budgetId, sprintf(
                'Created shared budget "%s" (%d proponent(s), %d beneficiary(ies))',
                $budget['title'], count($proponents), count($beneficiaries)
            ));

            $this->db->commit();
            return $budgetId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[SharedBudget Model] create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a budget and rebalance its members in one transaction. Members that are
     * missing from the submission are only UNLINKED from the budget; their records stay.
     * Province is immutable.
     */
    public function update($id, array $budget, array $proponents, array $beneficiaries): bool {
        $this->lastError = '';
        $existing = $this->findById($id);
        if (!$existing) {
            $this->lastError = 'Shared budget not found.';
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE shared_budgets SET title = ?, total_budget = ?, source_of_funds = ?, notes = ?, updated_by = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $budget['title'], $budget['total_budget'],
                $budget['source_of_funds'] ?: null, $budget['notes'] ?: null,
                $_SESSION['user_id'] ?? null, (int) $id,
            ]);

            $this->saveMembers((int) $id, $existing['province'], $proponents, $beneficiaries);
            $this->logActivity('update', (int) $id, sprintf('Updated shared budget "%s"', $budget['title']));

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[SharedBudget Model] update failed: ' . $e->getMessage());
            return false;
        }
    }

    /** Delete the budget only. Members are unlinked, never deleted. */
    public function delete($id): bool {
        $this->lastError = '';
        $existing = $this->findById($id);
        if (!$existing) {
            $this->lastError = 'Shared budget not found.';
            return false;
        }

        try {
            $this->db->beginTransaction();
            foreach (['proponents', 'beneficiaries'] as $table) {
                $this->db->prepare("UPDATE `{$table}` SET shared_budget_id = NULL WHERE shared_budget_id = ?")
                         ->execute([(int) $id]);
            }
            $this->db->prepare("DELETE FROM shared_budgets WHERE id = ?")->execute([(int) $id]);
            $this->logActivity('delete', (int) $id, sprintf(
                'Deleted shared budget "%s"; %d proponent(s) and %d beneficiary(ies) were kept and unlinked',
                $existing['title'], $existing['proponent_count'], $existing['beneficiary_count']
            ));
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log('[SharedBudget Model] delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or update every member inside the caller's open transaction, then unlink
     * members that were dropped from the form. Throws on any failure so the caller rolls back.
     */
    private function saveMembers(int $budgetId, string $province, array $proponents, array $beneficiaries): void {
        $keyToProponentId = [];
        $keptProponents   = [];
        $keptBeneficiaries = [];

        foreach ($proponents as $p) {
            $data = $p['data'];
            $data['province'] = $province;

            if (!empty($p['id'])) {
                $id = (int) $p['id'];
                $this->assertMember('proponents', $id, $budgetId);
                if (!$this->proponents->update($id, $data)) {
                    throw new RuntimeException('Could not update proponent "' . $data['proponent_name'] . '": ' . $this->proponents->getLastError());
                }
            } else {
                $id = (int) $this->proponents->create($data);
                if ($id <= 0) {
                    throw new RuntimeException('Could not create proponent "' . $data['proponent_name'] . '": ' . $this->proponents->getLastError());
                }
                $this->db->prepare("UPDATE proponents SET shared_budget_id = ? WHERE id = ?")->execute([$budgetId, $id]);
            }

            $this->replaceAssociations($id, $p['association_names'] ?? [], $p['association_addresses'] ?? []);
            $keyToProponentId[$p['key']] = $id;
            $keptProponents[] = $id;
        }

        foreach ($beneficiaries as $b) {
            $data = $b['data'];
            $data['province'] = $province;
            if (!isset($keyToProponentId[$b['proponent_key']])) {
                throw new RuntimeException('A beneficiary is linked to a proponent that is not in this budget.');
            }
            $data['proponent_id'] = $keyToProponentId[$b['proponent_key']];

            if (!empty($b['id'])) {
                $id = (int) $b['id'];
                $this->assertMember('beneficiaries', $id, $budgetId);
                if (!$this->beneficiaries->update($id, $data)) {
                    throw new RuntimeException('Could not update beneficiary "' . $data['first_name'] . ' ' . $data['last_name'] . '".');
                }
            } else {
                $id = (int) $this->beneficiaries->create($data);
                if ($id <= 0) {
                    throw new RuntimeException('Could not create beneficiary "' . $data['first_name'] . ' ' . $data['last_name'] . '".');
                }
                $this->db->prepare("UPDATE beneficiaries SET shared_budget_id = ? WHERE id = ?")->execute([$budgetId, $id]);
            }
            $keptBeneficiaries[] = $id;
        }

        $this->unlinkOthers('proponents', $budgetId, $keptProponents);
        $this->unlinkOthers('beneficiaries', $budgetId, $keptBeneficiaries);
    }

    /** An existing record may only be edited here if it already belongs to this budget. */
    private function assertMember(string $table, int $id, int $budgetId): void {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE id = ? AND shared_budget_id = ?");
        $stmt->execute([$id, $budgetId]);
        if ((int) $stmt->fetchColumn() === 0) {
            throw new RuntimeException('A record in the form does not belong to this shared budget.');
        }
    }

    private function unlinkOthers(string $table, int $budgetId, array $keepIds): void {
        $sql    = "UPDATE `{$table}` SET shared_budget_id = NULL WHERE shared_budget_id = ?";
        $params = [$budgetId];
        if ($keepIds) {
            $sql   .= " AND id NOT IN (" . implode(',', array_fill(0, count($keepIds), '?')) . ")";
            $params = array_merge($params, $keepIds);
        }
        $this->db->prepare($sql)->execute($params);
    }

    /**
     * Same result as Proponent::saveAssociations(), but without its CREATE TABLE
     * statements (DDL would commit our transaction). The table already exists by the
     * time any proponent can be saved here; it is created up front if somehow missing.
     */
    private function replaceAssociations(int $proponentId, array $names, array $addresses): void {
        $this->db->prepare("DELETE FROM proponent_associations WHERE proponent_id = ?")->execute([$proponentId]);
        $ins = $this->db->prepare(
            "INSERT INTO proponent_associations (proponent_id, association_name, association_address, sort_order)
             VALUES (?, ?, ?, ?)"
        );
        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $ins->execute([$proponentId, $name, trim((string) ($addresses[$index] ?? '')), $index]);
        }
    }

    // =========================================================
    // Request parsing and validation (shared by the form page)
    // =========================================================

    private static function cleanDate($v): ?string {
        $v = trim((string) $v);
        return $v !== '' ? $v : null;
    }

    private static function cleanCoord($v): ?float {
        $v = trim((string) $v);
        return $v !== '' ? floatval($v) : null;
    }

    private static function pickOptions(array $allowed, $values): array {
        $values = is_array($values) ? array_map('trim', $values) : [];
        return array_values(array_intersect($allowed, $values));
    }

    /** Turn the raw POST into normalized arrays. Never trusts province from the POST for provincial users. */
    public static function parseRequest(array $post): array {
        $budget = [
            'title'           => trim($post['title'] ?? ''),
            'province'        => trim($post['province'] ?? ''),
            'total_budget'    => floatval($post['total_budget'] ?? 0),
            'source_of_funds' => trim($post['source_of_funds'] ?? ''),
            'notes'           => trim($post['notes'] ?? ''),
        ];

        $proponents = [];
        foreach ((array) ($post['proponents'] ?? []) as $p) {
            if (!is_array($p)) continue;
            $names = array_values(array_filter(
                array_map(function ($n) { return trim((string) $n); }, (array) ($p['beneficiary_full_names'] ?? [])),
                function ($n) { return $n !== ''; }
            ));
            $nz = function ($k) use ($p) {
                $v = trim((string) ($p[$k] ?? ''));
                return $v !== '' ? $v : null;
            };
            $proponents[] = [
                'id'  => (int) ($p['id'] ?? 0),
                'key' => (string) ($p['key'] ?? ''),
                'association_names'     => (array) ($p['association_names'] ?? []),
                'association_addresses' => (array) ($p['association_addresses'] ?? []),
                'data' => [
                    'proponent_type' => $p['proponent_type'] ?? '',
                    'date_received' => self::cleanDate($p['date_received'] ?? ''),
                    'noted_findings' => trim($p['noted_findings'] ?? ''),
                    'control_number' => $nz('control_number'),
                    'number_of_copies' => 0,
                    'date_copies_received' => null,
                    'district' => trim($p['district'] ?? ''),
                    'proponent_name' => trim($p['proponent_name'] ?? ''),
                    'project_title' => trim($p['project_title'] ?? ''),
                    'amount' => floatval($p['amount'] ?? 0),
                    'number_of_associations' => intval($p['number_of_associations'] ?? 0),
                    'total_beneficiaries' => intval($p['total_beneficiaries'] ?? 0),
                    'beneficiary_full_name' => $names ? json_encode($names, JSON_UNESCAPED_UNICODE) : '',
                    'male_beneficiaries' => intval($p['male_beneficiaries'] ?? 0),
                    'female_beneficiaries' => intval($p['female_beneficiaries'] ?? 0),
                    'type_of_beneficiaries' => implode(', ', self::pickOptions(self::BENEFICIARY_TYPE_OPTIONS, $p['type_of_beneficiaries'] ?? [])),
                    'type_of_workers' => implode(', ', self::pickOptions(self::WORKER_TYPE_OPTIONS, $p['type_of_workers'] ?? [])),
                    'category' => $p['category'] ?? '',
                    'recipient_barangays' => trim($p['recipient_barangays'] ?? ''),
                    'letter_of_intent_date' => self::cleanDate($p['letter_of_intent_date'] ?? ''),
                    'date_forwarded_to_ro6' => self::cleanDate($p['date_forwarded_to_ro6'] ?? ''),
                    'rpmt_findings' => trim($p['rpmt_findings'] ?? ''),
                    'date_complied_by_proponent' => null,
                    'date_complied_by_proponent_nofo' => self::cleanDate($p['date_complied_by_proponent_nofo'] ?? ''),
                    'date_forwarded_to_nofo' => self::cleanDate($p['date_forwarded_to_nofo'] ?? ''),
                    'date_approved' => self::cleanDate($p['date_approved'] ?? ''),
                    'date_check_release' => self::cleanDate($p['date_check_release'] ?? ''),
                    'check_number' => $nz('check_number'),
                    'check_date_issued' => self::cleanDate($p['check_date_issued'] ?? ''),
                    'or_number' => $nz('or_number'),
                    'or_date_issued' => self::cleanDate($p['or_date_issued'] ?? ''),
                    'date_turnover' => self::cleanDate($p['date_turnover'] ?? ''),
                    'date_implemented' => self::cleanDate($p['date_implemented'] ?? ''),
                    'date_liquidated' => self::cleanDate($p['date_liquidated'] ?? ''),
                    'date_monitoring' => self::cleanDate($p['date_monitoring'] ?? ''),
                    'source_of_funds' => trim($p['source_of_funds'] ?? ''),
                    'latitude' => self::cleanCoord($p['latitude'] ?? ''),
                    'longitude' => self::cleanCoord($p['longitude'] ?? ''),
                    'status' => $p['status'] ?? 'pending',
                ],
            ];
        }

        $beneficiaries = [];
        foreach ((array) ($post['beneficiaries'] ?? []) as $b) {
            if (!is_array($b)) continue;
            $workers = implode(', ', self::pickOptions(self::WORKER_TYPE_OPTIONS, $b['type_of_worker'] ?? []));
            $types   = implode(', ', self::pickOptions(self::BENEFICIARY_TYPE_OPTIONS, $b['type_of_beneficiaries'] ?? []));
            $beneficiaries[] = [
                'id' => (int) ($b['id'] ?? 0),
                'proponent_key' => (string) ($b['proponent_key'] ?? ''),
                'data' => [
                    'last_name' => trim($b['last_name'] ?? ''),
                    'first_name' => trim($b['first_name'] ?? ''),
                    'middle_name' => trim($b['middle_name'] ?? ''),
                    'suffix' => trim($b['suffix'] ?? ''),
                    'gender' => $b['gender'] ?? '',
                    'barangay' => trim($b['barangay'] ?? ''),
                    'municipality' => trim($b['municipality'] ?? ''),
                    'contact_number' => trim($b['contact_number'] ?? ''),
                    'project_name' => trim($b['project_name'] ?? ''),
                    'type_of_worker' => $workers !== '' ? $workers : null,
                    'type_of_beneficiaries' => $types !== '' ? $types : null,
                    'amount_worth' => floatval($b['amount_worth'] ?? 0),
                    'noted_findings' => trim($b['noted_findings'] ?? ''),
                    'date_complied_by_proponent' => self::cleanDate($b['date_complied_by_proponent'] ?? ''),
                    'date_forwarded_to_ro6' => self::cleanDate($b['date_forwarded_to_ro6'] ?? ''),
                    'rpmt_findings' => trim($b['rpmt_findings'] ?? ''),
                    'date_approved' => self::cleanDate($b['date_approved'] ?? ''),
                    'date_forwarded_to_nofo' => self::cleanDate($b['date_forwarded_to_nofo'] ?? ''),
                    'date_turnover' => self::cleanDate($b['date_turnover'] ?? ''),
                    'date_monitoring' => self::cleanDate($b['date_monitoring'] ?? ''),
                    'latitude' => self::cleanCoord($b['latitude'] ?? ''),
                    'longitude' => self::cleanCoord($b['longitude'] ?? ''),
                    'status' => $b['status'] ?? 'pending',
                    'source_of_funds' => trim($b['source_of_funds'] ?? '') ?: null,
                ],
            ];
        }

        return ['budget' => $budget, 'proponents' => $proponents, 'beneficiaries' => $beneficiaries];
    }

    /**
     * Same rules as proponent-form.php and beneficiary-form.php, plus the sharing rules
     * (shares add up to the total, every share > 0, every beneficiary linked to a proponent in this budget).
     *
     * @param string $province the (already resolved) province every member will be saved in
     * @return string[] error messages; empty means valid
     */
    public function validate(array $parsed, string $province, ?int $budgetId = null): array {
        $errors = [];
        $budget = $parsed['budget'];
        $props  = $parsed['proponents'];
        $bens   = $parsed['beneficiaries'];

        if ($budget['title'] === '') $errors[] = 'Budget title is required.';
        if ($budget['total_budget'] <= 0) $errors[] = 'Total budget must be greater than zero.';
        if (!in_array($province, Auth::PROVINCES, true)) $errors[] = 'Invalid province selected.';
        if (!$props) $errors[] = 'Add at least one Proponent.';
        if (!$bens) $errors[] = 'Add at least one Beneficiary.';

        $range = self::PROVINCE_RANGES[$province] ?? null;
        $checkCoords = function (?float $lat, ?float $lng, string $label) use (&$errors, $range, $province) {
            if ($range && $lat !== null && ($lat < $range['lat'][0] || $lat > $range['lat'][1])) {
                $errors[] = "{$label}: latitude must be between {$range['lat'][0]} and {$range['lat'][1]} for {$province}.";
            }
            if ($range && $lng !== null && ($lng < $range['lng'][0] || $lng > $range['lng'][1])) {
                $errors[] = "{$label}: longitude must be between {$range['lng'][0]} and {$range['lng'][1]} for {$province}.";
            }
            if (($lat === null) !== ($lng === null)) {
                $errors[] = "{$label}: both latitude and longitude must be provided together.";
            }
        };

        $keys = [];
        $seenProponents = [];
        foreach ($props as $i => $p) {
            $d = $p['data'];
            $label = 'Proponent #' . ($i + 1) . ($d['proponent_name'] !== '' ? ' (' . $d['proponent_name'] . ')' : '');

            if ($p['key'] === '' || isset($keys[$p['key']])) $errors[] = "{$label}: invalid form data, please reload the page.";
            $keys[$p['key']] = true;

            if (!in_array($d['proponent_type'], self::PROPONENT_TYPES, true)) $errors[] = "{$label}: proponent type is required.";
            if ($d['proponent_name'] === '') $errors[] = "{$label}: name of proponent is required.";
            if ($d['project_title'] === '') $errors[] = "{$label}: project title is required.";
            if ($d['amount'] <= 0) $errors[] = "{$label}: share must be greater than zero.";
            if ($d['total_beneficiaries'] <= 0) $errors[] = "{$label}: total beneficiaries must be greater than zero.";
            if (!in_array($d['category'], self::CATEGORIES, true)) $errors[] = "{$label}: category is required.";
            if (!in_array($d['status'], self::STATUSES_PROPONENT, true)) $errors[] = "{$label}: invalid status.";
            if ($d['male_beneficiaries'] + $d['female_beneficiaries'] > $d['total_beneficiaries']) {
                $errors[] = "{$label}: male + female beneficiaries cannot exceed total beneficiaries.";
            }
            $nameCount = $d['beneficiary_full_name'] === '' ? 0 : count(json_decode($d['beneficiary_full_name'], true) ?: []);
            if ($d['total_beneficiaries'] > 0 && $nameCount !== $d['total_beneficiaries']) {
                $errors[] = "{$label}: please provide a full name for each beneficiary or adjust the total beneficiaries.";
            }
            $checkCoords($d['latitude'], $d['longitude'], $label);

            $dupKey = mb_strtolower($d['proponent_name'] . '|' . $d['project_title']);
            if ($d['proponent_name'] !== '' && isset($seenProponents[$dupKey])) {
                $errors[] = "{$label}: the same proponent name and project title appear twice in this form.";
            }
            $seenProponents[$dupKey] = true;
        }

        $seenBeneficiaries = [];
        foreach ($bens as $i => $b) {
            $d = $b['data'];
            $name = trim($d['first_name'] . ' ' . $d['last_name']);
            $label = 'Beneficiary #' . ($i + 1) . ($name !== '' ? ' (' . $name . ')' : '');

            if ($d['last_name'] === '') $errors[] = "{$label}: last name is required.";
            if ($d['first_name'] === '') $errors[] = "{$label}: first name is required.";
            if (!in_array($d['gender'], ['Male', 'Female'], true)) $errors[] = "{$label}: gender is required.";
            if ($d['barangay'] === '') $errors[] = "{$label}: barangay is required.";
            if ($d['municipality'] === '') $errors[] = "{$label}: municipality is required.";
            if ($d['project_name'] === '') $errors[] = "{$label}: project name is required.";
            if ($d['amount_worth'] <= 0) $errors[] = "{$label}: share must be greater than zero.";
            if (!in_array($d['status'], self::STATUSES_BENEFICIARY, true)) $errors[] = "{$label}: invalid status.";
            if (!isset($keys[$b['proponent_key']])) $errors[] = "{$label}: choose a proponent from this budget.";
            $checkCoords($d['latitude'], $d['longitude'], $label);

            $dupKey = mb_strtolower($d['last_name'] . '|' . $d['first_name'] . '|' . $d['project_name']);
            if ($d['last_name'] !== '' && isset($seenBeneficiaries[$dupKey])) {
                $errors[] = "{$label}: the same name and project appear twice in this form.";
            }
            $seenBeneficiaries[$dupKey] = true;
        }

        // The sharing rule, compared in whole centavos to avoid float drift.
        $allocatedCents = 0;
        foreach ($props as $p) $allocatedCents += (int) round($p['data']['amount'] * 100);
        foreach ($bens as $b)  $allocatedCents += (int) round($b['data']['amount_worth'] * 100);
        $totalCents = (int) round($budget['total_budget'] * 100);
        if ($allocatedCents !== $totalCents) {
            $errors[] = sprintf(
                'The shares add up to ₱%s but the total budget is ₱%s. They must match exactly.',
                number_format($allocatedCents / 100, 2), number_format($totalCents / 100, 2)
            );
        }

        // Duplicate guards against records already in the system (only when nothing else is wrong).
        if (!$errors) {
            foreach ($props as $i => $p) {
                $d = $p['data'];
                $dup = $this->proponents->checkProponentDuplicate($d['proponent_name'], $d['project_title'], $p['id'] ?: null);
                if ($dup) {
                    $errors[] = 'Proponent #' . ($i + 1) . ': a proponent with the same name and project title already exists (Record #' . $dup['id'] . ').';
                }
            }
            foreach ($bens as $i => $b) {
                $d = $b['data'];
                $dup = $this->beneficiaries->checkBeneficiaryDuplicate($d['last_name'], $d['first_name'], $d['project_name'], $b['id'] ?: null);
                if ($dup) {
                    $errors[] = 'Beneficiary #' . ($i + 1) . ': a beneficiary with the same full name and project name already exists (Record #' . $dup['id'] . ').';
                }
            }
        }

        return $errors;
    }

    /** Province a new budget will be saved in (null if invalid). Used by the form page before validate(). */
    public function provinceForNew($requested): ?string {
        return $this->resolveProvince($requested);
    }

    // =========================================================
    // Internal helpers
    // =========================================================

    private function logActivity($action, $recordId, $description): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, ?, 'shared_budgets', ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user_id'] ?? null, $action, $recordId, $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        } catch (PDOException $e) {
            error_log('[SharedBudget Model] Failed to log activity: ' . $e->getMessage());
        }
    }
}
