<?php
/**
 * Centralized input validation and sanitization helpers for the HMS application.
 * These functions enforce consistent data quality across all modules.
 */

function hms_validate_email(string $email): bool {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

function hms_validate_phone(string $phone): bool {
    $phone = trim($phone);
    if ($phone === '') return true; // optional
    return (bool)preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

function hms_validate_date(string $date): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

function hms_validate_age(int $age): bool {
    return $age >= 0 && $age <= 150;
}

function hms_validate_currency(float $amount): bool {
    return $amount >= 0 && $amount <= 999999999.99;
}

function hms_sanitize_string(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function hms_sanitize_sql_identifier(string $input): string {
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $input)) {
        throw new Exception('Invalid SQL identifier: ' . $input);
    }
    return $input;
}

function hms_check_duplicate_patient(mysqli $conn, string $phone, string $name, ?int $excludeId = null): bool {
    if ($phone === '') return false; // optional check

    $query = "SELECT COUNT(*) as cnt FROM patients WHERE phone = ?";
    if ($excludeId !== null) {
        $query .= " AND id != ?";
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) return false;

    if ($excludeId !== null) {
        $stmt->bind_param('si', $phone, $excludeId);
    } else {
        $stmt->bind_param('s', $phone);
    }

    $stmt->execute();
    $cnt = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    return $cnt > 0;
}

function hms_check_duplicate_service(mysqli $conn, int $patientId, string $serviceType): bool {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as cnt FROM patient_services WHERE patient_id = ? AND category = ? AND status NOT IN ('Cancelled', 'Completed')"
    );
    if (!$stmt) return false;

    $stmt->bind_param('is', $patientId, $serviceType);
    $stmt->execute();
    $cnt = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    return $cnt > 0;
}

function hms_check_duplicate_prescription(mysqli $conn, int $patientId, string $medication): bool {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as cnt FROM prescriptions WHERE patient_id = ? AND medication = ? AND status NOT IN ('Dispensed', 'Cancelled')"
    );
    if (!$stmt) return false;

    $stmt->bind_param('is', $patientId, $medication);
    $stmt->execute();
    $cnt = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    return $cnt > 0;
}
