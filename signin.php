<?php
session_start();
include "db.php";

if(isset($_COOKIE['remember_user'])){
    $_SESSION['user_id'] = $_COOKIE['remember_user'];
    
    $user_id = $_SESSION['user_id'];
    $user_query = mysqli_query($conn, "SELECT user_type FROM users WHERE id='$user_id'");
    if($user_query && mysqli_num_rows($user_query) > 0){
        $user = mysqli_fetch_assoc($user_query);
        if($user['user_type'] == 'admin'){
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user["id"];
            
            if(isset($_POST['remember'])) {
                setcookie("remember_user", $user["id"], time() + (86400 * 7), "/");
            } else {
                setcookie("remember_user", "", time() - 3600, "/");
            }
            
            if ($user['user_type'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Account does not exist!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign In | Flower n GO</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family: Arial, sans-serif; }
body {
    height:100vh;
    background: radial-gradient(circle at top right, #5b2b12, #120805);
    display:flex;
    justify-content:center;
    align-items:center;
}
.container {
    display:flex;
    align-items:center;
    justify-content:space-between;
    width:900px;
    max-width:90%;
}
.logo-box {
    background:black;
    padding:18px 30px;
    border-radius:8px;
    font-size:26px;
    color:gold;
    box-shadow:0 0 10px rgba(0,0,0,.6);
    font-weight:bold;
}
.form-box {
    width:360px;
    background:rgba(0,0,0,.8);
    padding:30px;
    border-radius:12px;
    color:white;
    box-shadow:0 0 20px rgba(0,0,0,.7);
}
.subtitle {
    color:#bbb;
    font-size:14px;
    margin-bottom:15px;
}
input[type="email"],
input[type="password"] {
    width:100%;
    padding:12px;
    margin-top:10px;
    border:none;
    border-radius:6px;
    font-size:14px;
}
.password-wrapper {
    position: relative;
    margin-top: 10px;
}
.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 15px;
    opacity: 0.7;
}
.remember-me {
    margin-top: 15px;
    display: flex;
    align-items: center;
    font-size: 13px;
}
.remember-me input[type="checkbox"] {
    margin-right: 8px;
}
button {
    width:100%;
    padding:12px;
    background:gold;
    color:black;
    border:none;
    margin-top:16px;
    font-weight:bold;
    border-radius:6px;
    cursor:pointer;
    transition:.3s;
}
button:hover {
    background:orange;
}
.links {
    display:flex;
    justify-content:space-between;
    margin-top:10px;
    font-size:13px;
}
a { color:cyan; text-decoration:none; }
a:hover { text-decoration:underline; }
.error {
    background:#ff3d3d;
    padding:8px;
    border-radius:6px;
    font-size:13px;
    margin-bottom:10px;
}
</style>
</head>

<body>

<div class="container">
    <div class="logo-box">🌸 Flower 'n GO</div>
    <div class="form-box">
        <h2>Sign In</h2>
        <div class="subtitle">Welcome back — enter your details</div>

        <?php if($error): ?>
        <div class="error"><?php echo $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>

            <div class="remember-me">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember me</label>
            </div>

            <div class="links"> 
                <a href="forgot.php">Forgot password?</a>
                <a href="signup.php">Create account</a>
            </div>

            <form method="POST" action="admin_dashboard.php">
            <!-- form inputs -->
            <button type="submit" name="signin">SIGN IN</button>
            </form>
    </div>
</div>

<script>
function togglePassword(){
  const pw = document.getElementById('password');
  const icon = document.querySelector(".toggle-password");
  if(pw.type === 'password'){
    pw.type = 'text';
    icon.textContent = '🙈';
  } else {
    pw.type = 'password';
    icon.textContent = '👁️';
  }
}
</script>

</body>
</html>