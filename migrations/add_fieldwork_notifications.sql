-- Fieldwork email alerts: the outbox that also stops the same alert being sent twice.
-- Idempotent. The app also creates this table by itself the first time it needs it,
-- so running this by hand is optional.

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
);
