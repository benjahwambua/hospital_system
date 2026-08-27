<?php
require_once 'config/config.php'; // Adjust path if necessary

echo "<h2>Database Structural Audit</h2>";

$tables_to_check = ['suppliers', 'purchase_orders', 'purchase_order_items'];

foreach ($tables_to_check as $table) {
    echo "<h3>Table: $table</h3>";
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    
    if ($result->num_rows == 0) {
        echo "<p style='color:red;'>✖ CRITICAL: Table '$table' does NOT exist!</p>";
        continue;
    }

    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>
            <tr style='background:#eee;'>
                <th>Column</th>
                <th>Type</th>
                <th>Null</th>
                <th>Key</th>
            </tr>";
            
    $columns = $conn->query("DESCRIBE $table");
    while($col = $columns->fetch_assoc()) {
        echo "<tr>
                <td>{$col['Field']}</td>
                <td>{$col['Type']}</td>
                <td>{$col['Null']}</td>
                <td>{$col['Key']}</td>
              </tr>";
    }
    echo "</table>";
}

echo "<h3>Server Info</h3>";
echo "MySQL Version: " . $conn->server_info;
?>