<?php
$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "grantgate_db"; // Mao ni inyong gamiton sa phpMyAdmin

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>