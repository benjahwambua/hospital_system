<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);

    // Validate inputs
    $valid_categories = ['procedures','treatment','lab','radiology'];
    if ($name && in_array($category, $valid_categories) && $price >= 0) {
        $stmt = $conn->prepare("INSERT INTO services_master (service_name, category, price) VALUES (?,?,?)");
        $stmt->bind_param("ssd", $name, $category, $price);
        if ($stmt->execute()) {
            $message = "Service added successfully.";
        } else {
            $message = "Error adding service.";
        }
        $stmt->close();
    } else {
        $message = "All fields are required and category must be valid.";
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container">
    <div class="card">
        <h2>Add New Service</h2>
        <?php if($message) echo "<div class='alert'>$message</div>"; ?>
        <form method="POST">
            <label>Service Name</label>
            <input type="text" name="name" required>

            <label>Category</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="procedures">Procedures</option>
                <option value="treatment">Treatment</option>
                <option value="lab">Lab</option>
                <option value="radiology">Radiology</option>
            </select>

            <label>Price (KSH)</label>
            <input type="number" name="price" step="0.01" min="0" required>

            <button type="submit">Add Service</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>