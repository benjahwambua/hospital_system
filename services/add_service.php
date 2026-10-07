<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_login();
require_module_access($conn, 'administration', 'create');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Invalid security token.');
    }

    // Service codes are system-generated from the new service ID; users do not type them.
    $name = trim((string)($_POST['name'] ?? ''));
    $category = strtolower(trim((string)($_POST['category'] ?? '')));
    $department = trim((string)($_POST['department'] ?? ''));
    $unit = trim((string)($_POST['unit'] ?? 'Each'));
    $description = trim((string)($_POST['description'] ?? ''));
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $requires_order = isset($_POST['requires_order']) ? 1 : 0;
    $requires_result = isset($_POST['requires_result']) ? 1 : 0;
    $billable = 1;

    $service_code = 'TMP-' . strtoupper(bin2hex(random_bytes(6)));

    $valid_categories = ['consultation', 'procedure', 'treatment', 'lab', 'radiology', 'maternity', 'inpatient', 'other'];

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
            $service_id = (int)$conn->insert_id;
            $stmt->close();

            // The ID is the authoritative sequence, so codes are deterministic: SVC-00001, SVC-00002, ...
            $service_code = 'SVC-' . str_pad((string)$service_id, 5, '0', STR_PAD_LEFT);
            $codeUpdate = $conn->prepare("UPDATE services_master SET service_code=? WHERE id=?");
            $codeUpdate->bind_param("si", $service_code, $service_id);
            if (!$codeUpdate->execute()) throw new RuntimeException('Unable to generate service code.');
            $codeUpdate->close();

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
            $error = 'Unable to complete the requested operation. No changes were saved.';
            error_log('HMS operation error: '.$e->getMessage());
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.container{max-width:1100px;margin:0 auto;padding:28px 24px;background:#f5f7fb}.card{background:#fff;border:1px solid #e5eaf1;border-radius:14px;box-shadow:0 4px 18px rgba(31,45,61,.05);padding:28px}.card h2{color:#25324a;margin-top:0}.card label{display:block;font-weight:700;font-size:12px;color:#475467;margin:14px 0 6px}.card input,.card select,.card textarea{width:100%;border:1px solid #d7dee8;border-radius:9px;padding:10px 12px}.card input:focus,.card select:focus,.card textarea:focus{outline:none;border-color:#075b9d;box-shadow:0 0 0 3px rgba(7,91,157,.08)}.card button{margin-top:18px;background:#075b9d;border:0;color:#fff;border-radius:8px;padding:10px 18px;font-weight:700}.code-preview{margin-bottom:18px;padding:12px 14px;background:#f1f6fb;border:1px solid #dbe8f3;border-radius:9px;display:flex;justify-content:space-between;gap:15px;color:#344054}.code-preview span{color:#667085;font-size:12px}
</style>
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

            <div class="code-preview"><strong>Service Code</strong><span>Generated automatically after saving (e.g. SVC-00068)</span></div>

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
                <option value="inpatient">Inpatient / Ward</option>
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
<div style="margin-top:14px;padding:10px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;font-size:13px;font-weight:600;">All clinical services are billable. Charges remain on the patient account until removed or paid.</div>

            <button type="submit">Create Service</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>