<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','accountant']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categories = ['general', 'billing', 'clinical', 'pharmacy', 'users'];
    $updateStmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $insertStmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");

    foreach ($categories as $category) {
        $submittedSettings = $_POST[$category] ?? [];
        if (is_array($submittedSettings) && count($submittedSettings) > 0) {
            foreach ($submittedSettings as $key => $value) {
                $fullKey = $category . '_' . trim((string)$key);
                $value = trim((string)$value);
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

<div class="main-content">
    <div class="container-fluid pt-4">
        <div class="card shadow-sm border-0 col-lg-12 mx-auto">
            <div class="card-header bg-white py-3">
                <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-tools mr-2"></i>Global System Configuration</h5>
            </div>
            <div class="card-body p-4">
                <?= $message ?>

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <?php foreach ($tabs as $key => $tab): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $key === 'general' ? 'active' : '' ?>" id="<?= $key ?>-tab" data-toggle="tab" href="#<?= $key ?>" role="tab">
                                <i class="<?= $tab['icon'] ?> mr-2"></i><?= htmlspecialchars($tab['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form method="POST">
                    <div class="tab-content mt-4" id="settingsTabContent">
                        <?php foreach ($tabs as $category => $tab): ?>
                            <div class="tab-pane fade <?= $category === 'general' ? 'show active' : '' ?>" id="<?= $category ?>" role="tabpanel">
                                <div class="row">
                                    <?php foreach ($tab['fields'] as $key => $field): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="small font-weight-bold"><?= htmlspecialchars($field['label']) ?></label>
                                            <?php if ($field['type'] === 'textarea'): ?>
                                                <textarea name="<?= $category ?>[<?= htmlspecialchars($key) ?>]" class="form-control" rows="3"><?= render_setting_value($settings, $category, $key) ?></textarea>
                                            <?php elseif ($field['type'] === 'select'): ?>
                                                <select name="<?= $category ?>[<?= htmlspecialchars($key) ?>]" class="form-control">
                                                    <?php foreach ($field['options'] as $optKey => $optLabel): ?>
                                                        <option value="<?= htmlspecialchars($optKey) ?>" <?= (render_setting_value($settings, $category, $key) === $optKey) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($optLabel) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <input type="<?= htmlspecialchars($field['type']) ?>" name="<?= $category ?>[<?= htmlspecialchars($key) ?>]" class="form-control" value="<?= render_setting_value($settings, $category, $key) ?>" <?= isset($field['step']) ? 'step="' . htmlspecialchars($field['step']) . '"' : '' ?>>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary px-5 font-weight-bold">Save All Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#settingsTabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>