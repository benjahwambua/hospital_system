<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_POST['patient_id'] ?? 0);
$item = trim($_POST['item'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$paid = intval($_POST['paid'] ?? 0);

if($patient_id>0 && $item!=='' && $amount>0){
    $stmt = $conn->prepare("INSERT INTO billing(patient_id,item,amount,paid,created_at) VALUES(?,?,?,?,NOW())");
    $stmt->bind_param("isdi",$patient_id,$item,$amount,$paid);
    if($stmt->execute()){
        echo json_encode(['success'=>true,'message'=>'Billing saved']);
    }else{
        echo json_encode(['success'=>false,'message'=>'Failed to save billing']);
    }
    $stmt->close();
}else{
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
}
?>
