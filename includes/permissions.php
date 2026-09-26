<?php
require_once __DIR__ . '/session.php';

function has_access_control_tables(mysqli $conn): bool {
    static $ready = null;
    if ($ready !== null) return $ready;
    $m = $conn->query("SHOW TABLES LIKE 'access_modules'");
    $u = $conn->query("SHOW TABLES LIKE 'user_module_access'");
    return $ready = ($m && $m->num_rows > 0 && $u && $u->num_rows > 0);
}

function can_access_module(mysqli $conn, string $moduleKey): bool {
    if (!empty($_SESSION['is_super'])) return true;
    if (!has_access_control_tables($conn)) return true; // preserve existing role access until migration is run
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $role = strtolower((string)($_SESSION['role'] ?? ''));
    if ($uid <= 0) return false;
    // Users without explicit assignments retain sensible legacy role access.
    $countStmt=$conn->prepare("SELECT COUNT(*) AS n FROM user_module_access WHERE user_id=?");
    if ($countStmt) {
        $countStmt->bind_param('i',$uid); $countStmt->execute();
        $n=(int)($countStmt->get_result()->fetch_assoc()['n'] ?? 0); $countStmt->close();
        if ($n===0) {
            $defaults=[
                'front_desk'=>in_array($role,['admin','receptionist','reception'],true),
                'clinical'=>in_array($role,['admin','doctor','nurse','receptionist','reception'],true),
                'laboratory'=>in_array($role,['admin','lab','lab_tech'],true),
                'radiology'=>in_array($role,['admin','doctor','nurse','radiologist'],true),
                'pharmacy'=>in_array($role,['admin','pharmacist'],true),
                'maternity'=>in_array($role,['admin','doctor','nurse'],true),
                'finance'=>in_array($role,['admin','cashier','accountant'],true),
                'procurement'=>in_array($role,['admin'],true),
                'finance_admin'=>in_array($role,['admin','accountant'],true),
                'administration'=>in_array($role,['admin'],true)
            ];
            return !empty($defaults[$moduleKey]);
        }
    }
    $stmt = $conn->prepare("
        SELECT uma.can_view
        FROM user_module_access uma
        JOIN access_modules am ON am.id=uma.module_id
        WHERE uma.user_id=? AND am.module_key=? AND am.active=1
        LIMIT 1
    ");
    if (!$stmt) return false;
    $stmt->bind_param('is',$uid,$moduleKey);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row['can_view']);
}

function can_module_action(mysqli $conn, string $moduleKey, string $action='view'): bool {
    if (!empty($_SESSION['is_super'])) return true;
    if (!has_access_control_tables($conn)) return true;
    $column = [
        'view'=>'can_view','create'=>'can_create','edit'=>'can_edit',
        'delete'=>'can_delete','approve'=>'can_approve'
    ][$action] ?? null;
    if ($column === null) return false;
    $uid=(int)($_SESSION['user_id'] ?? 0);
    $role=strtolower((string)($_SESSION['role'] ?? ''));
    if ($uid<=0) return false;
    $countStmt=$conn->prepare("SELECT COUNT(*) AS n FROM user_module_access WHERE user_id=?");
    if ($countStmt) {
        $countStmt->bind_param('i',$uid); $countStmt->execute();
        $n=(int)($countStmt->get_result()->fetch_assoc()['n'] ?? 0); $countStmt->close();
        if ($n===0) {
            // Preserve legacy role-based access until explicit assignments are created.
            return can_access_module($conn,$moduleKey);
        }
    }
    $stmt=$conn->prepare("SELECT uma.$column AS allowed FROM user_module_access uma JOIN access_modules am ON am.id=uma.module_id WHERE uma.user_id=? AND am.module_key=? AND am.active=1 LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('is',$uid,$moduleKey);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    $allowed = !empty($row['allowed']);
    // Every non-view action requires module visibility as well.
    if ($allowed && $action !== 'view') {
        $viewStmt=$conn->prepare("SELECT can_view FROM user_module_access uma JOIN access_modules am ON am.id=uma.module_id WHERE uma.user_id=? AND am.module_key=? AND am.active=1 LIMIT 1");
        if (!$viewStmt) return false;
        $viewStmt->bind_param('is',$uid,$moduleKey);
        $viewStmt->execute();
        $viewRow=$viewStmt->get_result()->fetch_assoc();
        $viewStmt->close();
        $allowed=!empty($viewRow['can_view']);
    }
    return $allowed;
}

function can_create(mysqli $conn, string $moduleKey): bool { return can_module_action($conn,$moduleKey,'create'); }
function can_edit(mysqli $conn, string $moduleKey): bool { return can_module_action($conn,$moduleKey,'edit'); }
function can_delete(mysqli $conn, string $moduleKey): bool { return can_module_action($conn,$moduleKey,'delete'); }
function can_approve(mysqli $conn, string $moduleKey): bool { return can_module_action($conn,$moduleKey,'approve'); }

function require_module_access(mysqli $conn, string $moduleKey, string $action='view'): void {
    if (!can_module_action($conn,$moduleKey,$action)) {
        http_response_code(403);
        exit('Forbidden: You do not have permission to access this module.');
    }
}
