<?php
include('../includes/header.php');
include('../includes/sidebar.php');
include('../config.php');

if (isset($_POST['submit'])) {

    $mother = $_POST['mother_name'];
    $date = $_POST['delivery_date'];
    $type = $_POST['delivery_type'];
    $weight = $_POST['baby_weight'];
    $remarks = $_POST['remarks'];

    $query = "INSERT INTO deliveries (mother_name, delivery_date, delivery_type, baby_weight, remarks)
              VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $mother, $date, $type, $weight, $remarks);

    if ($stmt->execute()) {
        header("Location: deliveries_list.php?added=1");
        exit;
    } else {
        echo "<script>alert('Failed to save');</script>";
    }
}
?>

<div class="container-fluid">

    <h4 class="mt-4 mb-4">Add Delivery Record</h4>

    <form action="" method="POST">

        <div class="mb-3">
            <label>Mother Name</label>
            <input type="text" name="mother_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Delivery Type</label>
            <select name="delivery_type" class="form-control" required>
                <option value="">-- Select --</option>
                <option>Normal</option>
                <option>CS (Caesarean)</option>
                <option>Assisted</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Baby Weight (KG)</label>
            <input type="text" name="baby_weight" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Remarks</label>
            <textarea name="remarks" class="form-control"></textarea>
        </div>

        <button type="submit" name="submit" class="btn btn-success">Save Record</button>
        <a href="deliveries_list.php" class="btn btn-secondary">Cancel</a>

    </form>
</div>

<?php include('../includes/footer.php'); ?>
