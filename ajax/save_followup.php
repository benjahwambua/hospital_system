<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'clinical', 'create');
require_role(['admin','doctor','nurse']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); echo json_encode(['status'=>'error','message'=>'Invalid security token']); exit; }
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD']==='POST'){
    $patient_id = intval($_POST['patient_id']);
    $date = $_POST['next_appointment'] ?? null;
    $notes = $_POST['followup_notes'] ?? '';

    $stmt = $conn->prepare("INSERT INTO followups (patient_id, next_appointment, notes, created_at) VALUES (?,?,?, NOW())");
    $stmt->bind_param("iss", $patient_id, $date, $notes);
    if($stmt->execute()){
        echo json_encode(['status'=>'success','message'=>'Follow-up saved']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to save follow-up']);
    }
    $stmt->close();
}
?>
