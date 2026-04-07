<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/session.php';

$po_id = $_GET['id'];
$drugs = $conn->query("SELECT id, drug_name FROM pharmacy_inventory");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_stmt = $conn->prepare("INSERT INTO po_items (po_id, drug_id, qty, unit_cost) VALUES (?, ?, ?, ?)");
    foreach ($_POST['drug_id'] as $key => $val) {
        $item_stmt->bind_param("iiid", $po_id, $_POST['drug_id'][$key], $_POST['qty'][$key], $_POST['unit_cost'][$key]);
        $item_stmt->execute();
    }
    header("Location: view_po.php?id=$po_id");
}

include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/sidebar.php';
?>
<div class="card p-4">
    <h4>Add Items to PO #<?= $po_id ?></h4>
    <form method="POST">
        <table class="table" id="poTable">
            <thead><tr><th>Item</th><th>Qty</th><th>Unit Cost</th></tr></thead>
            <tbody>
                <tr>
                    <td>
                        <select name="drug_id[]" class="form-control">
                            <?php while($d = $drugs->fetch_assoc()): ?>
                                <option value="<?= $d['id'] ?>"><?= $d['drug_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <td><input type="number" name="qty[]" class="form-control"></td>
                    <td><input type="number" step="0.01" name="unit_cost[]" class="form-control"></td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="btn btn-success">Finalize Order</button>
    </form>
</div>