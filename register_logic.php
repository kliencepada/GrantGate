<?php
session_start();
include('dbcon.php');

if(isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // Check kung ang email naa ba sa table
    $query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $query_run = mysqli_query($con, $query);

    if(mysqli_num_rows($query_run) > 0) {
        $row = mysqli_fetch_array($query_run);
        
        // Simple password check (Mas maayo kung password_hash ang gamit sa register)
        if($password == $row['password']) {
            $_SESSION['auth_user'] = ["id"=>$row['id'], "name"=>$row['name']];
            header("Location: dashboard.php"); // Or bisan asa nimo gusto i-redirect
            exit(0);
        } else {
            $_SESSION['message'] = "Invalid Password!";
            header("Location: register.php");
            exit(0);
        }
    } else {
        // MAO NI IMONG GIPANGITA: Error message kung wala pay account
        $_SESSION['message'] = "No account found! Please register first.";
        header("Location: register.php");
        exit(0);
    }
}
?>