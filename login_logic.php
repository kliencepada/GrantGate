<?php
session_start();
include('db_conn.php');

// PARA SA SIGN UP
if (isset($_POST['signup'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // I-check kung naa na ang email
    $check_email = "SELECT email FROM users WHERE email='$email' LIMIT 1";
    $check_run = mysqli_query($con, $check_email);

    if (mysqli_num_rows($check_run) > 0) {
        $_SESSION['status'] = "Email is already taken!";
        header("Location: landing_page.php");
        exit();
    } else {
        $query = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
        $query_run = mysqli_query($con, $query);

        if ($query_run) {
            $_SESSION['status'] = "Registered Successfully!";
            header("Location: landing_page.php");
            exit();
        } else {
            $_SESSION['status'] = "Registration Failed!";
            header("Location: landing_page.php");
            exit();
        }
    }
}

// PARA SA LOGIN
if (isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $login_query = "SELECT * FROM users WHERE email='$email' AND password='$password' LIMIT 1";
    $login_run = mysqli_query($con, $login_query);

    if (mysqli_num_rows($login_run) > 0) {
        $row = mysqli_fetch_array($login_run);
        
        $_SESSION['auth'] = true;
        $_SESSION['auth_user'] = [
            'user_id' => $row['id'],
            'user_name' => $row['name'],
            'user_email' => $row['email'],
        ];

        $_SESSION['status'] = "Welcome back!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['status'] = "Invalid Email or Password";
        header("Location: landing_page.php");
        exit();
    }
}
?>