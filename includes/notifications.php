<?php
/**
 * Small, fail-closed helpers for the persistent in-app notification inbox.
 * Notifications are best-effort: a missing migration must not break the
 * underlying clinical or administrative transaction.
 */
function hms_notifications_table_ready(mysqli $conn): bool {
    static $ready = null;
    if ($ready !== null) return $ready;
    $result = $conn->query("SHOW TABLES LIKE 'hms_notifications'");
    $ready = (bool)($result && $result->num_rows > 0);
    return $ready;
}

function hms_safe_notification_url(?string $url): ?string {
    $url = trim((string)$url);
    if ($url === '' || strlen($url) > 255 || str_contains($url, "\r") || str_contains($url, "\n")) return null;
    if (!str_starts_with($url, '/hospital_system/') || str_starts_with($url, '//')) return null;
    if (parse_url($url, PHP_URL_HOST) !== null) return null;
    return $url;
}

function hms_notify_user(
    mysqli $conn,
    int $userId,
    string $title,
    string $message,
    string $type = 'general',
    string $priority = 'info',
    ?string $targetUrl = null,
    ?string $relatedType = null,
    ?int $relatedId = null,
    ?string $dedupeKey = null
): bool {
    if ($userId < 1 || !hms_notifications_table_ready($conn)) return false;
    $title = trim($title);
    $message = trim($message);
    if ($title === '' || $message === '' || strlen($title) > 180 || strlen($message) > 1000) return false;
    if (!in_array($priority, ['info','success','warning','urgent'], true)) $priority = 'info';
    $type = trim($type) !== '' ? substr(trim($type), 0, 60) : 'general';
    $targetUrl = hms_safe_notification_url($targetUrl);
    $relatedType = $relatedType !== null && trim($relatedType) !== '' ? substr(trim($relatedType), 0, 80) : null;
    $relatedIdValue = ($relatedId !== null && $relatedId > 0) ? $relatedId : null;
    $dedupeValue = $dedupeKey !== null && trim($dedupeKey) !== '' ? substr($userId . ':' . trim($dedupeKey), 0, 190) : null;
    $actorValue = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $stmt = $conn->prepare('INSERT IGNORE INTO hms_notifications
        (user_id, notification_type, title, message, priority, target_url, related_type, related_id, dedupe_key, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?)');
    if (!$stmt) return false;
    $stmt->bind_param('issssssisi', $userId, $type, $title, $message, $priority, $targetUrl, $relatedType, $relatedIdValue, $dedupeValue, $actorValue);
    $ok = $stmt->execute() && $stmt->affected_rows === 1;
    $stmt->close();
    return $ok;
}

function hms_notify_staff_member(
    mysqli $conn,
    int $staffId,
    string $title,
    string $message,
    string $type = 'staff',
    string $priority = 'info',
    ?string $targetUrl = null,
    ?string $dedupeKey = null
): bool {
    if ($staffId < 1) return false;
    $table = $conn->query("SHOW TABLES LIKE 'hr_staff'");
    if (!$table || $table->num_rows < 1) return false;
    $stmt = $conn->prepare('SELECT linked_user_id FROM hr_staff WHERE id=? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('i', $staffId);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $userId = (int)($row['linked_user_id'] ?? 0);
    if ($userId < 1) return false;
    return hms_notify_user($conn, $userId, $title, $message, $type, $priority, $targetUrl, 'hr_staff', $staffId, $dedupeKey);
}

function hms_notification_unread_count(mysqli $conn, int $userId): int {
    if ($userId < 1 || !hms_notifications_table_ready($conn)) return 0;
    $stmt = $conn->prepare('SELECT COUNT(*) AS unread_count FROM hms_notifications WHERE user_id=? AND read_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())');
    if (!$stmt) return 0;
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) { $stmt->close(); return 0; }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['unread_count'] ?? 0);
}
