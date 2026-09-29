<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_role(['admin']);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }

    $service_code = strtoupper(trim((string)($_POST['service_code'] ?? '')));
    $name = trim((string)($_POST['name'] ?? ''));
    $category = strtolower(trim((string)($_POST['category'] ?? '')));
    $department = trim((string)($_POST['department'] ?? ''));
    $unit = trim((string)($_POST['unit'] ?? 'Each'));
    $description = trim((string)($_POST['description'] ?? ''));
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $requires_order = isset($_POST['requires_order']) ? 1 : 0;
    $requires_result = isset($_POST['requires_result']) ? 1 : 0;
    $billable = isset($_POST['billable']) ? 1 : 0;

    if ($service_code === '') {
        $service_code = 'SVC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    $valid_categories = ['consultation', 'procedure', 'treatment', 'lab', 'radiology', 'maternity', 'other'];

    if ($name === '' || !in_array($category, $valid_categories, true) || $department === '' || $unit === '' || $price < 0 || $cost_price < 0) {
        $error = 'Service name, category, department and valid prices are required.';
    } else {
        $conn->begin_transaction();

        try {
            $check = $conn->prepare("SELECT id FROM services_master WHERE service_code = ? LIMIT 1");
            $check->bind_param("s", $service_code);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                throw new RuntimeException('Service code already exists.');
            }
            $check->close();

            $user_id = current_user_id();
            $active = 1;

            $stmt = $conn->prepare(
                "INSERT INTO services_master
                (service_code, service_name, category, department, unit, description, price, cost_price,
                 requires_order, requires_result, billable, active, created_by, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())"
            );
            $stmt->bind_param(
                "ssssssddiiiii",
                $service_code, $name, $category, $department, $unit, $description,
                $price, $cost_price, $requires_order, $requires_result, $billable, $active, $user_id
            );
            $stmt->execute();
            $service_id = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare(
                "INSERT INTO service_prices
                (service_id, payer_id, plan_id, price, effective_from, active, reason, created_by, created_at)
                VALUES (?, NULL, NULL, ?, CURDATE(), 1, 'Initial standard/cash price', ?, NOW())"
            );
            $stmt->bind_param("idi", $service_id, $price, $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = 'Service created successfully.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="container">
    <div class="card">
        <h2>Add New Service</h2>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <label>Service Code</label>
            <input type="text" name="service_code" placeholder="e.g. LAB-CBC">

            <label>Service Name</label>
            <input type="text" name="name" required>

            <label>Category</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="consultation">Consultation</option>
                <option value="procedure">Procedure</option>
                <option value="treatment">Treatment</option>
                <option value="lab">Laboratory</option>
                <option value="radiology">Radiology</option>
                <option value="maternity">Maternity</option>
                <option value="other">Other</option>
            </select>

            <label>Department</label>
            <input type="text" name="department" placeholder="e.g. Laboratory" required>

            <label>Unit</label>
            <input type="text" name="unit" value="Each" required>

            <label>Description</label>
            <textarea name="description" rows="3"></textarea>

            <label>Cost Price (KSH)</label>
            <input type="number" name="cost_price" step="0.01" min="0" value="0">

            <label>Standard/Cash Price (KSH)</label>
            <input type="number" name="price" step="0.01" min="0" required>

            <label><input type="checkbox" name="requires_order"> Requires clinical order</label>
            <label><input type="checkbox" name="requires_result"> Requires result</label>
            <label><input type="checkbox" name="billable" checked> Billable</label>

            <button type="submit">Create Service</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>