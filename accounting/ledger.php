<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_module_access($conn, 'finance_admin', 'view');

if (file_exists(__DIR__ . '/../includes/auth.php')) {
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
    $referenceId = trim((string)($row['reference_id'] ?? ''));
    $invoiceId = isset($row['invoice_id']) ? (int)$row['invoice_id'] : 0;

    if ($invoiceId > 0) {
        return [
            'url' => '/hospital_system/billing/view_invoice.php?id=' . $invoiceId,
            'label' => 'Invoice #' . $invoiceId,
        ];
    }

    if ($referenceId !== '') {
        if (preg_match('/^PO\s*#?\s*(\d+)$/i', $referenceId, $m)) {
            return [
                'url' => '/hospital_system/procurement/purchase_orders.php?view_id=' . (int)$m[1],
                'label' => 'PO #' . (int)$m[1],
            ];
        }
        if (preg_match('/^(?:INV|INVOICE)\s*#?\s*(\d+)$/i', $referenceId, $m) || ctype_digit($referenceId)) {
            return [
                'url' => '/hospital_system/billing/view_invoice.php?id=' . (int)$referenceId,
                'label' => 'Invoice #' . (int)$referenceId,
            ];
        }
    }

    if (preg_match('/Invoice\s*#?\s*(\d+)/i', $note, $m)) {
        return [
            'url' => '/hospital_system/billing/view_invoice.php?id=' . (int)$m[1],
            'label' => 'Invoice #' . (int)$m[1],
        ];
    }

    if (preg_match('/PO\s*#?\s*(\d+)/i', $note, $m)) {
        return [
            'url' => '/hospital_system/procurement/purchase_orders.php?view_id=' . (int)$m[1],
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
    $sql = 'SELECT account, note, invoice_id, reference_id FROM accounting_entries WHERE DATE(created_at) <= ?';
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
        if ($account === '') {
            continue;
        }

        $trace = ledger_trace_link($row);
        if ($trace !== null) {
            $accountLinks[$account][$trace['label']] = $trace['url'];
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

    $sql = 'SELECT id, account, note, debit, credit, created_at, invoice_id, reference_id FROM accounting_entries WHERE DATE(created_at) BETWEEN ? AND ?';
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
<link rel="stylesheet" href="../assets/css/finance_modules.css">
<style>
.ledger-page{background:radial-gradient(circle at 8% 0%,rgba(33,150,210,.08),transparent 28%),#f4f7fb;min-height:calc(100vh - 70px);padding:30px 24px 52px}
.ledger-shell{max-width:1480px;margin:0 auto}
.ledger-topbar{background:linear-gradient(135deg,#082f55 0%,#0d5f91 52%,#2196d2 100%);color:#fff;border:0!important;border-radius:22px!important;padding:29px 31px!important;margin-bottom:22px;box-shadow:0 18px 42px rgba(8,47,85,.2)!important;position:relative;overflow:hidden}
.ledger-topbar:after{content:"";position:absolute;width:270px;height:270px;border:1px solid rgba(255,255,255,.12);border-radius:50%;right:-80px;top:-120px;box-shadow:0 0 0 35px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.015)}
.ledger-title{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}
.ledger-eyebrow{font-size:.68rem;text-transform:uppercase;letter-spacing:.17em;font-weight:800;opacity:.72;margin-bottom:7px}
.ledger-title h1{font-size:1.8rem!important;font-weight:800!important;letter-spacing:-.025em;margin:0!important;color:#fff!important}
.ledger-title p{margin:7px 0 0!important;color:#fff!important;opacity:.82!important;font-size:.92rem}
.ledger-filters{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:13px;margin-top:23px;position:relative;z-index:2}
.ledger-field label{display:block;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.75);margin-bottom:6px}
.ledger-field input,.ledger-field select{width:100%;border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:10px 12px;background:rgba(255,255,255,.96);color:#172033;outline:none}
.ledger-field input:focus,.ledger-field select:focus{border-color:#fff;box-shadow:0 0 0 3px rgba(255,255,255,.14)}
.ledger-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:end}
.ledger-btn{border:0;border-radius:10px;padding:10px 13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:7px;transition:all .18s ease;cursor:pointer}
.ledger-btn-primary{background:#fff;color:#0d5f91}
.ledger-btn-secondary,.ledger-btn-light{background:rgba(255,255,255,.11);color:#fff;border:1px solid rgba(255,255,255,.22)}
.ledger-btn:hover{transform:translateY(-2px);box-shadow:0 7px 15px rgba(0,0,0,.12);color:inherit;text-decoration:none}
.ledger-switch{display:inline-flex;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);border-radius:12px;padding:4px;backdrop-filter:blur(8px)}
.ledger-switch button{border:0;background:transparent;padding:9px 13px;border-radius:9px;font-weight:800;color:#fff;cursor:pointer}
.ledger-switch .active{background:#fff;color:#0d5f91;box-shadow:0 3px 10px rgba(0,0,0,.12)}
.ledger-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
.ledger-card{background:#fff;border:1px solid #e2e8f0;border-radius:17px;box-shadow:0 8px 25px rgba(20,40,70,.065);overflow:hidden}
.metric{padding:20px 21px;position:relative;overflow:hidden}
.metric:after{content:"";position:absolute;right:-25px;top:-28px;width:90px;height:90px;border-radius:50%;background:#edf6fc}
.metric small{position:relative;z-index:1;display:block;text-transform:uppercase;color:#697586;font-size:.68rem;font-weight:800;letter-spacing:.07em;margin-bottom:8px}
.metric strong{position:relative;z-index:1;font-size:1.35rem;color:#152033;font-weight:850;font-variant-numeric:tabular-nums}
.metric .muted{position:relative;z-index:1;color:#7a8494;font-size:.8rem;margin-top:5px}
.ledger-status{padding:14px 17px;border-radius:14px;margin-bottom:20px;font-weight:700;box-shadow:0 6px 18px rgba(20,40,70,.05)}
.status-ok{background:#ecfdf5;color:#087443;border:1px solid #b7efd3}
.status-warn{background:#fff7ed;color:#b45309;border:1px solid #fed7aa}
.ledger-card-header{padding:18px 21px;border-bottom:1px solid #e8edf3;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;background:linear-gradient(180deg,#fff,#fbfcfe)}
.ledger-card-header strong{font-size:.98rem;color:#182334}
.ledger-table-wrap{overflow:auto}
.ledger-table{width:100%;border-collapse:separate;border-spacing:0}
.ledger-table th,.ledger-table td{padding:13px 16px;border-bottom:1px solid #edf1f5;white-space:nowrap}
.ledger-table th{background:#f7f9fc;font-size:.68rem;text-transform:uppercase;color:#687386;letter-spacing:.07em;font-weight:800}
.ledger-table tbody tr{transition:background .15s ease}
.ledger-table tbody tr:hover{background:#f7fbff}
.ledger-table td{color:#374151;font-size:.88rem}
.ledger-table td.ref{max-width:400px;white-space:normal;line-height:1.45}
.amount-debit{color:#087443;font-weight:800;font-variant-numeric:tabular-nums}
.amount-credit{color:#b42318;font-weight:800;font-variant-numeric:tabular-nums}
.amount-balance{color:#1769aa;font-weight:800;font-variant-numeric:tabular-nums}
.trace-link{color:#1769aa;text-decoration:none;font-weight:800}
.trace-link:hover{text-decoration:underline}
.trace-chip,.badge-account{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;background:#edf6fc;color:#126ba5;font-size:.7rem;font-weight:800;text-decoration:none}
.trace-chip:hover{background:#dceffb;text-decoration:none;color:#0d5f91}
.opening-row td{background:#f7fafd;font-weight:700}
.ledger-pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px}
.pagination-links{display:flex;gap:7px;flex-wrap:wrap}
.pagination-links a,.pagination-links span{padding:8px 12px;border-radius:9px;background:#f1f4f8;color:#253044;text-decoration:none;font-weight:700}
.pagination-links .active{background:#1769aa;color:#fff}
@media(max-width:1050px){.ledger-filters{grid-template-columns:repeat(2,1fr)}.ledger-metrics{grid-template-columns:repeat(2,1fr)}}
@media(max-width:767px){.ledger-page{padding:18px 12px 35px}.ledger-topbar{padding:23px 20px!important;border-radius:17px!important}.ledger-title h1{font-size:1.4rem!important}.ledger-filters{grid-template-columns:1fr}.ledger-metrics{grid-template-columns:1fr}.ledger-actions{align-items:stretch}.ledger-actions .ledger-btn{flex:1}.ledger-table{min-width:900px}}
@media print{.no-print{display:none!important}.ledger-page{padding:0;background:#fff}.ledger-topbar,.ledger-card{box-shadow:none!important;border:1px solid #ddd!important}.ledger-topbar{color:#111}}

</style>

<div class="ledger-page">
    <div class="ledger-shell">
        <div class="ledger-topbar no-print ledger-hero">
            <div class="ledger-title">
                <div>
                    <div class="ledger-eyebrow">Finance &amp; Controls</div>
                    <h1><i class="fas fa-book-open mr-2"></i>General Ledger</h1>
                    <p>Review posted financial entries, balances, account movements and trial balance integrity.</p>
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
                                $accountLedgerUrl = '/hospital_system/accounting/ledger.php?' . http_build_query([
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
