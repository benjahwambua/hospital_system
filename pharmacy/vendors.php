<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid security token.'); }
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $category = $_POST['category'];

    $stmt = $conn->prepare("INSERT INTO vendors (name, phone, category) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $category);
    $stmt->execute();
    echo "<div class='alert alert-success'>Supplier Registered Successfully</div>";
}
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white"><h5>Register New Supplier</h5></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
            <div class="row">
                <div class="col-md-4"><label>Supplier Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-4"><label>Phone Number</label><input type="text" name="phone" class="form-control"></div>
                <div class="col-md-4">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option>Medicines</option>
                        <option>Equipment</option>
                        <option>Stationery</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary mt-3">Save Supplier</button>
        </form>
    </div>
</div>