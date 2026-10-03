<?php
/**
 * Clinical service ordering validation.
 * Ensures medical appropriateness and proper billing integration.
 */

class ServiceOrderValidator {
    private mysqli $conn;
    private array $errors = [];
    private array $warnings = [];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Validate before creating a clinical order.
     */
    public function validate_before_order(int $patientId, int $serviceId, ?string $clinicalNotes = null): bool {
        $this->errors = [];
        $this->warnings = [];

        // Patient must exist
        $patStmt = $this->conn->prepare("SELECT id, is_walkin FROM patients WHERE id = ? LIMIT 1");
        if (!$patStmt) {
            $this->errors[] = 'Database error: Unable to validate patient.';
            return false;
        }
        $patStmt->bind_param('i', $patientId);
        $patStmt->execute();
        $patient = $patStmt->get_result()->fetch_assoc();
        $patStmt->close();

        if (!$patient) {
            $this->errors[] = 'Patient #' . $patientId . ' not found.';
            return false;
        }

        // Service must exist and be active
        $svcStmt = $this->conn->prepare(
            "SELECT id, service_name, category, requires_order, requires_result FROM services_master WHERE id = ? AND active = 1 LIMIT 1"
        );
        if (!$svcStmt) {
            $this->errors[] = 'Database error: Unable to validate service.';
            return false;
        }
        $svcStmt->bind_param('i', $serviceId);
        $svcStmt->execute();
        $service = $svcStmt->get_result()->fetch_assoc();
        $svcStmt->close();

        if (!$service) {
            $this->errors[] = 'Service #' . $serviceId . ' not found or inactive.';
            return false;
        }

        // Lab orders require clinical notes or indication
        if ($service['category'] === 'lab' && (!$clinicalNotes || trim($clinicalNotes) === '')) {
            $this->warnings[] = 'Laboratory orders should include clinical indication or notes.';
        }

        // Maternity services require female patient
        if (in_array(strtolower($service['category']), ['anc', 'pnc', 'maternity'], true)) {
            $patGender = $this->conn->query("SELECT gender FROM patients WHERE id = " . (int)$patientId . " LIMIT 1")
                ->fetch_assoc()['gender'] ?? '';
            if (strtolower($patGender ?? '') !== 'female') {
                $this->errors[] = 'Maternity services can only be ordered for female patients.';
                return false;
            }
        }

        // Check for active duplicate orders in same encounter
        $dupStmt = $this->conn->prepare(
            "SELECT COUNT(*) as cnt FROM patient_services WHERE patient_id = ? AND service_id = ? AND status NOT IN ('Cancelled', 'Completed')"
        );
        if ($dupStmt) {
            $dupStmt->bind_param('ii', $patientId, $serviceId);
            $dupStmt->execute();
            $dupCount = (int)($dupStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $dupStmt->close();
            if ($dupCount > 0) {
                $this->warnings[] = 'This service has already been ordered and is pending/in progress.';
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate visit state before allowing new orders.
     */
    public function validate_visit_for_orders(int $visitId): bool {
        $this->errors = [];

        if ($visitId <= 0) {
            // Auto-create visit if needed
            return true;
        }

        $visitStmt = $this->conn->prepare("SELECT id, status FROM visits WHERE id = ? LIMIT 1");
        if (!$visitStmt) {
            $this->errors[] = 'Database error: Unable to validate visit.';
            return false;
        }

        $visitStmt->bind_param('i', $visitId);
        $visitStmt->execute();
        $visit = $visitStmt->get_result()->fetch_assoc();
        $visitStmt->close();

        if (!$visit) {
            $this->errors[] = 'Visit #' . $visitId . ' not found.';
            return false;
        }

        if (in_array(strtolower($visit['status'] ?? ''), ['closed', 'discharged', 'cancelled'], true)) {
            $this->errors[] = 'Cannot order services in a closed or discharged visit.';
            return false;
        }

        return true;
    }

    public function get_errors(): array { return $this->errors; }
    public function get_warnings(): array { return $this->warnings; }
    public function has_errors(): bool { return !empty($this->errors); }
}
