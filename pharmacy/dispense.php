<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role('pharmacist');

$encounter_id = intval($_GET['encounter_id'] ?? 0);

// Check payment
$inv = $conn->query("
SELECT status FROM invoices WHERE encounter_id=$encounter_id
")->fetch_assoc();

if (!$inv || $inv['status']!='paid') {
    die("Invoice not paid");
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $drug_id = intval($_POST['drug_id']);
    $qty = intval($_POST['qty']);

    $conn->query("
    UPDATE drugs SET stock = stock - $qty WHERE id=$drug_id
    ");

    $stmt = $conn->prepare("
    INSERT INTO dispensations (encounter_id,drug_id,qty,dispensed_by)
    VALUES (?,?,?,?)
    ");
    $stmt->bind_param("iiii",$encounter_id,$drug_id,$qty,$_SESSION['user_id']);
    $stmt->execute();
}

$drugs = $conn->query("SELECT * FROM drugs WHERE stock>0");
?>

<form method="post">
<select name="drug_id">
<?php while($d=$drugs->fetch_assoc()): ?>
<option value="<?= $d['id'] ?>"><?= $d['name'] ?> (<?= $d['stock'] ?>)</option>
<?php endwhile; ?>
</select>
<input name="qty" type="number">
<button>Dispense</button>
</form>
