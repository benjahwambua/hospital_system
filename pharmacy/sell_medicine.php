<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$medications = $conn->query("SELECT id, drug_name, quantity, selling_price FROM pharmacy_stock ORDER BY drug_name ASC");
$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name ASC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.main-content {
    padding: 30px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-title {
    font-size: 28px;
    color: #007bff;
    margin-bottom: 10px;
}

.page-subtitle {
    color: #666;
    margin-bottom: 30px;
    font-size: 14px;
}

.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 20px;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #007bff;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row-full {
    grid-column: 1 / -1;
}

.form-group {
    display: flex;
    flex-direction: column;
}

label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}

.required::after {
    content: " *";
    color: #f44336;
    font-weight: bold;
}

.form-control,
.form-select {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-control:focus,
.form-select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0,123,255,0.3);
}

.table-container {
    overflow-x: auto;
    margin-bottom: 20px;
}

.medicines-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.medicines-table thead {
    background: #007bff;
    color: white;
}

.medicines-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
}

.medicines-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.medicines-table tbody tr:hover {
    background: #f8f9fa;
}

.medicines-table input,
.medicines-table select {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    font-family: inherit;
}

.medicines-table input:focus,
.medicines-table select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 3px rgba(0,123,255,0.2);
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.total-section {
    display: flex;
    justify-content: flex-end;
    gap: 20px;
    margin: 30px 0;
    align-items: center;
}

.total-box {
    width: 300px;
    padding: 20px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-radius: 6px;
    text-align: right;
}

.total-label {
    font-size: 12px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.total-amount {
    font-size: 32px;
    font-weight: 700;
}

.button-group {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 2px solid #eee;
    flex-wrap: wrap;
}

.notice-box {
    display: none;
    margin-top: 20px;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #28a745;
    background: #eafaf1;
}

.notice-box.error {
    border-left-color: #dc3545;
    background: #fdeaea;
}

.notice-actions {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .total-section {
        flex-direction: column;
    }

    .total-box {
        width: 100%;
    }

    .button-group {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }

    .medicines-table {
        font-size: 12px;
    }

    .medicines-table th,
    .medicines-table td {
        padding: 8px;
    }

    .card {
        padding: 15px;
    }

    .page-title {
        font-size: 22px;
    }
}
</style>

<div class="main-content">
    <h1 class="page-title">Sell Medicine</h1>
    <p class="page-subtitle">Process medication sales for registered patients and walk-in customers</p>

    <div class="card">
        <form id="sell-medicine-form">

            <div class="section-title">Customer Information</div>

            <div class="form-row">
                <div class="form-group">
                    <label for="customer_type" class="required">Customer Type</label>
                    <select name="customer_type" id="customer_type" class="form-select" required>
                        <option value="">-- Select Customer Type --</option>
                        <option value="registered">Registered Patient</option>
                        <option value="walkin">Walk-in Customer</option>
                    </select>
                </div>
            </div>

            <div class="form-row form-row-full" id="patient-select-div" style="display:none;">
                <div class="form-group">
                    <label for="patient_id" class="required">Select Patient</label>
                    <select name="patient_id" id="patient_id" class="form-select">
                        <option value="">-- Select Patient --</option>
                        <?php while($p = $patients->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="section-title" style="margin-top: 30px;">Medicines</div>

            <div class="table-container">
                <table class="medicines-table" id="medicines-table">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th style="width: 100px;">Available</th>
                            <th style="width: 120px;">Unit Price (KSH)</th>
                            <th style="width: 100px;">Quantity</th>
                            <th style="width: 120px;">Total (KSH)</th>
                            <th style="width: 80px;">
                                <button type="button" class="btn btn-success btn-sm" id="add-row">+ Add Row</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-box">
                    <div class="total-label">Total Amount</div>
                    <div class="total-amount">KSH <span id="grand-total">0.00</span></div>
                </div>
            </div>

            <div class="section-title">Payment Details</div>

            <div class="form-row">
                <div class="form-group">
                    <label for="payment_mode" class="required">Payment Mode</label>
                    <select name="payment_mode" id="payment_mode" class="form-select" required>
                        <option value="">-- Select Payment Mode --</option>
                        <option value="Cash">Cash</option>
                        <option value="Mpesa">M-Pesa</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary" id="initialize-payment-btn">Initialize Payment</button>
                <a href="/hospital_system/reports/daily_sales_report.php" class="btn btn-secondary">View Daily Report</a>
                <a href="/hospital_system/patients/patient_list.php" class="btn btn-secondary">Back to Patients</a>
            </div>
        </form>

        <div id="result-box" class="notice-box">
            <div id="result-message"></div>
            <div class="notice-actions">
                <a href="#" id="view-invoice-link" class="btn btn-secondary" style="display:none;">View Invoice</a>
                <a href="#" id="print-invoice-link" class="btn btn-success" style="display:none;" target="_blank">Print Invoice</a>
                <button type="button" id="new-sale-btn" class="btn btn-primary" style="display:none;">New Sale</button>
            </div>
        </div>
    </div>
</div>

<script>
const medications = <?= json_encode($medications->fetch_all(MYSQLI_ASSOC)) ?>;

document.addEventListener("DOMContentLoaded", function(){
    const customerTypeSelect = document.getElementById("customer_type");
    const patientSelectDiv = document.getElementById("patient-select-div");
    const patientIdSelect = document.getElementById("patient_id");
    const tableBody = document.querySelector("#medicines-table tbody");
    const addRowBtn = document.getElementById("add-row");
    const grandTotalSpan = document.getElementById("grand-total");
    const form = document.getElementById("sell-medicine-form");
    const initializePaymentBtn = document.getElementById("initialize-payment-btn");
    const resultBox = document.getElementById("result-box");
    const resultMessage = document.getElementById("result-message");
    const viewInvoiceLink = document.getElementById("view-invoice-link");
    const printInvoiceLink = document.getElementById("print-invoice-link");
    const newSaleBtn = document.getElementById("new-sale-btn");

    function showResult(message, isError = false) {
        resultBox.style.display = "block";
        resultBox.classList.toggle("error", isError);
        resultMessage.textContent = message;
    }

    function resetResult() {
        resultBox.style.display = "none";
        resultBox.classList.remove("error");
        resultMessage.textContent = "";
        viewInvoiceLink.style.display = "none";
        printInvoiceLink.style.display = "none";
        newSaleBtn.style.display = "none";
    }

    customerTypeSelect.addEventListener("change", function(){
        if(this.value === "registered") {
            patientSelectDiv.style.display = "block";
            patientIdSelect.required = true;
        } else if(this.value === "walkin") {
            patientSelectDiv.style.display = "none";
            patientIdSelect.required = false;
            patientIdSelect.value = "";
        }
    });

    function recalcGrandTotal(){
        let total = 0;
        tableBody.querySelectorAll("tr").forEach(row => {
            const totalValue = parseFloat(row.querySelector(".total").value) || 0;
            total += totalValue;
        });
        grandTotalSpan.textContent = total.toFixed(2);
    }

    function addRow(){
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>
                <select class="form-select med-select" required>
                    <option value="">-- Select Medicine --</option>
                    ${medications.map(m => `<option value="${m.id}" data-price="${m.selling_price}" data-stock="${m.quantity}">${m.drug_name}</option>`).join('')}
                </select>
            </td>
            <td class="available text-center">0</td>
            <td><input type="number" class="form-control unit-price" readonly></td>
            <td><input type="number" class="form-control qty" min="1" value="1"></td>
            <td><input type="text" class="form-control total" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row">Remove</button></td>
        `;
        tableBody.appendChild(row);
        attachRowEvents(row);
    }

    function attachRowEvents(row){
        const medSelect = row.querySelector(".med-select");
        const availableCell = row.querySelector(".available");
        const unitPriceInput = row.querySelector(".unit-price");
        const qtyInput = row.querySelector(".qty");
        const totalInput = row.querySelector(".total");
        const removeBtn = row.querySelector(".remove-row");

        medSelect.addEventListener("change", function(){
            const selected = medications.find(m => m.id == this.value);
            if(!selected){
                availableCell.textContent = 0;
                unitPriceInput.value = "";
                totalInput.value = "";
                recalcGrandTotal();
                return;
            }
            availableCell.textContent = selected.quantity;
            unitPriceInput.value = parseFloat(selected.selling_price).toFixed(2);
            qtyInput.value = Math.min(parseInt(qtyInput.value) || 1, selected.quantity || 1);
            totalInput.value = (selected.selling_price * qtyInput.value).toFixed(2);
            recalcGrandTotal();
        });

        qtyInput.addEventListener("input", function(){
            const maxQty = parseInt(availableCell.textContent) || 0;
            let qty = Math.max(1, Math.min(parseInt(this.value) || 1, Math.max(maxQty, 1)));
            if(maxQty > 0 && qty > maxQty) {
                alert("Quantity exceeds available stock of " + maxQty);
            }
            this.value = qty;
            totalInput.value = (parseFloat(unitPriceInput.value || 0) * qty).toFixed(2);
            recalcGrandTotal();
        });

        removeBtn.addEventListener("click", function(e){
            e.preventDefault();
            row.remove();
            recalcGrandTotal();
        });
    }

    addRowBtn.addEventListener("click", function(e){
        e.preventDefault();
        addRow();
    });

    newSaleBtn.addEventListener("click", function(){
        form.reset();
        patientSelectDiv.style.display = "none";
        patientIdSelect.required = false;
        tableBody.innerHTML = "";
        addRow();
        recalcGrandTotal();
        resetResult();
        initializePaymentBtn.disabled = false;
    });

    addRow();

    form.addEventListener("submit", async function(e){
        e.preventDefault();
        resetResult();

        const customerType = customerTypeSelect.value;
        if(!customerType) {
            alert("Please select a customer type.");
            return;
        }

        let patientId = null;
        if(customerType === "registered") {
            patientId = patientIdSelect.value;
            if(!patientId) {
                alert("Please select a patient.");
                return;
            }
        }

        const items = [];
        tableBody.querySelectorAll("tr").forEach(row => {
            const medId = row.querySelector(".med-select").value;
            const qty = parseInt(row.querySelector(".qty").value) || 0;
            const price = parseFloat(row.querySelector(".unit-price").value) || 0;

            if(medId && qty > 0) {
                items.push({
                    med_id: parseInt(medId, 10),
                    quantity: qty,
                    unit_price: price,
                    total: qty * price
                });
            }
        });

        if(items.length === 0) {
            alert("Please add at least one medicine.");
            return;
        }

        const paymentMode = document.getElementById("payment_mode").value;
        if(!paymentMode) {
            alert("Please select a payment mode.");
            return;
        }

        const payload = {
            customer_type: customerType,
            patient_id: patientId,
            payment_mode: paymentMode,
            grand_total: parseFloat(grandTotalSpan.textContent),
            items: items
        };

        initializePaymentBtn.disabled = true;
        initializePaymentBtn.textContent = "Initializing...";

        try {
            const response = await fetch("/hospital_system/pharmacy/dispense_ajax.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if(data.status === "success" && data.invoice_id) {
                const invoiceUrl = `/hospital_system/pharmacy/view_invoice.php?id=${data.invoice_id}`;
                const printUrl = `${invoiceUrl}&print=1`;

                showResult("Payment initialized successfully. You can now view or print the invoice.");
                viewInvoiceLink.href = invoiceUrl;
                printInvoiceLink.href = printUrl;
                viewInvoiceLink.style.display = "inline-block";
                printInvoiceLink.style.display = "inline-block";
                newSaleBtn.style.display = "inline-block";
            } else {
                showResult(data.message || "Error initializing payment. Please try again.", true);
                initializePaymentBtn.disabled = false;
            }
        } catch(err) {
            console.error(err);
            showResult("Error initializing payment. Please check your connection and try again.", true);
            initializePaymentBtn.disabled = false;
        } finally {
            initializePaymentBtn.textContent = "Initialize Payment";
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
