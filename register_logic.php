<?php
session_start();
include('dbcon.php');

if(isset($_POST['register_btn'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $school = mysqli_real_escape_string($con, $_POST['school']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // I-insert ang data sa database
    $query = "INSERT INTO users (name, school, email, password) VALUES ('$name', '$school', '$email', '$password')";
    $query_run = mysqli_query($con, $query);

    if($query_run) {
        $_SESSION['message'] = "Registration Successful! Please Login.";
        header("Location: register.php"); // Balik sa main page para mo-login
        exit(0);
    } else {
        $_SESSION['message'] = "Something went wrong!";
        header("Location: register.php");
        exit(0);
    }
}
?>