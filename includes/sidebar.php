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
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $uri = rtrim($uri, '/');
    $target = '/' . trim($path, '/');
    $base = '/hospital_system' . $target;
    // The root dashboard must not match department pages that also end in dashboard.php.
    if ($target === '/dashboard.php') return ($uri === $base) ? 'active' : '';
    return ($uri === $base || str_ends_with($uri, $target)) ? 'active' : '';
}

// Keeps the parent dropdown open if a child link is active
function isParentActive($paths) {
    foreach ($paths as $path) {
        if (strpos($_SERVER['REQUEST_URI'], $path) !== false) return 'open';
    }
    return '';
}
?>

<style>
:root{--sidebar-bg:#063b73;--sidebar-bg-2:#052f5c;--sidebar-hover:rgba(255,255,255,.09);--sidebar-active:#fff;--sidebar-text:#dbeafe;--sidebar-muted:#8fb4dc;--sidebar-accent:#36c5f0}
.sidebar{width:260px;background:linear-gradient(180deg,var(--sidebar-bg),var(--sidebar-bg-2));color:var(--sidebar-text);height:calc(100vh - var(--header-height,75px));position:fixed;left:0;top:var(--header-height,75px);overflow-y:auto;box-shadow:5px 0 24px rgba(15,42,74,.16);z-index:1000;padding-bottom:24px}
.sidebar::-webkit-scrollbar{width:5px}.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.18);border-radius:10px}
.brand{padding:22px 18px;text-align:center;font-weight:800;font-size:1.08rem;letter-spacing:1.2px;color:#fff;background:rgba(0,0,0,.13);border-bottom:1px solid rgba(255,255,255,.08)}
.brand i{color:var(--sidebar-accent);margin-right:7px}
.user-profile{margin:14px 12px;padding:13px;border:1px solid rgba(255,255,255,.08);border-radius:12px;background:rgba(255,255,255,.055);display:flex;align-items:center}
.user-avatar{width:38px;height:38px;flex:0 0 38px;border-radius:11px;background:var(--sidebar-accent);color:#063b73;display:flex;align-items:center;justify-content:center;font-weight:800;margin-right:11px}
.user-info{min-width:0}.user-info .name{display:block;color:#fff;font-size:.84rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.user-info .role{display:block;color:var(--sidebar-muted);font-size:.72rem;margin-top:2px}
.sidebar nav{padding:2px 9px 25px}.sidebar nav>a,.has-submenu>a{position:relative;display:flex;align-items:center;gap:11px;margin:3px 3px;padding:10px 12px;border-radius:9px;color:var(--sidebar-text);text-decoration:none;font-size:13px;font-weight:600;transition:background .2s ease,transform .2s ease,color .2s ease,box-shadow .2s ease}
.sidebar nav a .icon-main{width:20px;text-align:center;font-size:15px;color:#b9d8f3}.sidebar nav a:hover{background:var(--sidebar-hover);color:#fff;transform:translateX(2px)}.sidebar nav a:hover .icon-main{color:var(--sidebar-accent)}
.sidebar nav a.active{background:var(--sidebar-active);color:#063b73;box-shadow:0 5px 14px rgba(0,0,0,.13)}.sidebar nav a.active .icon-main{color:#063b73}
.menu-title{display:flex;align-items:center;gap:8px;padding:17px 8px 6px;color:var(--sidebar-muted);font-size:9px;text-transform:uppercase;font-weight:800;letter-spacing:1.6px}.menu-title:after{content:"";height:1px;flex:1;background:rgba(255,255,255,.1)}
.has-submenu>a{margin-top:4px}.has-submenu.open>a{background:rgba(255,255,255,.075);color:#fff;border-left:3px solid var(--sidebar-accent);padding-left:9px}.caret{margin-left:auto;font-size:10px;color:var(--sidebar-muted);transition:transform .2s}.has-submenu.open>a .caret{transform:rotate(180deg);color:var(--sidebar-accent)}
.submenu{max-height:0;overflow:hidden;transition:max-height .25s ease;background:rgba(0,0,0,.09);margin:0 3px;border-radius:0 0 10px 10px}.has-submenu.open .submenu{max-height:1400px;padding:4px 3px 6px;margin-bottom:5px}
.submenu a{display:flex;align-items:center;gap:9px;margin:1px 0;padding:8px 10px 8px 34px;border-radius:7px;color:#c9def2;text-decoration:none;font-size:12px;font-weight:500;transition:.2s}.submenu a i{width:16px;text-align:center;font-size:12px;color:#91b9dc}.submenu a:hover{background:rgba(255,255,255,.07);color:#fff;transform:translateX(2px)}.submenu a.active{background:rgba(255,255,255,.96);color:#063b73;font-weight:700}.submenu a.active i{color:#063b73}
.logout-link{margin-top:8px!important;background:rgba(255,92,92,.08)!important;color:#ffb0b0!important;border:1px solid rgba(255,120,120,.18)}.logout-link:hover{background:#d9534f!important;color:#fff!important}
@media(max-width:768px){.sidebar{width:230px;height:calc(100vh - var(--header-height,75px));top:var(--header-height,75px)}.submenu a{padding-left:30px}}
</style>

<aside class="sidebar" role="navigation" aria-label="Main navigation">
    <div class="brand">
        <i class="fas fa-hospital-alt"></i> Emaqure Medical Centre
    </div>

    <div class="user-profile">
        <div class="user-avatar"><?= substr($fullName, 0, 1); ?></div>
        <div class="user-info">
            <span class="name"><?= htmlspecialchars($fullName); ?></span>
            <span class="role"><?= htmlspecialchars($userRole); ?></span>
        </div>
    </div>

    <nav>
        <a href="/hospital_system/dashboard.php" class="<?= isActive('dashboard.php') ?>">
            <i class="fas fa-th-large icon-main"></i> Hospital Command Centre
        </a>
        <?php if (can_access_module($conn, 'front_desk')): ?>
        <div class="menu-title">Front Desk</div>
        <a href="/hospital_system/reception/index.php" class="<?= isActive('reception/index.php') ?>">
            <i class="fas fa-th-large icon-main"></i> Reception Dashboard
        </a>
        <a href="/hospital_system/patients/reception_register.php" class="<?= isActive('reception_register.php') ?>">
            <i class="fas fa-user-plus icon-main"></i> Register Patient
        </a>
        <?php endif; ?>


        <?php if (can_access_module($conn, 'clinical')): ?>
        <div class="has-submenu <?= isParentActive(['clinical/index.php', 'patient_list.php', 'appointments.php', 'orders.php', 'ward_management.php', 'clinical/admit_patient.php', 'clinical/inpatient_charges.php', 'clinical/ward_configuration.php', 'vitals/vitals_add.php', 'prescriptions/view_prescriptions.php']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false">
                <i class="fas fa-stethoscope icon-main"></i> Clinical
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/clinical/index.php" class="<?= isActive('clinical/index.php') ?>"><i class="fas fa-th-large"></i> Clinical Dashboard</a>
                <a href="/hospital_system/patients/patient_list.php" class="<?= isActive('patient_list.php') ?>"><i class="fas fa-address-book"></i> Patient List</a>
                <a href="/hospital_system/patients/appointments.php" class="<?= isActive('patients/appointments.php') ?>"><i class="fas fa-calendar-check"></i> Patient Appointments</a>
                <a href="/hospital_system/appointments/appointments.php" class="<?= isActive('appointments/appointments.php') ?>"><i class="fas fa-calendar-alt"></i> Appointment Management</a>
                <a href="/hospital_system/clinical/orders.php" class="<?= isActive('clinical/orders.php') ?>"><i class="fas fa-flask"></i> Orders & Referrals</a>
                <a href="/hospital_system/clinical/ward_management.php" class="<?= isActive('ward_management.php') ?>"><i class="fas fa-bed"></i> Ward / IPD</a>
                <a href="/hospital_system/clinical/admit_patient.php" class="<?= isActive('clinical/admit_patient.php') ?>"><i class="fas fa-procedures"></i> Admit Patient</a>
                <a href="/hospital_system/clinical/inpatient_charges.php" class="<?= isActive('clinical/inpatient_charges.php') ?>"><i class="fas fa-file-invoice-dollar"></i> Inpatient Charges</a>
                <a href="/hospital_system/clinical/ward_configuration.php" class="<?= isActive('clinical/ward_configuration.php') ?>"><i class="fas fa-cog"></i> Ward &amp; Bed Configuration</a>
                <a href="/hospital_system/vitals/vitals_add.php" class="<?= isActive('vitals/vitals_add.php') ?>"><i class="fas fa-heartbeat"></i> Record Vital Signs</a>
                <a href="/hospital_system/prescriptions/view_prescriptions.php" class="<?= isActive('prescriptions/view_prescriptions.php') ?>"><i class="fas fa-prescription"></i> Prescriptions</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'laboratory')): ?>
        <div class="has-submenu <?= isParentActive(['lab/dashboard.php','lab_requests.php', 'lab_results.php', 'lab/lab_receipt.php', 'lab/inventory/']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false">
                <i class="fas fa-microscope icon-main"></i> Laboratory
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/lab/dashboard.php" class="<?= isActive('lab/dashboard.php') ?>"><i class="fas fa-th-large"></i> Laboratory Dashboard</a>
                <a href="/hospital_system/lab/lab_requests.php" class="<?= isActive('lab_requests.php') ?>"><i class="fas fa-vial"></i> Lab Requests</a>
                <a href="/hospital_system/lab/lab_results.php" class="<?= isActive('lab_results.php') ?>"><i class="fas fa-poll-h"></i> Lab Results</a>
                <a href="/hospital_system/lab/lab_receipt.php" class="<?= isActive('lab/lab_receipt.php') ?>"><i class="fas fa-receipt"></i> Lab Receipt</a>
                <a href="/hospital_system/lab/inventory/index.php" class="<?= isActive('lab/inventory/') ?>"><i class="fas fa-boxes"></i> Lab Inventory</a>
                <?php if (can_module_action($conn, 'laboratory', 'edit')): ?><a href="/hospital_system/lab/inventory/test_materials.php" class="<?= isActive('lab/inventory/test_materials.php') ?>"><i class="fas fa-flask"></i> Test Materials</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'radiology')): ?>
        <div class="has-submenu <?= isParentActive(['radiology/dashboard.php','radiology_requests.php', 'radiology_results.php']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false">
                <i class="fas fa-x-ray icon-main"></i> Radiology
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/radiology/dashboard.php" class="<?= isActive('radiology/dashboard.php') ?>"><i class="fas fa-th-large"></i> Radiology Dashboard</a>
                <a href="/hospital_system/radiology/radiology_requests.php" class="<?= isActive('radiology_requests.php') ?>"><i class="fas fa-x-ray"></i> Radiology Requests</a>
                <a href="/hospital_system/radiology/radiology_results.php" class="<?= isActive('radiology_results.php') ?>"><i class="fas fa-images"></i> Radiology Results</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'pharmacy')): ?>
        <div class="has-submenu <?= isParentActive(['pharmacy/dashboard.php','dispensing_queue.php', 'sell_medicine.php', 'add_stock.php', 'view_stock.php', 'manage_stock.php', 'pharmacy/stock_movements.php', 'stock_take.php', 'pharmacy_sales_report.php', 'walkin_sale.php']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false">
                <i class="fas fa-pills icon-main"></i> Pharmacy
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/pharmacy/dashboard.php" class="<?= isActive('pharmacy/dashboard.php') ?>"><i class="fas fa-th-large"></i> Pharmacy Dashboard</a>
                <a href="/hospital_system/pharmacy/dispensing_queue.php" class="<?= isActive('dispensing_queue.php') ?>"><i class="fas fa-clipboard-check"></i> Dispensing Queue</a>
                <a href="/hospital_system/pharmacy/sell_medicine.php" class="<?= isActive('sell_medicine.php') ?>"><i class="fas fa-file-prescription"></i> Sell Medicine</a>
                <a href="/hospital_system/pharmacy/view_stock.php" class="<?= isActive('view_stock.php') ?>"><i class="fas fa-capsules"></i> View Stock</a>
                <a href="/hospital_system/pharmacy/manage_stock.php" class="<?= isActive('manage_stock.php') ?>"><i class="fas fa-boxes"></i> Manage Stock</a>
                <a href="/hospital_system/pharmacy/add_stock.php" class="<?= isActive('add_stock.php') ?>"><i class="fas fa-plus-square"></i> Receive / Add Stock</a>
                <a href="/hospital_system/pharmacy/stock_movements.php" class="<?= isActive('pharmacy/stock_movements.php') ?>"><i class="fas fa-exchange-alt"></i> Stock Movement History</a>
                <a href="/hospital_system/pharmacy/stock_take.php" class="<?= isActive('stock_take.php') ?>"><i class="fas fa-clipboard-list"></i> Stock Take</a>
                <a href="/hospital_system/pharmacy/pharmacy_sales_report.php" class="<?= isActive('pharmacy_sales_report.php') ?>"><i class="fas fa-chart-line"></i> Pharmacy Sales Report</a>
                <a href="/hospital_system/pharmacy/walkin_sale.php" class="<?= isActive('walkin_sale.php') ?>"><i class="fas fa-cash-register"></i> Walk-in Sale</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'maternity')): ?>
        <div class="has-submenu <?= isParentActive(['maternity/index.php','maternity/add.php','maternity/antenatal.php','maternity/postnatal.php','maternity/deliveries.php','maternity/delivery_records.php','maternity/visit_history.php','maternity/admissions.php','maternity/stats.php']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false">
                <i class="fas fa-baby icon-main"></i> Maternity
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/maternity/index.php" class="<?= isActive('maternity/index.php') ?>"><i class="fas fa-th-large"></i> Maternity Dashboard</a>
                <a href="/hospital_system/maternity/add.php" class="<?= isActive('maternity/add.php') ?>"><i class="fas fa-notes-medical"></i> ANC / Labour / PNC</a>
                <a href="/hospital_system/maternity/antenatal.php" class="<?= isActive('maternity/antenatal.php') ?>"><i class="fas fa-heartbeat"></i> Antenatal (ANC)</a>
                <a href="/hospital_system/maternity/postnatal.php" class="<?= isActive('maternity/postnatal.php') ?>"><i class="fas fa-female"></i> Postnatal (PNC)</a>
                <a href="/hospital_system/maternity/deliveries.php" class="<?= isActive('maternity/deliveries.php') ?>"><i class="fas fa-baby"></i> Deliveries</a>
                <a href="/hospital_system/maternity/delivery_records.php" class="<?= isActive('maternity/delivery_records.php') ?>"><i class="fas fa-clipboard-list"></i> Delivery Records</a>
                <a href="/hospital_system/maternity/visit_history.php" class="<?= isActive('maternity/visit_history.php') ?>"><i class="fas fa-history"></i> Maternity Visit History</a>
                <a href="/hospital_system/maternity/admissions.php" class="<?= isActive('maternity/admissions.php') ?>"><i class="fas fa-procedures"></i> Admissions</a>
                <a href="/hospital_system/maternity/stats.php" class="<?= isActive('maternity/stats.php') ?>"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'nursing')): ?>
        <div class="menu-title">Inpatient Care</div>
        <div class="has-submenu <?= isParentActive(['nursing/']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false"><i class="fas fa-user-nurse icon-main"></i> Nursing <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/nursing/index.php" class="<?= isActive('nursing/index.php') ?>"><i class="fas fa-th-large"></i> Nursing Station</a>
                <a href="/hospital_system/nursing/medication_administration.php" class="<?= isActive('nursing/medication_administration.php') ?>"><i class="fas fa-pills"></i> Medication Administration (MAR)</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'finance_admin')): ?>
        <div class="menu-title">Finance & Billing Administration</div>
        <div class="has-submenu <?= isParentActive(['finance/', 'accounting/', 'billing/', 'expenses/', 'reports/sales_report.php']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false"><i class="fas fa-coins icon-main"></i> Finance & Billing <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/finance/dashboard.php" class="<?= isActive('finance/dashboard.php') ?>"><i class="fas fa-th-large"></i> Finance Dashboard</a>
                                <a href="/hospital_system/billing/view_bills.php" class="<?= isActive('view_bills.php') ?>"><i class="fas fa-file-invoice-dollar"></i> Billing & Invoices</a>
                <a href="/hospital_system/invoices/create_invoice.php" class="<?= isActive('invoices/create_invoice.php') ?>"><i class="fas fa-file-alt"></i> Create Invoice</a>
                <a href="/hospital_system/billing/mpesa.php" class="<?= isActive('mpesa.php') ?>"><i class="fas fa-mobile-alt"></i> M-Pesa Payments</a>
                <a href="/hospital_system/accounting/dashboard.php" class="<?= isActive('accounting/dashboard.php') ?>"><i class="fas fa-chart-pie"></i> Accounting Dashboard</a>
                <a href="/hospital_system/accounting/ledger.php" class="<?= isActive('accounting/ledger.php') ?>"><i class="fas fa-calculator"></i> Ledger</a>
                <a href="/hospital_system/accounting/reconciliation.php" class="<?= isActive('reconciliation.php') ?>"><i class="fas fa-balance-scale"></i> Financial Reconciliation</a>
                <a href="/hospital_system/expenses/add_expense.php" class="<?= isActive('add_expense.php') ?>"><i class="fas fa-money-bill-wave"></i> Record Expense</a>
                <a href="/hospital_system/expenses/view_expenses.php" class="<?= isActive('view_expenses.php') ?>"><i class="fas fa-file-contract"></i> Expense History</a>
                <a href="/hospital_system/reports/sales_report.php" class="<?= isActive('reports/sales_report.php') ?>"><i class="fas fa-chart-line"></i><span>Sales Report</span></a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'insurance')): ?>
        <div class="has-submenu <?= isParentActive(['insurance/']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false"><i class="fas fa-id-card icon-main"></i> Insurance &amp; SHA <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/insurance/index.php" class="<?= isActive('insurance/index.php') ?>"><i class="fas fa-th-large"></i> Insurance Dashboard</a>
                <a href="/hospital_system/insurance/coverage.php" class="<?= isActive('insurance/coverage.php') ?>"><i class="fas fa-id-card"></i> Patient Coverage</a>
                <a href="/hospital_system/insurance/preauthorizations.php" class="<?= isActive('insurance/preauthorizations.php') ?>"><i class="fas fa-file-signature"></i> Preauthorizations</a>
                <a href="/hospital_system/insurance/claims.php" class="<?= isActive('insurance/claims.php') ?>"><i class="fas fa-file-invoice-dollar"></i> Claims</a>
                <a href="/hospital_system/insurance/denials_appeals.php" class="<?= isActive('insurance/denials_appeals.php') ?>"><i class="fas fa-file-alt"></i> Denials &amp; Appeals</a>
                <a href="/hospital_system/insurance/remittances.php" class="<?= isActive('insurance/remittances.php') ?>"><i class="fas fa-money-check-alt"></i> Remittances & Reconciliation</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'procurement')): ?>
        <div class="has-submenu <?= isParentActive(['procurement/dashboard.php','procurement/']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false"><i class="fas fa-boxes icon-main"></i> Procurement <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/procurement/dashboard.php" class="<?= isActive('procurement/dashboard.php') ?>"><i class="fas fa-th-large"></i> Procurement Dashboard</a>
                <a href="/hospital_system/procurement/manage_suppliers.php" class="<?= isActive('manage_suppliers.php') ?>"><i class="fas fa-truck"></i> Suppliers</a>
                <a href="/hospital_system/procurement/purchase_orders.php" class="<?= isActive('purchase_orders.php') ?>"><i class="fas fa-shopping-basket"></i> Purchase Orders</a>
                <a href="/hospital_system/procurement/receive_inventory.php" class="<?= isActive('receive_inventory.php') ?>"><i class="fas fa-warehouse"></i> Receive Inventory</a>
                <a href="/hospital_system/procurement/supplier_payables.php" class="<?= isActive('supplier_payables.php') ?>"><i class="fas fa-file-invoice-dollar"></i> Supplier Payables</a>
                <a href="/hospital_system/procurement/supplier_statement.php" class="<?= isActive('supplier_statement.php') ?>"><i class="fas fa-file-alt"></i> Supplier Statement</a>
                <a href="/hospital_system/procurement/add_expenses.php" class="<?= isActive('procurement/add_expenses.php') ?>"><i class="fas fa-receipt"></i> Procurement Expenses</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'central_stores')): ?>
        <div class="menu-title">Inventory & Stores</div>
        <a href="/hospital_system/stores/index.php" class="<?= isActive('stores/index.php') ?>"><i class="fas fa-warehouse icon-main"></i> Central Stores</a>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'finance')): ?>
        <div class="menu-title">Finance</div>
        <a href="/hospital_system/cashier/index.php" class="<?= isActive('cashier/index.php') ?>"><i class="fas fa-cash-register icon-main"></i> Central Cashier</a>
        <a href="/hospital_system/cashier/shifts.php" class="<?= isActive('cashier/shifts.php') ?>"><i class="fas fa-clock icon-main"></i> Cashier Shift</a>
        <a href="/hospital_system/cashier/aged_receivables.php" class="<?= isActive('cashier/aged_receivables.php') ?>"><i class="fas fa-user-clock icon-main"></i> Aged Receivables</a>
        <a href="/hospital_system/cashier/payment_history.php" class="<?= isActive('cashier/payment_history.php') ?>"><i class="fas fa-receipt icon-main"></i> Payment History</a>
        <a href="/hospital_system/cashier/refund.php" class="<?= isActive('cashier/refund.php') ?>"><i class="fas fa-undo icon-main"></i> Refunds</a>
        <?php endif; ?>
        <?php if (can_access_module($conn, 'administration')): ?>
        <div class="menu-title">Administration</div>
        <div class="has-submenu <?= isParentActive(['administration/dashboard.php','users/', 'settings/', 'services/', 'reports/reports.php', 'reports/daily.php', 'reports/patient_medical_report.php', 'reports/medical_examination_certificate.php']) ?>">
            <a href="#" class="menu-toggle" aria-expanded="false"><i class="fas fa-cogs icon-main"></i> Administration <i class="fas fa-chevron-down caret"></i></a>
            <div class="submenu">
                <a href="/hospital_system/administration/dashboard.php" class="<?= isActive('administration/dashboard.php') ?>"><i class="fas fa-th-large"></i> Administration Dashboard</a>
                <a href="/hospital_system/users/view_users.php" class="<?= isActive('view_users.php') ?>"><i class="fas fa-users-cog"></i> Manage Users</a>
                <a href="/hospital_system/users/add_user.php" class="<?= isActive('users/add_user.php') ?>"><i class="fas fa-user-plus"></i> Add User</a>
                <a href="/hospital_system/users/access_rights.php" class="<?= isActive('access_rights.php') ?>"><i class="fas fa-user-shield"></i> Access Rights</a>
                <a href="/hospital_system/services/view_services.php" class="<?= isActive('services/view_services.php') ?>"><i class="fas fa-list-alt"></i> Service Catalogue</a>
                <a href="/hospital_system/services/add_service.php" class="<?= isActive('services/add_service.php') ?>"><i class="fas fa-plus-circle"></i> Add / Configure Service</a>
                <a href="/hospital_system/services/price_history.php" class="<?= isActive('services/price_history.php') ?>"><i class="fas fa-history"></i> Price History</a>
                <a href="/hospital_system/settings/system_settings.php" class="<?= isActive('system_settings.php') ?>"><i class="fas fa-sliders-h"></i> General Settings</a>
                <a href="/hospital_system/reports/reports.php" class="<?= isActive('reports/reports.php') ?>"><i class="fas fa-file-alt"></i> System Reports</a>
                <a href="/hospital_system/reports/daily.php" class="<?= isActive('reports/daily.php') ?>"><i class="fas fa-calendar-day"></i> Daily Operations Report</a>
                <a href="/hospital_system/reports/patient_medical_report.php" class="<?= isActive('reports/patient_medical_report.php') ?>"><i class="fas fa-file-medical"></i> Patient Medical Report</a>
                <a href="/hospital_system/reports/medical_examination_certificate.php" class="<?= isActive('reports/medical_examination_certificate.php') ?>"><i class="fas fa-file-signature"></i> Medical Examination Certificate</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can_access_module($conn, 'staff_leave')): ?>
        <div class="menu-title">Staff</div>
        <a href="/hospital_system/leave/index.php" class="<?= isActive('leave/index.php') ?>">
            <i class="fas fa-calendar-alt icon-main"></i> Staff Leave
        </a>
        <?php endif; ?>

        <div class="menu-title">Exit</div>
        <a href="/hospital_system/auth/logout.php" class="logout-link">
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
                        const trigger = item.querySelector(".menu-toggle");
                        if (trigger) trigger.setAttribute("aria-expanded", "false");
                    }
                });

                const isOpen = parent.classList.toggle("open");
                this.setAttribute("aria-expanded", isOpen ? "true" : "false");
            });
        });
    });
</script>

<main class="content">