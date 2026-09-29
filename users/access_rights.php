<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_login();
require_module_access($conn, 'administration', 'view');
require_super();

if (!has_access_control_tables($conn)) {
    http_response_code(500);
    exit('Access control is not installed. Run database/access_control_migration.sql first.');
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];
$message=''; $error='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_module_access($conn, 'administration', 'edit');
    if (!hash_equals($csrf,$_POST['csrf_token'] ?? '')) {
        $error='Invalid security token.';
    } else {
        $uid=(int)($_POST['user_id'] ?? 0);
        $selected=$_POST['modules'] ?? [];
        $selected=is_array($selected) ? $selected : [];
        $selected=array_map('intval',$selected);

        $conn->begin_transaction();
        try {
            $del=$conn->prepare("DELETE FROM user_module_access WHERE user_id=?");
            $del->bind_param('i',$uid); $del->execute(); $del->close();

            $ins=$conn->prepare("INSERT INTO user_module_access (user_id,module_id,can_view,can_create,can_edit,can_delete,can_approve) VALUES (?,?,1,?,?,?,?)");
            foreach ($selected as $mid) {
                $create=!empty($_POST['create'][$mid])?1:0;
                $edit=!empty($_POST['edit'][$mid])?1:0;
                $delete=!empty($_POST['delete'][$mid])?1:0;
                $approve=!empty($_POST['approve'][$mid])?1:0;
                $ins->bind_param('iiiiii',$uid,$mid,$create,$edit,$delete,$approve);
                $ins->execute();
            }
            $ins->close();
            $conn->commit();
            $message='Access rights saved successfully.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error='Unable to save access rights: '.$e->getMessage();
        }
    }
}

$users=$conn->query("SELECT id,full_name,username,role,is_super FROM users ORDER BY full_name ASC");
$modules=$conn->query("SELECT * FROM access_modules WHERE active=1 ORDER BY sort_order,id");
$selectedUser=(int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
$access=[];
if ($selectedUser>0) {
    $stmt=$conn->prepare("SELECT * FROM user_module_access WHERE user_id=?");
    $stmt->bind_param('i',$selectedUser); $stmt->execute();
    $res=$stmt->get_result();
    while($r=$res->fetch_assoc()) $access[(int)$r['module_id']]=$r;
    $stmt->close();
}
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<style>
.admin-page{padding:28px 24px 44px;background:#f5f7fb;min-height:calc(100vh - 72px)}
.admin-shell{max-width:1500px;margin:auto}.admin-hero{background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;border-radius:18px;padding:26px 28px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 10px 28px rgba(6,59,115,.16)}.admin-hero h1{font-size:26px;margin:4px 0}.admin-hero p{margin:0;color:#d9edff;font-size:13px}.eyebrow{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;color:#9edcff}.admin-card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden;margin-bottom:18px}.admin-card-head{padding:16px 20px;border-bottom:1px solid #edf0f5;display:flex;justify-content:space-between;align-items:center;gap:12px}.admin-card-body{padding:20px}.permission-table th{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#687386;white-space:nowrap}.permission-table td{vertical-align:middle}.permission-table tbody tr:hover{background:#f8fbff}.permission-check{width:18px;height:18px;accent-color:#075b9d}.user-select{max-width:520px}.save-bar{padding:15px 20px;background:#fbfcfe;border-top:1px solid #edf0f5;text-align:right}
@media(max-width:700px){.admin-page{padding:18px 12px 32px}.admin-hero{padding:22px;align-items:flex-start;flex-direction:column}.admin-card-body{padding:14px}.permission-table{min-width:760px}}
</style>
<div class="admin-page"><div class="admin-shell">
<div class="admin-hero"><div><div class="eyebrow">Administration</div><h1>Access Rights</h1><p>Assign module permissions to individual users.</p></div><i class="fas fa-user-shield fa-2x"></i></div>

document.addEventListener('DOMContentLoaded', function () {
    function syncActionPermissions() {
        document.querySelectorAll('.perm-view').forEach(function (view) {
            const moduleId = view.dataset.module;
            document.querySelectorAll('.perm-action[data-view="' + moduleId + '"]').forEach(function (action) {
                action.disabled = !view.checked;
                if (!view.checked) action.checked = false;
            });
        });
    }
    document.querySelectorAll('.perm-view').forEach(function (view) {
        view.addEventListener('change', syncActionPermissions);
    });
    syncActionPermissions();
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
