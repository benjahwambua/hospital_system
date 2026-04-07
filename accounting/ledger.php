<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if (file_exists(__DIR__ . '/../includes/auth.php')) {
    require_once __DIR__ . '/../includes/auth.php';
    if (function_exists('require_role')) {
        require_role(['admin', 'accountant', 'super_user']);
    }
}

function ledger_parse_date(?string $value, string $fallback): string
{
    if (!$value) {
        return $fallback;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return ($dt && $dt->format('Y-m-d') === $value) ? $value : $fallback;
}

function ledger_format_money(float $amount): string
{
    return 'KSH ' . number_format($amount, 2);
}


function ledger_trace_link(array $row): ?array
{
    $note = (string)($row['note'] ?? '');

    if (preg_match('/Invoice\s*#\s*(\d+)/i', $note, $m)) {
        return [
            'url' => '/hospital_system/pharmacy/view_invoice.php?id=' . (int)$m[1],
            'label' => 'Invoice #' . (int)$m[1],
        ];
    }

    if (preg_match('/PO\s*#\s*(\d+)/i', $note, $m)) {
        return [
            'url' => '/hospital_system/procurement/purchase_orders.php?id=' . (int)$m[1],
            'label' => 'PO #' . (int)$m[1],
        ];
    }

    if (preg_match('/patient[_\s-]?id\s*[:=#]?\s*(\d+)/i', $note, $m) || preg_match('/patient\s*#\s*(\d+)/i', $note, $m)) {
        return [
            'url' => '/hospital_system/patients/patient_dashboard.php?id=' . (int)$m[1],
            'label' => 'Patient #' . (int)$m[1],
        ];
    }

    return null;
}

function ledger_trial_balance_links(mysqli $conn, string $endDate, string $selectedAccount = ''): array
{
    $sql = 'SELECT account, note FROM accounting_entries WHERE DATE(created_at) <= ?';
    $types = 's';
    $params = [$endDate];
    if ($selectedAccount !== '') {
        $sql .= ' AND account = ?';
        $types .= 's';
        $params[] = $selectedAccount;
    } else {
        $sql .= " AND LOWER(account) LIKE '%receivable%'";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $accountLinks = [];
    while ($row = $res->fetch_assoc()) {
        $account = (string)($row['account'] ?? '');
        $note = (string)($row['note'] ?? '');
        if ($account === '') {
            continue;
        }

        if (preg_match('/patient[_\s-]?id\s*[:=#]?\s*(\d+)/i', $note, $m) || preg_match('/patient\s*#\s*(\d+)/i', $note, $m)) {
            $patientId = (int)$m[1];
            $accountLinks[$account]['Patient #' . $patientId] = '/hospital_system/patients/patient_dashboard.php?id=' . $patientId;
            continue;
        }

        if (preg_match('/Invoice\s*#\s*(\d+)/i', $note, $m)) {
            $invoiceId = (int)$m[1];
            $accountLinks[$account]['Invoice #' . $invoiceId] = '/hospital_system/pharmacy/view_invoice.php?id=' . $invoiceId;
        }
    }
    $stmt->close();

    return $accountLinks;
}

$today = date('Y-m-d');
$defaultStart = date('Y-m-01');
$defaultEnd = date('Y-m-t');

$startDate = ledger_parse_date($_GET['start_date'] ?? null, $defaultStart);
$endDate = ledger_parse_date($_GET['end_date'] ?? null, $defaultEnd);
if ($startDate > $endDate) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

$viewMode = $_GET['view_mode'] ?? 'ledger';
if (!in_array($viewMode, ['ledger', 'trial_balance'], true)) {
    $viewMode = 'ledger';
}

$selectedAccount = trim((string)($_GET['account'] ?? ''));
$export = $_GET['export'] ?? '';
$perPage = (int)($_GET['per_page'] ?? 50);
if (!in_array($perPage, [25, 50, 100, 250], true)) {
    $perPage = 50;
}
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$accountOptions = [];
$accountListRes = $conn->query("SELECT DISTINCT account FROM accounting_entries WHERE account IS NOT NULL AND account <> '' ORDER BY account ASC");
if ($accountListRes) {
    while ($row = $accountListRes->fetch_assoc()) {
        $accountOptions[] = $row['account'];
    }
}
if ($selectedAccount !== '' && !in_array($selectedAccount, $accountOptions, true)) {
    $selectedAccount = '';
}

$openingSql = 'SELECT COALESCE(SUM(debit - credit), 0) AS opening_balance FROM accounting_entries WHERE DATE(created_at) < ?';
$openingTypes = 's';
$openingParams = [$startDate];
if ($selectedAccount !== '') {
    $openingSql .= ' AND account = ?';
    $openingTypes .= 's';
    $openingParams[] = $selectedAccount;
}
$openingStmt = $conn->prepare($openingSql);
$openingStmt->bind_param($openingTypes, ...$openingParams);
$openingStmt->execute();
$openingBalance = (float)($openingStmt->get_result()->fetch_assoc()['opening_balance'] ?? 0);
$openingStmt->close();

$periodSummarySql = 'SELECT COALESCE(SUM(debit), 0) AS total_debit, COALESCE(SUM(credit), 0) AS total_credit, COUNT(*) AS row_count FROM accounting_entries WHERE DATE(created_at) BETWEEN ? AND ?';
$periodSummaryTypes = 'ss';
$periodSummaryParams = [$startDate, $endDate];
if ($selectedAccount !== '') {
    $periodSummarySql .= ' AND account = ?';
    $periodSummaryTypes .= 's';
    $periodSummaryParams[] = $selectedAccount;
}
$periodSummaryStmt = $conn->prepare($periodSummarySql);
$periodSummaryStmt->bind_param($periodSummaryTypes, ...$periodSummaryParams);
$periodSummaryStmt->execute();
$periodSummary = $periodSummaryStmt->get_result()->fetch_assoc() ?: [];
$periodSummaryStmt->close();

$totalDebit = (float)($periodSummary['total_debit'] ?? 0);
$totalCredit = (float)($periodSummary['total_credit'] ?? 0);
$rowCount = (int)($periodSummary['row_count'] ?? 0);
$closingBalance = $openingBalance + $totalDebit - $totalCredit;

$entries = [];
$totalPages = 1;
$trialBalanceRows = [];
$trialBalanceTraceLinks = [];
$isBalanced = abs($totalDebit - $totalCredit) < 0.00001;

if ($viewMode === 'ledger') {
    $countSql = 'SELECT COUNT(*) AS c FROM accounting_entries WHERE DATE(created_at) BETWEEN ? AND ?';
    $countTypes = 'ss';
    $countParams = [$startDate, $endDate];
    if ($selectedAccount !== '') {
        $countSql .= ' AND account = ?';
        $countTypes .= 's';
        $countParams[] = $selectedAccount;
    }
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($countTypes, ...$countParams);
    $countStmt->execute();
    $entryCount = (int)($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $countStmt->close();
    $totalPages = max(1, (int)ceil($entryCount / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = 'SELECT id, account, note, debit, credit, created_at FROM accounting_entries WHERE DATE(created_at) BETWEEN ? AND ?';
    $types = 'ss';
    $params = [$startDate, $endDate];
    if ($selectedAccount !== '') {
        $sql .= ' AND account = ?';
        $types .= 's';
        $params[] = $selectedAccount;
    }
    $sql .= ' ORDER BY created_at ASC, id ASC LIMIT ? OFFSET ?';
    $types .= 'ii';
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $runningBalance = $openingBalance;
    while ($row = $result->fetch_assoc()) {
        $row['debit'] = (float)$row['debit'];
        $row['credit'] = (float)$row['credit'];
        $runningBalance += $row['debit'] - $row['credit'];
        $row['running_balance'] = $runningBalance;
        $entries[] = $row;
    }
    $stmt->close();
} else {
    $trialSql = 'SELECT account, COALESCE(SUM(debit), 0) AS total_debit, COALESCE(SUM(credit), 0) AS total_credit, COALESCE(SUM(debit - credit), 0) AS net_balance, COUNT(*) AS line_count FROM accounting_entries WHERE DATE(created_at) <= ?';
    $trialTypes = 's';
    $trialParams = [$endDate];
    if ($selectedAccount !== '') {
        $trialSql .= ' AND account = ?';
        $trialTypes .= 's';
        $trialParams[] = $selectedAccount;
    }
    $trialSql .= ' GROUP BY account HAVING total_debit <> 0 OR total_credit <> 0 OR net_balance <> 0 ORDER BY account ASC';

    $trialStmt = $conn->prepare($trialSql);
    $trialStmt->bind_param($trialTypes, ...$trialParams);
    $trialStmt->execute();
    $trialRes = $trialStmt->get_result();
    while ($row = $trialRes->fetch_assoc()) {
        $row['total_debit'] = (float)$row['total_debit'];
        $row['total_credit'] = (float)$row['total_credit'];
        $row['net_balance'] = (float)$row['net_balance'];
        $trialBalanceRows[] = $row;
    }
    $trialStmt->close();
    $trialBalanceTraceLinks = ledger_trial_balance_links($conn, $endDate, $selectedAccount);

    $tbTotalsSql = 'SELECT COALESCE(SUM(debit), 0) AS total_debit, COALESCE(SUM(credit), 0) AS total_credit FROM accounting_entries WHERE DATE(created_at) <= ?';
    $tbTotalsTypes = 's';
    $tbTotalsParams = [$endDate];
    if ($selectedAccount !== '') {
        $tbTotalsSql .= ' AND account = ?';
        $tbTotalsTypes .= 's';
        $tbTotalsParams[] = $selectedAccount;
    }
    $tbTotalsStmt = $conn->prepare($tbTotalsSql);
    $tbTotalsStmt->bind_param($tbTotalsTypes, ...$tbTotalsParams);
    $tbTotalsStmt->execute();
    $tbTotals = $tbTotalsStmt->get_result()->fetch_assoc() ?: [];
    $tbTotalsStmt->close();

    $totalDebit = (float)($tbTotals['total_debit'] ?? 0);
    $totalCredit = (float)($tbTotals['total_credit'] ?? 0);
    $closingBalance = $totalDebit - $totalCredit;
    $isBalanced = abs($totalDebit - $totalCredit) < 0.00001;
}

if ($export === 'csv') {
    $filename = $viewMode === 'trial_balance' ? 'trial_balance_' . $endDate . '.csv' : 'general_ledger_' . $startDate . '_to_' . $endDate . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    if ($viewMode === 'trial_balance') {
        fputcsv($output, ['Account', 'Debit', 'Credit', 'Net Balance', 'Lines']);
        foreach ($trialBalanceRows as $row) {
            fputcsv($output, [
                $row['account'],
                number_format($row['total_debit'], 2, '.', ''),
                number_format($row['total_credit'], 2, '.', ''),
                number_format($row['net_balance'], 2, '.', ''),
                $row['line_count'],
            ]);
        }
    } else {
        fputcsv($output, ['Date', 'Account', 'Reference', 'Debit', 'Credit', 'Running Balance', 'Trace']);
        fputcsv($output, [$startDate, 'Opening Balance', '', '', '', number_format($openingBalance, 2, '.', '')]);
        foreach ($entries as $row) {
            $trace = ledger_trace_link($row);
            fputcsv($output, [
                $row['created_at'],
                $row['account'],
                $row['note'],
                number_format($row['debit'], 2, '.', ''),
                number_format($row['credit'], 2, '.', ''),
                number_format($row['running_balance'], 2, '.', ''),
                $trace['label'] ?? '',
            ]);
        }
    }

    fclose($output);
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<style>
    .ledger-page { background: #f6f8fb; min-height: 100vh; padding: 24px; }
    .ledger-shell { max-width: 1500px; margin: 0 auto; }
    .ledger-topbar, .ledger-card { background: #fff; border: 1px solid #e7ebf3; border-radius: 16px; box-shadow: 0 6px 24px rgba(31, 41, 55, 0.06); }
    .ledger-topbar { padding: 20px; margin-bottom: 20px; }
    .ledger-title { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; }
    .ledger-title h1 { font-size: 1.5rem; margin: 0; color: #1f2937; }
    .ledger-title p { margin: 6px 0 0; color: #6b7280; }
    .ledger-filters { display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-top: 18px; }
    .ledger-field label { display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; color:#6b7280; margin-bottom:6px; }
    .ledger-field input, .ledger-field select { width:100%; border:1px solid #d8dee9; border-radius:10px; padding:10px 12px; background:#fff; }
    .ledger-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:end; }
    .ledger-btn { border:none; border-radius:10px; padding:10px 14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .ledger-btn-primary { background:#2563eb; color:#fff; }
    .ledger-btn-secondary { background:#eef2ff; color:#3730a3; }
    .ledger-btn-light { background:#f3f4f6; color:#111827; }
    .ledger-switch { display:inline-flex; background:#eef2ff; border-radius:12px; padding:4px; }
    .ledger-switch button { border:none; background:transparent; padding:8px 12px; border-radius:10px; font-weight:700; color:#4338ca; }
    .ledger-switch .active { background:#fff; box-shadow:0 2px 8px rgba(67,56,202,0.14); }
    .ledger-metrics { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:20px; }
    .metric { padding:18px; }
    .metric small { display:block; text-transform:uppercase; color:#6b7280; font-weight:700; margin-bottom:6px; }
    .metric strong { font-size:1.35rem; color:#111827; }
    .metric .muted { color:#6b7280; font-size:0.88rem; }
    .ledger-status { padding:14px 16px; border-radius:14px; margin-bottom:20px; font-weight:600; }
    .status-ok { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    .status-warn { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .ledger-card { overflow:hidden; }
    .ledger-card-header { padding:18px 20px; border-bottom:1px solid #edf2f7; display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; }
    .ledger-table-wrap { overflow:auto; }
    .ledger-table { width:100%; border-collapse:collapse; }
    .ledger-table th, .ledger-table td { padding:12px 16px; border-bottom:1px solid #edf2f7; white-space:nowrap; }
    .ledger-table th { background:#f9fafb; font-size:0.78rem; text-transform:uppercase; color:#6b7280; letter-spacing:.04em; }
    .ledger-table td.ref { max-width: 380px; white-space: normal; }
    .amount-debit { color:#047857; font-weight:700; }
    .amount-credit { color:#b91c1c; font-weight:700; }
    .amount-balance { color:#1d4ed8; font-weight:700; }
    .trace-link { color:#1d4ed8; text-decoration:none; font-weight:700; }
    .trace-chip { display:inline-flex; padding:4px 9px; border-radius:999px; background:#eef2ff; color:#312e81; font-size:11px; font-weight:700; text-decoration:none; }
    .badge-account { display:inline-flex; padding:4px 10px; border-radius:999px; background:#eef2ff; color:#3730a3; font-weight:700; font-size:.78rem; }
    .opening-row td { background:#f8fafc; font-weight:700; }
    .ledger-pagination { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 20px; }
    .pagination-links { display:flex; gap:8px; flex-wrap:wrap; }
    .pagination-links a, .pagination-links span { padding:8px 12px; border-radius:10px; background:#f3f4f6; color:#111827; text-decoration:none; }
    .pagination-links .active { background:#2563eb; color:#fff; }
    @media print {
        .no-print { display:none !important; }
        .ledger-page { padding:0; background:#fff; }
        .ledger-topbar, .ledger-card { box-shadow:none; border:1px solid #ddd; }
    }
</style>

<div class="ledger-page">
    <div class="ledger-shell">
        <div class="ledger-topbar no-print">
            <div class="ledger-title">
                <div>
                    <h1>General Ledger</h1>
                    <p>Production-ready accounting view with opening balances, controlled filters, exports, and trial balance checks inspired by ERP workflows.</p>
                </div>
                <div class="ledger-switch">
                    <button type="button" class="<?= $viewMode === 'ledger' ? 'active' : '' ?>" onclick="setViewMode('ledger')">Ledger</button>
                    <button type="button" class="<?= $viewMode === 'trial_balance' ? 'active' : '' ?>" onclick="setViewMode('trial_balance')">Trial Balance</button>
                </div>
            </div>

            <form method="GET" id="ledgerFilterForm">
                <input type="hidden" name="view_mode" id="view_mode" value="<?= htmlspecialchars($viewMode) ?>">
                <input type="hidden" name="page" value="1">
                <div class="ledger-filters">
                    <div class="ledger-field">
                        <label for="start_date">Start date</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" <?= $viewMode === 'trial_balance' ? 'disabled' : '' ?>>
                    </div>
                    <div class="ledger-field">
                        <label for="end_date">End date</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    <div class="ledger-field">
                        <label for="account">Account</label>
                        <select id="account" name="account">
                            <option value="">All accounts</option>
                            <?php foreach ($accountOptions as $accountName): ?>
                                <option value="<?= htmlspecialchars($accountName) ?>" <?= $selectedAccount === $accountName ? 'selected' : '' ?>><?= htmlspecialchars($accountName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ledger-field">
                        <label for="per_page">Rows per page</label>
                        <select id="per_page" name="per_page">
                            <?php foreach ([25, 50, 100, 250] as $size): ?>
                                <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ledger-actions">
                        <button type="submit" class="ledger-btn ledger-btn-primary">Apply</button>
                        <a class="ledger-btn ledger-btn-secondary" href="?view_mode=<?= urlencode($viewMode) ?>">Reset</a>
                        <a class="ledger-btn ledger-btn-light" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['view_mode' => $viewMode, 'start_date' => $startDate, 'end_date' => $endDate, 'account' => $selectedAccount, 'per_page' => $perPage, 'export' => 'csv']))); ?>">Export CSV</a>
                        <button type="button" class="ledger-btn ledger-btn-light" onclick="window.print()">Print</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="ledger-metrics">
            <div class="ledger-card metric">
                <small>Opening balance</small>
                <strong><?= ledger_format_money($openingBalance) ?></strong>
                <div class="muted">Balance before <?= htmlspecialchars($startDate) ?></div>
            </div>
            <div class="ledger-card metric">
                <small><?= $viewMode === 'trial_balance' ? 'Debits to date' : 'Debits in period' ?></small>
                <strong><?= ledger_format_money($totalDebit) ?></strong>
                <div class="muted"><?= $viewMode === 'trial_balance' ? 'Cumulative through selected end date' : $rowCount . ' posted lines in selected window' ?></div>
            </div>
            <div class="ledger-card metric">
                <small><?= $viewMode === 'trial_balance' ? 'Credits to date' : 'Credits in period' ?></small>
                <strong><?= ledger_format_money($totalCredit) ?></strong>
                <div class="muted">Use this to reconcile cash, expense, and payable movements.</div>
            </div>
            <div class="ledger-card metric">
                <small><?= $viewMode === 'trial_balance' ? 'Net position' : 'Closing balance' ?></small>
                <strong><?= ledger_format_money($closingBalance) ?></strong>
                <div class="muted"><?= $selectedAccount !== '' ? 'Filtered to ' . htmlspecialchars($selectedAccount) : 'Across all available accounts' ?></div>
            </div>
        </div>

        <div class="ledger-status <?= $isBalanced ? 'status-ok' : 'status-warn' ?>">
            <?= $isBalanced
                ? 'Ledger check passed: total debits and credits are balanced for the selected scope.'
                : 'Attention required: debits and credits do not balance for the selected scope. Review source postings before period close.' ?>
        </div>

        <div class="ledger-card">
            <div class="ledger-card-header">
                <div>
                    <strong><?= $viewMode === 'trial_balance' ? 'Trial Balance' : 'Ledger Entries' ?></strong><br>
                    <small style="color:#6b7280;">
                        <?= $viewMode === 'trial_balance'
                            ? 'Grouped balances by account up to the selected end date.'
                            : 'Chronological posted entries with opening and running balances.' ?>
                    </small>
                </div>
                <div style="color:#6b7280; font-size:.9rem;">
                    <?= $viewMode === 'trial_balance'
                        ? count($trialBalanceRows) . ' accounts'
                        : number_format($rowCount) . ' lines' ?>
                </div>
            </div>
            <div class="ledger-table-wrap">
                <table class="ledger-table">
                    <thead>
                    <?php if ($viewMode === 'trial_balance'): ?>
                        <tr>
                            <th>Account</th>
                            <th>Journal Lines</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Net Balance</th>
                            <th>Trace</th>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Reference</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Running Balance</th>
                            <th>Trace</th>
                        </tr>
                    <?php endif; ?>
                    </thead>
                    <tbody>
                    <?php if ($viewMode === 'trial_balance'): ?>
                        <?php if ($trialBalanceRows): ?>
                            <?php foreach ($trialBalanceRows as $row): ?>
                                <?php
                                $accountName = (string)$row['account'];
                                $accountLedgerUrl = '/hospital_system/pharmacy/ledger.php?' . http_build_query([
                                    'view_mode' => 'ledger',
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'account' => $accountName,
                                ]);
                                $traceLinks = $trialBalanceTraceLinks[$accountName] ?? [];
                                ?>
                                <tr>
                                    <td><a class="trace-chip" href="<?= htmlspecialchars($accountLedgerUrl) ?>"><?= htmlspecialchars($row['account']) ?></a></td>
                                    <td><?= (int)$row['line_count'] ?></td>
                                    <td class="amount-debit"><?= number_format($row['total_debit'], 2) ?></td>
                                    <td class="amount-credit"><?= number_format($row['total_credit'], 2) ?></td>
                                    <td class="<?= $row['net_balance'] >= 0 ? 'amount-balance' : 'amount-credit' ?>"><?= number_format($row['net_balance'], 2) ?></td>
                                    <td>
                                        <?php if (!empty($traceLinks)): ?>
                                            <?php $shown = 0; ?>
                                            <?php foreach ($traceLinks as $label => $url): ?>
                                                <?php if ($shown >= 4) break; ?>
                                                <a class="trace-chip" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">Open <?= htmlspecialchars($label) ?></a>
                                                <?php $shown++; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="color:#9ca3af;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:40px;">No posted journal lines found for the selected filters.</td></tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <tr class="opening-row">
                            <td><?= htmlspecialchars($startDate) ?></td>
                            <td><span class="badge-account">Opening</span></td>
                            <td class="ref">Opening balance before the selected reporting period.</td>
                            <td>-</td>
                            <td>-</td>
                            <td class="amount-balance"><?= number_format($openingBalance, 2) ?></td>
                            <td><span style="color:#9ca3af;">—</span></td>
                        </tr>
                        <?php if ($entries): ?>
                            <?php foreach ($entries as $row): ?>
                                <?php $trace = ledger_trace_link($row); ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d M Y H:i', strtotime($row['created_at']))) ?></td>
                                    <td><span class="badge-account"><?= htmlspecialchars($row['account']) ?></span></td>
                                    <td class="ref">
                                        <?= htmlspecialchars($row['note'] ?: 'Posted journal entry #' . $row['id']) ?>
                                    </td>
                                    <td class="amount-debit"><?php if ($row['debit'] > 0): ?><?php if ($trace): ?><a class="trace-link" href="<?= htmlspecialchars($trace['url']) ?>" target="_blank" rel="noopener"><?= number_format($row['debit'], 2) ?></a><?php else: ?><?= number_format($row['debit'], 2) ?><?php endif; ?><?php else: ?>-<?php endif; ?></td>
                                    <td class="amount-credit"><?php if ($row['credit'] > 0): ?><?php if ($trace): ?><a class="trace-link" href="<?= htmlspecialchars($trace['url']) ?>" target="_blank" rel="noopener"><?= number_format($row['credit'], 2) ?></a><?php else: ?><?= number_format($row['credit'], 2) ?><?php endif; ?><?php else: ?>-<?php endif; ?></td>
                                    <td class="amount-balance"><?= number_format($row['running_balance'], 2) ?></td>
                                    <td><?php if ($trace): ?><a class="trace-chip" href="<?= htmlspecialchars($trace['url']) ?>" target="_blank" rel="noopener">Open <?= htmlspecialchars($trace['label']) ?></a><?php else: ?><span style="color:#9ca3af;">—</span><?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding:40px;">No posted journal lines found for the selected filters.</td></tr>
                        <?php endif; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($viewMode === 'ledger'): ?>
                <div class="ledger-pagination no-print">
                    <div>Page <?= $page ?> of <?= $totalPages ?></div>
                    <div class="pagination-links">
                        <?php
                        $baseParams = array_merge($_GET, [
                            'view_mode' => $viewMode,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'account' => $selectedAccount,
                            'per_page' => $perPage,
                        ]);
                        ?>
                        <?php if ($page > 1): ?>
                            <a href="?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $page - 1]))) ?>">Previous</a>
                        <?php endif; ?>
                        <span class="active"><?= $page ?></span>
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= htmlspecialchars(http_build_query(array_merge($baseParams, ['page' => $page + 1]))) ?>">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function setViewMode(mode) {
    document.getElementById('view_mode').value = mode;
    document.getElementById('ledgerFilterForm').submit();
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
