<?php
$con = mysqli_connect("localhost", "root", "", "grantgate_db");

if(!$con) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>