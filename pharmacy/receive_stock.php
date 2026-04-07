<?php
require_once __DIR__.'/../config/config.php';
require_login();

$po_id=intval($_GET['id']);

$items=$conn->query("
  SELECT * FROM purchase_order_items WHERE purchase_order_id=$po_id
");

while($i=$items->fetch_assoc()){
  $conn->query("
    UPDATE medications
    SET quantity = quantity + {$i['quantity']}
    WHERE id={$i['medication_id']}
  ");

  $stmt=$conn->prepare("
    INSERT INTO stock_movements (medication_id, quantity, movement_type, reference, user_id)
    VALUES (?,?, 'IN', ?,?)
  ");
  $ref="PO#$po_id";
  $stmt->bind_param("iisi",$i['medication_id'],$i['quantity'],$ref,$_SESSION['user_id']);
  $stmt->execute();
}

$conn->query("UPDATE purchase_orders SET status='received' WHERE id=$po_id");
header("Location: view_stock.php");
