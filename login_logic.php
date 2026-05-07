<?php
session_start();
include('dbcon.php');

if(isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $query_run = mysqli_query($con, $query);

    if(mysqli_num_rows($query_run) > 0) {
        $row = mysqli_fetch_array($query_run);
        if($password == $row['password']) {
            $_SESSION['message'] = "Welcome back!";
            header("Location: dashboard.php"); // Padulong sa dashboard
            exit(0);
        } else {
            $_SESSION['message'] = "Wrong password!";
            header("Location: register.php");
            exit(0);
        }
    } else {
        // MAO NI IMONG GUSTO: Pop-up alert kung wala pay account
        $_SESSION['message'] = "No account found! Please create an account first.";
        header("Location: register.php");
        exit(0);
    }
}
?>