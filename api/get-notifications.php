<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');
// Live, per-user data: never let the browser or a proxy reuse it.
header('Cache-Control: no-store, no-cache, must-revalidate');

// This is a fetch() endpoint, so answer with JSON instead of the redirects that
// requireLogin() sends (a redirect would hand the page login HTML to parse as JSON).
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'code' => 'session_expired', 'error' => 'Your session has expired. Please sign in again.']);
    exit;
}
try {
    if ($auth->isMaintenanceModeEnabled() && !$auth->hasRole('admin')) {
        http_response_code(503);
        echo json_encode(['success' => false, 'code' => 'maintenance', 'error' => 'The system is under maintenance.']);
        exit;
    }
} catch (Exception $e) {
    // Fail open, same as Auth::requireLogin()
}

// Read what we need from the session, then release the session lock so this
// (up to 14 queries) request does not block the user's other page loads.
$sessionProvince = $auth->getProvince();
$crossProvince   = $auth->isCrossProvince();
session_write_close();

try {
    $db = Database::getInstance()->getConnection();

    // =========================================================
    // Province scope (same rule as the models' provinceScope()):
    // cross-province roles see everything, provincial users see
    // their own province, a provincial user with no province sees nothing.
    // =========================================================
    if ($crossProvince) {
        $pScopeSql  = '';
        $fwScopeSql = '';
        $scopeParams = [];
    } elseif ($sessionProvince === null) {
        $pScopeSql  = ' AND 1 = 0';
        $fwScopeSql = ' AND 1 = 0';
        $scopeParams = [];
    } else {
        $pScopeSql  = ' AND province = ?';
        $fwScopeSql = ' AND fs.province = ?';
        $scopeParams = [$sessionProvince];
    }

    // "Today" is Philippine time, passed in from PHP, because the database server's
    // own date can differ. Same rule as Dashboard, Proponent and FieldworkSchedule.
    $today  = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
    $t      = $db->quote($today);   // safe: generated above, never user input

    $days   = function ($n) { return $n . ' day' . ($n != 1 ? 's' : ''); };
    $fmt    = function ($d) { return $d ? date('M d, Y', strtotime($d)) : ''; };

    $notifications = [];
    $totals = ['overdue' => 0, 'upcoming' => 0];   // real totals, even when a list is capped
    $capped = false;

    /**
     * Run one notification query.
     *   $from  : "FROM ... WHERE ..." (scope fragment already appended)
     *   $select: column list
     *   $order : "ORDER BY ..."
     *   $urgency: 'overdue' counts toward the overdue total, anything else toward upcoming
     *   $map   : row => notification array
     * Returns nothing; appends to $notifications and adds to $totals.
     */
    $run = function (string $select, string $from, string $order, array $params, string $urgency, callable $map, int $limit = 25, string $label = '')
        use ($db, &$notifications, &$totals, &$capped) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) {$from}");
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $stmt = $db->prepare("SELECT {$select} {$from} {$order} LIMIT " . (int) $limit);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totals[$urgency === 'overdue' ? 'overdue' : 'upcoming'] += $total;
            if ($total > count($rows)) {
                $capped = true;
            }
            foreach ($rows as $row) {
                $notifications[] = $map($row);
            }
        } catch (PDOException $e) {
            // One broken query (e.g. a table missing on an older install) must not hide the rest.
            error_log("[Notifications] {$label} query failed: " . $e->getMessage());
        }
    };

    // ---------------------------------------------------------
    // Overdue liquidations. Liquidated and monitored projects are past this step even
    // when date_liquidated was never filled in, same rule as Dashboard::getOverdueLiquidations().
    // ---------------------------------------------------------
    $run(
        "id, proponent_name, project_title, province, liquidation_deadline,
         DATEDIFF({$t}, liquidation_deadline) AS days_overdue",
        "FROM proponents
         WHERE liquidation_deadline IS NOT NULL AND date_liquidated IS NULL
           AND status NOT IN ('liquidated', 'monitored')
           AND liquidation_deadline < {$t} {$pScopeSql}",
        "ORDER BY liquidation_deadline ASC",
        $scopeParams, 'overdue',
        function ($r) use ($days, $fmt) {
            $n = (int) $r['days_overdue'];
            return [
                'id' => 'liquidation_' . $r['id'], 'type' => 'liquidation', 'category' => 'liquidation',
                'urgency' => 'overdue', 'severity' => 'danger',
                'title' => 'Overdue Liquidation',
                'subject' => $r['proponent_name'], 'project' => $r['project_title'], 'province' => $r['province'],
                'message' => $r['proponent_name'] . ' - Liquidation deadline passed',
                'details' => 'Deadline: ' . $fmt($r['liquidation_deadline']) . ' (' . $days($n) . ' overdue)',
                'chip' => $days($n) . ' overdue',
                'href' => 'proponent-view.php?id=' . (int) $r['id'],
                'proponent_id' => $r['id'], 'proponent_name' => $r['proponent_name'], 'project_title' => $r['project_title'],
                'deadline' => $r['liquidation_deadline'], 'days_overdue' => $n,
                'timestamp' => strtotime($r['liquidation_deadline']),
            ];
        },
        25, 'Overdue liquidation'
    );

    // Upcoming liquidation deadlines (within 14 days)
    $run(
        "id, proponent_name, project_title, province, liquidation_deadline,
         DATEDIFF(liquidation_deadline, {$t}) AS days_remaining",
        "FROM proponents
         WHERE liquidation_deadline IS NOT NULL AND date_liquidated IS NULL
           AND status NOT IN ('liquidated', 'monitored')
           AND liquidation_deadline >= {$t}
           AND liquidation_deadline <= DATE_ADD({$t}, INTERVAL 14 DAY) {$pScopeSql}",
        "ORDER BY liquidation_deadline ASC",
        $scopeParams, 'upcoming',
        function ($r) use ($days, $fmt) {
            $n = (int) $r['days_remaining'];
            return [
                'id' => 'upcoming_liquidation_' . $r['id'], 'type' => 'upcoming_liquidation', 'category' => 'liquidation',
                'urgency' => 'upcoming', 'severity' => 'warning',
                'title' => 'Upcoming Liquidation Deadline',
                'subject' => $r['proponent_name'], 'project' => $r['project_title'], 'province' => $r['province'],
                'message' => $r['proponent_name'] . ' - Liquidation due soon',
                'details' => 'Deadline: ' . $fmt($r['liquidation_deadline']) . ' (' . $days($n) . ' remaining)',
                'chip' => $n === 0 ? 'due today' : 'in ' . $days($n),
                'href' => 'proponent-view.php?id=' . (int) $r['id'],
                'proponent_id' => $r['id'], 'proponent_name' => $r['proponent_name'], 'project_title' => $r['project_title'],
                'deadline' => $r['liquidation_deadline'], 'days_remaining' => $n,
                'timestamp' => strtotime($r['liquidation_deadline']),
            ];
        },
        25, 'Upcoming liquidation'
    );

    // ---------------------------------------------------------
    // Beneficiary monitoring: due 30 days after turn-over, until a monitoring date is
    // entered or the record is marked monitored.
    // ---------------------------------------------------------
    $benefName = "TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))";
    $dueSql    = "DATE_ADD(date_turnover, INTERVAL 30 DAY)";

    $run(
        "id, {$benefName} AS name, project_name, province, {$dueSql} AS monitoring_due_date,
         DATEDIFF({$dueSql}, {$t}) AS days_remaining",
        "FROM beneficiaries
         WHERE date_turnover IS NOT NULL AND date_monitoring IS NULL AND status != 'monitored'
           AND {$dueSql} >= {$t} AND {$dueSql} <= DATE_ADD({$t}, INTERVAL 7 DAY) {$pScopeSql}",
        "ORDER BY date_turnover ASC",
        $scopeParams, 'upcoming',
        function ($r) use ($days, $fmt) {
            $n = (int) $r['days_remaining'];
            return [
                'id' => 'upcoming_monitoring_beneficiary_' . $r['id'], 'type' => 'upcoming_monitoring', 'category' => 'monitoring',
                'urgency' => 'upcoming', 'severity' => 'info',
                'title' => 'Upcoming Monitoring (Beneficiary)',
                'subject' => $r['name'], 'project' => $r['project_name'], 'province' => $r['province'],
                'message' => $r['name'] . ' - Monitoring due soon',
                'details' => 'Expected: ' . $fmt($r['monitoring_due_date']) . ' (' . $days($n) . ' remaining)',
                'chip' => $n === 0 ? 'due today' : 'in ' . $days($n),
                'href' => 'beneficiary-view.php?id=' . (int) $r['id'],
                'beneficiary_id' => $r['id'], 'beneficiary_name' => $r['name'], 'project_name' => $r['project_name'],
                'deadline' => $r['monitoring_due_date'], 'days_remaining' => $n,
                'timestamp' => strtotime($r['monitoring_due_date']),
            ];
        },
        25, 'Upcoming beneficiary monitoring'
    );

    $run(
        "id, {$benefName} AS name, project_name, province, {$dueSql} AS monitoring_due_date,
         DATEDIFF({$t}, {$dueSql}) AS days_overdue",
        "FROM beneficiaries
         WHERE date_turnover IS NOT NULL AND date_monitoring IS NULL AND status != 'monitored'
           AND {$dueSql} < {$t} {$pScopeSql}",
        "ORDER BY date_turnover ASC",
        $scopeParams, 'overdue',
        function ($r) use ($days, $fmt) {
            $n = (int) $r['days_overdue'];
            return [
                'id' => 'overdue_monitoring_beneficiary_' . $r['id'], 'type' => 'monitoring', 'category' => 'monitoring',
                'urgency' => 'overdue', 'severity' => 'warning',
                'title' => 'Overdue Monitoring (Beneficiary)',
                'subject' => $r['name'], 'project' => $r['project_name'], 'province' => $r['province'],
                'message' => $r['name'] . ' - Monitoring overdue',
                'details' => 'Expected: ' . $fmt($r['monitoring_due_date']) . ' (' . $days($n) . ' overdue)',
                'chip' => $days($n) . ' overdue',
                'href' => 'beneficiary-view.php?id=' . (int) $r['id'],
                'beneficiary_id' => $r['id'], 'beneficiary_name' => $r['name'], 'project_name' => $r['project_name'],
                'deadline' => $r['monitoring_due_date'], 'days_overdue' => $n,
                'timestamp' => strtotime($r['monitoring_due_date']),
            ];
        },
        25, 'Overdue beneficiary monitoring'
    );

    // ---------------------------------------------------------
    // Proponent monitoring (rule unchanged; what date_monitoring means is a product decision)
    // ---------------------------------------------------------
    $run(
        "id, proponent_name, project_title, province, date_monitoring AS expected_monitoring_date,
         DATEDIFF(date_monitoring, {$t}) AS days_remaining",
        "FROM proponents
         WHERE date_monitoring IS NOT NULL AND date_monitoring >= {$t}
           AND date_monitoring <= DATE_ADD({$t}, INTERVAL 14 DAY)
           AND status != 'monitored' {$pScopeSql}",
        "ORDER BY date_monitoring ASC",
        $scopeParams, 'upcoming',
        function ($r) use ($days, $fmt) {
            $n = (int) $r['days_remaining'];
            return [
                'id' => 'upcoming_monitoring_proponent_' . $r['id'], 'type' => 'upcoming_monitoring', 'category' => 'monitoring',
                'urgency' => 'upcoming', 'severity' => 'info',
                'title' => 'Upcoming Monitoring (Proponent)',
                'subject' => $r['proponent_name'], 'project' => $r['project_title'], 'province' => $r['province'],
                'message' => $r['proponent_name'] . ' - Monitoring scheduled soon',
                'details' => 'Scheduled: ' . $fmt($r['expected_monitoring_date']) . ' (' . $days($n) . ' remaining)',
                'chip' => $n === 0 ? 'due today' : 'in ' . $days($n),
                'href' => 'proponent-view.php?id=' . (int) $r['id'],
                'proponent_id' => $r['id'], 'proponent_name' => $r['proponent_name'], 'project_title' => $r['project_title'],
                'deadline' => $r['expected_monitoring_date'], 'days_remaining' => $n,
                'timestamp' => strtotime($r['expected_monitoring_date']),
            ];
        },
        25, 'Upcoming proponent monitoring'
    );

    $run(
        "id, proponent_name, project_title, province, date_monitoring AS expected_monitoring_date,
         DATEDIFF({$t}, date_monitoring) AS days_overdue",
        "FROM proponents
         WHERE date_monitoring IS NOT NULL AND date_monitoring < {$t}
           AND status != 'monitored' {$pScopeSql}",
        "ORDER BY date_monitoring ASC",
        $scopeParams, 'overdue',
        function ($r) use ($days, $fmt) {
            $n = (int) $r['days_overdue'];
            return [
                'id' => 'overdue_monitoring_proponent_' . $r['id'], 'type' => 'monitoring', 'category' => 'monitoring',
                'urgency' => 'overdue', 'severity' => 'warning',
                'title' => 'Overdue Monitoring (Proponent)',
                'subject' => $r['proponent_name'], 'project' => $r['project_title'], 'province' => $r['province'],
                'message' => $r['proponent_name'] . ' - Monitoring overdue',
                'details' => 'Scheduled: ' . $fmt($r['expected_monitoring_date']) . ' (' . $days($n) . ' overdue)',
                'chip' => $days($n) . ' overdue',
                'href' => 'proponent-view.php?id=' . (int) $r['id'],
                'proponent_id' => $r['id'], 'proponent_name' => $r['proponent_name'], 'project_title' => $r['project_title'],
                'deadline' => $r['expected_monitoring_date'], 'days_overdue' => $n,
                'timestamp' => strtotime($r['expected_monitoring_date']),
            ];
        },
        25, 'Overdue proponent monitoring'
    );

    // ---------------------------------------------------------
    // Fieldwork
    // ---------------------------------------------------------
    $fwFrom = "FROM fieldwork_schedule fs LEFT JOIN users u ON fs.assigned_user_id = u.id";
    $fwCols = "fs.id, fs.title, fs.location, fs.province, fs.start_date, fs.end_date, u.full_name AS assigned_user_name";
    $fwItem = function ($r, $id, $title, $message, $details, $severity, $urgency, $chip) {
        return [
            'id' => $id . $r['id'], 'type' => 'fieldwork', 'category' => 'fieldwork',
            'urgency' => $urgency, 'severity' => $severity,
            'title' => $title,
            'subject' => $r['title'], 'project' => $r['location'] ?: '', 'province' => $r['province'],
            'message' => $message, 'details' => $details, 'chip' => $chip,
            'href' => 'fieldwork-schedule.php',
            'fieldwork_id' => $r['id'],
            'timestamp' => strtotime($r['start_date']),
        ];
    };

    // Today: starts today, or a multi-day activity that started earlier and is still running
    $run(
        $fwCols,
        "{$fwFrom}
         WHERE fs.status IN ('pending', 'ongoing') AND fs.start_date <= {$t}
           AND COALESCE(fs.end_date, fs.start_date) >= {$t} {$fwScopeSql}",
        "ORDER BY fs.start_date ASC",
        $scopeParams, 'upcoming',
        function ($r) use ($fwItem, $t) {
            return $fwItem($r, 'fieldwork_today_', 'Fieldwork Today',
                $r['title'] . ' is scheduled for today',
                'Assigned: ' . ($r['assigned_user_name'] ?? 'N/A') . ($r['location'] ? ' | Location: ' . $r['location'] : ''),
                'warning', 'upcoming', 'today');
        },
        25, 'Fieldwork today'
    );

    // Tomorrow
    $run(
        $fwCols,
        "{$fwFrom}
         WHERE fs.status = 'pending' AND DATEDIFF(fs.start_date, {$t}) = 1 {$fwScopeSql}",
        "ORDER BY fs.start_date ASC",
        $scopeParams, 'upcoming',
        function ($r) use ($fwItem, $fmt) {
            return $fwItem($r, 'fieldwork_1day_', 'Fieldwork Tomorrow',
                $r['title'] . ' scheduled in 1 day',
                'Date: ' . $fmt($r['start_date']) . ' | Assigned: ' . ($r['assigned_user_name'] ?? 'N/A'),
                'info', 'upcoming', 'tomorrow');
        },
        25, 'Fieldwork tomorrow'
    );

    // In 2-3 days
    $run(
        $fwCols . ", DATEDIFF(fs.start_date, {$t}) AS days_remaining",
        "{$fwFrom}
         WHERE fs.status = 'pending' AND DATEDIFF(fs.start_date, {$t}) BETWEEN 2 AND 3 {$fwScopeSql}",
        "ORDER BY fs.start_date ASC",
        $scopeParams, 'upcoming',
        function ($r) use ($fwItem, $fmt) {
            $n = (int) $r['days_remaining'];
            return $fwItem($r, 'fieldwork_upcoming_', 'Upcoming Fieldwork',
                $r['title'] . ' scheduled in ' . $n . ' days',
                'Date: ' . $fmt($r['start_date']) . ' | Assigned: ' . ($r['assigned_user_name'] ?? 'N/A'),
                'info', 'upcoming', 'in ' . $n . ' days');
        },
        25, 'Fieldwork upcoming'
    );

    // Past due and still pending/ongoing: the Fieldwork page asks the user to mark it completed or missed
    $run(
        $fwCols . ", DATEDIFF({$t}, COALESCE(fs.end_date, fs.start_date)) AS days_overdue",
        "{$fwFrom}
         WHERE fs.status IN ('pending', 'ongoing') AND COALESCE(fs.end_date, fs.start_date) < {$t} {$fwScopeSql}",
        "ORDER BY fs.start_date ASC",
        $scopeParams, 'overdue',
        function ($r) use ($fwItem, $fmt, $days) {
            $n = (int) $r['days_overdue'];
            return $fwItem($r, 'fieldwork_overdue_', 'Overdue Fieldwork',
                $r['title'] . ' — past due, not yet marked completed or missed',
                'Was due: ' . $fmt($r['end_date'] ?: $r['start_date']) . ' (' . $days($n) . ' overdue)',
                'danger', 'overdue', $days($n) . ' overdue');
        },
        25, 'Fieldwork overdue'
    );

    // Most severe first, then the oldest date
    usort($notifications, function ($a, $b) {
        $severityOrder = ['danger' => 0, 'warning' => 1, 'info' => 2];
        $cmp = $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']];
        return $cmp !== 0 ? $cmp : $a['timestamp'] <=> $b['timestamp'];
    });

    echo json_encode([
        'success'       => true,
        // Real number of alerts (not just the ones listed), so the badge is honest
        'count'         => $totals['overdue'] + $totals['upcoming'],
        'totals'        => $totals,
        'truncated'     => $capped,
        'generated_at'  => time(),
        'notifications' => $notifications,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications'
    ]);
    error_log('[Notifications] ' . $e->getMessage());
}
