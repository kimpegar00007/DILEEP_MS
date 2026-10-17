<?php
// models/Dashboard.php
// Every number, chart, map point and overdue alert on the dashboard comes from getData().
// index.php renders its first view from it and api/dashboard-stats.php returns the same
// structure when filters are applied, so the two can never disagree.
//
// Province-scoped like the other models: provincial users only ever see their session
// province; super_admin and regional_director may narrow to one province.

class Dashboard {
    private $db;

    /** Roles that see all provinces (no province filter applied) */
    private const CROSS_PROVINCE_ROLES = ['super_admin', 'regional_director'];

    /** "Approved" means approved or any later step, matching the map and CQPR */
    private const APPROVED_BENEFICIARY_STATUSES = ['approved', 'implemented', 'monitored'];
    private const APPROVED_PROPONENT_STATUSES   = ['approved', 'implemented', 'liquidated', 'monitored'];

    /** How many overdue proponents the alert lists by name */
    private const OVERDUE_LIST_LIMIT = 5;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * @param array $filters province (honoured for cross-province roles only),
     *                       year (int|null), approved_only (bool)
     */
    public function getData(array $filters = []): array {
        $isCrossProvince = in_array($_SESSION['role'] ?? '', self::CROSS_PROVINCE_ROLES, true);
        $province        = $this->resolveProvince($filters['province'] ?? null, $isCrossProvince);
        $year            = !empty($filters['year']) ? (int) $filters['year'] : null;
        $approvedOnly    = !empty($filters['approved_only']);

        [$pSql, $pParams] = $this->provinceCondition($province, $isCrossProvince);

        // Shared WHERE fragments for beneficiaries and proponents
        $yearSql    = '';
        $yearParams = [];
        if ($year) {
            $yearSql    = ' AND (YEAR(date_approved) = ? OR (date_approved IS NULL AND YEAR(created_at) = ?))';
            $yearParams = [$year, $year];
        }

        $benefWhere  = $pSql . $yearSql . ($approvedOnly ? $this->statusIn(self::APPROVED_BENEFICIARY_STATUSES) : '');
        $propWhere   = $pSql . $yearSql . ($approvedOnly ? $this->statusIn(self::APPROVED_PROPONENT_STATUSES) : '');
        $whereParams = array_merge($pParams, $yearParams);

        // Municipalities and districts repeat across provinces, so the all-province view
        // groups and labels them by province too.
        $labelByProvince = $isCrossProvince && $province === null;

        $beneficiaryStats = $this->getBeneficiaryStats($benefWhere, $whereParams);
        $proponentStats   = $this->getProponentStats($propWhere, $whereParams);

        return [
            'filters' => [
                'province'      => $province,
                'year'          => $year,
                'approved_only' => $approvedOnly,
            ],
            'isCrossProvince'          => $isCrossProvince,
            'hasData'                  => $beneficiaryStats['total'] > 0 || $proponentStats['total'] > 0,
            'beneficiaryStats'         => $beneficiaryStats,
            'proponentStats'           => $proponentStats,
            'fieldworkStats'           => $this->getFieldworkStats($pSql, $pParams, $year),
            'municipalityDistribution' => $this->getMunicipalityDistribution($benefWhere, $whereParams, $labelByProvince),
            'districtDistribution'     => $this->getDistrictDistribution($propWhere, $whereParams, $labelByProvince),
            'workerTypeDistribution'   => $this->getWorkerTypeDistribution($benefWhere, $propWhere, $whereParams),
            'fundingSourceBreakdown'   => $this->getFundingSourceBreakdown($benefWhere, $propWhere, $whereParams),
            'categoryDistribution'     => $this->getCategoryDistribution($propWhere, $whereParams),
            'trends'                   => $this->getMonthlyTrends($benefWhere, $propWhere, $whereParams, $year),
            'overdue'                  => $this->getOverdueLiquidations($pSql, $pParams),
            'mapPoints'                => $this->getMapPoints($benefWhere, $propWhere, $whereParams),
        ];
    }

    // =========================================================
    // Scope helpers
    // =========================================================

    /** The province the data is limited to, or null for all provinces */
    private function resolveProvince($requested, bool $isCrossProvince): ?string {
        if ($isCrossProvince) {
            return in_array($requested, Auth::PROVINCES, true) ? $requested : null;
        }
        return $_SESSION['province'] ?? null;
    }

    private function provinceCondition(?string $province, bool $isCrossProvince): array {
        if ($province !== null) {
            return [' AND province = ?', [$province]];
        }
        // Provincial user without a province: fail closed
        return $isCrossProvince ? ['', []] : [' AND 1 = 0', []];
    }

    /** Status list is a class constant, so it is safe to inline */
    private function statusIn(array $statuses): string {
        return " AND status IN ('" . implode("', '", $statuses) . "')";
    }

    private function fetchAll(string $sql, array $params): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchRow(string $sql, array $params): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** SUM() returns NULL on empty sets and strings otherwise; normalise to numbers */
    private function toNumbers(array $row, array $floatKeys = []): array {
        foreach ($row as $key => $value) {
            $row[$key] = in_array($key, $floatKeys, true) ? (float) $value : (int) $value;
        }
        return $row;
    }

    private function today(): DateTimeImmutable {
        return new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
    }

    // =========================================================
    // Stat cards and status overview
    // =========================================================

    private function getBeneficiaryStats(string $where, array $params): array {
        $row = $this->fetchRow(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved'    THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented,
                SUM(CASE WHEN status = 'monitored'   THEN 1 ELSE 0 END) as monitored,
                SUM(CASE WHEN gender = 'Male'        THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN gender = 'Female'      THEN 1 ELSE 0 END) as female_count,
                SUM(CASE WHEN (type_of_beneficiaries LIKE '%Child Labor%' OR type_of_beneficiaries LIKE '%PCL%'
                            OR type_of_worker LIKE '%Child Labor%' OR type_of_worker LIKE '%PCL%') THEN 1 ELSE 0 END) as pcl_count,
                SUM(amount_worth) as total_amount
            FROM beneficiaries WHERE 1=1" . $where,
            $params
        );
        return $this->toNumbers($row, ['total_amount']);
    }

    private function getProponentStats(string $where, array $params): array {
        $row = $this->fetchRow(
            "SELECT
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
                SUM(CASE WHEN (type_of_beneficiaries LIKE '%Child Labor%' OR type_of_beneficiaries LIKE '%PCL%'
                            OR type_of_workers LIKE '%Child Labor%' OR type_of_workers LIKE '%PCL%') THEN 1 ELSE 0 END) as pcl_count,
                SUM(amount) as total_amount
            FROM proponents WHERE 1=1" . $where,
            $params
        );
        return $this->toNumbers($row, ['total_amount']);
    }

    private function getFieldworkStats(string $pSql, array $pParams, ?int $year): array {
        $sql    = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'ongoing'   THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed
            FROM fieldwork_schedule WHERE 1=1" . $pSql;
        $params = $pParams;
        if ($year) {
            $sql     .= ' AND YEAR(start_date) = ?';
            $params[] = $year;
        }
        // fieldwork_schedule is created lazily by FieldworkSchedule::ensureTable(), so a fresh
        // install may not have it yet; show zeros instead of failing the whole dashboard.
        try {
            return $this->toNumbers($this->fetchRow($sql, $params));
        } catch (PDOException $e) {
            error_log('[Dashboard] Fieldwork stats unavailable: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'ongoing' => 0, 'completed' => 0, 'missed' => 0];
        }
    }

    // =========================================================
    // Charts (each returns rows of {label, count[, total_amount]})
    // =========================================================

    private function getMunicipalityDistribution(string $where, array $params, bool $labelByProvince): array {
        $rows = $this->fetchAll(
            "SELECT municipality, province, COUNT(*) as count
            FROM beneficiaries
            WHERE municipality IS NOT NULL AND municipality != ''" . $where .
            " GROUP BY province, municipality ORDER BY count DESC, municipality ASC LIMIT 10",
            $params
        );
        return array_map(function ($r) use ($labelByProvince) {
            return [
                'label' => $labelByProvince && $r['province'] ? "{$r['municipality']} ({$r['province']})" : $r['municipality'],
                'count' => (int) $r['count'],
            ];
        }, $rows);
    }

    private function getDistrictDistribution(string $where, array $params, bool $labelByProvince): array {
        $rows = $this->fetchAll(
            "SELECT district, province, COUNT(*) as count
            FROM proponents
            WHERE district IS NOT NULL AND district != ''" . $where .
            " GROUP BY province, district ORDER BY count DESC, district ASC",
            $params
        );
        return array_map(function ($r) use ($labelByProvince) {
            return [
                'label' => $labelByProvince && $r['province'] ? "{$r['district']} ({$r['province']})" : $r['district'],
                'count' => (int) $r['count'],
            ];
        }, $rows);
    }

    /**
     * Worker types are multi-select checkboxes stored as one ", "-joined string, so each
     * stored combination is split and every type it names is counted once for that record.
     */
    private function getWorkerTypeDistribution(string $benefWhere, string $propWhere, array $params): array {
        $combos = array_merge(
            $this->fetchAll(
                "SELECT type_of_worker as types, COUNT(*) as count FROM beneficiaries
                WHERE type_of_worker IS NOT NULL AND type_of_worker != ''" . $benefWhere .
                " GROUP BY type_of_worker",
                $params
            ),
            $this->fetchAll(
                "SELECT type_of_workers as types, COUNT(*) as count FROM proponents
                WHERE type_of_workers IS NOT NULL AND type_of_workers != ''" . $propWhere .
                " GROUP BY type_of_workers",
                $params
            )
        );

        $counts = [];
        foreach ($combos as $combo) {
            $types = array_unique(array_filter(array_map('trim', explode(',', $combo['types']))));
            foreach ($types as $type) {
                $counts[$type] = ($counts[$type] ?? 0) + (int) $combo['count'];
            }
        }
        arsort($counts);

        $rows = [];
        foreach ($counts as $type => $count) {
            $rows[] = ['label' => $type, 'count' => $count];
        }
        return $rows;
    }

    private function getFundingSourceBreakdown(string $benefWhere, string $propWhere, array $params): array {
        $sourceExpr = "CASE WHEN source_of_funds IS NULL OR source_of_funds = '' THEN 'Not Specified' ELSE source_of_funds END";

        $parts = array_merge(
            $this->fetchAll(
                "SELECT {$sourceExpr} as source, COUNT(*) as count, SUM(amount_worth) as total_amount
                FROM beneficiaries WHERE 1=1" . $benefWhere . " GROUP BY {$sourceExpr}",
                $params
            ),
            $this->fetchAll(
                "SELECT {$sourceExpr} as source, COUNT(*) as count, SUM(amount) as total_amount
                FROM proponents WHERE 1=1" . $propWhere . " GROUP BY {$sourceExpr}",
                $params
            )
        );

        $combined = [];
        foreach ($parts as $part) {
            $source = $part['source'];
            if (!isset($combined[$source])) {
                $combined[$source] = ['label' => $source, 'count' => 0, 'total_amount' => 0.0];
            }
            $combined[$source]['count']        += (int) $part['count'];
            $combined[$source]['total_amount'] += (float) $part['total_amount'];
        }

        $rows = array_values($combined);
        usort($rows, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
        return $rows;
    }

    /** Beneficiaries have no category, so this is group projects only */
    private function getCategoryDistribution(string $where, array $params): array {
        $rows = $this->fetchAll(
            "SELECT category, COUNT(*) as count
            FROM proponents
            WHERE category IS NOT NULL AND category != ''" . $where .
            " GROUP BY category ORDER BY count DESC",
            $params
        );
        return array_map(fn($r) => ['label' => $r['category'], 'count' => (int) $r['count']], $rows);
    }

    /**
     * Approvals per month. No year picked: the last 12 months including this one.
     * Year picked: January to December of that year (to this month for the current year).
     * Months without approvals are returned as 0 so both lines share one axis.
     */
    private function getMonthlyTrends(string $benefWhere, string $propWhere, array $params, ?int $year): array {
        $thisMonth = $this->today()->modify('first day of this month');

        if ($year) {
            $start = new DateTimeImmutable(sprintf('%04d-01-01', $year), new DateTimeZone('Asia/Manila'));
            $end   = ($year === (int) $thisMonth->format('Y')) ? $thisMonth : $start->modify('+11 months');
        } else {
            $start = $thisMonth->modify('-11 months');
            $end   = $thisMonth;
        }

        $months = [];
        for ($m = $start; $m <= $end; $m = $m->modify('+1 month')) {
            $months[] = $m->format('Y-m');
        }
        $rangeSql    = ' AND date_approved >= ? AND date_approved < ?';
        $rangeParams = [$start->format('Y-m-d'), $end->modify('+1 month')->format('Y-m-d')];

        $count = function (string $table, string $where) use ($params, $rangeSql, $rangeParams, $months) {
            $rows = $this->fetchAll(
                "SELECT DATE_FORMAT(date_approved, '%Y-%m') as month, COUNT(*) as count
                FROM {$table}
                WHERE date_approved IS NOT NULL" . $where . $rangeSql .
                " GROUP BY DATE_FORMAT(date_approved, '%Y-%m')",
                array_merge($params, $rangeParams)
            );
            $byMonth = array_column($rows, 'count', 'month');
            return array_map(fn($m) => (int) ($byMonth[$m] ?? 0), $months);
        };

        return [
            'months'        => $months,
            'beneficiaries' => $count('beneficiaries', $benefWhere),
            'proponents'    => $count('proponents', $propWhere),
        ];
    }

    // =========================================================
    // Overdue alert and map
    // =========================================================

    /**
     * Proponents past their liquidation deadline that are not liquidated yet. Liquidated and
     * monitored projects are past that step even when date_liquidated was never filled in.
     * Only the province filter applies: an overdue project is overdue whatever year it was approved.
     */
    private function getOverdueLiquidations(string $pSql, array $pParams): array {
        $today = $this->today();
        $rows  = $this->fetchAll(
            "SELECT id, proponent_name, project_title, liquidation_deadline
            FROM proponents
            WHERE date_liquidated IS NULL
            AND liquidation_deadline IS NOT NULL
            AND liquidation_deadline < ?
            AND status NOT IN ('liquidated', 'monitored')" . $pSql .
            " ORDER BY liquidation_deadline ASC",
            array_merge([$today->format('Y-m-d')], $pParams)
        );

        $items = [];
        foreach (array_slice($rows, 0, self::OVERDUE_LIST_LIMIT) as $row) {
            $deadline = new DateTimeImmutable($row['liquidation_deadline'], new DateTimeZone('Asia/Manila'));
            $items[]  = [
                'id'                => (int) $row['id'],
                'proponent_name'    => $row['proponent_name'],
                'project_title'     => $row['project_title'],
                'deadline_label'    => $deadline->format('F d, Y'),
                'days_overdue'      => (int) $deadline->diff($today)->days,
            ];
        }

        return ['total' => count($rows), 'items' => $items];
    }

    /** Approved-and-later records with coordinates inside the region's bounding box */
    private function getMapPoints(string $benefWhere, string $propWhere, array $params): array {
        $coordSql = " AND latitude IS NOT NULL AND longitude IS NOT NULL
                AND latitude != 0 AND longitude != 0
                AND latitude BETWEEN 9.0 AND 12.0
                AND longitude BETWEEN 122.0 AND 124.0";

        $beneficiaries = $this->fetchAll(
            "SELECT id, CONCAT(first_name, ' ', last_name) as name, project_name as project,
                CONCAT_WS(', ', NULLIF(barangay, ''), NULLIF(municipality, '')) as location,
                amount_worth as amount, latitude, longitude, status, NULL as total_beneficiaries,
                'beneficiary' as type
            FROM beneficiaries
            WHERE status IN ('approved', 'implemented', 'monitored')" . $coordSql . $benefWhere,
            $params
        );

        $proponents = $this->fetchAll(
            "SELECT id, proponent_name as name, project_title as project, district as location,
                amount, latitude, longitude, status, total_beneficiaries,
                'proponent' as type
            FROM proponents
            WHERE status IN ('approved', 'implemented', 'liquidated', 'monitored')" . $coordSql . $propWhere,
            $params
        );

        return array_map(function ($p) {
            $p['latitude']  = (float) $p['latitude'];
            $p['longitude'] = (float) $p['longitude'];
            $p['amount']    = (float) $p['amount'];
            return $p;
        }, array_merge($beneficiaries, $proponents));
    }
}
