<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Fetch suppliers for the dropdown
$suppliers = $conn->query("SELECT id, name FROM suppliers");
?>

<div class="container-fluid">
    <h2 class="h3 mb-4 text-gray-800">New Purchase Order</h2>
    
    <form action="process_po.php" method="POST">
        <div class="card shadow border-left-primary mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="small font-weight-bold">Select Supplier</label>
                        <select name="supplier_id" class="form-control" required>
                            <option value="">-- Choose Supplier --</option>
                            <?php while($row = $suppliers->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small font-weight-bold">Order Date</label>
                        <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Order Items</h6>
                <button type="button" class="btn btn-sm btn-success" onclick="addRow()"><i class="fa fa-plus"></i> Add Item Row</button>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="poTable">
                    <thead>
                        <tr>
                            <th>Item Description (Medicine Name)</th>
                            <th width="150">Qty</th>
                            <th width="150">Est. Unit Price</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="po_items_body">
                        <tr>
                            <td><input type="text" name="items[0][name]" class="form-control" placeholder="e.g. Paracetamol" required></td>
                            <td><input type="number" name="items[0][qty]" class="form-control" min="1" required></td>
                            <td><input type="number" step="0.01" name="items[0][price]" class="form-control" required></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fa fa-times"></i></button></td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-3 text-right">
                    <button type="submit" class="btn btn-primary px-5">Generate Purchase Order</button>
                </div>
            </div>
        </div>
    </form>
</div>



<script>
let rowCount = 1;

function addRow() {
    const tableBody = document.getElementById('po_items_body');
    const row = `
        <tr>
            <td><input type="text" name="items[${rowCount}][name]" class="form-control" required></td>
            <td><input type="number" name="items[${rowCount}][qty]" class="form-control" min="1" required></td>
            <td><input type="number" step="0.01" name="items[${rowCount}][price]" class="form-control" required></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fa fa-times"></i></button></td>
        </tr>
    `;
    tableBody.insertAdjacentHTML('beforeend', row);
    rowCount++;
}

function removeRow(btn) {
    const row = btn.closest('tr');
    const tbody = document.getElementById('po_items_body');
    if (tbody.rows.length > 1) {
        row.remove();
    } else {
        alert("At least one item is required.");
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>