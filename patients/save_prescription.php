<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);

    if ($patient_id && $medicine_id && $quantity > 0) {
        // Fetch selling price from pharmacy_stock
        $stmt = $conn->prepare("SELECT selling_price FROM pharmacy_stock WHERE id=?");
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $medicine = $res->fetch_assoc();
        $stmt->close();

        if ($medicine) {
            $price = $medicine['selling_price'];

            $stmt2 = $conn->prepare("INSERT INTO prescriptions (patient_id, medicine_id, quantity, created_at) VALUES (?,?,?,NOW())");
            $stmt2->bind_param("iii", $patient_id, $medicine_id, $quantity);

            if ($stmt2->execute()) {
                $stmt2->close();
                header("Location: patient_dashboard.php?id=$patient_id");
                exit;
            } else {
                $error = "Failed to add prescription.";
            }
        } else {
            $error = "Medicine not found.";
        }
    } else {
        $error = "All fields are required and quantity must be greater than zero.";
    }
}

if (!empty($error)) {
    echo "<div class='alert'>$error</div>";
}