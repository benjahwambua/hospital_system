<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

if($id>0){
    $stmt = $conn->prepare("UPDATE lab_requests SET status='paid' WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status'=>'success','message'=>'Lab request marked as paid']);
}else{
    echo json_encode(['status'=>'error','message'=>'Invalid ID']);
}
?>
