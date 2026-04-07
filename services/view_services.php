<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$message = '';

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $service_id = intval($_POST['service_id']);
    $new_price = floatval($_POST['price']);

    if ($service_id > 0 && $new_price >= 0) {
        $stmt = $conn->prepare("UPDATE services_master SET price=? WHERE id=?");
        $stmt->bind_param("di", $new_price, $service_id);
        if ($stmt->execute()) {
            $message = "Price updated successfully.";
        } else {
            $message = "Error updating price: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Invalid service or price.";
    }
}

// Handle search
$search = trim($_GET['search'] ?? '');
$search_sql = $search ? "WHERE service_name LIKE ? OR category LIKE ?" : "";
$stmt = $conn->prepare("SELECT * FROM services_master $search_sql ORDER BY category, service_name");

if ($search) {
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
}
$stmt->execute();
$services = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container">
    <div class="card">
        <h2>All Services</h2>
        <?php if($message) echo "<div class='alert'>$message</div>"; ?>

        <form method="GET" style="margin-bottom:15px;">
            <input type="text" name="search" placeholder="Search by Name or Category" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
            <a href="view_services.php"><button type="button">Reset</button></a>
        </form>

        <?php if($services && $services->num_rows > 0): ?>
        <table class="table-blue">
            <tr>
                <th>ID</th>
                <th>Service Name</th>
                <th>Category</th>
                <th>Price (KSH)</th>
                <th>Active</th>
                <th>Created At</th>
                <th>Update Price</th>
            </tr>
            <?php while($s = $services->fetch_assoc()): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['service_name']) ?></td>
                <td><?= ucfirst($s['category']) ?></td>
                <td><?= number_format($s['price'],2) ?></td>
                <td><?= $s['active'] ? 'Yes' : 'No' ?></td>
                <td><?= $s['created_at'] ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:5px;">
                        <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                        <input type="number" name="price" step="0.01" min="0" value="<?= $s['price'] ?>" required>
                        <button type="submit" name="update_price">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <p>No services found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>