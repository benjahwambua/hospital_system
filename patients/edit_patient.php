<?php
// patients/edit_patient.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
$id = intval($_GET['id'] ?? 0);
if (!$id) header('Location: /hospital_system/patients/view_patients.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full = $conn->real_escape_string($_POST['full_name']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $addr = $conn->real_escape_string($_POST['address']);
    $stmt = $conn->prepare("UPDATE patients SET full_name=?, gender=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("ssssi",$full,$gender,$phone,$addr,$id); $stmt->execute(); header("Location: /hospital_system/patients/view_patients.php"); exit;
}
$pat = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php';
?>
<div class="card"><h3>Edit Patient</h3>
<form method="post">
  <label>Full name</label><input name="full_name" class="form-control" value="<?php echo htmlspecialchars($pat['full_name']); ?>">
  <label>Gender</label><input name="gender" class="form-control" value="<?php echo htmlspecialchars($pat['gender']); ?>">
  <label>Phone</label><input name="phone" class="form-control" value="<?php echo htmlspecialchars($pat['phone']); ?>">
  <label>Address</label><textarea name="address" class="form-control"><?php echo htmlspecialchars($pat['address']); ?></textarea>
  <div style="margin-top:8px"><button class="btn" type="submit">Save</button></div>
</form></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
