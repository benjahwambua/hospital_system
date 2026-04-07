<?php
$password_to_hash = "admin123";
$hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);
echo $hashed_password;

?>