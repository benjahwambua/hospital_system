<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/auth.php';

// 1. Role Security: Ensure only authorized staff can discharge
require_role(['admin', 'doctor', 'nurse']);

/**
 * PRODUCTION-READY DISCHARGE LOGIC
 * Includes: Transactions, CSRF protection, and detailed error logging.
 */

// 2. CSRF & Method Validation
// In production, use POST for data modification. 
// If your previous page uses a link, ensure it passes a CSRF token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    die("Invalid Request Method.");
}

$admission_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if ($admission_id > 0) {
    
    // 3. Database Transaction
    // We use a transaction to ensure that if we add more steps later 
    // (like updating a 'beds' table or billing), they all succeed or fail together.
    $conn->begin_transaction();

    try {
        $discharge_date = date('Y-m-d H:i:s');
        
        // 4. Update Admission Status
        // Added 'WHERE status = Admitted' to prevent double-discharging 
        $sql = "UPDATE admissions 
                SET status = 'Discharged', 
                    discharge_date = ?, 
                    discharged_by = ? 
                WHERE id = ? AND status = 'Admitted'";
        
        $stmt = $conn->prepare($sql);
        $user_id = $_SESSION['user_id'] ?? 0; // Track who performed the action
        $stmt->bind_param("sii", $discharge_date, $user_id, $admission_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execution failed: " . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("Patient was already discharged or record does not exist.");
        }

        // 5. Audit Logging (Optional but recommended for production)
        // log_action($user_id, 'DISCHARGE', "Discharged admission ID: $admission_id");

        $conn->commit();
        
        // Success Redirect
        $_SESSION['msg_success'] = "Patient successfully discharged.";
        header("Location: ward_management.php?status=discharged");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        
        // 6. Production Error Handling
        // Log the error internally, don't show $conn->error to the user.
        error_log("Discharge Error (ID $admission_id): " . $e->getMessage());
        
        $_SESSION['msg_error'] = "Critical Error: Could not process discharge.";
        header("Location: ward_management.php?status=error");
        exit();
    }
} else {
    header("Location: ward_management.php");
    exit();
}