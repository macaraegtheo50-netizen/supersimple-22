<?php
$conn = mysqli_connect("localhost", "root", "", "batad_market_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// Start the session here so it works on every page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>