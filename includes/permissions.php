<?php
require_once __DIR__ . '/session.php';

function has_access_control_tables(mysqli $conn): bool {
    static $ready = null;
    if ($ready !== null) return $ready;
    $m = $conn->query("SHOW TABLES LIKE 'access_modules'");
    $u = $conn->query("SHOW TABLES LIKE 'user_module_access'");
    return $ready = ($m && $m->num_rows > 0 && $u && $u->num_rows > 0);
}

function can_module_action(mysqli $conn, string $moduleKey, string $action = 'view'): bool {
    $actions = [
        'view' => 'can_view',
        'create' => 'can_create',
        'edit' => 'can_edit',
        'delete' => 'can_delete',
        'approve' => 'can_approve',
    ];

    if (!isset($actions[$action])) return false;
    if ($moduleKey === 'administration') return !empty($_SESSION['is_super']);
    if (!empty($_SESSION['is_super'])) return true;
    if (!has_access_control_tables($conn)) return false;

    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) return false;

    $column = $actions[$action];
    $stmt = $conn->prepare(
        "SELECT uma.$column AS allowed, uma.can_view
         FROM user_module_access uma
         JOIN access_modules am ON am.id = uma.module_id
         WHERE uma.user_id = ? AND am.module_key = ? AND am.active = 1
         LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param('is', $uid, $moduleKey);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['can_view'])) return false;
    return !empty($row['allowed']);
}

function can_access_module(mysqli $conn, string $moduleKey): bool {
    return can_module_action($conn, $moduleKey, 'view');
}

function can_create(mysqli $conn, string $moduleKey): bool {
    return can_module_action($conn, $moduleKey, 'create');
}

function can_edit(mysqli $conn, string $moduleKey): bool {
    return can_module_action($conn, $moduleKey, 'edit');
}

function can_delete(mysqli $conn, string $moduleKey): bool {
    return can_module_action($conn, $moduleKey, 'delete');
}

function can_approve(mysqli $conn, string $moduleKey): bool {
    return can_module_action($conn, $moduleKey, 'approve');
}

function require_module_access(mysqli $conn, string $moduleKey, string $action = 'view'): void {
    if (!can_module_action($conn, $moduleKey, $action)) {
        http_response_code(403);
        exit('Forbidden: You do not have permission to access this module.');
    }
}
