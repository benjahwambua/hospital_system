<?php
include('../includes/header.php');
include('../includes/sidebar.php');
include('../config.php');

$id = $_GET['id'];

$query = "SELECT * FROM deliveries WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Record not found.");
}

if (isset($_POST['update'])) {

    $mother = $_POST['mother_name'];
    $date = $_POST['delivery_date'];
    $type = $_POST['delivery_type'];
    $weight = $_POST['baby_weight'];
    $remarks = $_POST['remarks'];

    $update = "UPDATE deliveries SET mother_name=?, delivery_date=?, delivery_type=?, baby_weight=?, remarks=? WHERE id=?";
    $stmt2 = $conn->prepare($update);
    $stmt2->bind_param("sssssi", $mother, $date, $type, $weight, $remarks, $id);

    if ($stmt2->execute()) {
        header("Location: deliveries_list.php?updated=1");
        exit;
    } else {
        echo "<script>alert('Failed to update');</script>";
    }
}
?>

<div class="container-fluid">

    <h4 class="mt-4 mb-4">Edit Delivery</h4>

    <form method="POST">

        <div class="mb-3">
            <label>Mother Name</label>
            <input type="text" name="mother_name" class="form-control" value="<?= $data['mother_name'] ?>" required>
        </div>

        <div class="mb-3">
            <label>Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" value="<?= $data['delivery_date'] ?>" required>
        </div>

        <div class="mb-3">
            <label>Delivery Type</label>
            <select name="delivery_type" class="form-control" required>
                <option <?= $data['delivery_type']=="Normal" ? "selected":"" ?>>Normal</option>
                <option <?= $data['delivery_type']=="CS (Caesarean)" ? "selected":"" ?>>CS (Caesarean)</option>
                <option <?= $data['delivery_type']=="Assisted" ? "selected":"" ?>>Assisted</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Baby Weight (KG)</label>
            <input type="text" name="baby_weight" class="form-control" value="<?= $data['baby_weight'] ?>" required>
        </div>

        <div class="mb-3">
            <label>Remarks</label>
            <textarea name="remarks" class="form-control"><?= $data['remarks'] ?></textarea>
        </div>

        <button type="submit" name="update" class="btn btn-success">Update Record</button>
        <a href="deliveries_list.php" class="btn btn-secondary">Cancel</a>

    </form>

</div>

<?php include('../includes/footer.php'); ?>
