<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login(); require_module_access($conn,'staff_leave','approve');
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); $csrf=$_SESSION['csrf_token'];
$id=(int)($_GET['id']??0); $error=''; $row=null;
if($id>0){$s=$conn->prepare("SELECT r.*,u.full_name FROM staff_leave_requests r JOIN users u ON u.id=r.user_id WHERE r.id=? LIMIT 1");$s->bind_param('i',$id);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();}
if(!$row){http_response_code(404); exit('Leave request not found.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf_token']??''))){$error='Invalid security token.';}
 else{
  $decision=(string)($_POST['decision']??''); $notes=trim((string)($_POST['review_notes']??''));
  if(!in_array($decision,['Approved','Rejected'],true)) $error='Invalid decision.';
  elseif($row['status']!=='Pending') $error='This request has already been processed.';
  else{
   $s=$conn->prepare("UPDATE staff_leave_requests SET status=?,reviewed_by=?,reviewed_at=NOW(),review_notes=?,updated_at=NOW() WHERE id=? AND status='Pending'");
   $uid=(int)$_SESSION['user_id']; $s->bind_param('sisi',$decision,$uid,$notes,$id); $s->execute(); $changed=$s->affected_rows; $s->close();
   if($changed!==1) $error='The request could not be updated.';
   else {
    if(function_exists('audit')) audit('staff_leave_'.strtolower($decision),"leave_request_id={$id},staff_user_id=".(int)$row['user_id']);
    header('Location: index.php'); exit;
   }
  }
 }
}
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="main-content" style="padding:25px"><div class="container-fluid" style="max-width:850px"><div class="card shadow-sm border-0"><div class="card-body p-4"><a href="index.php" class="btn btn-light btn-sm mb-3">← Back</a><h2 class="h4 font-weight-bold">Review Leave Request</h2><dl class="row mt-4"><dt class="col-sm-3">Staff</dt><dd class="col-sm-9"><?=htmlspecialchars($row['full_name'])?></dd><dt class="col-sm-3">Leave</dt><dd class="col-sm-9"><?=htmlspecialchars($row['leave_type'])?></dd><dt class="col-sm-3">Period</dt><dd class="col-sm-9"><?=htmlspecialchars($row['start_date'])?> → <?=htmlspecialchars($row['end_date'])?> (<?=htmlspecialchars($row['days'])?> days)</dd><dt class="col-sm-3">Reason</dt><dd class="col-sm-9"><?=nl2br(htmlspecialchars($row['reason']??'—'))?></dd></dl>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><div class="form-group"><label>Review Notes</label><textarea name="review_notes" class="form-control" maxlength="500"></textarea></div><button name="decision" value="Approved" class="btn btn-success mr-2">Approve</button><button name="decision" value="Rejected" class="btn btn-danger">Reject</button></form>
</div></div></div></div><?php include __DIR__.'/../includes/footer.php'; ?>