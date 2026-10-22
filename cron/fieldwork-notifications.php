<?php
// cron/fieldwork-notifications.php
// Runs the fieldwork email alerts without anyone being logged in.
//
// cPanel -> Cron Jobs, every 10 minutes:
//   */10 * * * * /usr/local/bin/php /home/<cpanel-user>/public_html/cron/fieldwork-notifications.php >/dev/null 2>&1
//
// Each run: moves pending activities that have started to "ongoing", queues the day-before and
// day-of reminders and the overdue notices, then sends whatever is waiting in the outbox.
// Running it more often than needed is harmless: every alert is sent once.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script runs from the command line (cron) only.');
}

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/FieldworkSchedule.php';

// One run at a time: if the last one is still sending, this one steps aside.
$lock = fopen(sys_get_temp_dir() . '/dileep-fieldwork-notifications.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Another run is still in progress.\n";
    exit(0);
}

$stamp = date('Y-m-d H:i:s');
try {
    (new FieldworkSchedule())->autoUpdateStatuses();

    $notifier = new FieldworkNotifier();
    if (!$notifier->isEnabled()) {
        echo "[{$stamp}] Email alerts are off or mail is not configured (check MAIL_* in .env).\n";
        exit(0);
    }

    $queued = $notifier->scan();
    $totals = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
    for ($batch = 0; $batch < 8; $batch++) {     // up to 8 x 25 emails per run
        $done = $notifier->dispatch();
        foreach ($done as $k => $n) {
            $totals[$k] += $n;
        }
        if (array_sum($done) < FieldworkNotifier::BATCH_SIZE) {
            break;
        }
    }
    $purged = (int) date('G') === 3 ? $notifier->purgeOld() : 0;

    echo "[{$stamp}] queued {$queued['reminders']} reminder(s), {$queued['overdue']} overdue notice(s); "
       . "sent {$totals['sent']}, failed {$totals['failed']}, skipped {$totals['skipped']}"
       . ($purged ? ", purged {$purged} old row(s)" : '') . ".\n";
} catch (Throwable $e) {
    error_log('[fieldwork-notifications cron] ' . $e->getMessage());
    echo "[{$stamp}] Error: " . $e->getMessage() . "\n";
    exit(1);
}
