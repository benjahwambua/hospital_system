<?php
/**
 * Database migration helper for audit schema setup.
 * This ensures the audit_log table exists and is ready for production use.
 */

function ensure_audit_table(mysqli $conn): bool {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'audit_log'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        return true;
    }

    $createAuditTable = "
        CREATE TABLE IF NOT EXISTS `audit_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `action` VARCHAR(255) NOT NULL,
            `details` LONGTEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_created (created_at),
            CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    return $conn->query($createAuditTable) !== false;
}

function ensure_walkin_column(mysqli $conn): bool {
    $colCheck = $conn->query("SHOW COLUMNS FROM patients LIKE 'is_walkin'");
    if ($colCheck && $colCheck->num_rows > 0) {
        return true;
    }

    return $conn->query("ALTER TABLE patients ADD COLUMN is_walkin TINYINT(1) DEFAULT 0 AFTER clinic_category") !== false;
}

function ensure_maternity_record(mysqli $conn, int $patientId): bool {
    $checkStmt = $conn->prepare("SELECT id FROM maternity_records WHERE patient_id = ? LIMIT 1");
    if (!$checkStmt) return false;

    $checkStmt->bind_param('i', $patientId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        return true;
    }
    $checkStmt->close();

    $insertStmt = $conn->prepare(
        "INSERT INTO maternity_records (patient_id, status, created_at) VALUES (?, 'Active', NOW())"
    );
    if (!$insertStmt) return false;

    $insertStmt->bind_param('i', $patientId);
    $result = $insertStmt->execute();
    $insertStmt->close();

    return $result;
}

function maternity_category(string $clinicalType): bool {
    return in_array($clinicalType, ['ANC', 'PNC', 'Maternity'], true);
}
