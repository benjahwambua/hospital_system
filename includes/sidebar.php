<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/permissions.php';

/** * Check for Super User status
 */
$isSuperUser = (isset($_SESSION['is_super']) && $_SESSION['is_super'] === 1);
$fullName = $_SESSION['full_name'] ?? 'Guest User';
$userRole = $_SESSION['role'] ?? 'Staff';

// Highlights the exact link
function isActive($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
}

// Keeps the parent dropdown open if a child link is active
function isParentActive($paths) {
    foreach ($paths as $path) {
        if (strpos($_SERVER['REQUEST_URI'], $path) !== false) return 'open';
    }
    return '';
}
?>

<aside class="sidebar" role="navigation" aria-label="Main navigation">
    <div class="brand"><i class="fas fa-hospital-alt"></i> HMS</div>

    <div class="user-profile">
        <div class="user-avatar"><?= substr($fullName, 0, 1); ?></div>
        <div class="user-info">
            <span class="name"><?= htmlspecialchars($fullName); ?></span>
            <span class="role"><?= htmlspecialchars($userRole); ?></span>
        </div>
    </div>

    <nav>
        <a href="/hospital_system/dashboard.php" class="<?= isActive('dashboard.php') ?>">
            <i class="fas fa-th-large icon-main"></i> Dashboard
        </a>
        <?php if (can_access_module($conn, 'front_desk')): ?>
        <div class="menu-title">Front Desk</div>
        <a href="/hospital_system/reception/index.php" class="<?= isActive('reception/index.php') ?>">
            <i class="fas fa-concierge-bell icon-main"></i> Reception
        </a>
        <a href="/hospital_system/patients/reception_register.php" class="<?= isActive('reception_register.php') ?>">
            <i class="fas fa-user-plus icon-main"></i> Register Patient
        </a>
        <?php endif; ?>


        <?php if (can_access_module($conn, 'clinical')): ?>
        <div class="has-submenu <?= isParentActive(['clinical/index.php', 'patient_list.php', 'appointments.php', 'orders.php', 'ward_management.php', 'admit_patient.php', 'discharge_patient.php', 'diagnostics.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-stethoscope icon-main"></i> Clinical
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/clinical/index.php" class="<?= isActive('clinical/index.php') ?>"><i class="fas fa-th-large"></i> Clinical Dashboard</a>
                <a href="/hospital_system/patients/patient_list.php" class="<?= isActive('patient_list.php') ?>"><i class="fas fa-address-book"></i> Patient List</a>
                <a href="/hospital_system/patients/appointments.php" class="<?= isActive('appointments.php') ?>"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/hospital_system/clinical/orders.php" class="<?= isActive('clinical/orders.php') ?>"><i class="fas fa-flask"></i> Orders & Referrals</a>
                <a href="/hospital_system/clinical/ward_management.php" class="<?= isActive('ward_management.php') ?>"><i class="fas fa-bed"></i> Ward / IPD</a>
                <a href="/hospital_system/clinical/admit_patient.php" class="<?= isActive('admit_patient.php') ?>"><i class="fas fa-procedures"></i> Admissions</a>
                <a href="/hospital_system/clinical/discharge_patient.php" class="<?= isActive('discharge_patient.php') ?>"><i class="fas fa-sign-out-alt"></i> Discharge</a>
                <a href="/hospital_system/diagnostics/diagnostics.php" class="<?= isActive('diagnostics.php') ?>"><i class="fas fa-diagnoses"></i> Diagnostics</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'laboratory')): ?>
        <div class="has-submenu <?= isParentActive(['lab_requests.php', 'lab_results.php', 'lab/inventory/']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-microscope icon-main"></i> Laboratory
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/lab/lab_requests.php" class="<?= isActive('lab_requests.php') ?>"><i class="fas fa-vial"></i> Lab Requests</a>
                <a href="/hospital_system/lab/lab_results.php" class="<?= isActive('lab_results.php') ?>"><i class="fas fa-poll-h"></i> Lab Results</a>
                <a href="/hospital_system/lab/inventory/index.php" class="<?= isActive('lab/inventory/') ?>"><i class="fas fa-boxes"></i> Lab Inventory</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'radiology')): ?>
        <div class="has-submenu <?= isParentActive(['radiology_requests.php', 'radiology_results.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-x-ray icon-main"></i> Radiology
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/radiology/radiology_requests.php" class="<?= isActive('radiology_requests.php') ?>"><i class="fas fa-x-ray"></i> Radiology Requests</a>
                <a href="/hospital_system/radiology/radiology_results.php" class="<?= isActive('radiology_results.php') ?>"><i class="fas fa-images"></i> Radiology Results</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'pharmacy')): ?>
        <div class="has-submenu <?= isParentActive(['dispensing_queue.php', 'sell_medicine.php', 'add_stock.php', 'view_stock.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-pills icon-main"></i> Pharmacy
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/pharmacy/dispensing_queue.php" class="<?= isActive('dispensing_queue.php') ?>"><i class="fas fa-clipboard-check"></i> Dispensing Queue</a>
                <a href="/hospital_system/pharmacy/sell_medicine.php" class="<?= isActive('sell_medicine.php') ?>"><i class="fas fa-file-prescription"></i> Sell Medicine</a>
                <a href="/hospital_system/pharmacy/add_stock.php" class="<?= isActive('add_stock.php') ?>"><i class="fas fa-box-open"></i> Add Stock</a>
                <a href="/hospital_system/pharmacy/view_stock.php" class="<?= isActive('view_stock.php') ?>"><i class="fas fa-capsules"></i> View Stock</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'maternity')): ?>
        <div class="has-submenu <?= isParentActive(['maternity/index.php','maternity/add.php','maternity/antenatal.php','maternity/postnatal.php','maternity/deliveries.php','maternity/admissions.php','maternity/stats.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-baby icon-main"></i> Maternity
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/maternity/index.php" class="<?= isActive('maternity/index.php') ?>"><i class="fas fa-th-large"></i> Maternity Dashboard</a>
                <a href="/hospital_system/maternity/add.php" class="<?= isActive('maternity/add.php') ?>"><i class="fas fa-notes-medical"></i> ANC / Labour / PNC</a>
                <a href="/hospital_system/maternity/antenatal.php" class="<?= isActive('maternity/antenatal.php') ?>"><i class="fas fa-heartbeat"></i> Antenatal (ANC)</a>
                <a href="/hospital_system/maternity/postnatal.php" class="<?= isActive('maternity/postnatal.php') ?>"><i class="fas fa-female"></i> Postnatal (PNC)</a>
                <a href="/hospital_system/maternity/deliveries.php" class="<?= isActive('maternity/deliveries.php') ?>"><i class="fas fa-baby"></i> Deliveries</a>
                <a href="/hospital_system/maternity/admissions.php" class="<?= isActive('maternity/admissions.php') ?>"><i class="fas fa-procedures"></i> Admissions</a>
                <a href="/hospital_system/maternity/stats.php" class="<?= isActive('maternity/stats.php') ?>"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'finance_admin')): ?>
        <div class="menu-title">Finance & Billing Administration</div>
        <div class="has-submenu <?= isParentActive(['accounting/', 'billing/', 'expenses/']) ?>">
            <a href="#" class="menu-toggle"><i class="fas fa-coins icon-main"></i> Finance & Billing <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/accounting/dashboard.php" class="<?= isActive('accounting/dashboard.php') ?>"><i class="fas fa-chart-bar"></i> Finance Dashboard</a>
                <a href="/hospital_system/billing/view_bills.php" class="<?= isActive('view_bills.php') ?>"><i class="fas fa-file-invoice-dollar"></i> Billing & Invoices</a>
                <a href="/hospital_system/billing/mpesa.php" class="<?= isActive('mpesa.php') ?>"><i class="fas fa-mobile-alt"></i> M-Pesa Payments</a>
                <a href="/hospital_system/accounting/ledger.php" class="<?= isActive('accounting/ledger.php') ?>"><i class="fas fa-calculator"></i> Ledger</a>
                <a href="/hospital_system/accounting/reconciliation.php" class="<?= isActive('reconciliation.php') ?>"><i class="fas fa-balance-scale"></i> Financial Reconciliation</a>
                <a href="/hospital_system/expenses/add_expense.php" class="<?= isActive('add_expense.php') ?>"><i class="fas fa-money-bill-wave"></i> Record Expense</a>
                <a href="/hospital_system/expenses/view_expenses.php" class="<?= isActive('view_expenses.php') ?>"><i class="fas fa-file-contract"></i> Expense History</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'procurement')): ?>
        <div class="has-submenu <?= isParentActive(['procurement/']) ?>">
            <a href="#" class="menu-toggle"><i class="fas fa-boxes icon-main"></i> Procurement <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/procurement/manage_suppliers.php" class="<?= isActive('manage_suppliers.php') ?>"><i class="fas fa-truck"></i> Suppliers</a>
                <a href="/hospital_system/procurement/purchase_orders.php" class="<?= isActive('purchase_orders.php') ?>"><i class="fas fa-shopping-basket"></i> Purchase Orders</a>
                <a href="/hospital_system/procurement/receive_inventory.php" class="<?= isActive('receive_inventory.php') ?>"><i class="fas fa-warehouse"></i> Receive Inventory</a>
                <a href="/hospital_system/procurement/supplier_payables.php" class="<?= isActive('supplier_payables.php') ?>"><i class="fas fa-file-invoice-dollar"></i> Supplier Payables</a>
                <a href="/hospital_system/procurement/supplier_statement.php" class="<?= isActive('supplier_statement.php') ?>"><i class="fas fa-file-alt"></i> Supplier Statement</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'finance')): ?>
        <div class="menu-title">Finance</div>
        <a href="/hospital_system/cashier/index.php" class="<?= isActive('cashier/index.php') ?>"><i class="fas fa-cash-register icon-main"></i> Central Cashier</a>
        <a href="/hospital_system/cashier/shifts.php" class="<?= isActive('cashier/shifts.php') ?>"><i class="fas fa-clock icon-main"></i> Cashier Shift</a>
        <a href="/hospital_system/cashier/aged_receivables.php" class="<?= isActive('cashier/aged_receivables.php') ?>"><i class="fas fa-user-clock icon-main"></i> Aged Receivables</a>
        <a href="/hospital_system/cashier/payment_history.php" class="<?= isActive('cashier/payment_history.php') ?>"><i class="fas fa-receipt icon-main"></i> Payment History</a>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'administration')): ?>
        <div class="menu-title">Administration</div>
        <div class="has-submenu <?= isParentActive(['users/', 'settings/']) ?>">
            <a href="#" class="menu-toggle"><i class="fas fa-cogs icon-main"></i> Administration <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/users/view_users.php" class="<?= isActive('view_users.php') ?>"><i class="fas fa-users-cog"></i> Manage Users</a>
                <a href="/hospital_system/users/access_rights.php" class="<?= isActive('access_rights.php') ?>"><i class="fas fa-user-shield"></i> Access Rights</a>
                <a href="/hospital_system/settings/system_settings.php" class="<?= isActive('system_settings.php') ?>"><i class="fas fa-sliders-h"></i> General Settings</a>
                <a href="/hospital_system/reports/sales_report.php" class="<?= isActive('sales_report.php') ?>"><i class="fas fa-chart-line"></i> System Reports</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="menu-title">Exit</div>
        <a href="/hospital_system/logout.php" class="logout-link">
            <i class="fas fa-power-off icon-main"></i> Logout
        </a>

    </nav>
</aside>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggles = document.querySelectorAll(".menu-toggle");

        toggles.forEach(toggle => {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                const parent = this.parentElement;
                
                document.querySelectorAll(".has-submenu").forEach(item => {
                    if (item !== parent) {
                        item.classList.remove("open");
                    }
                });

                parent.classList.toggle("open");
            });
        });
    });
</script>

<main class="content">