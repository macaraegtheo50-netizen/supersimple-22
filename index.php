<?php 
include 'config.php'; 
// session_start(); should be in config.php. If not, add it here.

if(isset($_POST['login'])){
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password']; 
    
    $res = mysqli_query($conn, "SELECT * FROM users WHERE username='$user'");
    $row = mysqli_fetch_assoc($res);
    
    if($row && password_verify($pass, $row['password'])){
        $_SESSION['user'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid Credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Batad Public Market</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="slider">
        <div class="slides">
            <img src="img/1.png" alt="Market 1">
            <img src="img/2.png" alt="Market 2">
            <img src="img/3.png" alt="Market 3">
            <img src="img/1.png" alt="Market 1 Loop"> 
        </div>
    </div>

    <div class="login-container">
        <form method="POST" id="loginForm">
            <h2>Batad Public Market</h2>
            <p style="color: rgba(255,255,255,0.7); margin-bottom: 25px; font-size: 0.9rem;">Official Management System</p>
            
            <?php if(isset($error)): ?>
                <p style="color: #ff7675; font-size: 0.8rem; margin-bottom: 10px;"><?php echo $error; ?></p>
            <?php endif; ?>

            <input type="text" id="username" name="username" placeholder="Username" required autocomplete="off">
            <input type="password" id="password" name="password" placeholder="Password" required>
            
            <div id="btn-area">
                <button type="submit" name="login" id="loginBtn">Sign In</button>
            </div>
        </form>
        <p style="margin-top: 20px; font-size: 0.7rem; color: rgba(255,255,255,0.4);">LGU Batad Treasurer's Office Portal</p>
    </div>

    <script>
        const loginBtn = document.getElementById('loginBtn');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        loginBtn.addEventListener('mouseover', function() {
            if (usernameInput.value.trim() === "" || passwordInput.value.trim() === "") {
                const x = Math.random() * 160 - 80; 
                const y = Math.random() * 60 - 30;  
                loginBtn.style.transform = `translate(${x}px, ${y}px)`;
                loginBtn.innerText = 'Fill up first!';
                loginBtn.classList.add('btn-panic');
            }
        });

        [usernameInput, passwordInput].forEach(input => {
            input.addEventListener('input', () => {
                if (usernameInput.value.trim() !== "" && passwordInput.value.trim() !== "") {
                    loginBtn.style.transform = `translate(0, 0)`;
                    loginBtn.innerText = 'Sign In';
                    loginBtn.classList.remove('btn-panic');
                }
            });
        });
    </script>
</body>
</html>