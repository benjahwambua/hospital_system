-- HMS persistent in-app notifications.
-- Apply after database/access_control_migration.sql.
-- This does not grant access to existing users; assign the notifications module
-- explicitly through Access Rights after reviewing the permission matrix.
CREATE TABLE IF NOT EXISTS hms_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(60) NOT NULL DEFAULT 'general',
    title VARCHAR(180) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    priority ENUM('info','success','warning','urgent') NOT NULL DEFAULT 'info',
    target_url VARCHAR(255) NULL,
    related_type VARCHAR(80) NULL,
    related_id BIGINT NULL,
    dedupe_key VARCHAR(190) NULL,
    read_at DATETIME NULL,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    UNIQUE KEY uq_hms_notification_dedupe (dedupe_key),
    KEY idx_hms_notification_inbox (user_id, read_at, created_at),
    KEY idx_hms_notification_related (related_type, related_id),
    KEY idx_hms_notification_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO access_modules (module_key, module_name, description, sort_order)
VALUES ('notifications', 'Notifications', 'Personal notification inbox and workflow alerts', 96);
