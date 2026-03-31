<?php
// fix_admin.php
include 'config.php';

$user = 'admin';
$pass = 'admin123';
// This creates the long $2y$ hash that PHP requires
$hashed = password_hash($pass, PASSWORD_DEFAULT);

// Delete any old admin accounts to avoid duplicates
mysqli_query($conn, "DELETE FROM users WHERE username='$user'");

// Insert the fresh admin with the HASHED password
$sql = "INSERT INTO users (username, password, role) VALUES ('$user', '$hashed', 'admin')";

if(mysqli_query($conn, $sql)){
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2>✅ Admin Account Fixed!</h2>";
    echo "<p>Username: <b>admin</b></p>";
    echo "<p>Password: <b>admin123</b></p>";
    echo "<a href='index.php' style='padding:10px 20px; background:#27ae60; color:white; text-decoration:none; border-radius:5px;'>Back to Login</a>";
    echo "</div>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>