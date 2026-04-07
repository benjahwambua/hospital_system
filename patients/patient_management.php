<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$msg = "";

/* =========================
   RECEPTION: REGISTER PATIENT
========================= */
if (isset($_POST['register_patient'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $age       = intval($_POST['age']);
    $gender    = $conn->real_escape_string($_POST['gender']);
    $clinic_category = $conn->real_escape_string($_POST['clinic_category']);
    $complaint = $conn->real_escape_string($_POST['complaint']);
    $vitals    = $conn->real_escape_string($_POST['vitals']);

    // Insert patient
    $stmt = $conn->prepare("INSERT INTO patients (full_name, age, gender, clinic_category, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("siss", $full_name, $age, $gender, $clinic_category);
    $stmt->execute();
    $patient_id = $stmt->insert_id;
    $stmt->close();

    // Create initial encounter
    $stmt = $conn->prepare("INSERT INTO encounters (patient_id, type, status, presenting_complaint, vitals, created_at) VALUES (?, 'walkin', 'open', ?, ?, NOW())");
    $stmt->bind_param("iss", $patient_id, $complaint, $vitals);
    $stmt->execute();
    $encounter_id = $stmt->insert_id;
    $stmt->close();

    $msg = "Patient registered successfully. Encounter ID: $encounter_id";
}

/* =========================
   DOCTOR: UPDATE PATIENT PROFILE
========================= */
if (isset($_POST['update_profile'])) {
    $enc_id = intval($_POST['encounter_id']);
    $diagnosis = $conn->real_escape_string($_POST['diagnosis']);
    $notes     = $conn->real_escape_string($_POST['notes']);
    $allergies = $conn->real_escape_string($_POST['allergies']);
    $next_appt = $_POST['next_appointment'] ?: null;

    $stmt = $conn->prepare("UPDATE encounters SET diagnosis=?, doctor_notes=?, allergies=?, next_appointment=? WHERE id=?");
    $stmt->bind_param("ssssi", $diagnosis, $notes, $allergies, $next_appt, $enc_id);
    $stmt->execute();
    $stmt->close();

    $msg = "Patient profile updated.";
}

/* =========================
   DISPENSE MEDICINE
========================= */
if (isset($_POST['dispense_medicine'])) {
    $enc_id  = intval($_POST['encounter_id']);
    $med_id  = intval($_POST['med_id']);
    $qty     = intval($_POST['qty']);
    $payment_mode = $_POST['payment_mode'];

    // Fetch med
    $med = $conn->query("SELECT id, drug_name, quantity, selling_price FROM pharmacy_stock WHERE id=$med_id")->fetch_assoc();
    if (!$med || $med['quantity'] < $qty) { $msg = "Invalid med or insufficient stock"; }
    else {
        $total = $med['selling_price'] * $qty;

        // Invoice
        $inv = $conn->query("SELECT id, invoice_no FROM invoices WHERE encounter_id=$enc_id LIMIT 1")->fetch_assoc();
        if ($inv) {
            $invoice_id = $inv['id'];
            $invoice_no = $inv['invoice_no'];
            $conn->query("UPDATE invoices SET total=total+$total, status='paid', payment_mode='$payment_mode', paid_amount=$total WHERE id=$invoice_id");
        } else {
            $invoice_no = 'INV-'.date('YmdHis');
            $stmt = $conn->prepare("INSERT INTO invoices (encounter_id, invoice_no, total, status, payment_mode, paid_amount) VALUES (?, ?, ?, 'paid', ?, ?)");
            $stmt->bind_param("isdsi", $enc_id, $invoice_no, $total, $payment_mode, $total);
            $stmt->execute();
            $invoice_id = $stmt->insert_id;
            $stmt->close();
        }

        // Invoice item
        $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, med_id, description, qty, unit_price, total, item_type) VALUES (?, ?, ?, ?, ?, ?, 'pharmacy')");
        $desc = $med['drug_name'];
        $stmt->bind_param("iisdid", $invoice_id, $med_id, $desc, $qty, $med['selling_price'], $total);
        $stmt->execute();
        $stmt->close();

        // Update stock
        $newbal = $med['quantity'] - $qty;
        $conn->query("UPDATE pharmacy_stock SET quantity=$newbal WHERE id=$med_id");
        $stmt = $conn->prepare("INSERT INTO stock_movements (stock_id, movement_type, quantity_change, balance_after, note, user_id) VALUES (?, 'out', ?, ?, 'Sale', ?)");
        $stmt->bind_param("iiii", $med_id, $qty, $newbal, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $msg = "Medicine dispensed and payment recorded. Invoice: $invoice_no";
    }
}

/* =========================
   FETCH DATA
========================= */
$clinic_categories = ['ANC Attendance','Casualty','CWC Attendance','FP Attendance','General Outpatient','PNC Attendance','Post Abortal Care','SGBV'];
$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name");
$meds     = $conn->query("SELECT id, drug_name, quantity, selling_price FROM pharmacy_stock ORDER BY drug_name");
$encounters = $conn->query("SELECT e.id, p.full_name FROM encounters e JOIN patients p ON p.id=e.patient_id WHERE e.status='open' ORDER BY e.created_at DESC");
$paid_invoices = $conn->query("
    SELECT i.*, p.full_name FROM invoices i
    JOIN encounters e ON e.id=i.encounter_id
    JOIN patients p ON p.id=e.patient_id
    WHERE i.status='paid'
    ORDER BY i.created_at DESC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
<h3>Hospital Workflow</h3>

<?php if($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- RECEPTION REGISTER -->
<h4>1️⃣ Register Patient / Walk-in</h4>
<form method="post">
<label>Full Name</label><input name="full_name" class="form-control" required>
<label>Age</label><input type="number" name="age" class="form-control" required>
<label>Gender</label>
<select name="gender" class="form-control">
<option value="Male">Male</option><option value="Female">Female</option>
</select>
<label>Clinic Category</label>
<select name="clinic_category" class="form-control">
<?php foreach($clinic_categories as $c): ?><option><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
</select>
<label>Presenting Complaint</label><textarea name="complaint" class="form-control" required></textarea>
<label>Vitals</label><textarea name="vitals" class="form-control" required></textarea>
<div style="margin-top:10px;"><button class="btn" name="register_patient">Register Patient</button></div>
</form>

<hr>
<!-- DOCTOR UPDATE -->
<h4>2️⃣ Doctor: Update Patient Profile</h4>
<form method="post">
<label>Encounter</label>
<select name="encounter_id" class="form-control">
<?php while($e=$encounters->fetch_assoc()): ?>
<option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?> (<?= $e['id'] ?>)</option>
<?php endwhile; ?>
</select>
<label>Diagnosis</label><input name="diagnosis" class="form-control">
<label>Doctor Notes</label><textarea name="notes" class="form-control"></textarea>
<label>Allergies</label><textarea name="allergies" class="form-control"></textarea>
<label>Next Appointment</label><input type="date" name="next_appointment" class="form-control">
<div style="margin-top:10px;"><button class="btn" name="update_profile">Save Profile</button></div>
</form>

<hr>
<!-- DISPENSE MEDICINE -->
<h4>3️⃣ Dispense Medicine / Payment</h4>
<form method="post">
<label>Encounter</label>
<select name="encounter_id" class="form-control">
<?php while($e=$encounters->fetch_assoc()): ?>
<option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?> (<?= $e['id'] ?>)</option>
<?php endwhile; ?>
</select>
<label>Medicine</label>
<select name="med_id" class="form-control">
<?php while($m=$meds->fetch_assoc()): ?>
<option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['drug_name']) ?> - Stock <?= $m['quantity'] ?> - KES <?= number_format($m['selling_price'],2) ?></option>
<?php endwhile; ?>
</select>
<label>Quantity</label><input type="number" name="qty" value="1" min="1" class="form-control">
<label>Payment Mode</label>
<select name="payment_mode" class="form-control">
<option value="Cash">Cash</option>
<option value="MPESA">MPESA</option>
</select>
<div style="margin-top:10px;"><button class="btn" name="dispense_medicine">Dispense & Pay</button></div>
</form>

<hr>
<!-- PAID INVOICES REPORT -->
<h4>4️⃣ Paid Invoices / Collections</h4>
<table class="table">
<thead><tr><th>Invoice No</th><th>Patient</th><th>Payment Mode</th><th>Amount</th><th>Date</th></tr></thead>
<tbody>
<?php while($p=$paid_invoices->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($p['invoice_no']) ?></td>
<td><?= htmlspecialchars($p['full_name']) ?></td>
<td><?= htmlspecialchars($p['payment_mode']) ?></td>
<td><?= number_format($p['paid_amount'],2) ?></td>
<td><?= $p['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
