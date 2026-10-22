# Fieldwork email alerts

The Schedule of Activities / Fieldwork module emails the assigned user automatically, so people in the field are
reminded even when they never open the system.

## What is sent

| Email | When | Goes to |
|---|---|---|
| New fieldwork assigned | An activity is created for someone, or reassigned to someone else | Assignee |
| Fieldwork rescheduled | Start or end date changes (same assignee) | Assignee |
| Reminder: tomorrow | Morning before the start date (activity still pending/ongoing) | Assignee |
| Reminder: today | Morning of the start date | Assignee |
| Action needed: not closed | 1, 3 and 7 days after the last day while it is still not Completed or Missed. From day 3 the creator is copied. Activities more than 14 days past are ignored. | Assignee (+ creator) |
| Marked as missed | Someone marks an activity Missed | Assignee + creator |

Notes
- The person who made the change is never emailed about their own change.
- Nothing is marked Missed automatically (see the fieldwork rules); the overdue notices ask the assignee to confirm.
- Morning means 7:00 AM Philippine time or later; the first cron run after 7:00 queues them.
- Just before sending, each email is checked against the activity as it is now. If it was completed, deleted,
  reassigned or rescheduled in the meantime, the email is skipped.
- Each alert is sent once (unique key in `fieldwork_notifications`). Failed sends are retried on later cron runs,
  up to 3 attempts.

## How it runs

- Assignment, reschedule and missed emails are queued when the activity is saved and sent right after the response.
- Reminders and overdue notices come from `cron/fieldwork-notifications.php`, which also moves pending activities
  that have started to Ongoing, so statuses stay current without anyone logging in.

## One-time setup on Namecheap

1. **Create a mailbox**: cPanel > Email Accounts > Create, e.g. `noreply@dileepsys.com`.
2. **Add the settings to the server's `.env`** (the deploy never overwrites it). Copy the `MAIL_*` block from
   `.env.namecheap.example`, fill in the mailbox password, and make sure `APP_URL=https://dileepsys.com/`.
   Port 465 with `MAIL_ENCRYPTION=ssl` is the usual choice; use 587 with `tls` if 465 is blocked.
3. **Improve delivery**: cPanel > Email Deliverability, then repair/enable SPF and DKIM for dileepsys.com.
   Ask a few recipients to check Spam the first time, especially `@dole.gov.ph` inboxes.
4. **Add the cron job**: cPanel > Cron Jobs, every 10 minutes:

   ```
   */10 * * * * /usr/local/bin/php /home/dilemvwz/public_html/cron/fieldwork-notifications.php >/dev/null 2>&1
   ```

   (Adjust the PHP path if cPanel shows a different one under Cron Jobs > "PHP command".)
5. The table `fieldwork_notifications` is created automatically on first use. `migrations/add_fieldwork_notifications.sql`
   does the same by hand if you prefer.

## Checking and switching off

- Run it by hand from cPanel Terminal or a cron one-off: `php public_html/cron/fieldwork-notifications.php`. It prints
  how many reminders and notices were queued and how many emails were sent, failed or skipped.
- Look at the outbox in phpMyAdmin: `SELECT * FROM fieldwork_notifications ORDER BY id DESC LIMIT 50;` (`last_error`
  explains failures).
- Switch everything off without removing settings: `FIELDWORK_EMAILS=false` in `.env`. Emptying `MAIL_MAILER` also disables it.

## Limits

Shared hosting caps outgoing mail (often around 100-500 per hour). A run sends at most 200 emails, and the volume of
fieldwork alerts is far below that.

## Local testing

Set `MAIL_MAILER=log` in `.env` to write messages to the PHP error log instead of sending them, or point `MAIL_HOST`
at a local catch-all SMTP server with `MAIL_ENCRYPTION=none`.
