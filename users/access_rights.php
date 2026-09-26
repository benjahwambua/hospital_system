<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
undefined

if (!has_access_control_tables($conn)) {
    http_response_code(500);
    exit('Access control is not installed. Run database/access_control_migration.sql first.');
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];
$message=''; $error='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
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

            $ins=$conn->prepare("INSERT INTO user_module_access (user_id,module_id,can_view,can_create,can_edit,can_delete,can_approve) VALUES (?,?,1,?,?,?,?,?)");
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
<div class="main-content" style="padding:25px;">
<div class="container-fluid">
<h2 class="h4 font-weight-bold text-gray-800 mb-2">Access Rights</h2>
<p class="text-muted mb-4">Configure which HMS modules each user can access. Super Users always have full access.</p>
<?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<div class="card shadow-sm border-0 mb-4"><div class="card-body">
<form method="get" class="form-inline">
<label class="font-weight-bold mr-2">Select User</label>
<select name="user_id" class="form-control mr-2" onchange="this.form.submit()" required>
<option value="">-- Select user --</option>
<?php while($u=$users->fetch_assoc()): ?>
<option value="<?=$u['id']?>" <?=$selectedUser===$u['id']?'selected':''?>><?=htmlspecialchars($u['full_name'])?> (<?=htmlspecialchars($u['role'])?>)<?=$u['is_super']?' — SUPER':''?></option>
<?php endwhile; ?>
</select>
<noscript><button class="btn btn-primary">Load</button></noscript>
</form>
</div></div>

<?php if($selectedUser>0): ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
<input type="hidden" name="user_id" value="<?=$selectedUser?>">
<div class="card shadow-sm border-0"><div class="card-header bg-white d-flex justify-content-between align-items-center">
<strong>Module Permissions</strong><span class="small text-muted">View is required before other actions.</span>
</div><div class="card-body p-0">
<div class="table-responsive"><table class="table mb-0">
<thead class="bg-light"><tr><th>Module</th><th>Description</th><th class="text-center">View</th><th class="text-center">Create</th><th class="text-center">Edit</th><th class="text-center">Delete</th><th class="text-center">Approve</th></tr></thead>
<tbody>
<?php $modules->data_seek(0); while($m=$modules->fetch_assoc()): $a=$access[(int)$m['id']]??[]; ?>
<tr>
<td class="font-weight-bold"><?=htmlspecialchars($m['module_name'])?></td>
<td class="text-muted small"><?=htmlspecialchars($m['description']??'')?></td>
<td class="text-center"><input type="checkbox" class="perm-view" name="modules[]" value="<?=$m['id']?>" data-module="<?=$m['id']?>" <?=!empty($a['can_view'])?'checked':''?>></td>
<td class="text-center"><input type="checkbox" class="perm-action" name="create[<?=$m['id']?>]" value="1" data-view="<?=$m['id']?>" <?=!empty($a['can_create'])?'checked':''?>></td>
<td class="text-center"><input type="checkbox" class="perm-action" name="edit[<?=$m['id']?>]" value="1" data-view="<?=$m['id']?>" <?=!empty($a['can_edit'])?'checked':''?>></td>
<td class="text-center"><input type="checkbox" class="perm-action" name="delete[<?=$m['id']?>]" value="1" data-view="<?=$m['id']?>" <?=!empty($a['can_delete'])?'checked':''?>></td>
<td class="text-center"><input type="checkbox" class="perm-action" name="approve[<?=$m['id']?>]" value="1" data-view="<?=$m['id']?>" <?=!empty($a['can_approve'])?'checked':''?>></td>
</tr>
<?php endwhile; ?>
</tbody></table></div>
</div><div class="card-footer bg-white text-right">
<button class="btn btn-primary"><i class="fas fa-save"></i> Save Access Rights</button>
</div></div>
</form>
<?php endif; ?>
</div></div>
<script>
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
