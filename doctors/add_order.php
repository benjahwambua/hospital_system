<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_login();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$doctor_id = $_SESSION['user_id']; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("
        INSERT INTO patient_orders 
        (patient_id, doctor_id, order_type, item_name, quantity, unit_price)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "iissid",
        $_POST['patient_id'],
        $_SESSION['user_id'],
        $_POST['order_type'],
        $_POST['item_name'],
        $_POST['quantity'],
        $_POST['unit_price']
    );
    $stmt->execute();
    header("Location: ../patients/patient_history.php?id=".$_POST['patient_id']);
}
?>
<?php include __DIR__ . '/includes/footer.php'; ?>