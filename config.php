<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default XAMPP password is empty
$db   = "batad_market_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>