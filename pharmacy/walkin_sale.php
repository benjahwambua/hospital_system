<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

/* Fetch available medications */
$medications = $conn->query("SELECT id, drug_name, quantity, selling_price FROM pharmacy_stock ORDER BY drug_name ASC");
?>

<div class="container mt-4">
    <div class="card shadow-sm p-3">
        <h3>Walk-in Medicine Sale</h3>

        <div id="alert-container"></div>

        <form id="walkin-sale-form">
            <div class="mb-3">
                <label>Customer Name</label>
                <input type="text" name="customer_name" class="form-control" required placeholder="Walk-in customer">
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="medicines-table">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Available</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th><button type="button" class="btn btn-sm btn-success" id="add-row">Add</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic rows go here -->
                    </tbody>
                </table>
            </div>

            <div class="mb-3">
                <label>Total Amount</label>
                <input type="text" id="grand-total" class="form-control" readonly value="0">
            </div>

            <div class="mb-3">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-select" required>
                    <option value="cash">Cash</option>
                    <option value="mpesa">M-Pesa</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Process Sale</button>
        </form>
    </div>
</div>

<script>
const medications = <?= json_encode($medications->fetch_all(MYSQLI_ASSOC)) ?>;

document.addEventListener("DOMContentLoaded", function(){

    const tableBody = document.querySelector("#medicines-table tbody");
    const addRowBtn = document.getElementById("add-row");
    const grandTotalInput = document.getElementById("grand-total");

    function recalcGrandTotal(){
        let total = 0;
        tableBody.querySelectorAll("tr").forEach(row=>{
            const t = parseFloat(row.querySelector(".total").value) || 0;
            total += t;
        });
        grandTotalInput.value = total.toFixed(2);
    }

    function addRow(){
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>
                <select class="form-select med-select" required>
                    <option value="">-- Select --</option>
                    ${medications.map(m=>`<option value="${m.id}" data-price="${m.selling_price}" data-stock="${m.quantity}">${m.drug_name}</option>`).join("")}
                </select>
            </td>
            <td class="available">0</td>
            <td><input type="number" class="form-control unit-price" readonly></td>
            <td><input type="number" class="form-control qty" min="1" value="1"></td>
            <td><input type="text" class="form-control total" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        `;

        tableBody.appendChild(row);
        updateRowEvents(row);
    }

    function updateRowEvents(row){
        const medSelect = row.querySelector(".med-select");
        const availableCell = row.querySelector(".available");
        const unitPriceInput = row.querySelector(".unit-price");
        const qtyInput = row.querySelector(".qty");
        const totalInput = row.querySelector(".total");
        const removeBtn = row.querySelector(".remove-row");

        medSelect.addEventListener("change", function(){
            const selected = medications.find(m => m.id == this.value);
            if(selected){
                availableCell.textContent = selected.quantity;
                unitPriceInput.value = parseFloat(selected.selling_price).toFixed(2);
                totalInput.value = (selected.selling_price * qtyInput.value).toFixed(2);
            } else {
                availableCell.textContent = 0;
                unitPriceInput.value = "";
                totalInput.value = "";
            }
            recalcGrandTotal();
        });

        qtyInput.addEventListener("input", function(){
            const price = parseFloat(unitPriceInput.value) || 0;
            const qty = parseInt(this.value) || 0;
            const stock = parseInt(availableCell.textContent) || 0;

            if(qty > stock) this.value = stock;

            totalInput.value = (price * this.value).toFixed(2);
            recalcGrandTotal();
        });

        removeBtn.addEventListener("click", function(){
            row.remove();
            recalcGrandTotal();
        });
    }

    addRowBtn.addEventListener("click", addRow);

    // Initialize with 1 row
    addRow();

    // Handle form submit
    const form = document.getElementById("walkin-sale-form");
    form.addEventListener("submit", function(e){
        e.preventDefault();

        const customerName = form.customer_name.value;
        const paymentMode = form.payment_mode.value;
        const items = [];

        tableBody.querySelectorAll("tr").forEach(row=>{
            const medId = row.querySelector(".med-select").value;
            const qty = row.querySelector(".qty").value;
            if(medId && qty > 0){
                items.push({med_id: medId, quantity: qty});
            }
        });

        if(items.length === 0){
            alert("Add at least one medicine.");
            return;
        }

        fetch("dispense_ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                customer_name: customerName,
                payment_mode: paymentMode,
                items: items
            })
        })
        .then(res => res.json())
        .then(data=>{
            if(data.status === "success"){
                alert("Sale processed! Invoice: " + data.invoice_no);
                location.reload();
            } else {
                alert(data.message || "Error processing sale.");
            }
        })
        .catch(err=>{
            console.error(err);
            alert("Network error.");
        });

    });

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
