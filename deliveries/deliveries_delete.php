<?php
include('../config.php');

$id = $_GET['id'];

$query = "DELETE FROM deliveries WHERE id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: deliveries_list.php?deleted=1");
exit;
?>
