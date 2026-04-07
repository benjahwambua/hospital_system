<?php
include("../config/db.php");

echo "<h2>Testing Patient Queue...</h2>";

$sql = "SELECT pq.id, p.full_name, pq.department, pq.status, pq.arrival_time
        FROM patient_queue pq
        JOIN patients p ON pq.patient_id = p.id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✔ Patient queue is working and contains data.</p>";

    echo "<table border='1' cellpadding='6'>";
    echo "<tr><th>Queue ID</th><th>Patient</th><th>Department</th><th>Status</th><th>Arrival Time</th></tr>";

    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['department']}</td>
                <td>{$row['status']}</td>
                <td>{$row['arrival_time']}</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "<p style='color:red;'>✘ patient_queue table exists but no data found.</p>";
}
?>
