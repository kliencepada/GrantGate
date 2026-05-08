<?php
session_start();
include('dbcon.php');

if(isset($_POST['register_btn'])) {
    // Pag-sanitize sa input
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $school = mysqli_real_escape_string($con, $_POST['school']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // 1. I-check kung ang email naa na ba sa database (Likay sa duplicate)
    $check_email = "SELECT email FROM users WHERE email='$email' LIMIT 1";
    $check_email_run = mysqli_query($con, $check_email);

    if(mysqli_num_rows($check_email_run) > 0) {
        $_SESSION['message'] = "Email already exists!";
        header("Location: register.php");
        exit(0);
    } else {
        // 2. I-hash ang password (Security Best Practice)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. I-insert ang data (Gamiton ang $hashed_password)
        $query = "INSERT INTO users (name, school, email, password) VALUES ('$name', '$school', '$email', '$hashed_password')";
        $query_run = mysqli_query($con, $query);

        if($query_run) {
            $_SESSION['message'] = "Registration Successful! Please Login.";
            // Pabilin sa register.php kay didto na man ang login form sa sliding design
            header("Location: register.php"); 
            exit(0);
        } else {
            $_SESSION['message'] = "Something went wrong!";
            header("Location: register.php");
            exit(0);
        }
    }
} else {
    header("Location: register.php");
    exit(0);
}
?>