<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Ensure richer supplier columns exist (Odoo-style details)
$columns = [];
$colRes = $conn->query('SHOW COLUMNS FROM suppliers');
if ($colRes) {
    while ($col = $colRes->fetch_assoc()) {
        $columns[] = $col['Field'] ?? '';
    }
}
$addCol = static function (mysqli $conn, string $name, string $def) {
    $conn->query("ALTER TABLE suppliers ADD COLUMN {$name} {$def}");
};
if (!in_array('contact_person', $columns, true)) $addCol($conn, 'contact_person', 'VARCHAR(150) DEFAULT NULL');
if (!in_array('mobile', $columns, true)) $addCol($conn, 'mobile', 'VARCHAR(60) DEFAULT NULL');
if (!in_array('website', $columns, true)) $addCol($conn, 'website', 'VARCHAR(255) DEFAULT NULL');
if (!in_array('tax_pin', $columns, true)) $addCol($conn, 'tax_pin', 'VARCHAR(80) DEFAULT NULL');
if (!in_array('vat_number', $columns, true)) $addCol($conn, 'vat_number', 'VARCHAR(80) DEFAULT NULL');
if (!in_array('payment_terms', $columns, true)) $addCol($conn, 'payment_terms', 'VARCHAR(120) DEFAULT NULL');
if (!in_array('bank_name', $columns, true)) $addCol($conn, 'bank_name', 'VARCHAR(120) DEFAULT NULL');
if (!in_array('bank_account', $columns, true)) $addCol($conn, 'bank_account', 'VARCHAR(120) DEFAULT NULL');
if (!in_array('credit_limit', $columns, true)) $addCol($conn, 'credit_limit', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
if (!in_array('address_line', $columns, true)) $addCol($conn, 'address_line', 'VARCHAR(255) DEFAULT NULL');
if (!in_array('city', $columns, true)) $addCol($conn, 'city', 'VARCHAR(120) DEFAULT NULL');
if (!in_array('country', $columns, true)) $addCol($conn, 'country', 'VARCHAR(120) DEFAULT NULL');
if (!in_array('status', $columns, true)) $addCol($conn, 'status', "VARCHAR(40) NOT NULL DEFAULT 'Active'");
if (!in_array('notes', $columns, true)) $addCol($conn, 'notes', 'TEXT DEFAULT NULL');
if (!in_array('created_at', $columns, true)) $addCol($conn, 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $posted)) {
        $msg = 'Invalid CSRF token.';
        $msgType = 'danger';
    } else {
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $contact = trim((string)($_POST['contact_person'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $mobile = trim((string)($_POST['mobile'] ?? ''));
        $website = trim((string)($_POST['website'] ?? ''));
        $taxPin = trim((string)($_POST['tax_pin'] ?? ''));
        $vat = trim((string)($_POST['vat_number'] ?? ''));
        $terms = trim((string)($_POST['payment_terms'] ?? ''));
        $bank = trim((string)($_POST['bank_name'] ?? ''));
        $bankAcc = trim((string)($_POST['bank_account'] ?? ''));
        $creditLimit = (float)($_POST['credit_limit'] ?? 0);
        $address = trim((string)($_POST['address_line'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $country = trim((string)($_POST['country'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'Active'));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($name === '') {
            $msg = 'Supplier name is required.';
            $msgType = 'danger';
        } else {
            if ($supplierId > 0) {
                $stmt = $conn->prepare('UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, mobile=?, website=?, tax_pin=?, vat_number=?, payment_terms=?, bank_name=?, bank_account=?, credit_limit=?, address_line=?, city=?, country=?, status=?, notes=? WHERE id=?');
                $stmt->bind_param('sssssssssssdsssssi', $name, $contact, $email, $phone, $mobile, $website, $taxPin, $vat, $terms, $bank, $bankAcc, $creditLimit, $address, $city, $country, $status, $notes, $supplierId);
                $stmt->execute();
                $stmt->close();
                $msg = 'Supplier updated successfully.';
            } else {
                $stmt = $conn->prepare('INSERT INTO suppliers (name, contact_person, email, phone, mobile, website, tax_pin, vat_number, payment_terms, bank_name, bank_account, credit_limit, address_line, city, country, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->bind_param('sssssssssssdsssss', $name, $contact, $email, $phone, $mobile, $website, $taxPin, $vat, $terms, $bank, $bankAcc, $creditLimit, $address, $city, $country, $status, $notes);
                $stmt->execute();
                $stmt->close();
                $msg = 'Supplier created successfully.';
            }
        }
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$sql = 'SELECT * FROM suppliers';
$params = [];
$types = '';
if ($search !== '') {
    $sql .= ' WHERE name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR phone LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}
$sql .= ' ORDER BY id DESC';
$rows = [];
$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
.supplier-shell { max-width: 1400px; margin: 0 auto; padding: 6px 4px 20px; }
.supplier-card { border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 6px 20px rgba(15,23,42,.05); }
.supplier-card .card-header { background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:700; }
.supplier-form label { font-size:.78rem; text-transform:uppercase; color:#6b7280; font-weight:700; margin-bottom:4px; }
.supplier-form .form-control { border-radius:10px; }
</style>
<div class="container-fluid">
    <div class="supplier-shell">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h3 text-gray-800">Manage Suppliers</h2>
    </div>

    <?php if ($msg !== ''): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="card supplier-card mb-3">
        <div class="card-body">
            <form method="get" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Search supplier" value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <div class="card supplier-card mb-4">
        <div class="card-header"><strong>Add / Update Supplier Details</strong></div>
        <div class="card-body">
            <form method="post" class="row supplier-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="supplier_id" id="supplier_id" value="0">
                <div class="col-md-4 mb-2"><label>Name *</label><input name="name" id="name" class="form-control" required></div>
                <div class="col-md-4 mb-2"><label>Contact Person</label><input name="contact_person" id="contact_person" class="form-control"></div>
                <div class="col-md-4 mb-2"><label>Status</label><select name="status" id="status" class="form-control"><option>Active</option><option>Inactive</option></select></div>
                <div class="col-md-3 mb-2"><label>Email</label><input name="email" id="email" type="email" class="form-control"></div>
                <div class="col-md-3 mb-2"><label>Phone</label><input name="phone" id="phone" class="form-control"></div>
                <div class="col-md-3 mb-2"><label>Mobile</label><input name="mobile" id="mobile" class="form-control"></div>
                <div class="col-md-3 mb-2"><label>Website</label><input name="website" id="website" class="form-control"></div>
                <div class="col-md-3 mb-2"><label>Tax PIN</label><input name="tax_pin" id="tax_pin" class="form-control"></div>
                <div class="col-md-3 mb-2"><label>VAT Number</label><input name="vat_number" id="vat_number" class="form-control"></div>
                <div class="col-md-3 mb-2"><label>Payment Terms</label><input name="payment_terms" id="payment_terms" class="form-control" placeholder="e.g. Net 30"></div>
                <div class="col-md-3 mb-2"><label>Credit Limit</label><input name="credit_limit" id="credit_limit" type="number" step="0.01" class="form-control" value="0"></div>
                <div class="col-md-4 mb-2"><label>Bank Name</label><input name="bank_name" id="bank_name" class="form-control"></div>
                <div class="col-md-4 mb-2"><label>Bank Account</label><input name="bank_account" id="bank_account" class="form-control"></div>
                <div class="col-md-4 mb-2"><label>Address</label><input name="address_line" id="address_line" class="form-control"></div>
                <div class="col-md-6 mb-2"><label>City</label><input name="city" id="city" class="form-control"></div>
                <div class="col-md-6 mb-2"><label>Country</label><input name="country" id="country" class="form-control"></div>
                <div class="col-12 mb-2"><label>Internal Notes</label><textarea name="notes" id="notes" class="form-control" rows="2"></textarea></div>
                <div class="col-12 mt-2">
                    <button class="btn btn-success">Save Supplier</button>
                    <button type="button" class="btn btn-light" onclick="resetForm()">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card supplier-card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm" id="supplier-table">
                <thead class="thead-light"><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Status</th><th>Payment Terms</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$r['name']) ?></td>
                        <td><?= htmlspecialchars((string)($r['contact_person'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($r['phone'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($r['status'] ?? 'Active')) ?></td>
                        <td><?= htmlspecialchars((string)($r['payment_terms'] ?? '')) ?></td>
                        <td><button class="btn btn-sm btn-primary" onclick='editSupplier(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>

<script>
function editSupplier(s) {
    Object.keys(s).forEach((k) => {
        const el = document.getElementById(k);
        if (el) el.value = s[k] ?? '';
    });
    document.getElementById('supplier_id').value = s.id || 0;
    window.scrollTo({top: 0, behavior: 'smooth'});
}
function resetForm() {
    document.querySelector('form.row').reset();
    document.getElementById('supplier_id').value = 0;
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
