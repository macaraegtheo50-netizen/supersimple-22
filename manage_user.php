<?php include 'config.php'; 
if($_SESSION['role'] != 'admin') die("Unauthorized Access");

if(isset($_POST['add_user'])){
    $u = $_POST['username'];
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $r = $_POST['role'];
    mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$u', '$p', '$r')");
    echo "<script>alert('User Created');</script>";
}
?>
<form method="POST">
    <input type="text" name="username" placeholder="New Username" required>
    <input type="password" name="password" placeholder="New Password" required>
    <select name="role">
        <option value="encoder">Encoder</option>
        <option value="staff">Staff (View Only)</option>
    </select>
    <button type="submit" name="add_user">Create User</button>
</form>
<a href="dashboard.php">Back to Dashboard</a>