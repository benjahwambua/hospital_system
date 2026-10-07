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
<div class="main-content" style="padding:25px"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4"><div><div class="text-uppercase small text-muted font-weight-bold">Administration · Human Resources</div><h2 class="h4 font-weight-bold mb-0">Staff Leave Management</h2></div><a href="request.php" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Request Leave</a></div>
<div class="card shadow-sm border-0"><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead class="bg-light"><tr><th>Staff</th><th>Leave Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php while($r=$rows->fetch_assoc()): ?>
<tr><td><strong><?=htmlspecialchars($r['full_name'])?></strong><div class="small text-muted"><?=htmlspecialchars($r['username'])?></div></td><td><?=htmlspecialchars($r['leave_type'])?></td><td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td><td><?=htmlspecialchars($r['days'])?></td><td><?=htmlspecialchars($r['reason']??'—')?></td><td><span class="badge badge-<?= $r['status']==='Approved'?'success':($r['status']==='Rejected'?'danger':($r['status']==='Cancelled'?'secondary':'warning')) ?>"><?=htmlspecialchars($r['status'])?></span></td><td><?php if($canApprove && $r['status']==='Pending'): ?><a class="btn btn-sm btn-outline-success" href="action.php?id=<?=$r['id']?>&decision=approve">Review</a><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td></tr>
<?php endwhile; if($rows->num_rows===0): ?><tr><td colspan="7" class="text-center text-muted py-4">No leave requests found.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>