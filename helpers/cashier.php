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

function cashier_shift_column_exists($conn,string $column): bool {
    $safe=$conn->real_escape_string($column);
    $q=$conn->query("SHOW COLUMNS FROM cashier_shifts LIKE '{$safe}'");
    return $q && $q->num_rows>0;
}

function cashier_shift_totals($conn, int $shiftId): array {
    $totals = ['cash'=>0.0, 'mpesa'=>0.0, 'other'=>0.0, 'total'=>0.0];
    if ($shiftId <= 0) return $totals;

    $stmt=$conn->prepare("SELECT p.id, p.method, p.amount,
        COALESCE((SELECT SUM(r.amount) FROM payment_refunds r WHERE r.payment_id=p.id AND r.status='Approved'),0) AS refunded
        FROM payments p
        WHERE p.cashier_shift_id=? ORDER BY p.id ASC");
    if ($stmt) {
        $stmt->bind_param('i',$shiftId);
        $stmt->execute();
        $res=$stmt->get_result();
        while($row=$res->fetch_assoc()){
            $method=strtolower(trim((string)$row['method']));
            $amount=max((float)$row['amount']-(float)$row['refunded'],0);
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

    $conn->begin_transaction();
    try {
        // Lock the shift only after the transaction has started.
        $stmt=$conn->prepare("SELECT * FROM cashier_shifts WHERE id=? AND cashier_id=? AND status='Open' LIMIT 1 FOR UPDATE");
        if(!$stmt) throw new Exception('Unable to load cashier shift: '.$conn->error);
        $stmt->bind_param('ii',$shiftId,$cashierId);
        $stmt->execute();
        $shift=$stmt->get_result()->fetch_assoc();
        $stmt->close();
        if(!$shift) throw new Exception('Open cashier shift not found.');
        // Re-lock the shift inside the transaction before calculating totals.
        $stmt=$conn->prepare("SELECT * FROM cashier_shifts WHERE id=? AND cashier_id=? AND status='Open' LIMIT 1 FOR UPDATE");
        if(!$stmt) throw new Exception('Unable to lock cashier shift: '.$conn->error);
        $stmt->bind_param('ii',$shiftId,$cashierId); $stmt->execute(); $locked=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$locked) throw new Exception('Open cashier shift not found.');
        $shift=$locked;

        $totals=cashier_shift_totals($conn,$shiftId);
        $expected=(float)$shift['opening_cash']+$totals['cash'];
        $variance=$closingCash-$expected;

        $set=['closed_at=NOW()','closing_cash=?','expected_cash=?','cash_variance=?','closing_notes=?','status=\'Closed\''];
        $types='ddds'; $values=[$closingCash,$expected,$variance,$notes];
        if(cashier_shift_column_exists($conn,'expected_mpesa')){$set[]='expected_mpesa=?';$types.='d';$values[]=$totals['mpesa'];}
        if(cashier_shift_column_exists($conn,'expected_other')){$set[]='expected_other=?';$types.='d';$values[]=$totals['other'];}
        $sql="UPDATE cashier_shifts SET ".implode(', ',$set)." WHERE id=? AND status='Open'";
        $upd=$conn->prepare($sql);
        if(!$upd) throw new Exception('Unable to close cashier shift: '.$conn->error);
        $types.='i';$values[]=$shiftId;$upd->bind_param($types,...$values);
    if(!$upd->execute()){ $err=$upd->error; $upd->close(); throw new Exception('Unable to close cashier shift: '.$err); }
    $upd->close();
        $conn->commit();
    } catch(Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    return ['opening_cash'=>(float)$shift['opening_cash'],'cash_collected'=>$totals['cash'],'mpesa_collected'=>$totals['mpesa'],'other_collected'=>$totals['other'],'total_collected'=>$totals['total'],'expected_cash'=>$expected,'closing_cash'=>$closingCash,'variance'=>$variance];
}
