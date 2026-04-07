<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role('doctor');

$encounter_id = intval($_GET['encounter_id'] ?? 0);
if (!$encounter_id) die("Invalid encounter");

// Add billing item
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $desc = $_POST['description'];
    $amount = floatval($_POST['amount']);

    $stmt = $conn->prepare("
    INSERT INTO billing_items (encounter_id, service_type, description, amount)
    VALUES (?,'procedure',?,?)
    ");
    $stmt->bind_param("isd",$encounter_id,$desc,$amount);
    $stmt->execute();
}

// Auto-create invoice
$conn->query("
INSERT INTO invoices (encounter_id,total)
SELECT $encounter_id, SUM(amount)
FROM billing_items
WHERE encounter_id=$encounter_id
AND NOT EXISTS (
    SELECT 1 FROM invoices WHERE encounter_id=$encounter_id
)
");

$items = $conn->query("
SELECT * FROM billing_items WHERE encounter_id=$encounter_id
");
?>

<h3>Billing</h3>
<form method="post">
<input name="description" placeholder="Procedure">
<input name="amount" type="number" step="0.01">
<button>Add</button>
</form>

<table>
<?php while($i=$items->fetch_assoc()): ?>
<tr><td><?= $i['description'] ?></td><td><?= $i['amount'] ?></td></tr>
<?php endwhile; ?>
</table>
