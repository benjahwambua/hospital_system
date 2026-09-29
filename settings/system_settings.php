<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'administration', 'edit');
require_super();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $message = "<div class='alert alert-danger'>Invalid security token.</div>";
    } else {
    $categories = ['general', 'billing', 'clinical', 'pharmacy', 'mpesa', 'users'];
    $updateStmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $insertStmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");

    foreach ($categories as $category) {
        $submittedSettings = $_POST[$category] ?? [];
        if (is_array($submittedSettings) && count($submittedSettings) > 0) {
            foreach ($submittedSettings as $key => $value) {
                $fullKey = $category . '_' . trim((string)$key);
                $value = trim((string)$value);

                // Do not erase existing Daraja secrets when a password field is left blank.
                if ($category === 'mpesa' && in_array($key, ['passkey', 'consumer_secret'], true) && $value === '') {
                    continue;
                }

                if ($key === '') {
                    continue;
                }

                if ($updateStmt) {
                    $updateStmt->bind_param('ss', $value, $fullKey);
                    $updateStmt->execute();
                    if ($updateStmt->affected_rows === 0 && $insertStmt) {
                        $insertStmt->bind_param('ss', $fullKey, $value);
                        $insertStmt->execute();
                    }
                }
            }
        }
    }

    if ($updateStmt) {
        $updateStmt->close();
    }
    if ($insertStmt) {
        $insertStmt->close();
    }

    $message = "<div class='alert alert-success'>System settings updated successfully.</div>";
    }
}


// Fetch settings
$settings = [];
$settingsRes = $conn->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC");
if ($settingsRes) {
    while ($row = $settingsRes->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

function render_setting_value(array $settings, string $category, string $key, string $default = ''): string {
    $fullKey = $category . '_' . $key;
    return htmlspecialchars($settings[$fullKey] ?? $default, ENT_QUOTES, 'UTF-8');
}

function humanize_key(string $key): string {
    return ucwords(str_replace(['_', '-'], [' ', ' '], $key));
}

$tabs = [
    'general' => [
        'title' => 'General',
        'icon' => 'fas fa-building',
        'fields' => [
            'hospital_name' => ['label' => 'Hospital Name', 'type' => 'text'],
            'tax_id' => ['label' => 'Tax / PIN Number', 'type' => 'text'],
            'phone' => ['label' => 'Contact Phone', 'type' => 'text'],
            'email' => ['label' => 'Email Address', 'type' => 'email'],
            'address' => ['label' => 'Physical Address', 'type' => 'textarea'],
            'timezone' => ['label' => 'Default Time Zone', 'type' => 'text'],
            'footer_text' => ['label' => 'Receipt Footer Text', 'type' => 'textarea'],
        ]
    ],
    'billing' => [
        'title' => 'Billing & Accounting',
        'icon' => 'fas fa-calculator',
        'fields' => [
            'currency' => ['label' => 'Currency Symbol', 'type' => 'text'],
            'invoice_prefix' => ['label' => 'Invoice Prefix', 'type' => 'text'],
            'default_tax_rate' => ['label' => 'Default Tax Rate (%)', 'type' => 'number', 'step' => '0.01'],
            'payment_methods' => ['label' => 'Enabled Payment Methods', 'type' => 'text'],
            'auto_invoice' => ['label' => 'Auto-generate Invoices', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No']],
        ]
    ],
    'clinical' => [
        'title' => 'Clinical',
        'icon' => 'fas fa-stethoscope',
        'fields' => [
            'default_vitals_bp' => ['label' => 'Default BP (mmHg)', 'type' => 'text'],
            'default_vitals_temp' => ['label' => 'Default Temperature (°C)', 'type' => 'number', 'step' => '0.1'],
            'consultation_fee' => ['label' => 'Consultation Fee', 'type' => 'number', 'step' => '0.01'],
            'followup_days' => ['label' => 'Default Follow-up Days', 'type' => 'number'],
        ]
    ],
    'pharmacy' => [
        'title' => 'Pharmacy',
        'icon' => 'fas fa-pills',
        'fields' => [
            'low_stock_threshold' => ['label' => 'Low Stock Alert Threshold', 'type' => 'number'],
            'expiry_alert_days' => ['label' => 'Expiry Alert Days', 'type' => 'number'],
            'default_markup' => ['label' => 'Default Markup (%)', 'type' => 'number', 'step' => '0.01'],
        ]
    ],
    'mpesa' => [
        'title' => 'M-Pesa / Daraja',
        'icon' => 'fas fa-mobile-alt',
        'fields' => [
            'environment' => ['label' => 'Environment', 'type' => 'select', 'options' => ['sandbox' => 'Sandbox / Testing', 'live' => 'Live / Production']],
            'shortcode' => ['label' => 'Business Shortcode', 'type' => 'text'],
            'passkey' => ['label' => 'Daraja Passkey', 'type' => 'password'],
            'consumer_key' => ['label' => 'Consumer Key', 'type' => 'text'],
            'consumer_secret' => ['label' => 'Consumer Secret', 'type' => 'password'],
            'callback_url' => ['label' => 'Callback URL', 'type' => 'url'],
            'account_reference' => ['label' => 'Account Reference', 'type' => 'text'],
        ]
    ],
    'users' => [
        'title' => 'Users & Security',
        'icon' => 'fas fa-users',
        'fields' => [
            'session_timeout' => ['label' => 'Session Timeout (minutes)', 'type' => 'number'],
            'password_min_length' => ['label' => 'Minimum Password Length', 'type' => 'number'],
            'audit_log_enabled' => ['label' => 'Enable Audit Logging', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No']],
        ]
    ],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content settings-page">
    <div class="settings-shell">
        <div class="settings-hero">
            <div>
                <div class="eyebrow"><i class="fas fa-shield-alt"></i> Administration</div>
                <h1>System Settings</h1>
                <p>Manage hospital-wide configuration from one place.</p>
            </div>
            <div class="settings-icon"><i class="fas fa-sliders-h"></i></div>
        </div>

        <?= $message ?>

        <div class="settings-card">
            <div class="settings-tabs" role="tablist">
                <?php foreach ($tabs as $key => $tab): ?>
                    <button type="button" class="settings-tab <?= $key === 'general' ? 'active' : '' ?>" data-target="<?= $key ?>">
                        <span class="tab-icon"><i class="<?= $tab['icon'] ?>"></i></span>
                        <span><?= htmlspecialchars($tab['title']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <form method="POST" id="settingsForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="settings-content">
                    <?php foreach ($tabs as $category => $tab): ?>
                        <section class="settings-panel <?= $category === 'general' ? 'active' : '' ?>" id="panel-<?= $category ?>">
                            <div class="panel-heading">
                                <div>
                                    <h2><?= htmlspecialchars($tab['title']) ?></h2>
                                    <span>Configuration</span>
                                </div>
                                <i class="<?= $tab['icon'] ?>"></i>
                            </div>
                            <div class="field-grid">
                                <?php foreach ($tab['fields'] as $key => $field): ?>
                                    <div class="setting-field">
                                        <label for="<?= $category ?>_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($field['label']) ?></label>
                                        <?php if ($field['type'] === 'textarea'): ?>
                                            <textarea id="<?= $category ?>_<?= htmlspecialchars($key) ?>" name="<?= $category ?>[<?= htmlspecialchars($key) ?>]" class="form-control" rows="4"><?= render_setting_value($settings, $category, $key) ?></textarea>
                                        <?php elseif ($field['type'] === 'select'): ?>
                                            <select id="<?= $category ?>_<?= htmlspecialchars($key) ?>" name="<?= $category ?>[<?= htmlspecialchars($key) ?>]" class="form-control">
                                                <?php foreach ($field['options'] as $optKey => $optLabel): ?>
                                                    <option value="<?= htmlspecialchars($optKey) ?>" <?= render_setting_value($settings, $category, $key) === $optKey ? 'selected' : '' ?>><?= htmlspecialchars($optLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <input id="<?= $category ?>_<?= htmlspecialchars($key) ?>" type="<?= htmlspecialchars($field['type']) ?>" name="<?= $category ?>[<?= htmlspecialchars($key) ?>]" class="form-control" value="<?= render_setting_value($settings, $category, $key) ?>" <?= isset($field['step']) ? 'step="' . htmlspecialchars($field['step']) . '"' : '' ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
                <div class="settings-footer">
                    <span><i class="fas fa-lock"></i> Super-user access</span>
                    <button type="submit" class="save-settings"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.settings-page{padding:30px 25px 50px;min-height:calc(100vh - 75px)}
.settings-shell{max-width:1180px;margin:0 auto}
.settings-hero{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;padding:25px 28px;border-radius:16px;background:linear-gradient(135deg,#063b73,#075b9d);color:#fff;box-shadow:0 10px 28px rgba(6,59,115,.16)}
.settings-hero .eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:1.6px;opacity:.8;font-weight:800;margin-bottom:7px}.settings-hero h1{margin:0;font-size:28px;font-weight:800}.settings-hero p{margin:6px 0 0;color:#dceeff;font-size:13px}.settings-icon{width:58px;height:58px;border-radius:15px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;font-size:24px}
.settings-card{background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 28px rgba(25,55,90,.08);overflow:hidden}
.settings-tabs{display:flex;gap:4px;padding:10px;background:#f5f8fb;border-bottom:1px solid #e5ebf2;overflow-x:auto}
.settings-tab{border:0;background:transparent;color:#58708a;padding:11px 16px;border-radius:10px;display:flex;align-items:center;gap:9px;font-size:12px;font-weight:700;white-space:nowrap;cursor:pointer;transition:.2s}
.settings-tab:hover{background:#e9f2fa;color:#063b73}.settings-tab.active{background:#fff;color:#063b73;box-shadow:0 3px 10px rgba(25,55,90,.09)}
.tab-icon{width:28px;height:28px;border-radius:8px;background:#edf5fb;display:flex;align-items:center;justify-content:center;color:#0876b9}.settings-content{padding:27px}
.settings-panel{display:none}.settings-panel.active{display:block}.panel-heading{display:flex;justify-content:space-between;align-items:center;padding-bottom:18px;margin-bottom:22px;border-bottom:1px solid #edf1f5}.panel-heading h2{margin:0;color:#183b5c;font-size:19px;font-weight:800}.panel-heading span{color:#8a9bad;font-size:11px}.panel-heading>i{font-size:22px;color:#b9c9d8}
.field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px 24px}.setting-field label{display:block;margin-bottom:7px;color:#41566b;font-size:12px;font-weight:800}.setting-field .form-control{width:100%;box-sizing:border-box;border:1px solid #dce5ed;border-radius:9px;background:#fbfcfe;padding:10px 12px;color:#24384a;font-size:13px;outline:none;transition:.2s}.setting-field .form-control:focus{border-color:#53a9d6;box-shadow:0 0 0 3px rgba(83,169,214,.12);background:#fff}.settings-footer{display:flex;justify-content:space-between;align-items:center;padding:17px 27px;border-top:1px solid #edf1f5;background:#fbfcfd}.settings-footer span{font-size:11px;color:#8495a5;font-weight:700}.settings-footer i{margin-right:5px}.save-settings{border:0;border-radius:9px;padding:11px 20px;background:#0876b9;color:#fff;font-size:12px;font-weight:800;cursor:pointer;box-shadow:0 5px 12px rgba(8,118,185,.18)}.save-settings:hover{background:#063b73}
.alert{border-radius:9px;margin-bottom:18px}
@media(max-width:760px){.settings-page{padding:20px 12px 35px}.settings-hero{padding:20px}.settings-hero h1{font-size:23px}.settings-icon{display:none}.settings-content{padding:20px}.field-grid{grid-template-columns:1fr}.settings-footer{padding:15px 20px;gap:15px;align-items:flex-start;flex-direction:column}.save-settings{width:100%}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.settings-tab');
    const panels = document.querySelectorAll('.settings-panel');
    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            const target = this.dataset.target;
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById('panel-' + target);
            if (panel) panel.classList.add('active');
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>