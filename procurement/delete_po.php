<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_module_access($conn,'procurement','delete');
if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
if($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf_token'],(string)($_POST['csrf_token']??''))){header('Location: purchase_orders.php?error=security');exit;}
$id=(int)($_POST['id']??0);
if($id<=0){header('Location: purchase_orders.php?error=invalid');exit;}
$conn->begin_transaction();
try{
 $s=$conn->prepare("SELECT status FROM purchase_orders WHERE id=? FOR UPDATE");$s->bind_param('i',$id);$s->execute();$po=$s->get_result()->fetch_assoc();$s->close();
 if(!$po)throw new Exception('Purchase order not found.');
 if(!in_array($po['status'],['Pending','Cancelled'],true))throw new Exception('Only pending or cancelled purchase orders can be deleted.');
 $s=$conn->prepare("SELECT COUNT(*) c FROM purchase_order_items WHERE purchase_order_id=? AND COALESCE(received_qty,0)>0");$s->bind_param('i',$id);$s->execute();$received=(int)$s->get_result()->fetch_assoc()['c'];$s->close();if($received>0)throw new Exception('This purchase order has received inventory and cannot be deleted.');
 $s=$conn->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id=?");$s->bind_param('i',$id);$s->execute();$s->close();
 $s=$conn->prepare("DELETE FROM purchase_orders WHERE id=?");$s->bind_param('i',$id);if(!$s->execute())throw new Exception('Unable to delete purchase order.');$s->close();
 $conn->commit();header('Location: purchase_orders.php?deleted=1');exit;
}catch(Throwable $e){$conn->rollback();header('Location: view_po.php?id='.$id.'&error=Unable+to+delete+purchase+order');
 error_log('PO deletion error: '.$e->getMessage());exit;}
?>