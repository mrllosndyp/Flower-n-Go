<?php
session_start();
include "db.php";


// for remember Login
if(isset($_COOKIE['remember_user'])){
    $_SESSION['user'] = $_COOKIE['remember_user'];
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
            header("Location: dashboard.php");
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
/* RESET */
* { margin:0; padding:0; box-sizing:border-box; font-family: Arial, sans-serif; }

/* PAGE BACKGROUND */
body {
    height:100vh;
    background: radial-gradient(circle at top right, #5b2b12, #120805);
    display:flex;
    justify-content:center;
    align-items:center;
}

/* MAIN CONTAINER */
.container {
    display:flex;
    align-items:center;
    justify-content:space-between;
    width:900px;
    max-width:90%;
}

/* LOGO BOX */
.logo-box {
    background:black;
    padding:18px 30px;
    border-radius:8px;
    font-size:26px;
    color:gold;
    box-shadow:0 0 10px rgba(0,0,0,.6);
    font-weight:bold;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 30%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 15px;
    opacity: 0.7;
    transition: 0.2s;
    user-select: none;
}

.toggle-password:hover {
    opacity: 7;
}

/* For correct alignment */
.password-wrapper {
    position: relative;
}


/* FORM BOX */
.form-box {
    width:360px;
    background:rgba(0,0,0,.8);
    padding:30px;
    border-radius:12px;
    color:white;
    box-shadow:0 0 20px rgba(0,0,0,.7);
}

h2 { margin-bottom:6px; }

.subtitle {
    color:#bbb;
    font-size:14px;
    margin-bottom:15px;
}

/* INPUTS */
input {
    width:100%;
    padding:12px;
    margin-top:10px;
    outline:none;
    border:none;
    border-radius:6px;
    font-size:14px;
}

/* BUTTON */
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

/* LINKS */
.links {
    display:flex;
    justify-content:space-between;
    margin-top:10px;
    font-size:13px;
}

a { color:cyan; text-decoration:none; }
a:hover { text-decoration:underline; }

/* ERRORS */
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
          <label style="font-size:13px;">
            <input type="checkbox" name="remember"> Remember me
            </label>

      </div>


            <div class="links"> 
                <a href="forgot.php">Forgot password?</a>
                <a href="signup.php">Create account</a>
            </div>

            <button type="submit" name="signin">SIGN IN</button>

        </form>
    </div>

</div>

<script>
// password toggle
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

// validation (safe mode)
const form = document.querySelector("form");

if(form){
  form.addEventListener('submit', function(e){
    const email = this.querySelector('input[type="email"]').value.trim();
    const pw = document.getElementById('password').value.trim();

    if(isset($_POST['remember'])){
    setcookie("remember_user", $user['name'], time() + (86400 * 7), "/"); // 7 days
}

    if(!email || !pw){
      e.preventDefault();
      alert("Please fill in both email and password.");
    }
  });
}
</script>


</body>
</html>
