<?php
// includes/FieldworkNotifier.php
// Email alerts for the Schedule of Activities / Fieldwork module.
//
// Everything goes through an outbox table (fieldwork_notifications):
//   1. Something happens (an activity is assigned, a day passes) -> a row is queued.
//      A unique key on (activity, event, event key, recipient) means the same alert is never queued twice.
//   2. dispatch() sends queued rows. It runs right after a save (so assignment emails are
//      immediate) and from cron/fieldwork-notifications.php every few minutes (so reminders and
//      overdue notices go out even when nobody is logged in).
//   3. Just before sending, the row is checked against the activity as it is now: an activity
//      that was completed, deleted, reassigned or rescheduled in the meantime is skipped.
//
// Events:  assigned, rescheduled, reminder_tomorrow, reminder_today, overdue, missed.
// Nothing here is allowed to break a save: queue calls catch their own errors.

require_once __DIR__ . '/Mailer.php';

class FieldworkNotifier {
    /** Days after the last day at which an overdue notice goes out; the creator is copied from the second one on. */
    public const OVERDUE_TIERS    = [1, 3, 7];
    public const OVERDUE_MAX_DAYS = 14;     // older leftovers are stale data, not worth an email
    public const MAX_ATTEMPTS     = 3;
    public const MORNING_HOUR     = 7;      // reminders and overdue notices are queued from 7:00 Philippine time
    public const BATCH_SIZE       = 25;

    private const CLOSED = ['completed', 'missed'];

    private PDO $db;
    private Mailer $mailer;
    private static bool $tableChecked      = false;
    private static bool $dispatchScheduled = false;

    public function __construct() {
        $this->db     = Database::getInstance()->getConnection();
        $this->mailer = new Mailer();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        if (self::$tableChecked) {
            return;
        }
        self::$tableChecked = true;
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS fieldwork_notifications (
                    id                INT PRIMARY KEY AUTO_INCREMENT,
                    activity_id       INT NOT NULL,
                    event_type        VARCHAR(30) NOT NULL,
                    event_key         VARCHAR(40) NOT NULL,
                    recipient_user_id INT NOT NULL,
                    recipient_role    ENUM('assignee','creator') NOT NULL DEFAULT 'assignee',
                    status            ENUM('pending','sending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
                    attempts          TINYINT UNSIGNED NOT NULL DEFAULT 0,
                    last_error        VARCHAR(255) DEFAULT NULL,
                    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    sent_at           DATETIME DEFAULT NULL,
                    UNIQUE KEY uq_fieldwork_notification (activity_id, event_type, event_key, recipient_user_id),
                    KEY idx_fieldwork_notification_status (status, id)
                )
            ");
        } catch (PDOException $e) {
            error_log('[FieldworkNotifier] Table check failed: ' . $e->getMessage());
        }
    }

    /** Emails are on when mail is configured and MAIL_ENABLED is not switched off. */
    public function isEnabled(): bool {
        $flag = strtolower((string) env_any(['FIELDWORK_EMAILS', 'MAIL_ENABLED'], 'true'));
        return !in_array($flag, ['0', 'false', 'no', 'off'], true) && $this->mailer->isConfigured();
    }

    private function now(): DateTimeImmutable {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    }

    // =========================================================
    // Hooks called by FieldworkSchedule when someone saves
    // =========================================================

    public function onCreated(int $activityId): void {
        $this->safely(function () use ($activityId) {
            $this->queue($activityId, 'assigned', 'new', false, true);
        });
    }

    /** $before is the row as it was before the update; the new values are read back from the database. */
    public function onUpdated(array $before): void {
        $this->safely(function () use ($before) {
            $after = $this->activity((int) $before['id']);
            if (!$after) {
                return;
            }
            $token = bin2hex(random_bytes(4));

            if ((int) $after['assigned_user_id'] !== (int) $before['assigned_user_id']) {
                // The new assignee gets the full details, which already cover any new dates.
                $this->queue((int) $after['id'], 'assigned', $token, false, true);
            } elseif ($after['start_date'] !== $before['start_date']
                   || ($after['end_date'] ?: null) !== ($before['end_date'] ?: null)) {
                $this->queue((int) $after['id'], 'rescheduled', $token, false, true);
            }

            $this->onStatusChanged((int) $after['id'], (string) $before['status'], (string) $after['status']);
        });
    }

    public function onStatusChanged(int $activityId, string $oldStatus, string $newStatus): void {
        if ($newStatus !== 'missed' || $oldStatus === 'missed') {
            return;
        }
        $this->safely(function () use ($activityId) {
            $this->queue($activityId, 'missed', bin2hex(random_bytes(4)), true, true);
        });
    }

    private function safely(callable $work): void {
        try {
            $work();
        } catch (Throwable $e) {
            error_log('[FieldworkNotifier] ' . $e->getMessage());
        }
    }

    // =========================================================
    // Queueing
    // =========================================================

    /**
     * Queues one row per recipient: the assignee, and the creator too when asked (and different).
     * $skipActor leaves out the person who made the change, who already knows about it.
     * Returns how many new rows were queued.
     */
    public function queue(int $activityId, string $eventType, string $eventKey, bool $includeCreator, bool $skipActor): int {
        if (!$this->isEnabled()) {
            return 0;
        }
        $activity = $this->activity($activityId);
        if (!$activity) {
            return 0;
        }

        $recipients = [[(int) $activity['assigned_user_id'], 'assignee']];
        if ($includeCreator && (int) $activity['created_by'] !== (int) $activity['assigned_user_id']) {
            $recipients[] = [(int) $activity['created_by'], 'creator'];
        }
        $actorId = $skipActor ? (int) ($_SESSION['user_id'] ?? 0) : 0;

        $insert = $this->db->prepare("
            INSERT IGNORE INTO fieldwork_notifications (activity_id, event_type, event_key, recipient_user_id, recipient_role)
            SELECT ?, ?, ?, u.id, ?
            FROM users u
            WHERE u.id = ? AND u.is_active = 1 AND u.email IS NOT NULL AND u.email <> ''
        ");

        $queued = 0;
        foreach ($recipients as [$userId, $role]) {
            if ($userId <= 0 || $userId === $actorId) {
                continue;
            }
            $insert->execute([$activityId, $eventType, $eventKey, $role, $userId]);
            $queued += $insert->rowCount();
        }

        if ($queued > 0 && PHP_SAPI !== 'cli') {
            $this->scheduleDispatch();
        }
        return $queued;
    }

    /** Sends what was just queued once the response has gone back to the browser. */
    private function scheduleDispatch(): void {
        if (self::$dispatchScheduled) {
            return;
        }
        self::$dispatchScheduled = true;

        register_shutdown_function(function () {
            ignore_user_abort(true);
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            if (function_exists('litespeed_finish_request')) {
                litespeed_finish_request();
            } elseif (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            @set_time_limit(60);
            try {
                (new FieldworkNotifier())->dispatch(10);
            } catch (Throwable $e) {
                error_log('[FieldworkNotifier] Dispatch after save failed: ' . $e->getMessage());
            }
        });
    }

    // =========================================================
    // Daily rules (run by cron)
    // =========================================================

    /**
     * Queues the reminders and overdue notices that are due now. Safe to run as often as you like:
     * the unique key keeps each alert to one email.
     *
     * @return array{reminders: int, overdue: int}
     */
    public function scan(): array {
        $result = ['reminders' => 0, 'overdue' => 0];
        $now    = $this->now();
        if (!$this->isEnabled() || (int) $now->format('G') < self::MORNING_HOUR) {
            return $result;
        }
        $today    = $now->format('Y-m-d');
        $tomorrow = $now->modify('+1 day')->format('Y-m-d');

        // Starting today or tomorrow, and nobody has closed it.
        $stmt = $this->db->prepare("
            SELECT id, start_date FROM fieldwork_schedule
            WHERE status IN ('pending', 'ongoing') AND start_date IN (?, ?)
        ");
        $stmt->execute([$today, $tomorrow]);
        foreach ($stmt->fetchAll() as $row) {
            $type = $row['start_date'] === $today ? 'reminder_today' : 'reminder_tomorrow';
            $result['reminders'] += $this->queue((int) $row['id'], $type, $row['start_date'], false, false);
        }

        // Past the last day and still open: day 1, then day 3 and day 7 with the creator copied.
        $stmt = $this->db->prepare("
            SELECT id, COALESCE(end_date, start_date) AS last_day,
                   DATEDIFF(?, COALESCE(end_date, start_date)) AS days_overdue
            FROM fieldwork_schedule
            WHERE status IN ('pending', 'ongoing')
              AND COALESCE(end_date, start_date) < ?
              AND DATEDIFF(?, COALESCE(end_date, start_date)) <= ?
        ");
        $stmt->execute([$today, $today, $today, self::OVERDUE_MAX_DAYS]);
        foreach ($stmt->fetchAll() as $row) {
            $tier = 0;
            foreach (self::OVERDUE_TIERS as $t) {
                if ((int) $row['days_overdue'] >= $t) {
                    $tier = $t;
                }
            }
            if ($tier === 0) {
                continue;
            }
            $key = 'd' . $tier . ':' . str_replace('-', '', $row['last_day']);
            $result['overdue'] += $this->queue((int) $row['id'], 'overdue', $key, $tier >= 3, false);
        }

        return $result;
    }

    /** Housekeeping: finished rows older than 90 days are removed. */
    public function purgeOld(): int {
        $stmt = $this->db->prepare("
            DELETE FROM fieldwork_notifications
            WHERE status IN ('sent', 'skipped', 'failed') AND created_at < (NOW() - INTERVAL 90 DAY)
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    // =========================================================
    // Sending
    // =========================================================

    /** @return array{sent: int, failed: int, skipped: int} */
    public function dispatch(int $limit = self::BATCH_SIZE): array {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        if (!$this->isEnabled()) {
            return $result;
        }

        // A run that died mid-send leaves rows in 'sending'; hand them back after 15 minutes.
        $this->db->exec("
            UPDATE fieldwork_notifications SET status = 'pending'
            WHERE status = 'sending' AND updated_at < (NOW() - INTERVAL 15 MINUTE)
        ");

        $ids = $this->db->query(
            "SELECT id FROM fieldwork_notifications WHERE status = 'pending' ORDER BY id ASC LIMIT " . max(1, (int) $limit)
        )->fetchAll(PDO::FETCH_COLUMN);

        $claim = $this->db->prepare("
            UPDATE fieldwork_notifications SET status = 'sending', attempts = attempts + 1
            WHERE id = ? AND status = 'pending'
        ");
        foreach ($ids as $id) {
            $claim->execute([$id]);
            if ($claim->rowCount() !== 1) {
                continue;   // another run took it
            }
            $outcome = $this->deliver((int) $id);
            $result[$outcome]++;
        }
        return $result;
    }

    /** @return string 'sent' | 'failed' | 'skipped' */
    private function deliver(int $id): string {
        $stmt = $this->db->prepare("
            SELECT n.*, fs.title, fs.description, fs.location, fs.start_date, fs.end_date,
                   fs.status AS activity_status, fs.assigned_user_id, fs.created_by,
                   ru.email AS to_email, ru.full_name AS to_name, ru.is_active AS to_active,
                   au.full_name AS assignee_name, cu.full_name AS creator_name
            FROM fieldwork_notifications n
            LEFT JOIN fieldwork_schedule fs ON fs.id = n.activity_id
            LEFT JOIN users ru ON ru.id = n.recipient_user_id
            LEFT JOIN users au ON au.id = fs.assigned_user_id
            LEFT JOIN users cu ON cu.id = fs.created_by
            WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return 'skipped';
        }

        $skip = $this->skipReason($row);
        if ($skip !== null) {
            $this->finish($id, 'skipped', $skip);
            return 'skipped';
        }

        [$subject, $html, $text] = $this->render($row);
        $error = $this->mailer->send($row['to_email'], (string) $row['to_name'], $subject, $html, $text);

        if ($error === null) {
            $this->finish($id, 'sent', null);
            return 'sent';
        }

        error_log("[FieldworkNotifier] Send failed for notification {$id}: {$error}");
        $giveUp = (int) $row['attempts'] >= self::MAX_ATTEMPTS;
        $this->finish($id, $giveUp ? 'failed' : 'pending', $error);
        return 'failed';
    }

    /** Why this email should no longer go out, or null to send it. */
    private function skipReason(array $row): ?string {
        if ($row['title'] === null) {
            return 'The activity was deleted';
        }
        if (!(int) $row['to_active'] || trim((string) $row['to_email']) === '') {
            return 'The recipient is inactive or has no email';
        }

        $type = $row['event_type'];
        if ($type === 'missed') {
            return null;
        }
        if (in_array($row['activity_status'], self::CLOSED, true)) {
            return 'The activity is already ' . $row['activity_status'];
        }
        if ($row['recipient_role'] === 'assignee' && (int) $row['assigned_user_id'] !== (int) $row['recipient_user_id']) {
            return 'The activity was reassigned to someone else';
        }
        if (strpos($type, 'reminder_') === 0 && $row['event_key'] !== $row['start_date']) {
            return 'The activity was rescheduled';
        }
        if ($type === 'overdue') {
            $lastDay = str_replace('-', '', $row['end_date'] ?: $row['start_date']);
            if (substr(strstr($row['event_key'], ':'), 1) !== $lastDay) {
                return 'The activity was rescheduled';
            }
        }
        return null;
    }

    private function finish(int $id, string $status, ?string $error): void {
        $this->db->prepare("
            UPDATE fieldwork_notifications
            SET status = ?, last_error = ?, sent_at = IF(? = 'sent', NOW(), sent_at)
            WHERE id = ?
        ")->execute([$status, $error === null ? null : substr($error, 0, 255), $status, $id]);
    }

    private function activity(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM fieldwork_schedule WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // =========================================================
    // Email content
    // =========================================================

    private static function h($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function formatDate(string $date): string {
        return (new DateTime($date))->format('M j, Y');
    }

    /** @return array{0: string, 1: string, 2: string} subject, html, text */
    private function render(array $row): array {
        $title    = (string) $row['title'];
        $isCreator = $row['recipient_role'] === 'creator';
        $assignee = (string) ($row['assignee_name'] ?: 'The assigned user');
        $first    = $row['start_date'];
        $last     = $row['end_date'] ?: $row['start_date'];
        $dates    = $last !== $first
            ? self::formatDate($first) . ' to ' . self::formatDate($last)
            : self::formatDate($first);
        $daysOver = max(0, (int) $this->now()->setTime(0, 0)->diff(new DateTimeImmutable($last, new DateTimeZone('Asia/Manila')))->days);

        switch ($row['event_type']) {
            case 'assigned':
                $subject = "New fieldwork assigned to you: {$title}";
                $headline = 'You have a new fieldwork activity';
                $intro = 'A fieldwork activity has been assigned to you.';
                $ask = '';
                break;
            case 'rescheduled':
                $subject = "Fieldwork rescheduled: {$title}";
                $headline = 'Your fieldwork was rescheduled';
                $intro = 'The schedule of your fieldwork activity has changed. Please note the new dates below.';
                $ask = '';
                break;
            case 'reminder_tomorrow':
                $subject = "Reminder: fieldwork tomorrow - {$title}";
                $headline = 'Your fieldwork starts tomorrow';
                $intro = 'This is a reminder about your upcoming fieldwork.';
                $ask = '';
                break;
            case 'reminder_today':
                $subject = "Reminder: fieldwork today - {$title}";
                $headline = 'Your fieldwork starts today';
                $intro = 'This is a reminder that your fieldwork starts today.';
                $ask = '';
                break;
            case 'overdue':
                $daysText = $daysOver . ' day' . ($daysOver === 1 ? '' : 's');
                $subject = "Action needed: fieldwork not closed - {$title}";
                $headline = $isCreator ? 'A fieldwork activity is still not closed' : 'Please confirm your fieldwork';
                $intro = $isCreator
                    ? "{$assignee}'s activity ended {$daysText} ago and is still not marked Completed or Missed."
                    : "This activity ended {$daysText} ago and is still not marked Completed or Missed.";
                $ask = $isCreator
                    ? 'Please follow up so it can be closed or rescheduled.'
                    : 'Please open the schedule and mark it Completed or Missed, or reschedule it.';
                break;
            case 'missed':
            default:
                $subject = "Fieldwork marked as missed: {$title}";
                $headline = 'A fieldwork activity was marked as missed';
                $intro = $isCreator
                    ? "{$assignee}'s activity was marked as Missed."
                    : 'This activity was marked as Missed.';
                $ask = 'If this is a mistake, open the schedule and update it.';
                break;
        }

        $rows = [['Activity', $title], ['Date', $dates]];
        if (trim((string) $row['location']) !== '') {
            $rows[] = ['Location', $row['location']];
        }
        if ($isCreator || $row['event_type'] === 'assigned') {
            $rows[] = ['Assigned to', $assignee];
        }
        if ($row['event_type'] === 'assigned' && $row['creator_name']) {
            $rows[] = ['Created by', $row['creator_name']];
        }
        $description = trim((string) $row['description']);
        if ($description !== '') {
            $rows[] = ['Details', mb_strlen($description) > 400 ? mb_substr($description, 0, 400) . '...' : $description];
        }

        $baseUrl = rtrim((string) env_any(['APP_URL'], ''), '/');
        $link    = $baseUrl !== '' ? $baseUrl . '/fieldwork-schedule.php' : '';

        $tableHtml = '';
        $text      = ($row['to_name'] ? "Hello {$row['to_name']},\n\n" : '') . "{$intro}\n";
        foreach ($rows as [$label, $value]) {
            $tableHtml .= '<tr><td style="padding:6px 12px 6px 0;color:#6b7280;vertical-align:top;white-space:nowrap">'
                . self::h($label) . '</td><td style="padding:6px 0;color:#111827">' . nl2br(self::h($value)) . '</td></tr>';
            $text .= "\n{$label}: {$value}";
        }
        if ($ask !== '') {
            $text .= "\n\n{$ask}";
        }
        if ($link !== '') {
            $text .= "\n\nOpen the schedule: {$link}";
        }
        $text .= "\n\nThis is an automatic message from the DILEEP Monitoring System. Please do not reply.\n";

        $button = $link === '' ? '' : '<p style="margin:24px 0 0"><a href="' . self::h($link)
            . '" style="background:#1b7a3d;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;font-weight:600;display:inline-block">Open the schedule</a></p>';

        $html = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb">'
            . '<div style="background:#1b7a3d;color:#ffffff;padding:16px 24px;font-size:13px;letter-spacing:.04em">DILEEP MONITORING SYSTEM &middot; SCHEDULE OF ACTIVITIES</div>'
            . '<div style="padding:24px">'
            . '<h2 style="margin:0 0 12px;font-size:20px;color:#111827">' . self::h($headline) . '</h2>'
            . ($row['to_name'] ? '<p style="margin:0 0 8px;color:#374151">Hello ' . self::h($row['to_name']) . ',</p>' : '')
            . '<p style="margin:0 0 16px;color:#374151">' . self::h($intro) . '</p>'
            . '<table role="presentation" style="border-collapse:collapse;width:100%;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;margin:8px 0">' . $tableHtml . '</table>'
            . ($ask !== '' ? '<p style="margin:16px 0 0;color:#111827;font-weight:600">' . self::h($ask) . '</p>' : '')
            . $button
            . '</div>'
            . '<div style="padding:14px 24px;background:#f9fafb;color:#6b7280;font-size:12px">This is an automatic message from the DILEEP Monitoring System. Please do not reply.</div>'
            . '</div></body></html>';

        return [$subject, $html, $text];
    }
}
