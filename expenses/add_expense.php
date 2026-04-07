<?php
require_once __DIR__ . '/../config/config.php'; 
require_once __DIR__ . '/../includes/session.php'; 
require_login(); 
require_once __DIR__ . '/../includes/auth.php'; 
require_role(['admin','accountant']); 

include __DIR__ . '/../includes/header.php'; 
include __DIR__ . '/../includes/sidebar.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category       = mysqli_real_escape_string($conn, $_POST['category']);
    $amount         = floatval($_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $ref_no         = mysqli_real_escape_string($conn, $_POST['reference_no']);
    $note           = mysqli_real_escape_string($conn, $_POST['note']);
    $expense_date   = $_POST['expense_date'];
    $created_at     = $expense_date . ' ' . date('H:i:s');
    $user_id        = $_SESSION['user_id'] ?? 0;

    if ($amount <= 0) {
        $message = "<div class='alert alert-danger shadow-sm'>Amount must be greater than zero.</div>";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Insert with added fields for Payment Method and Reference
            $sql_exp = "INSERT INTO expenses (category, amount, payment_method, reference_no, note, expense_date, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_exp = $conn->prepare($sql_exp);
            $stmt_exp->bind_param("sdssssi", $category, $amount, $payment_method, $ref_no, $note, $expense_date, $user_id);
            $stmt_exp->execute();

            $sql_ledger = "INSERT INTO accounting_entries (account, note, debit, credit, created_at) VALUES (?, ?, 0, ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $stmt_ledger->bind_param("ssds", $category, $note, $amount, $created_at);
            $stmt_ledger->execute();

            mysqli_commit($conn);
            $message = "<div class='alert alert-success shadow-sm'>Transaction processed successfully.</div>";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "<div class='alert alert-danger shadow-sm'>System Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="main-content">
    <div class="container-fluid pt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card expense-card">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="m-0 font-weight-bold"><i class="fas fa-file-invoice-dollar mr-2"></i>Record Hospital Expenditure</h5>
                    </div>
                    <div class="card-body p-4">
                        <?= $message ?>
                        <form method="POST" autocomplete="off">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">EXPENSE CATEGORY</label>
                                    <select name="category" class="form-control" required>
                                        <optgroup label="Clinical">
                                            <option value="Medical Supplies">Medical Supplies</option>
                                            <option value="Pharmacy Restock">Pharmacy Restock</option>
                                            <option value="Lab Reagents">Lab Reagents</option>
                                        </optgroup>
                                        <optgroup label="Operational">
                                            <option value="Staff Salaries">Staff Salaries</option>
                                            <option value="Utilities">Utilities (Elect/Water)</option>
                                            <option value="Facility Maintenance">Facility Maintenance</option>
                                            <option value="Cleaning & Sanitation">Cleaning & Sanitation</option>
                                            <option value="Security Services">Security Services</option>
                                        </optgroup>
                                        <optgroup label="Administrative">
                                            <option value="Admin Expenses">Administrative Expenses</option>
                                            <option value="Legal & Audit">Legal & Audit Fees</option>
                                            <option value="Marketing & PR">Marketing & PR</option>
                                            <option value="Software/IT Licensing">Software/IT Licensing</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">PAYMENT METHOD</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="M-Pesa">M-Pesa</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">AMOUNT (KSH)</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold">REF / TRANSACTION ID</label>
                                    <input type="text" name="reference_no" class="form-control" placeholder="e.g. TXN-12345" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="small font-weight-bold">TRANSACTION DATE</label>
                                <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group mb-4">
                                <label class="small font-weight-bold">NOTE / DESCRIPTION</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block btn-save">Commit Transaction</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>