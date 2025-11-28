<?php
session_start();
include "db.php";

if(isset($_POST['login'])){
  $email = $_POST['email'];
  $password = $_POST['password'];

  $sql = mysqli_query($conn,"SELECT * FROM users WHERE email='$email'");
  if(mysqli_num_rows($sql)===1){
    $row = mysqli_fetch_assoc($sql);

    if(password_verify($password, $row['password'])){
      $_SESSION['user'] = $row['name'];
      header("Location:index.php");
      exit;
    } else {
      echo "<script>alert('Wrong password');</script>";
    }

  } else {
    echo "<script>alert('No account found');</script>";
  }
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Sign In</title>
<style>
body{background:#f4f4f4;height:100vh;display:flex;align-items:center;justify-content:center;font-family:Segoe UI,sans-serif}
.box{background:white;width:320px;padding:30px;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,0.1)}
input{width:100%;padding:10px;margin-bottom:12px;border:none;background:#f2f2f2;border-radius:6px}
button{width:100%;padding:12px;border:none;background:#111;color:white;border-radius:8px}
a{text-decoration:none;display:block;text-align:center;margin-top:10px;color:black;font-weight:bold}
</style>
</head>
<body>

<form class="box" method="POST">

<h2>Login</h2>

<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>

<button name="login">Login</button>

<a href="signup.php">Create account</a>

</form>

</body>
</html>
