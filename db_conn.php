<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "grantgate_db"; // Gi-match sa imong phpMyAdmin (image_2b3fdb.jpg)

$con = mysqli_connect($servername, $username, $password, $dbname);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
?>