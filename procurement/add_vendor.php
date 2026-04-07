<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO vendors (name, phone, category) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $_POST['name'], $_POST['phone'], $_POST['category']);
    $stmt->execute();
    echo "<div class='alert alert-success'>Supplier Registered!</div>";
}
?>
<div class="card p-4">
    <h3>Register New Supplier</h3>
    <form method="POST">
        <input type="text" name="name" class="form-control mb-2" placeholder="Supplier Name" required>
        <input type="text" name="phone" class="form-control mb-2" placeholder="Phone">
        <select name="category" class="form-control mb-2">
            <option>Medicines</option>
            <option>Equipment</option>
            <option>General</option>
        </select>
        <button class="btn btn-primary">Save Vendor</button>
    </form>
</div>