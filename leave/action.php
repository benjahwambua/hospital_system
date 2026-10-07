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
<style>
.lv-page{padding:28px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}.lv-wrap{max-width:950px;margin:auto}.lv-card{background:#fff;border:1px solid #e7ebf2;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}.lv-head{background:linear-gradient(135deg,#273449,#475569);color:#fff;padding:26px 30px}.lv-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;color:#d6dee8}.lv-head h1{margin:5px 0;font-size:26px}.lv-head p{margin:0;color:rgba(255,255,255,.8);font-size:14px}.lv-body{padding:26px 30px}.lv-details dt{color:#6b778c;font-size:12px;text-transform:uppercase;letter-spacing:.5px}.lv-details dd{color:#25324a;font-weight:600}
@media(max-width:700px){.lv-page{padding:18px 12px}.lv-body{padding:20px}.lv-head{padding:22px}.lv-head h1{font-size:22px}}
</style>
<div class="lv-page"><div class="lv-wrap"><div class="lv-card"><div class="lv-head"><div class="lv-kicker">Administration · Staff Leave</div><h1>Review Leave Request</h1><p>Review the request and record an approval decision.</p></div><div class="lv-body"><div class="d-flex justify-content-between align-items-center mb-4"><strong class="text-muted">Leave Request Details</strong><a href="index.php" class="btn btn-light">Back to Leave</a></div><dl class="row mt-4 lv-details"><dt class="col-sm-3">Staff</dt><dd class="col-sm-9"><?=htmlspecialchars($row['full_name'])?></dd><dt class="col-sm-3">Leave</dt><dd class="col-sm-9"><?=htmlspecialchars($row['leave_type'])?></dd><dt class="col-sm-3">Period</dt><dd class="col-sm-9"><?=htmlspecialchars($row['start_date'])?> → <?=htmlspecialchars($row['end_date'])?> (<?=htmlspecialchars($row['days'])?> days)</dd><dt class="col-sm-3">Reason</dt><dd class="col-sm-9"><?=nl2br(htmlspecialchars($row['reason']??'—'))?></dd></dl>
<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><div class="form-group"><label>Review Notes</label><textarea name="review_notes" class="form-control" maxlength="500"></textarea></div><button name="decision" value="Approved" class="btn btn-success mr-2">Approve</button><button name="decision" value="Rejected" class="btn btn-danger">Reject</button></form>
</div></div></div></div><?php include __DIR__.'/../includes/footer.php'; ?>