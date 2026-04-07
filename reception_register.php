<?php
// Updating reception_register.php to add 'register_for_maternity' field

// Assuming necessary dependencies are included above this block

// Add new field to the form
// This code is for adding a new field to the form that collects patient information
?>
<form method="post" action="register.php">
    <!-- Other fields -->
    <label for="register_for_maternity">Register for Maternity:</label>
    <input type="checkbox" name="register_for_maternity" id="register_for_maternity" value="yes"><br>
    <input type="submit" value="Submit">
</form>

<?php
// Handling form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinical_type = $_POST['clinical_type'];
    $register_for_maternity = isset($_POST['register_for_maternity']) ? true : false;

    // Logic for linking maternity patients
    if ($clinical_type === 'Maternity' || $clinical_type === 'ANC' || $clinical_type === 'PNC') {
        // Insert or link the patient as a maternity patient in the database
        // Example SQL logic; replace with actual database operations
        if ($register_for_maternity) {
            // Logic to link this patient as being registered for maternity
        }
    }
}
?>