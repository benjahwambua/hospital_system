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

<style>
.lv-page{background:radial-gradient(circle at 8% 0%,rgba(19,168,184,.07),transparent 28%),#f4f7fb;min-height:calc(100vh - 75px);padding:30px 24px 52px}
.lv-wrap{max-width:1480px;margin:0 auto}
.lv-hero{position:relative;overflow:hidden;color:#fff;background:linear-gradient(135deg,#0b3d91 0%,#1261c9 55%,#13a8b8 100%);border-radius:22px;margin-bottom:22px;padding:29px 31px;box-shadow:0 16px 38px rgba(16,77,153,.22)}
.lv-hero:after{content:"";position:absolute;width:270px;height:270px;border:1px solid rgba(255,255,255,.12);border-radius:50%;right:-80px;top:-120px;box-shadow:0 0 0 35px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.015);pointer-events:none}
.lv-hero-main{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}
.lv-kicker{font-size:.68rem;text-transform:uppercase;letter-spacing:.17em;font-weight:800;opacity:.72;margin-bottom:7px;color:#fff}
.lv-hero h1{font-size:1.8rem!important;font-weight:800!important;letter-spacing:-.025em;margin:0!important;color:#fff!important}
.lv-hero p{margin:7px 0 0!important;color:#fff!important;opacity:.82!important;font-size:.92rem}
.lv-actions{display:flex;gap:8px;flex-wrap:wrap;position:relative;z-index:2}
.lv-action{border-radius:10px;padding:10px 14px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:7px;transition:.18s ease}
.lv-action:hover{transform:translateY(-1px);text-decoration:none}
.lv-action-light{background:rgba(255,255,255,.11);color:#fff;border:1px solid rgba(255,255,255,.22)}
.lv-action-light:hover{color:#fff;background:rgba(255,255,255,.17)}
.lv-action-primary{background:#fff;color:#0d5f91}
.lv-action-primary:hover{color:#082f55}
.lv-alert{border-radius:12px;border:1px solid;margin-bottom:18px;padding:13px 16px;font-weight:700}
.lv-card{background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden}
.lv-card-header{padding:18px 21px;border-bottom:1px solid #e8edf3;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;background:linear-gradient(180deg,#fff,#fbfcfe)}
.lv-card-header strong{font-size:.98rem;color:#182334}
.lv-card-header small{display:block;color:#7a8494;font-size:.78rem;margin-top:3px}
.lv-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
.lv-metric{padding:20px 21px;position:relative;overflow:hidden}
.lv-metric:after{content:"";position:absolute;right:-25px;top:-28px;width:90px;height:90px;border-radius:50%;background:#edf6fc}
.lv-metric small,.lv-metric strong,.lv-metric span{position:relative;z-index:1;display:block}
.lv-metric small{text-transform:uppercase;color:#697586;font-size:.68rem;font-weight:800;letter-spacing:.07em;margin-bottom:8px}
.lv-metric strong{font-size:1.35rem;color:#152033;font-weight:850;font-variant-numeric:tabular-nums}
.lv-metric span{color:#7a8494;font-size:.8rem;margin-top:5px}
.lv-table{width:100%;border-collapse:separate;border-spacing:0;min-width:850px}
.lv-table th,.lv-table td{padding:13px 16px;border-bottom:1px solid #edf1f5;white-space:nowrap}
.lv-table th{background:#f7f9fc;color:#687386;font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;font-weight:800}
.lv-table td{color:#374151;font-size:.86rem}
.lv-table tbody tr{transition:background .15s}
.lv-table tbody tr:hover td{background:#f7fbff}
.lv-staff{font-weight:850;color:#253044}
.lv-muted{color:#667085;font-size:.76rem;margin-top:3px}
.lv-badge{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:.62rem;font-weight:850;letter-spacing:.05em;text-transform:uppercase}
.lv-badge-success{background:#ecfdf5;border:1px solid #b7efd3;color:#087443}
.lv-badge-danger{background:#fff1f1;border:1px solid #ffd7d7;color:#c92a2a}
.lv-badge-secondary{background:#f3f4f6;border:1px solid #e5e7eb;color:#687386}
.lv-badge-warning{background:#fff8e6;border:1px solid #f5dfaa;color:#9a6700}
.lv-empty{padding:55px 25px!important;text-align:center!important;color:#7a8494!important}
.lv-empty-icon{width:54px;height:54px;margin:0 auto 13px;border-radius:16px;background:#edf6fc;color:#1769aa;display:flex;align-items:center;justify-content:center;font-size:21px}
.lv-form-wrap{max-width:1000px}
.lv-form{padding:22px}
.lv-section{margin:0 0 20px;padding:0 0 17px;border-bottom:1px solid #edf1f5}
.lv-section:last-of-type{border-bottom:0;margin-bottom:5px}
.lv-section-title{display:flex;align-items:center;gap:9px;margin-bottom:14px;color:#1e293b;font-size:.84rem;font-weight:850;text-transform:uppercase;letter-spacing:.055em}
.lv-section-title i{width:28px;height:28px;border-radius:8px;background:#edf6fc;color:#1769aa;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem}
.lv-field{margin-bottom:14px}
.lv-field label{display:block;font-size:.68rem;text-transform:uppercase;color:#697586;font-weight:800;letter-spacing:.045em;margin-bottom:6px}
.lv-field input,.lv-field select,.lv-field textarea{width:100%;border:1px solid #dfe5ed;border-radius:9px;background:#fff;color:#172033;padding:10px 12px;outline:none;transition:.15s ease}
.lv-field input,.lv-field select{height:42px}
.lv-field textarea{min-height:90px;resize:vertical}
.lv-field input:focus,.lv-field select:focus,.lv-field textarea:focus{border-color:#2f78c8;box-shadow:0 0 0 3px rgba(47,120,200,.10)}
.lv-form-actions{display:flex;gap:9px;align-items:center;padding-top:4px}
.lv-btn{height:42px;border:0;border-radius:10px;padding:0 14px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;text-decoration:none}
.lv-btn-primary{background:#17469a;color:#fff}.lv-btn-primary:hover{background:#123a82;color:#fff}
.lv-btn-success{background:#087f55;color:#fff}.lv-btn-success:hover{background:#066844;color:#fff}
.lv-btn-danger{background:#c92a2a;color:#fff}.lv-btn-danger:hover{background:#a91f1f;color:#fff}
.lv-btn-light{background:#f3f5f8;color:#475467;border:1px solid #dfe5ed}.lv-btn-light:hover{background:#e9edf2;color:#344054}
.lv-details{margin:0 0 22px}
.lv-details dt{color:#697586;font-size:.68rem;text-transform:uppercase;letter-spacing:.045em;font-weight:800;padding-top:11px}
.lv-details dd{color:#253044;font-weight:650;padding-top:11px;border-bottom:1px solid #edf1f5;padding-bottom:11px}
@media(max-width:1050px){.lv-metrics{grid-template-columns:repeat(2,1fr)}}
@media(max-width:767px){.lv-page{padding:18px 12px 35px}.lv-hero{padding:23px 20px;border-radius:17px}.lv-hero h1{font-size:1.4rem!important}.lv-metrics{grid-template-columns:1fr}.lv-form{padding:18px}.lv-card-header{padding:16px}.lv-details dt,.lv-details dd{padding-left:12px;padding-right:12px}}
</style>
<div class="lv-page"><div class="lv-wrap lv-form-wrap"><div class="lv-hero"><div class="lv-hero-main"><div><div class="lv-kicker">Staff · Human Resources</div><h1>Request Leave</h1><p>Submit a leave request for review and approval.</p></div><div class="lv-actions"><a href="index.php" class="lv-action lv-action-light">Back to Leave</a></div></div></div><div class="lv-card"><div class="lv-card-header"><div><strong>New Leave Request</strong><small>Provide the leave period and supporting reason or notes.</small></div></div><div class="lv-form">
<?php if($message): ?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif; ?><?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>"><div class="lv-section"><div class="lv-section-title"><i class="fas fa-calendar-alt"></i> Leave Details</div><div class="form-row"><div class="form-group col-md-6 lv-field"><label>Leave Type</label><select name="leave_type" class="form-control" required><option value="">Select</option><?php foreach(['Annual','Sick','Maternity','Paternity','Compassionate','Unpaid','Other'] as $x): ?><option><?=htmlspecialchars($x)?></option><?php endforeach; ?></select></div><div class="form-group col-md-3 lv-field"><label>Start Date</label><input type="date" name="start_date" class="form-control" required></div><div class="form-group col-md-3 lv-field"><label>End Date</label><input type="date" name="end_date" class="form-control" required></div></div></div><div class="lv-section"><div class="lv-section-title"><i class="fas fa-sticky-note-o"></i> Notes</div><div class="form-group lv-field"><label>Reason / Notes</label><textarea name="reason" class="form-control" rows="4" maxlength="500"></textarea></div><div class="lv-form-actions"><button class="lv-btn lv-btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Submit Request</button></div></div></form>
</div></div></div></div><?php include __DIR__.'/../includes/footer.php'; ?>