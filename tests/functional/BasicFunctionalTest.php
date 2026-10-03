<?php
/**
 * Basic Functional Tests - Can be run standalone or via PHPUnit
 * Usage: php tests/functional/BasicFunctionalTest.php
 * Or: php vendor/bin/phpunit tests/functional/BasicFunctionalTest.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../billing/invoice_validator.php';

class BasicFunctionalTest {
    private mysqli $conn;
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function run_all_tests(): void {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "FUNCTIONAL TEST SUITE\n";
        echo str_repeat("=", 70) . "\n\n";

        $this->test_invoice_validation();
        $this->test_payment_amount_validation();
        $this->test_patient_exists();
        $this->test_service_exists();

        $this->print_summary();
    }

    private function test_invoice_validation(): void {
        echo "Running: Invoice Validation Tests\n";
        $validator = new InvoiceValidator($this->conn);
        
        // Test with real invoice from test data
        $result = $this->conn->query("SELECT id FROM invoices LIMIT 1")->fetch_assoc();
        if ($result) {
            $invoiceId = (int)$result['id'];
            $valid = $validator->validate_invoice_before_payment($invoiceId);
            $this->assert($valid, "Invoice #$invoiceId should be valid for payment");
        } else {
            $this->assert(false, "No test invoices found in database");
        }
    }

    private function test_payment_amount_validation(): void {
        echo "Running: Payment Amount Validation Tests\n";
        $validator = new InvoiceValidator($this->conn);
        
        $result = $this->conn->query("SELECT id FROM invoices LIMIT 1")->fetch_assoc();
        if ($result) {
            $invoiceId = (int)$result['id'];
            
            // Test invalid amount (0)
            $valid = $validator->validate_payment_amount($invoiceId, 0);
            $this->assert(!$valid, "Payment of 0 should fail validation");
            
            // Test large amount (should fail if exceeds outstanding)
            $valid = $validator->validate_payment_amount($invoiceId, 999999999);
            $this->assert(!$valid, "Overpayment should fail validation");
        }
    }

    private function test_patient_exists(): void {
        echo "Running: Patient Existence Tests\n";
        $result = $this->conn->query("SELECT id FROM patients LIMIT 1")->fetch_assoc();
        $this->assert($result !== null, "Test patient should exist in database");
    }

    private function test_service_exists(): void {
        echo "Running: Service Existence Tests\n";
        $result = $this->conn->query("SELECT id FROM services_master LIMIT 1")->fetch_assoc();
        $this->assert($result !== null, "Test service should exist in database");
    }

    private function assert(bool $condition, string $message): void {
        if ($condition) {
            echo "  ✅ PASS: $message\n";
            $this->passed++;
        } else {
            echo "  ❌ FAIL: $message\n";
            $this->failed++;
        }
    }

    private function print_summary(): void {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "SUMMARY\n";
        echo str_repeat("=", 70) . "\n";
        echo "Passed: $this->passed\n";
        echo "Failed: $this->failed\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";
        echo "Pass Rate: " . (($this->passed + $this->failed) > 0 ? round(100 * $this->passed / ($this->passed + $this->failed), 1) : 0) . "%\n";
        echo str_repeat("=", 70) . "\n\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new BasicFunctionalTest($conn);
    $test->run_all_tests();
    exit(0);
}
