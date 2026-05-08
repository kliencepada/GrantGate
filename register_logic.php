<?php
session_start();
include('db_conn.php');

if (isset($_POST['register_btn'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // 1. I-check kung registered na ang email
    $check_email = "SELECT email FROM users WHERE email='$email' LIMIT 1";
    $check_run = mysqli_query($con, $check_email);

    if (mysqli_num_rows($check_run) > 0) {
        $_SESSION['status'] = "This email is already registered!";
        $_SESSION['status_code'] = "warning"; // Yellow icon
        header("Location: landing_page.php");
        exit();
    } else {
        // 2. I-insert ang bag-ong user
        $query = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
        $query_run = mysqli_query($con, $query);

        if ($query_run) {
            $_SESSION['status'] = "Registered Successfully!";
            $_SESSION['status_code'] = "success"; // Green icon
            header("Location: landing_page.php");
            exit();
        } else {
            $_SESSION['status'] = "Something went wrong!";
            $_SESSION['status_code'] = "error"; // Red icon
            header("Location: landing_page.php");
            exit();
        }
    }
}
?>