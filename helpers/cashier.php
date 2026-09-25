<?php
function cashier_shifts_available($conn): bool {
    $check = $conn->query("SHOW TABLES LIKE 'cashier_shifts'");
    return $check && $check->num_rows > 0;
}

function get_open_cashier_shift($conn, int $cashierId = 0): ?array {
    if (!cashier_shifts_available($conn)) return null;
    if ($cashierId <= 0) $cashierId = (int)($_SESSION['user_id'] ?? 0);
    if ($cashierId <= 0) return null;

    $stmt = $conn->prepare("SELECT * FROM cashier_shifts WHERE cashier_id=? AND status='Open' ORDER BY id DESC LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $cashierId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function open_cashier_shift($conn, int $cashierId, float $openingCash = 0.0, ?string $notes = null): int {
    if ($cashierId <= 0) throw new Exception('Invalid cashier.');
    if ($openingCash < 0) throw new Exception('Opening cash cannot be negative.');

    $conn->begin_transaction();
    try {
        $existing = get_open_cashier_shift($conn, $cashierId);
        if ($existing) throw new Exception('You already have an open cashier shift.');

        $stmt = $conn->prepare("INSERT INTO cashier_shifts (cashier_id, opening_cash, opening_notes, status, opened_at, created_at) VALUES (?, ?, ?, 'Open', NOW(), NOW())");
        if (!$stmt) throw new Exception('Unable to open cashier shift: '.$conn->error);
        $stmt->bind_param('ids', $cashierId, $openingCash, $notes);
        if (!$stmt->execute()) { $err=$stmt->error; $stmt->close(); throw new Exception('Unable to open cashier shift: '.$err); }
        $id=(int)$stmt->insert_id; $stmt->close();
        $conn->commit();
        return $id;
    } catch(Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function cashier_shift_totals($conn, int $shiftId): array {
    $totals = ['cash'=>0.0, 'mpesa'=>0.0, 'other'=>0.0, 'total'=>0.0];
    if ($shiftId <= 0) return $totals;

    $stmt=$conn->prepare("SELECT method,
        COALESCE(SUM(amount),0) - COALESCE((SELECT SUM(r.amount) FROM payment_refunds r
            JOIN payments rp ON rp.id=r.payment_id
            WHERE rp.cashier_shift_id=p.cashier_shift_id AND r.status='Approved'),0) AS total
        FROM payments p WHERE cashier_shift_id=? GROUP BY method");
    if ($stmt) {
        $stmt->bind_param('i',$shiftId);
        $stmt->execute();
        $res=$stmt->get_result();
        while($row=$res->fetch_assoc()){
            $method=strtolower(trim((string)$row['method']));
            $amount=(float)$row['total'];
            if(strpos($method,'mpesa')!==false) $totals['mpesa'] += $amount;
            elseif(strpos($method,'cash')!==false) $totals['cash'] += $amount;
            else $totals['other'] += $amount;
        }
        $stmt->close();
    }
    $totals['total']=$totals['cash']+$totals['mpesa']+$totals['other'];
    return $totals;
}

function close_cashier_shift($conn, int $shiftId, int $cashierId, float $closingCash, ?string $notes = null): array {
    if ($shiftId <= 0 || $cashierId <= 0) throw new Exception('Invalid cashier shift.');
    if ($closingCash < 0) throw new Exception('Closing cash cannot be negative.');

    $stmt=$conn->prepare("SELECT * FROM cashier_shifts WHERE id=? AND cashier_id=? AND status='Open' LIMIT 1 FOR UPDATE");
    if(!$stmt) throw new Exception('Unable to load cashier shift: '.$conn->error);
    $stmt->bind_param('ii',$shiftId,$cashierId);
    $stmt->execute();
    $shift=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    if(!$shift) throw new Exception('Open cashier shift not found.');

    $conn->begin_transaction();
    try {
        // Re-lock the shift inside the transaction before calculating totals.
        $stmt=$conn->prepare("SELECT * FROM cashier_shifts WHERE id=? AND cashier_id=? AND status='Open' LIMIT 1 FOR UPDATE");
        if(!$stmt) throw new Exception('Unable to lock cashier shift: '.$conn->error);
        $stmt->bind_param('ii',$shiftId,$cashierId); $stmt->execute(); $locked=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$locked) throw new Exception('Open cashier shift not found.');
        $shift=$locked;

        $totals=cashier_shift_totals($conn,$shiftId);
        $expected=(float)$shift['opening_cash']+$totals['cash'];
        $variance=$closingCash-$expected;

        $upd=$conn->prepare("UPDATE cashier_shifts SET closed_at=NOW(), closing_cash=?, expected_cash=?, cash_variance=?, closing_notes=?, status='Closed' WHERE id=? AND status='Open'");
    if(!$upd) throw new Exception('Unable to close cashier shift: '.$conn->error);
    $upd->bind_param('dddsi',$closingCash,$expected,$variance,$notes,$shiftId);
    if(!$upd->execute()){ $err=$upd->error; $upd->close(); throw new Exception('Unable to close cashier shift: '.$err); }
    $upd->close();
        $conn->commit();
    } catch(Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    return ['opening_cash'=>(float)$shift['opening_cash'],'cash_collected'=>$totals['cash'],'mpesa_collected'=>$totals['mpesa'],'other_collected'=>$totals['other'],'total_collected'=>$totals['total'],'expected_cash'=>$expected,'closing_cash'=>$closingCash,'variance'=>$variance];
}
