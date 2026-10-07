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
.lv-page{padding:28px 24px 42px;background:#f5f7fb;min-height:calc(100vh - 60px)}
.lv-wrap{max-width:1500px;margin:auto}
.lv-hero{background:linear-gradient(135deg,#273449,#475569);color:#fff;border-radius:18px;padding:26px 30px;display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px;box-shadow:0 12px 30px rgba(39,52,73,.2)}
.lv-kicker{font-size:11px;text-transform:uppercase;letter-spacing:1.5px;font-weight:800;color:#d6dee8}
.lv-hero h1{margin:5px 0;font-size:28px}
.lv-hero p{margin:0;color:rgba(255,255,255,.8);font-size:14px}
.lv-card{background:#fff;border:1px solid #e7ebf2;border-radius:14px;box-shadow:0 4px 16px rgba(31,45,61,.05);overflow:hidden}
.lv-card-head{padding:16px 20px;border-bottom:1px solid #edf0f5}
.lv-table th{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:#6b778c;border-top:0}
.lv-table td{vertical-align:middle}
.lv-staff{font-weight:700;color:#25324a}
.lv-muted{font-size:12px;color:#7b8798}
@media(max-width:700px){.lv-page{padding:18px 12px}.lv-hero{flex-direction:column;align-items:flex-start}.lv-hero h1{font-size:23px}}
</style>
<div class="lv-page"><div class="lv-wrap">
<div class="lv-hero"><div><div class="lv-kicker">Administration · Human Resources</div><h1>Staff Leave Management</h1><p>Submit, review and track staff leave requests.</p></div><a href="request.php" class="btn btn-light"><i class="fas fa-plus mr-1"></i> Request Leave</a></div>
<div class="lv-card"><div class="lv-card-head"><strong>Leave Requests</strong></div><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0 lv-table"><thead class="bg-light"><tr><th>Staff</th><th>Leave Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php while($r=$rows->fetch_assoc()): ?>
<tr><td><div class="lv-staff"><?=htmlspecialchars($r['full_name'])?></div><div class="lv-muted"><?=htmlspecialchars($r['username'])?></div></td><td><?=htmlspecialchars($r['leave_type'])?></td><td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td><td><?=htmlspecialchars($r['days'])?></td><td><?=htmlspecialchars($r['reason']??'—')?></td><td><span class="badge badge-<?= $r['status']==='Approved'?'success':($r['status']==='Rejected'?'danger':($r['status']==='Cancelled'?'secondary':'warning')) ?>"><?=htmlspecialchars($r['status'])?></span></td><td><?php if($canApprove && $r['status']==='Pending'): ?><a class="btn btn-sm btn-outline-success" href="action.php?id=<?=$r['id']?>&decision=approve">Review</a><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td></tr>
<?php endwhile; if($rows->num_rows===0): ?><tr><td colspan="7" class="text-center text-muted py-4">No leave requests found.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>