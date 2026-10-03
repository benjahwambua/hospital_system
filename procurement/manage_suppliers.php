<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'procurement', 'view');

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
        require_module_access($conn, 'procurement', $supplierId > 0 ? 'edit' : 'create');
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
/* Procurement / Suppliers — dashboard visual system */
.supplier-page{
  background:radial-gradient(circle at 8% 0%,rgba(19,168,184,.07),transparent 28%),#f4f7fb;
  min-height:calc(100vh - 75px);padding:30px 24px 52px
}
.supplier-shell{max-width:1480px;margin:0 auto}
.supplier-hero{
  position:relative;overflow:hidden;color:#fff;
  background:linear-gradient(135deg,#0b3d91 0%,#1261c9 55%,#13a8b8 100%);
  border-radius:22px;margin-bottom:22px;padding:29px 31px;
  box-shadow:0 16px 38px rgba(16,77,153,.22)
}
.supplier-hero:after{
  content:"";position:absolute;width:270px;height:270px;border:1px solid rgba(255,255,255,.12);
  border-radius:50%;right:-80px;top:-120px;
  box-shadow:0 0 0 35px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.015);
  pointer-events:none
}
.supplier-hero-main{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}
.supplier-eyebrow{font-size:.68rem;text-transform:uppercase;letter-spacing:.17em;font-weight:800;opacity:.72;margin-bottom:7px}
.supplier-hero h1{font-size:1.8rem!important;font-weight:800!important;letter-spacing:-.025em;margin:0!important;color:#fff!important}
.supplier-hero p{margin:7px 0 0!important;color:#fff!important;opacity:.82!important;font-size:.92rem}
.supplier-actions{display:flex;gap:8px;flex-wrap:wrap;position:relative;z-index:2}
.supplier-action{
  border-radius:10px;padding:10px 14px;font-weight:800;text-decoration:none;
  display:inline-flex;align-items:center;gap:7px;transition:.18s ease
}
.supplier-action:hover{transform:translateY(-1px);text-decoration:none}
.supplier-action-light{background:rgba(255,255,255,.11);color:#fff;border:1px solid rgba(255,255,255,.22)}
.supplier-action-light:hover{color:#fff;background:rgba(255,255,255,.17)}
.supplier-action-primary{background:#fff;color:#0d5f91}
.supplier-action-primary:hover{color:#082f55}
.supplier-alert{border-radius:12px;border:1px solid;margin-bottom:18px;padding:13px 16px;font-weight:700}
.supplier-alert.success{background:#ecfdf5;border-color:#b7efd3;color:#087443}
.supplier-alert.danger{background:#fff1f1;border-color:#ffd7d7;color:#c92a2a}
.supplier-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
.supplier-card{
  background:#fff;border:1px solid #e2e8f0;border-radius:17px;
  box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden
}
.supplier-metric{padding:20px 21px;position:relative;overflow:hidden}
.supplier-metric:after{
  content:"";position:absolute;right:-25px;top:-28px;width:90px;height:90px;border-radius:50%;background:#edf6fc
}
.supplier-metric small,.supplier-metric strong,.supplier-metric span{position:relative;z-index:1;display:block}
.supplier-metric small{text-transform:uppercase;color:#697586;font-size:.68rem;font-weight:800;letter-spacing:.07em;margin-bottom:8px}
.supplier-metric strong{font-size:1.35rem;color:#152033;font-weight:850;font-variant-numeric:tabular-nums}
.supplier-metric span{color:#7a8494;font-size:.8rem;margin-top:5px}
.supplier-card-header{
  padding:18px 21px;border-bottom:1px solid #e8edf3;display:flex;
  justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;
  background:linear-gradient(180deg,#fff,#fbfcfe)
}
.supplier-card-header strong{font-size:.98rem;color:#182334}
.supplier-card-header small{display:block;color:#7a8494;font-size:.78rem;margin-top:3px}
.supplier-search{display:flex;gap:9px;align-items:center}
.supplier-search input{
  min-width:300px;height:42px;border:1px solid #dfe5ed;border-radius:10px;
  padding:0 13px;outline:none;color:#172033
}
.supplier-search input:focus{border-color:#2f78c8;box-shadow:0 0 0 3px rgba(47,120,200,.10)}
.supplier-btn{
  height:42px;border:0;border-radius:10px;padding:0 14px;font-weight:800;
  display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;text-decoration:none
}
.supplier-btn-primary{background:#17469a;color:#fff}
.supplier-btn-primary:hover{background:#123a82;color:#fff}
.supplier-btn-success{background:#087f55;color:#fff}
.supplier-btn-success:hover{background:#066844;color:#fff}
.supplier-btn-light{background:#f3f5f8;color:#475467;border:1px solid #dfe5ed}
.supplier-form-wrap{margin-bottom:20px}
.supplier-form{padding:22px}
.supplier-section{
  margin:0 0 20px;padding:0 0 17px;border-bottom:1px solid #edf1f5
}
.supplier-section:last-of-type{border-bottom:0;margin-bottom:5px}
.supplier-section-title{
  display:flex;align-items:center;gap:9px;margin-bottom:14px;color:#1e293b;
  font-size:.84rem;font-weight:850;text-transform:uppercase;letter-spacing:.055em
}
.supplier-section-title i{
  width:28px;height:28px;border-radius:8px;background:#edf6fc;color:#1769aa;
  display:inline-flex;align-items:center;justify-content:center;font-size:.75rem
}
.supplier-field{margin-bottom:14px}
.supplier-field label{
  display:block;font-size:.68rem;text-transform:uppercase;color:#697586;
  font-weight:800;letter-spacing:.045em;margin-bottom:6px
}
.supplier-field input,.supplier-field select,.supplier-field textarea{
  width:100%;border:1px solid #dfe5ed;border-radius:9px;background:#fff;
  color:#172033;padding:10px 12px;outline:none;transition:.15s ease
}
.supplier-field input,.supplier-field select{height:42px}
.supplier-field textarea{min-height:78px;resize:vertical}
.supplier-field input:focus,.supplier-field select:focus,.supplier-field textarea:focus{
  border-color:#2f78c8;box-shadow:0 0 0 3px rgba(47,120,200,.10)
}
.supplier-form-actions{
  display:flex;gap:9px;align-items:center;padding-top:4px
}
.supplier-table-wrap{overflow:auto}
.supplier-table{width:100%;border-collapse:separate;border-spacing:0;min-width:850px}
.supplier-table th,.supplier-table td{padding:13px 16px;border-bottom:1px solid #edf1f5;white-space:nowrap}
.supplier-table th{
  background:#f7f9fc;color:#687386;font-size:.67rem;text-transform:uppercase;
  letter-spacing:.07em;font-weight:800
}
.supplier-table td{color:#374151;font-size:.86rem}
.supplier-table tbody tr{transition:background .15s}
.supplier-table tbody tr:hover td{background:#f7fbff}
.supplier-name{font-weight:850;color:#253044}
.supplier-contact{color:#667085}
.supplier-status{
  display:inline-flex;padding:5px 9px;border-radius:999px;font-size:.62rem;
  font-weight:850;letter-spacing:.05em;text-transform:uppercase
}
.supplier-status.active{background:#ecfdf5;border:1px solid #b7efd3;color:#087443}
.supplier-status.inactive{background:#f3f4f6;border:1px solid #e5e7eb;color:#687386}
.supplier-edit{
  border:1px solid #dbe6ff;background:#eef4ff;color:#2f6fed;
  border-radius:8px;padding:6px 10px;font-size:.68rem;font-weight:850;cursor:pointer
}
.supplier-edit:hover{background:#e3edff}
.supplier-empty{padding:55px 25px!important;text-align:center!important;color:#7a8494!important}
.supplier-empty-icon{
  width:54px;height:54px;margin:0 auto 13px;border-radius:16px;background:#edf6fc;
  color:#1769aa;display:flex;align-items:center;justify-content:center;font-size:21px
}
.supplier-empty strong{display:block;color:#25324a;font-size:.95rem;margin-bottom:5px}
.supplier-empty span{font-size:.78rem}
@media(max-width:1050px){
  .supplier-metrics{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:767px){
  .supplier-page{padding:18px 12px 35px}
  .supplier-hero{padding:23px 20px;border-radius:17px}
  .supplier-hero h1{font-size:1.4rem!important}
  .supplier-metrics{grid-template-columns:1fr}
  .supplier-search{width:100%}
  .supplier-search input{min-width:0;flex:1}
  .supplier-form{padding:18px}
}
@media print{
  .supplier-page{padding:0;background:#fff}
  .supplier-hero,.supplier-metrics,.supplier-form-wrap,.supplier-search,.no-print{display:none!important}
  .supplier-card{box-shadow:none!important;border:1px solid #ddd!important}
}
</style>
<div class="supplier-page">
  <div class="supplier-shell">
    <div class="supplier-hero">
      <div class="supplier-hero-main">
        <div>
          <div class="supplier-eyebrow">Supply Chain · Procurement</div>
          <h1>Supplier Management</h1>
          <p>Maintain supplier records, commercial terms and procurement contact information.</p>
        </div>
        <div class="supplier-actions">
          <a href="purchase_orders.php" class="supplier-action supplier-action-light"><i class="fa fa-file-text-o"></i> Purchase Orders</a>
          <?php if (can_create($conn, 'procurement')): ?>
            <a href="#supplier-form" class="supplier-action supplier-action-primary"><i class="fa fa-plus"></i> Add Supplier</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($msg !== ''): ?>
      <div class="supplier-alert <?= $msgType === 'danger' ? 'danger' : 'success' ?>">
        <i class="fa <?= $msgType === 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle' ?> mr-2"></i>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <?php
      $supplierTotal = count($rows);
      $activeSuppliers = 0;
      $inactiveSuppliers = 0;
      foreach ($rows as $supplierRow) {
          if (strcasecmp((string)($supplierRow['status'] ?? 'Active'), 'Active') === 0) $activeSuppliers++;
          else $inactiveSuppliers++;
      }
    ?>
    <div class="supplier-metrics">
      <div class="supplier-card supplier-metric">
        <small>Total Suppliers</small>
        <strong><?= number_format($supplierTotal) ?></strong>
        <span>Suppliers in current register</span>
      </div>
      <div class="supplier-card supplier-metric">
        <small>Active Suppliers</small>
        <strong><?= number_format($activeSuppliers) ?></strong>
        <span>Available for procurement</span>
      </div>
      <div class="supplier-card supplier-metric">
        <small>Inactive</small>
        <strong><?= number_format($inactiveSuppliers) ?></strong>
        <span>Supplier records not active</span>
      </div>
      <div class="supplier-card supplier-metric">
        <small>Search Scope</small>
        <strong><?= $search !== '' ? 'Filtered' : 'All' ?></strong>
        <span><?= $search !== '' ? 'Matching supplier records' : 'Complete supplier register' ?></span>
      </div>
    </div>

    <div class="supplier-card mb-4">
      <div class="supplier-card-header">
        <div>
          <strong>Supplier Directory</strong>
          <small>Search and review registered procurement suppliers.</small>
        </div>
        <form method="get" class="supplier-search">
          <input type="text" name="search" placeholder="Search supplier, contact, email or phone" value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="supplier-btn supplier-btn-primary"><i class="fa fa-search"></i> Search</button>
          <?php if ($search !== ''): ?><a href="manage_suppliers.php" class="supplier-btn supplier-btn-light">Reset</a><?php endif; ?>
        </form>
      </div>
    </div>

    <div class="supplier-card supplier-form-wrap" id="supplier-form">
      <div class="supplier-card-header">
        <div>
          <strong id="supplier-form-title">Supplier Profile</strong>
          <small id="supplier-form-subtitle">Create or update supplier information used by procurement.</small>
        </div>
      </div>
      <form method="post" class="supplier-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="supplier_id" id="supplier_id" value="0">

        <div class="supplier-section">
          <div class="supplier-section-title"><i class="fa fa-building"></i> Company & Contact</div>
          <div class="row">
            <div class="col-md-4 supplier-field"><label>Name *</label><input name="name" id="name" required></div>
            <div class="col-md-4 supplier-field"><label>Contact Person</label><input name="contact_person" id="contact_person"></div>
            <div class="col-md-4 supplier-field"><label>Status</label><select name="status" id="status"><option>Active</option><option>Inactive</option></select></div>
            <div class="col-md-3 supplier-field"><label>Email</label><input name="email" id="email" type="email"></div>
            <div class="col-md-3 supplier-field"><label>Phone</label><input name="phone" id="phone"></div>
            <div class="col-md-3 supplier-field"><label>Mobile</label><input name="mobile" id="mobile"></div>
            <div class="col-md-3 supplier-field"><label>Website</label><input name="website" id="website"></div>
          </div>
        </div>

        <div class="supplier-section">
          <div class="supplier-section-title"><i class="fa fa-file-text-o"></i> Tax & Commercial Terms</div>
          <div class="row">
            <div class="col-md-3 supplier-field"><label>Tax PIN</label><input name="tax_pin" id="tax_pin"></div>
            <div class="col-md-3 supplier-field"><label>VAT Number</label><input name="vat_number" id="vat_number"></div>
            <div class="col-md-3 supplier-field"><label>Payment Terms</label><input name="payment_terms" id="payment_terms" placeholder="e.g. Net 30"></div>
            <div class="col-md-3 supplier-field"><label>Credit Limit</label><input name="credit_limit" id="credit_limit" type="number" step="0.01" value="0"></div>
          </div>
        </div>

        <div class="supplier-section">
          <div class="supplier-section-title"><i class="fa fa-bank"></i> Banking & Address</div>
          <div class="row">
            <div class="col-md-4 supplier-field"><label>Bank Name</label><input name="bank_name" id="bank_name"></div>
            <div class="col-md-4 supplier-field"><label>Bank Account</label><input name="bank_account" id="bank_account"></div>
            <div class="col-md-4 supplier-field"><label>Address</label><input name="address_line" id="address_line"></div>
            <div class="col-md-6 supplier-field"><label>City</label><input name="city" id="city"></div>
            <div class="col-md-6 supplier-field"><label>Country</label><input name="country" id="country"></div>
          </div>
        </div>

        <div class="supplier-section">
          <div class="supplier-section-title"><i class="fa fa-sticky-note-o"></i> Internal Notes</div>
          <div class="supplier-field mb-0"><label>Notes</label><textarea name="notes" id="notes" rows="3"></textarea></div>
        </div>

        <div class="supplier-form-actions">
          <button class="supplier-btn supplier-btn-success" type="submit"><i class="fa fa-save"></i> Save Supplier</button>
          <button type="button" class="supplier-btn supplier-btn-light" onclick="resetForm()"><i class="fa fa-undo"></i> Reset</button>
        </div>
      </form>
    </div>

    <div class="supplier-card">
      <div class="supplier-card-header">
        <div>
          <strong>Supplier Register</strong>
          <small><?= number_format($supplierTotal) ?> supplier record<?= $supplierTotal === 1 ? '' : 's' ?> in the current scope.</small>
        </div>
      </div>
      <div class="supplier-table-wrap">
        <table class="supplier-table" id="supplier-table">
          <thead>
            <tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Email</th><th>Status</th><th>Payment Terms</th><th>Action</th></tr>
          </thead>
          <tbody>
          <?php if ($rows): foreach ($rows as $r): ?>
            <tr>
              <td><span class="supplier-name"><?= htmlspecialchars((string)$r['name']) ?></span></td>
              <td><span class="supplier-contact"><?= htmlspecialchars((string)($r['contact_person'] ?? '')) ?: '—' ?></span></td>
              <td><?= htmlspecialchars((string)($r['phone'] ?? '')) ?: '—' ?></td>
              <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?: '—' ?></td>
              <td><span class="supplier-status <?= strtolower((string)($r['status'] ?? 'Active')) === 'active' ? 'active' : 'inactive' ?>"><?= htmlspecialchars((string)($r['status'] ?? 'Active')) ?></span></td>
              <td><?= htmlspecialchars((string)($r['payment_terms'] ?? '')) ?: '—' ?></td>
              <td>
                <?php if (can_edit($conn, 'procurement')): ?>
                  <button type="button" class="supplier-edit" onclick='editSupplier(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa fa-pencil"></i> Edit</button>
                <?php else: ?>
                  <span class="supplier-contact">View only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="7" class="supplier-empty"><div class="supplier-empty-icon"><i class="fa fa-building-o"></i></div><strong>No suppliers found</strong><span><?= $search !== '' ? 'Try a different search term.' : 'Add your first supplier using the profile form above.' ?></span></td></tr>
          <?php endif; ?>
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
    document.getElementById('supplier-form-title').textContent = 'Edit Supplier Profile';
    document.getElementById('supplier-form-subtitle').textContent = 'Update the selected supplier record and save your changes.';
    const form = document.getElementById('supplier-form');
    if (form) form.scrollIntoView({behavior:'smooth', block:'start'});
}
function resetForm() {
    const form = document.querySelector('.supplier-form');
    if (form) form.reset();
    document.getElementById('supplier_id').value = 0;
    document.getElementById('supplier-form-title').textContent = 'Supplier Profile';
    document.getElementById('supplier-form-subtitle').textContent = 'Create or update supplier information used by procurement.';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>