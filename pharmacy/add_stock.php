<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/config.php";
require_login();

/* =========================
   FETCH SUPPLIERS
========================= */
// Reverted back to 'name' as that is the actual column in your database
$suppliers_query = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $drug_name     = trim($_POST['drug_name']);
    $unit          = trim($_POST['unit']);
    $quantity      = intval($_POST['quantity']);
    $buying_price  = floatval($_POST['buying_price']);
    $selling_price = floatval($_POST['selling_price']);
    $invoice_no    = trim($_POST['invoice_no']);
    $batch_no      = trim($_POST['batch_no']);
    $expiry_date   = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    
    // Check if supplier was selected, otherwise default to 'N/A' or 'Direct Purchase'
    $supplier_name = !empty($_POST['supplier_name_hidden']) ? $_POST['supplier_name_hidden'] : "Direct/Generic Supplier";
    $total_cost    = $quantity * $buying_price;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO pharmacy_stock 
            (drug_name, unit, quantity, buying_price, selling_price, invoice_no, supplier, batch_no, expiry_date) 
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param("ssidddsss", $drug_name, $unit, $quantity, $buying_price, $selling_price, $invoice_no, $supplier_name, $batch_no, $expiry_date);
        $stmt->execute();

        // Only post to ledger if there is a financial value
        if ($total_cost > 0) {
            $ledger_stmt = $conn->prepare("INSERT INTO accounting_entries (account, note, debit, credit, reference_id) VALUES (?, ?, ?, ?, ?)");
            $acc = "Pharmacy Inventory";
            $note = "Stock Entry: $drug_name (Qty: $quantity)";
            $debit = 0;
            $credit = $total_cost;
            $ref = !empty($invoice_no) ? "INV-" . $invoice_no : "STOCK-ADJ";
            $ledger_stmt->bind_param("ssdds", $acc, $note, $debit, $credit, $ref);
            $ledger_stmt->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Stock successfully logged.";
        header("Location: view_stock.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "System Error: " . $e->getMessage();
    }
}

include __DIR__ . "/../includes/header.php";
include __DIR__ . "/../includes/sidebar.php";
?>

<style>
    /* Elevated Card Design */
    .card {
        border: none !important;
        border-radius: 15px !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08) !important;
    }
    .card-header {
        border-top-left-radius: 15px !important;
        border-top-right-radius: 15px !important;
        border-bottom: 2px solid #f8f9fc !important;
    }
    
    /* Modern Input Fields */
    .form-control {
        border-radius: 10px;
        border: 1px solid #d1d3e2;
        background-color: #f8f9fc;
        padding: 12px 15px;
        transition: all 0.3s;
    }
    .form-control:focus {
        background-color: #ffffff;
        border-color: #4e73df;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.15);
    }
    .input-group-text {
        border-radius: 10px 0 0 10px;
        background-color: #eaecf4;
        border: 1px solid #d1d3e2;
        font-weight: bold;
        color: #5a5c69;
    }
    
    /* Highly Visible Submit Button */
    .btn-primary.btn-block {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        border-radius: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 15px rgba(78, 115, 223, 0.4);
    }
    .btn-primary.btn-block:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(78, 115, 223, 0.6);
    }
    
    /* Subtle enhancements */
    label.small.font-weight-bold {
        color: #4e73df;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
</style>
<div class="container-fluid px-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-pills text-primary mr-2"></i> Inventory Procurement</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0">
                <li class="breadcrumb-item"><a href="view_stock.php">Pharmacy</a></li>
                <li class="breadcrumb-item active">Add Stock</li>
            </ol>
        </nav>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger shadow border-left-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                        <h6 class="m-0 font-weight-bold text-primary">Medicine & Stock Specifications</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 form-group">
                                <label class="small font-weight-bold">Drug/Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="drug_name" class="form-control form-control-lg border-left-primary" placeholder="Enter Full Name" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="small font-weight-bold">Packaging Unit</label>
                                <input type="text" name="unit" class="form-control form-control-lg" placeholder="e.g. Tablets/Box" required>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-4 form-group">
                                <label class="small font-weight-bold">Quantity Received</label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="small font-weight-bold">Batch Number</label>
                                <input type="text" name="batch_no" class="form-control" placeholder="Optional">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="small font-weight-bold">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4 border-bottom-primary">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-success">Financials & Pricing</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="small font-weight-bold">Unit Buying Price (Cost)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">KES</span></div>
                                    <input type="number" step="0.01" name="buying_price" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="small font-weight-bold">Unit Selling Price (Retail)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">KES</span></div>
                                    <input type="number" step="0.01" name="selling_price" class="form-control" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-light">
                        <h6 class="m-0 font-weight-bold text-secondary">Supplier & Invoice</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="small font-weight-bold">Select Supplier</label>
                            <select name="supplier_id" class="form-control" onchange="updateSupplierName(this)">
                                <option value="">-- No Supplier (Generic) --</option>
                                <?php while($s = $suppliers_query->fetch_assoc()): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <input type="hidden" name="supplier_name_hidden" id="supplier_name_hidden" value="Generic Supplier">
                            <small class="text-muted">You can leave this blank if unknown.</small>
                        </div>

                        <div class="form-group mt-4">
                            <label class="small font-weight-bold">Invoice/Reference Number</label>
                            <input type="text" name="invoice_no" class="form-control" placeholder="e.g. INV-99002">
                        </div>

                        <div class="alert alert-info small mt-4">
                            <i class="fas fa-info-circle mr-1"></i> 
                            Saving this will automatically generate a credit entry in the General Ledger.
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-sm mt-4">
                            <i class="fas fa-check-circle mr-1"></i> Save Stock Entry
                        </button>
                        <a href="view_stock.php" class="btn btn-light btn-block mt-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function updateSupplierName(select) {
    var text = select.options[select.selectedIndex].text;
    // Only update hidden field if a real supplier is chosen, otherwise default to "Generic"
    document.getElementById('supplier_name_hidden').value = select.value === "" ? "Generic Supplier" : text;
}
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>