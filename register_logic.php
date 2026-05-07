<?php
include 'db_conn.php';

if (isset($_POST['register_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $school = mysqli_real_escape_string($conn, $_POST['school']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; 

    $query = "INSERT INTO users (name, school, email, password) VALUES ('$name', '$school', '$email', '$password')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Registration Successful!'); window.location.href='register.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>