<?php
// models/PublicStats.php
// Province totals for the public landing page (landing.php and api/public-stats.php).
// Aggregates only: no names, projects or record details ever leave this class.
//
// Counts approved and later records, the same rule the map and CQPR use, so pending
// encodes never show up as released money.

class PublicStats {
    private $db;

    private const APPROVED_BENEFICIARY_STATUSES = ['approved', 'implemented', 'monitored'];
    private const APPROVED_PROPONENT_STATUSES   = ['approved', 'implemented', 'liquidated', 'monitored'];

    /** Seconds a computed result is reused before the database is asked again */
    private const CACHE_TTL = 600;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * @return array{provinces: array<string, array>, all: array, updated_at: string}
     */
    public function getTotals(): array {
        $cached = $this->readCache();
        if ($cached !== null) {
            return $cached;
        }

        $empty = ['group_projects' => 0, 'individual_grantees' => 0, 'people_served' => 0, 'amount_released' => 0.0];
        $provinces = array_fill_keys(Auth::PROVINCES, $empty);

        $placeholders = implode(',', array_fill(0, count(Auth::PROVINCES), '?'));

        // =========================================================
        // Group projects (proponents)
        // =========================================================
        $statusSql = implode(',', array_fill(0, count(self::APPROVED_PROPONENT_STATUSES), '?'));
        $stmt = $this->db->prepare(
            "SELECT province, COUNT(*) AS projects, COALESCE(SUM(total_beneficiaries), 0) AS people,
                    COALESCE(SUM(amount), 0) AS amount
             FROM proponents
             WHERE status IN ($statusSql) AND province IN ($placeholders)
             GROUP BY province"
        );
        $stmt->execute(array_merge(self::APPROVED_PROPONENT_STATUSES, Auth::PROVINCES));
        foreach ($stmt->fetchAll() as $row) {
            $p = &$provinces[$row['province']];
            $p['group_projects']  += (int) $row['projects'];
            $p['people_served']   += (int) $row['people'];
            $p['amount_released'] += (float) $row['amount'];
            unset($p);
        }

        // =========================================================
        // Individual grantees (beneficiaries)
        // =========================================================
        $statusSql = implode(',', array_fill(0, count(self::APPROVED_BENEFICIARY_STATUSES), '?'));
        $stmt = $this->db->prepare(
            "SELECT province, COUNT(*) AS grantees, COALESCE(SUM(amount_worth), 0) AS amount
             FROM beneficiaries
             WHERE status IN ($statusSql) AND province IN ($placeholders)
             GROUP BY province"
        );
        $stmt->execute(array_merge(self::APPROVED_BENEFICIARY_STATUSES, Auth::PROVINCES));
        foreach ($stmt->fetchAll() as $row) {
            $p = &$provinces[$row['province']];
            $p['individual_grantees'] += (int) $row['grantees'];
            $p['people_served']       += (int) $row['grantees'];
            $p['amount_released']     += (float) $row['amount'];
            unset($p);
        }

        $all = $empty;
        foreach ($provinces as $p) {
            foreach ($p as $key => $value) {
                $all[$key] += $value;
            }
        }

        $result = [
            'provinces'  => $provinces,
            'all'        => $all,
            'updated_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format(DATE_ATOM),
        ];
        $this->writeCache($result);
        return $result;
    }

    // =========================================================
    // File cache (shared hosting has no APCu)
    // =========================================================

    private function cacheFile(): string {
        return sys_get_temp_dir() . '/dileep-public-stats-' . md5(__DIR__ . (getenv('DB_NAME') ?: '')) . '.json';
    }

    private function readCache(): ?array {
        $file = $this->cacheFile();
        if (!is_file($file) || filemtime($file) < time() - self::CACHE_TTL) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        return is_array($data) && isset($data['provinces'], $data['all']) ? $data : null;
    }

    private function writeCache(array $data): void {
        $file = $this->cacheFile();
        if (@file_put_contents($file . '.tmp', json_encode($data), LOCK_EX) !== false) {
            @rename($file . '.tmp', $file);
        }
    }
}
