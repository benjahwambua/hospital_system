<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_module_access($conn,'staff_leave','view');

$uid=(int)($_SESSION['user_id']??0);
$canApprove=function_exists('can_approve') ? can_approve($conn,'staff_leave') : false;
$stmt=$conn->prepare("SELECT r.*,u.full_name,u.username FROM staff_leave_requests r JOIN users u ON u.id=r.user_id WHERE (?=1 OR r.user_id=?) ORDER BY r.created_at DESC");
$isAdmin=$canApprove?1:0; $stmt->bind_param('ii',$isAdmin,$uid); $stmt->execute(); $rows=$stmt->get_result();

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
<div class="lv-page"><div class="lv-wrap">
<div class="lv-hero"><div class="lv-hero-main"><div><div class="lv-kicker">Staff · Human Resources</div><h1>Staff Leave Management</h1><p>Submit, review and track staff leave requests.</p></div><div class="lv-actions"><a href="request.php" class="lv-action lv-action-primary"><i class="fas fa-plus"></i> Request Leave</a></div></div></div>
<div class="lv-metrics"><div class="lv-card lv-metric"><small>Total Requests</small><strong><?=number_format($rows->num_rows)?></strong><span>Requests in your leave view</span></div><div class="lv-card lv-metric"><small>Access</small><strong><?=$canApprove?'Review':'Request'?></strong><span><?=$canApprove?'Approval queue enabled':'Personal leave requests'?></span></div><div class="lv-card lv-metric"><small>Module</small><strong>Staff</strong><span>Human resources leave</span></div><div class="lv-card lv-metric"><small>Status</small><strong>Live</strong><span>Current leave register</span></div></div><div class="lv-card"><div class="lv-card-header"><div><strong>Leave Requests</strong><small>Review and track staff leave requests.</small></div></div><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0 lv-table"><thead class="bg-light"><tr><th>Staff</th><th>Leave Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php while($r=$rows->fetch_assoc()): ?>
<tr><td><div class="lv-staff"><?=htmlspecialchars($r['full_name'])?></div><div class="lv-muted"><?=htmlspecialchars($r['username'])?></div></td><td><?=htmlspecialchars($r['leave_type'])?></td><td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td><td><?=htmlspecialchars($r['days'])?></td><td><?=htmlspecialchars($r['reason']??'—')?></td><td><span class="lv-badge lv-badge-<?= $r['status']==='Approved'?'success':($r['status']==='Rejected'?'danger':($r['status']==='Cancelled'?'secondary':'warning')) ?>"><?=htmlspecialchars($r['status'])?></span></td><td><?php if($canApprove && $r['status']==='Pending'): ?><a class="btn btn-sm btn-outline-success" href="action.php?id=<?=$r['id']?>&decision=approve">Review</a><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td></tr>
<?php endwhile; if($rows->num_rows===0): ?><tr><td colspan="7" class="text-center text-muted py-4">No leave requests found.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>