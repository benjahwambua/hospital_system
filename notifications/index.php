<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_login();
require_module_access($conn, 'notifications', 'view');

$userId = (int)($_SESSION['user_id'] ?? 0);
$csrf = csrf_token();
$error = '';
$message = '';
$ready = hms_notifications_table_ready($conn);
$filter = (string)($_GET['filter'] ?? 'all');
if (!in_array($filter, ['all','unread'], true)) $filter = 'all';
$e = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your security token expired. Refresh the page and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($action === 'mark_all_read') {
            $stmt = $conn->prepare('UPDATE hms_notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                if ($stmt->execute()) $message = 'All notifications marked as read.';
                else $error = 'Unable to update your notifications.';
                $stmt->close();
            } else $error = 'Unable to prepare the notification update.';
        } elseif (in_array($action, ['mark_read','open'], true) && $notificationId > 0) {
            $stmt = $conn->prepare('SELECT target_url FROM hms_notifications WHERE id=? AND user_id=? LIMIT 1');
            if (!$stmt) $error = 'Unable to open this notification.';
            else {
                $stmt->bind_param('ii', $notificationId, $userId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$row) $error = 'Notification not found in your inbox.';
                else {
                    $mark = $conn->prepare('UPDATE hms_notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?');
                    if ($mark) {
                        $mark->bind_param('ii', $notificationId, $userId);
                        $mark->execute();
                        $mark->close();
                    }
                    $target = hms_safe_notification_url($row['target_url'] ?? null);
                    if ($action === 'open' && $target !== null) { header('Location: ' . $target); exit; }
                    $message = 'Notification marked as read.';
                }
            }
        } else {
            $error = 'Unsupported notification action.';
        }
    }
}

$unreadCount = $ready ? hms_notification_unread_count($conn, $userId) : 0;
$rows = null;
if ($ready) {
    if ($filter === 'unread') {
        $stmt = $conn->prepare('SELECT id, notification_type, title, message, priority, target_url, related_type, related_id, read_at, created_at, expires_at FROM hms_notifications WHERE user_id=? AND read_at IS NULL AND (expires_at IS NULL OR expires_at>NOW()) ORDER BY created_at DESC LIMIT 200');
    } else {
        $stmt = $conn->prepare('SELECT id, notification_type, title, message, priority, target_url, related_type, related_id, read_at, created_at, expires_at FROM hms_notifications WHERE user_id=? AND (expires_at IS NULL OR expires_at>NOW()) ORDER BY created_at DESC LIMIT 200');
    }
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) $rows = $stmt->get_result();
        $stmt->close();
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.notifications-page{max-width:1250px;margin:0 auto;padding:24px}.notifications-hero{background:linear-gradient(120deg,#0b3d75,#087f8c);color:#fff;border-radius:18px;padding:25px 28px;margin-bottom:20px}.notifications-hero h1{font-size:clamp(1.45rem,2.4vw,2.1rem);font-weight:800;margin:5px 0 8px}.notifications-hero p{margin:0;color:#e1f5ff}.notifications-kicker{text-transform:uppercase;font-size:.72rem;font-weight:800;letter-spacing:1.4px;color:#b7f1ff}.notifications-toolbar{display:flex;gap:10px;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:16px}.notifications-tabs{display:flex;gap:8px;flex-wrap:wrap}.notifications-tabs a,.notifications-btn{display:inline-flex;align-items:center;gap:7px;border:0;border-radius:9px;padding:9px 13px;font-weight:750;font-size:.83rem;text-decoration:none;cursor:pointer}.notifications-tabs a{background:#e8eef5;color:#334155}.notifications-tabs a.active{background:#0b477b;color:#fff}.notifications-btn{background:#0b477b;color:#fff}.notifications-panel{background:#fff;border:1px solid #e2e8f0;border-radius:15px;overflow:hidden;box-shadow:0 5px 18px rgba(15,42,74,.045)}.notification-row{display:grid;grid-template-columns:42px minmax(0,1fr) auto;gap:14px;padding:18px 20px;border-bottom:1px solid #e8eef5;align-items:start}.notification-row:last-child{border-bottom:0}.notification-row.unread{background:#f5fbff}.notification-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:#e0f2fe;color:#075985}.notification-icon.warning{background:#fef3c7;color:#92400e}.notification-icon.urgent{background:#fee2e2;color:#991b1b}.notification-title{font-weight:800;color:#123b63;margin:0 0 5px}.notification-message{color:#475569;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere}.notification-meta{font-size:.76rem;color:#64748b;margin-top:7px}.notification-actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap}.notification-empty{padding:42px 20px;text-align:center;color:#64748b}.notification-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px}.notification-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.notification-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}.notification-warning{background:#fffbeb;color:#92400e;border:1px solid #fde68a}.notification-unread-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#0284c7;margin-left:6px;vertical-align:middle}@media(max-width:650px){.notifications-page{padding:14px}.notification-row{grid-template-columns:34px minmax(0,1fr);padding:15px;gap:10px}.notification-icon{width:34px;height:34px}.notification-actions{grid-column:2}}
</style>
<div class="main-content"><main class="notifications-page">
<section class="notifications-hero"><div class="notifications-kicker">Hospital Command Centre</div><h1>Notifications &amp; Work Alerts</h1><p>Your personal inbox for shift assignments and future workflow alerts. Notifications are scoped to your signed-in account.</p></section>
<?php if($error!==''): ?><div class="notification-alert notification-error"><?= $e($error) ?></div><?php endif; ?>
<?php if($message!==''): ?><div class="notification-alert notification-success"><?= $e($message) ?></div><?php endif; ?>
<?php if(!$ready): ?>
<div class="notification-alert notification-warning">The notification table is not installed. Back up the database, ensure <code>database/access_control_migration.sql</code> is applied, then apply <code>database/notifications_migration.sql</code>. Assign the Notifications module permission through Access Rights after reviewing the permission matrix.</div>
<?php else: ?>
<div class="notifications-toolbar"><nav class="notifications-tabs" aria-label="Notification filters"><a class="<?= $filter==='all'?'active':'' ?>" href="?filter=all">All notifications</a><a class="<?= $filter==='unread'?'active':'' ?>" href="?filter=unread">Unread (<?= $unreadCount ?>)</a></nav><?php if($unreadCount>0): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="mark_all_read"><button class="notifications-btn" type="submit"><i class="fas fa-check-double"></i> Mark all read</button></form><?php endif; ?></div>
<section class="notifications-panel" aria-label="Your notifications">
<?php if($rows && $rows->num_rows>0): while($row=$rows->fetch_assoc()): $isUnread=empty($row['read_at']); $priority=(string)$row['priority']; ?>
<article class="notification-row <?= $isUnread?'unread':'' ?>"><div class="notification-icon <?= in_array($priority,['warning','urgent'],true)?$priority:'' ?>"><i class="fas <?= $priority==='urgent'?'fa-triangle-exclamation':($priority==='warning'?'fa-exclamation-circle':($priority==='success'?'fa-check-circle':'fa-bell')) ?>"></i></div><div><h2 class="notification-title"><?= $e($row['title']) ?><?php if($isUnread): ?><span class="notification-unread-dot" aria-label="Unread"></span><?php endif; ?></h2><div class="notification-message"><?= $e($row['message']) ?></div><div class="notification-meta"><?= $e(date('D, d M Y H:i',strtotime($row['created_at']))) ?> · <?= $e(ucfirst($priority)) ?><?= $row['read_at']?' · Read':'' ?></div></div><div class="notification-actions"><?php if(!empty($row['target_url'])): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="open"><input type="hidden" name="notification_id" value="<?= (int)$row['id'] ?>"><button class="notifications-btn" type="submit"><i class="fas fa-arrow-up-right-from-square"></i> Open</button></form><?php elseif($isUnread): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="notification_id" value="<?= (int)$row['id'] ?>"><button class="notifications-btn" type="submit">Mark read</button></form><?php endif; ?></div></article>
<?php endwhile; else: ?><div class="notification-empty"><i class="far fa-bell-slash fa-2x"></i><h2>No notifications here</h2><p><?= $filter==='unread'?'You are all caught up.':'New workflow alerts will appear here when generated.' ?></p></div><?php endif; ?>
</section>
<?php endif; ?>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
