<?php
 $servername = "localhost";
 $username = "root";
 $password = "";
 $dbname = "grantgate_db"; // Gi-match sa imong phpMyAdmin

try {
    // Ang DSN (Data Source Name) sa PDO kinahanglan ug prefix na 'mysql:'
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
    
    // Mga options para sa PDO (Best Practices)
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mag-throw ug exception kung naay error sa SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // I-return ang result as associative array by default
        PDO::ATTR_EMULATE_PREPARES   => false,                  // I-disable ang emulated prepares para mas secure contra SQL injection
    ];

    // Ang PDO constructor kay (dsn, username, password, options)
    $pdo = new PDO($dsn, $username, $password, $options);

} catch (PDOException $e) {
    // Kung malaki ang connection, ma-catch diri ug dili mag-show sa user ang imong database details
    die("Connection failed: " . $e->getMessage());
}
?>