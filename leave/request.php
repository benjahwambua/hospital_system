<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login(); require_module_access($conn,'staff_leave','create');
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); $csrf=$_SESSION['csrf_token']; $error=''; $message='';
$uid=(int)($_SESSION['user_id']??0);
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf_token']??''))) $error='Invalid security token.';
 else{
  $type=trim((string)($_POST['leave_type']??'')); $start=(string)($_POST['start_date']??''); $end=(string)($_POST['end_date']??''); $reason=trim((string)($_POST['reason']??''));
  $allowed=['Annual','Sick','Maternity','Paternity','Compassionate','Unpaid','Other'];
  $sd=DateTime::createFromFormat('Y-m-d',$start); $ed=DateTime::createFromFormat('Y-m-d',$end);
  if(!in_array($type,$allowed,true)||!$sd||!$ed||$sd->format('Y-m-d')!==$start||$ed->format('Y-m-d')!==$end) $error='Enter a valid leave type and dates.';
  elseif($ed<$sd) $error='End date cannot be before start date.';
  else{
   $days=(float)$sd->diff($ed)->days+1;
   $over=$conn->prepare("SELECT id FROM staff_leave_requests WHERE user_id=? AND status IN ('Pending','Approved') AND start_date<=? AND end_date>=? LIMIT 1");
   $over->bind_param('iss',$uid,$end,$start); $over->execute(); $existing=$over->get_result()->fetch_assoc(); $over->close();
   if($existing) $error='You already have a pending or approved leave request overlapping these dates.';
   else{
    $s=$conn->prepare("INSERT INTO staff_leave_requests(user_id,leave_type,start_date,end_date,days,reason) VALUES(?,?,?,?,?,?)");
    $s->bind_param('isssds',$uid,$type,$start,$end,$days,$reason);
    if($s->execute()) $message='Leave request submitted for approval.'; else $error='Unable to submit the leave request.';
    $s->close();
   }
  }
 }
}
include __DIR__.'/../includes/header.php'; include __DIR__.'/../includes/sidebar.php';
?>
<div class="main-content" style="padding:25px"><div class="container-fluid" style="max-width:900px"><div class="card shadow-sm border-0"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-4"><div><div class="text-uppercase small text-muted font-weight-bold">Staff Leave</div><h2 class="h4 font-weight-bold mb-0">Request Leave</h2></div><a href="index.php" class="btn btn-light">Back</a></div>
<?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?><?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><div class="form-row"><div class="form-group col-md-6"><label>Leave Type</label><select name="leave_type" class="form-control" required><option value="">Select</option><?php foreach(['Annual','Sick','Maternity','Paternity','Compassionate','Unpaid','Other'] as $x): ?><option><?=htmlspecialchars($x)?></option><?php endforeach; ?></select></div><div class="form-group col-md-3"><label>Start Date</label><input type="date" name="start_date" class="form-control" required></div><div class="form-group col-md-3"><label>End Date</label><input type="date" name="end_date" class="form-control" required></div></div><div class="form-group"><label>Reason / Notes</label><textarea name="reason" class="form-control" rows="4" maxlength="500"></textarea></div><button class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Submit Request</button></form>
</div></div></div></div><?php include __DIR__.'/../includes/footer.php'; ?>