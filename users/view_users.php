<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$allowedRoles = ['admin', 'doctor', 'pharmacist', 'lab_tech', 'reception'];

function redirect_with_message(string $message): void
{
    header('Location: view_users.php?msg=' . urlencode($message));
    exit();
}

function count_super_users(mysqli $conn): int
{
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE is_super = 1");
    $row = $result ? $result->fetch_assoc() : ['total' => 0];
    return (int)($row['total'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        redirect_with_message('Security token mismatch. Please try again.');
    }

    if (isset($_POST['inline_save'])) {
        $uid = (int)($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $isSelf = $uid === (int)($_SESSION['user_id'] ?? 0);
        $isSuper = isset($_POST['is_super']) ? 1 : 0;

        if ($uid <= 0 || $fullName === '') {
            redirect_with_message('Invalid user details provided.');
        }

        if (!in_array($role, $allowedRoles, true)) {
            redirect_with_message('Invalid role selected.');
        }

        if ($newPassword !== '' && strlen($newPassword) < 8) {
            redirect_with_message('Password must be at least 8 characters long.');
        }

        $userStmt = $conn->prepare("SELECT id, is_super FROM users WHERE id = ? LIMIT 1");
        $userStmt->bind_param('i', $uid);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (!$user) {
            redirect_with_message('User not found.');
        }

        if ($isSelf) {
            $isSuper = (int)$user['is_super'];
        }

        if ((int)$user['is_super'] === 1 && $isSuper === 0 && count_super_users($conn) <= 1) {
            redirect_with_message('You cannot remove the final Super User account.');
        }

        $stmt = $conn->prepare("UPDATE users SET full_name = ?, role = ?, is_super = ? WHERE id = ?");
        $stmt->bind_param('ssii', $fullName, $role, $isSuper, $uid);
        $stmt->execute();
        $stmt->close();

        if ($newPassword !== '') {
            $newPassHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pwStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwStmt->bind_param('si', $newPassHash, $uid);
            $pwStmt->execute();
            $pwStmt->close();
        }

        redirect_with_message('Changes saved for ' . $fullName);
    }

    if (isset($_POST['delete_user'])) {
        $deleteId = (int)($_POST['delete_user'] ?? 0);
        $selfId = (int)($_SESSION['user_id'] ?? 0);

        if ($deleteId <= 0) {
            redirect_with_message('Invalid delete request.');
        }

        if ($deleteId === $selfId) {
            redirect_with_message('You cannot delete your own account.');
        }

        $checkStmt = $conn->prepare("SELECT is_super, full_name FROM users WHERE id = ? LIMIT 1");
        $checkStmt->bind_param('i', $deleteId);
        $checkStmt->execute();
        $userToDelete = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$userToDelete) {
            redirect_with_message('User not found.');
        }

        if ((int)$userToDelete['is_super'] === 1 && count_super_users($conn) <= 1) {
            redirect_with_message('You cannot delete the final Super User account.');
        }

        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param('i', $deleteId);
        $deleteStmt->execute();
        $deleteStmt->close();

        redirect_with_message('User deleted successfully.');
    }
}

$res = $conn->query("SELECT * FROM users ORDER BY full_name ASC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .inline-input { border: 1px solid transparent; background: transparent; padding: 5px; width: 100%; border-radius: 4px; transition: 0.3s; }
    .inline-input:focus, .inline-input:hover { border: 1px solid #d1d3e2; background: #fff; outline: none; }
    .role-select { border: 1px solid transparent; background: transparent; cursor: pointer; appearance: none; -webkit-appearance: none; }
    .role-select:hover { border: 1px solid #d1d3e2; background: #fff; }
    .save-btn { border: none; background: none; color: #28a745; cursor: pointer; opacity: 0.3; transition: 0.3s; }
    tr:hover .save-btn { opacity: 1; }
    .delete-btn { border: none; background: none; color: #e74a3b; opacity: 0.3; transition: 0.3s; cursor: pointer; }
    tr:hover .delete-btn { opacity: 1; }
    .super-check { width: 18px; height: 18px; cursor: pointer; }
    .badge-super { font-size: 10px; background: #ffeeba; color: #856404; padding: 2px 5px; border-radius: 4px; margin-left: 5px; }
    .table-warning-soft { background: rgba(255, 243, 205, 0.45); }
    .security-note { background:#f8f9fc; border-left:4px solid #4e73df; padding:12px 16px; border-radius:6px; }
</style>

<div class="main-content" style="padding: 25px;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 text-gray-800 font-weight-bold">Quick-Edit Staff Table</h2>
            <a href="add_users.php" class="btn btn-primary btn-sm px-3 shadow-sm">Add New User</a>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success py-2 small"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="security-note mb-3 small text-muted">
            <strong>Production safeguards:</strong> all edits are CSRF-protected, deletes require POST, passwords are hashed, and the final Super User account cannot be removed or demoted.
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="pl-4">Full Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="text-center">Super?</th>
                            <th>New Password</th>
                            <th class="text-right pr-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $res->fetch_assoc()): ?>
                        <?php $isSelf = ((int)$r['id'] === (int)($_SESSION['user_id'] ?? 0)); ?>
                        <tr class="<?= $r['is_super'] ? 'table-warning-soft' : '' ?>" style="transition: 0.3s;">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
                                <td class="pl-4">
                                    <input type="text" name="full_name" class="inline-input font-weight-bold" value="<?= htmlspecialchars($r['full_name']) ?>" required>
                                    <?php if($r['is_super']): ?><span class="badge-super"><i class="fas fa-shield-alt"></i> SUPER</span><?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($r['username']) ?></td>
                                <td>
                                    <select name="role" class="inline-input role-select" <?= $isSelf ? '' : '' ?>>
                                        <option value="admin" <?= $r['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="doctor" <?= $r['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option>
                                        <option value="pharmacist" <?= $r['role'] === 'pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
                                        <option value="lab_tech" <?= $r['role'] === 'lab_tech' ? 'selected' : '' ?>>Lab Tech</option>
                                        <option value="reception" <?= $r['role'] === 'reception' ? 'selected' : '' ?>>Reception</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="is_super" class="super-check" <?= $r['is_super'] ? 'checked' : '' ?> title="Grant Super User Privileges" <?= $isSelf ? 'disabled' : '' ?>>
                                    <?php if($isSelf && $r['is_super']): ?>
                                        <input type="hidden" name="is_super" value="1">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="password" name="new_password" class="inline-input small" placeholder="Set new password..." autocomplete="new-password">
                                    <?php if($isSelf): ?><div class="small text-muted mt-1">You can update your own password privately here.</div><?php endif; ?>
                                </td>
                                <td class="text-right pr-4" style="white-space: nowrap;">
                                    <button type="submit" name="inline_save" class="save-btn mr-2" title="Save Changes">
                                        <i class="fas fa-check-circle fa-lg"></i>
                                    </button>
                                </form>

                                <?php if(!$isSelf): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" name="delete_user" value="<?= (int)$r['id'] ?>" class="delete-btn" title="Delete User">
                                            <i class="fas fa-trash fa-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-muted small mt-3"><i class="fas fa-info-circle"></i> Tip: A Super User can update their own password, but the system prevents deletion or accidental removal of the last Super User account.</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
