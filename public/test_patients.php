<?php
include("../config/db.php");

echo "<h2>Testing Patients Table...</h2>";

$sql = "SELECT * FROM patients LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✔ Patients table found and contains data.</p>";

    echo "<table border='1' cellpadding='6'>";
    echo "<tr><th>ID</th><th>Name</th><th>Gender</th><th>Phone</th></tr>";

    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['gender']}</td>
                <td>{$row['phone']}</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "<p style='color:red;'>✘ Patients table exists but contains no data.</p>";
}
?>
