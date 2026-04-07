<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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

<style>
    :root {
        --primary-bright: #007bff;
        --sidebar-bg: #004a99;
        --hover-bg: #005bc1;
        --active-bg: #ffffff;
        --text-light: #e0f2ff;
        --accent-glow: #00d4ff;
        --icon-glow: rgba(0, 212, 255, 0.4);
    }

    .sidebar {
        width: 260px;
        background: var(--sidebar-bg);
        color: var(--text-light);
        transition: all 0.3s;
        height: 100vh;
        position: fixed;
        overflow-y: auto;
        box-shadow: 4px 0 15px rgba(0,0,0,0.15);
        z-index: 1000;
    }

    /* Scrollbar styling for sidebar */
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }

    .brand {
        background: rgba(0, 0, 0, 0.2);
        color: #ffffff;
        padding: 25px 20px;
        text-align: center;
        font-weight: 800;
        font-size: 1.2em;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        min-height: 70px;
    }

    .user-profile {
        padding: 20px;
        background: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        background: var(--accent-glow);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: var(--sidebar-bg);
        margin-right: 12px;
    }
    .user-info .name { font-size: 0.9em; font-weight: 700; display: block; color: #fff; }
    .user-info .role { font-size: 0.75em; opacity: 0.8; }

    .sidebar nav { padding-bottom: 30px; }

    .sidebar nav a {
        padding: 12px 18px;
        display: flex;
        align-items: center;
        color: var(--text-light);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin: 4px 12px;
        border-radius: 8px;
    }

    .sidebar nav a i.icon-main {
        margin-right: 14px;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: all 0.3s ease;
        text-shadow: 0 0 8px var(--icon-glow);
    }

    .sidebar nav a:hover {
        background: var(--hover-bg);
        color: #ffffff;
        transform: translateX(4px);
    }

    .sidebar nav a:hover i.icon-main {
        transform: scale(1.2);
        color: var(--accent-glow);
    }

    .sidebar nav a.active {
        background: var(--active-bg);
        color: var(--sidebar-bg);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        font-weight: 700;
    }

    .sidebar nav a.active i.icon-main {
        color: var(--sidebar-bg);
        text-shadow: none;
    }

    .menu-title {
        padding: 24px 25px 8px;
        font-size: 10px;
        text-transform: uppercase;
        color: var(--accent-glow);
        font-weight: 800;
        letter-spacing: 2px;
        display: flex;
        align-items: center;
    }
    .menu-title::after {
        content: "";
        height: 1px;
        flex-grow: 1;
        background: rgba(0, 212, 255, 0.2);
        margin-left: 10px;
    }

    /* Submenu Styles */
    .has-submenu > a { position: relative; }
    .caret {
        position: absolute;
        right: 15px;
        font-size: 12px;
        transition: transform 0.3s ease;
    }
    .has-submenu.open > a .caret { transform: rotate(180deg); }
    .has-submenu.open > a { background: rgba(0,0,0,0.15); border-left: 3px solid var(--accent-glow); }
    
    .submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out;
        background: rgba(0,0,0,0.08);
        margin: 0 12px;
        border-radius: 0 0 8px 8px;
    }
    .has-submenu.open .submenu {
        max-height: 500px; 
        padding: 5px 0;
        margin-bottom: 10px;
    }
    
    .submenu a {
        padding: 10px 15px 10px 45px;
        margin: 2px 0;
        font-size: 13px;
        border-radius: 0;
    }
    .submenu a:hover { transform: translateX(2px); background: rgba(255,255,255,0.05); }
    .submenu a i { margin-right: 10px; font-size: 14px; opacity: 0.7; }

    .logout-link {
        background: rgba(255, 77, 77, 0.1) !important;
        color: #ff9e9e !important;
        margin-top: 25px !important;
        border: 1px dashed rgba(255, 77, 77, 0.4);
    }
    .logout-link:hover {
        background: #ff4d4d !important;
        color: #fff !important;
        border-style: solid;
    }
</style>

<aside class="sidebar" role="navigation">
    <div class="brand">
        <i class="fas fa-hospital-alt"></i> Hospitalis
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
            <i class="fas fa-th-large icon-main"></i> Dashboard
        </a>
        <a href="/hospital_system/patients/appointments.php" class="<?= isActive('appointments.php') ?>">
            <i class="fas fa-calendar-check icon-main"></i> Appointments
        </a>

        <div class="menu-title">Hospital Core</div>

        <div class="has-submenu <?= isParentActive(['triage.php', 'consultations.php', 'ward_management.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-stethoscope icon-main"></i> Clinical Care
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/clinical/triage.php" class="<?= isActive('triage.php') ?>"><i class="fas fa-heartbeat"></i> Triage & Vitals</a>
                <a href="/hospital_system/clinical/consultations.php" class="<?= isActive('consultations.php') ?>"><i class="fas fa-user-md"></i> Doctor's Queue</a>
                <a href="/hospital_system/clinical/ward_management.php" class="<?= isActive('ward_management.php') ?>"><i class="fas fa-bed"></i> Ward / IPD</a>
            </div>
        </div>

        <div class="has-submenu <?= isParentActive(['reception_register.php', 'patient_list.php', 'add_service.php', 'view_services.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-users icon-main"></i> Patients & Services
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/patients/reception_register.php" class="<?= isActive('reception_register.php') ?>"><i class="fas fa-user-plus"></i> New Patient</a>
                <a href="/hospital_system/patients/patient_list.php" class="<?= isActive('patient_list.php') ?>"><i class="fas fa-address-book"></i> Patient List</a>
                <a href="/hospital_system/patients/add_service.php" class="<?= isActive('add_service.php') ?>"><i class="fas fa-hand-holding-medical"></i> Add Service</a>
                <a href="/hospital_system/patients/view_services.php" class="<?= isActive('view_services.php') ?>"><i class="fas fa-briefcase-medical"></i> View Services</a>
            </div>
        </div>

        <div class="has-submenu <?= isParentActive(['lab_requests.php', 'lab_results.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-microscope icon-main"></i> Laboratory
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/lab/lab_requests.php" class="<?= isActive('lab_requests.php') ?>"><i class="fas fa-vial"></i> Lab Requests</a>
                <a href="/hospital_system/lab/lab_results.php" class="<?= isActive('lab_results.php') ?>"><i class="fas fa-poll-h"></i> Lab Results</a>
            </div>
        </div>

        <div class="has-submenu <?= isParentActive(['sell_medicine.php', 'add_stock.php', 'view_stock.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-pills icon-main"></i> Pharmacy
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/pharmacy/sell_medicine.php" class="<?= isActive('sell_medicine.php') ?>"><i class="fas fa-file-prescription"></i> Sell Medicine</a>
                <a href="/hospital_system/pharmacy/add_stock.php" class="<?= isActive('add_stock.php') ?>"><i class="fas fa-box-open"></i> Add Stock</a>
                <a href="/hospital_system/pharmacy/view_stock.php" class="<?= isActive('view_stock.php') ?>"><i class="fas fa-capsules"></i> View Stock</a>
            </div>
        </div>

        <div class="has-submenu <?= isParentActive(['maternity/add.php', 'deliveries.php']) ?>">
            <a href="#" class="menu-toggle">
                <i class="fas fa-baby icon-main"></i> Maternity
                <i class="fas fa-chevron-down caret"></i>
            </a>
            <div class="submenu">
                <a href="/hospital_system/maternity/add.php" class="<?= isActive('maternity/add.php') ?>"><i class="fas fa-baby-carriage"></i> New Entry</a>
                <a href="/hospital_system/maternity/deliveries.php" class="<?= isActive('deliveries.php') ?>"><i class="fas fa-child"></i> Deliveries</a>
            </div>
        </div>

        <?php if ($isSuperUser): ?>
            <div class="menu-title">Administration</div>

            <div class="has-submenu <?= isParentActive(['create_invoice.php', 'view_bills.php', 'ledger.php', 'add_expense.php', 'view_expenses.php']) ?>">
                <a href="#" class="menu-toggle">
                    <i class="fas fa-coins icon-main"></i> Finance & Billing
                    <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="submenu">
                    <a href="/hospital_system/billing/create_invoice.php" class="<?= isActive('create_invoice.php') ?>"><i class="fas fa-file-invoice-dollar"></i> Generate Invoice</a>
                    <a href="/hospital_system/billing/view_bills.php" class="<?= isActive('view_bills.php') ?>"><i class="fas fa-receipt"></i> View Bills</a>
                    <a href="/hospital_system/accounting/ledger.php" class="<?= isActive('ledger.php') ?>"><i class="fas fa-calculator"></i> Ledger</a>
                    <a href="/hospital_system/expenses/add_expense.php" class="<?= isActive('add_expense.php') ?>"><i class="fas fa-money-bill-wave"></i> Record Expense</a>
                    <a href="/hospital_system/expenses/view_expenses.php" class="<?= isActive('view_expenses.php') ?>"><i class="fas fa-file-contract"></i> Expense History</a>
                </div>
            </div>

            <div class="has-submenu <?= isParentActive(['manage_suppliers.php', 'purchase_orders.php', 'receive_inventory.php']) ?>">
                <a href="#" class="menu-toggle">
                    <i class="fas fa-boxes icon-main"></i> Procurement
                    <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="submenu">
                    <a href="/hospital_system/procurement/manage_suppliers.php" class="<?= isActive('manage_suppliers.php') ?>"><i class="fas fa-truck"></i> Suppliers</a>
                    <a href="/hospital_system/procurement/purchase_orders.php" class="<?= isActive('purchase_orders.php') ?>"><i class="fas fa-shopping-basket"></i> Purchase Orders</a>
                    <a href="/hospital_system/procurement/receive_inventory.php" class="<?= isActive('receive_inventory.php') ?>"><i class="fas fa-warehouse"></i> Receive Inventory</a>
                </div>
            </div>

            <div class="has-submenu <?= isParentActive(['add_user.php', 'view_users.php', 'sales_report.php', 'system_settings.php']) ?>">
                <a href="#" class="menu-toggle">
                    <i class="fas fa-cogs icon-main"></i> System & Reports
                    <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="submenu">
                    <a href="/hospital_system/reports/sales_report.php" class="<?= isActive('sales_report.php') ?>"><i class="fas fa-chart-line"></i> Financial Reports</a>
                    <a href="/hospital_system/users/view_users.php" class="<?= isActive('view_users.php') ?>"><i class="fas fa-users-cog"></i> Manage Users</a>
                    <a href="/hospital_system/settings/system_settings.php" class="<?= isActive('system_settings.php') ?>"><i class="fas fa-sliders-h"></i> General Settings</a>
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