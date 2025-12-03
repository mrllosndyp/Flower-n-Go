<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) { 
  header("Location: signin.php"); 
  exit; 
}

$uid = $_SESSION['user_id'];
$msg = "";

// UPDATE PROFILE
if (isset($_POST['update'])) {
  $name = trim($_POST['name']);
  $phone = $_POST['phone'] ?: null;
  $address = $_POST['address'] ?: null;
  $city = $_POST['city'] ?: null;

  $upd = mysqli_prepare($conn, "UPDATE users 
  SET name=?, phone=?, address=?, city=?
  WHERE id=?");

  mysqli_stmt_bind_param($upd, "ssssi", $name, $phone, $address, $city, $uid);
  mysqli_stmt_execute($upd);
  $msg = "Profile updated.";
  $_SESSION['user'] = $name;
}

// FETCH USER
$q = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
$user = mysqli_fetch_assoc($q);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Profile</title>
<style>
body{background:#0d0a08;color:white;font-family:Arial}
.form-card{
  background:#1b120d;
  padding:25px;
  border-radius:14px;
  max-width:700px;
  margin:40px auto;
}
.field input,.field textarea{
  width:100%;
  padding:12px;
  border-radius:10px;
  margin-bottom:10px;
  border:none;
}
.btn-primary{
  background:#D4AF37;
  border:none;
  padding:10px;
  width:100%;
  border-radius:20px;
}
.success{background:#225522;padding:10px;margin-bottom:10px}
</style>
</head>
<body>

<div class="form-card">
  <h2>Edit Profile</h2>
  <?php if($msg): ?><div class="success"><?=$msg?></div><?php endif; ?>
  
  <form method="POST">
    <div class="field">
      <input type="text" name="name" value="<?=$user['name']?>" required>
    </div>
    <div class="field">
      <input type="tel" name="phone" value="<?=$user['phone']?>" placeholder="Phone">
    </div>
    <div class="field">
      <textarea name="address" placeholder="Address"><?=$user['address']?></textarea>
    </div>
    <div class="field">
      <input type="text" name="city" value="<?=$user['city']?>" placeholder="City">
    </div>
    
    <button class="btn-primary" name="update">Save</button>
  </form>
</div>

</body>
</html>