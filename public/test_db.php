<?php
include("../config/db.php");

echo "<h2>Testing Database Connection...</h2>";

if ($conn) {
    echo "<p style='color:green;'>✔ Connected successfully to hms_db!</p>";
} else {
    echo "<p style='color:red;'>✘ Failed to connect.</p>";
}
?>
