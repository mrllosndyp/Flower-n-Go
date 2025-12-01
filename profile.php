<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id'])) { header("Location: signin.php"); exit; }

$uid = $_SESSION['user_id'];
$msg = "";

if(isset($_POST['update'])){
  $name = trim($_POST['name']);
  $location = $_POST['location'] ?: null;
  $dob = $_POST['dob'] ?: null;
  $gender = $_POST['gender'] ?: null;
  $height = $_POST['height'] ?: null;
  $weight = $_POST['weight'] ?: null;

  $upd = mysqli_prepare($conn,"UPDATE users SET name=?, location=?, dob=?, gender=?, height=?, weight=? WHERE id=?");
  mysqli_stmt_bind_param($upd,"ssssssi",$name,$location,$dob,$gender,$height,$weight,$uid);
  mysqli_stmt_execute($upd);
  $msg = "Profile updated.";
  $_SESSION['user'] = explode(' ',$name)[0];
}

if(isset($_POST['changepw'])){
  $current = $_POST['currentpw'];
  $new = $_POST['newpw'];
  $new2 = $_POST['newpw2'];

  $q = mysqli_prepare($conn,"SELECT password FROM users WHERE id=?");
  mysqli_stmt_bind_param($q,"i",$uid);
  mysqli_stmt_execute($q);
  $r = mysqli_stmt_get_result($q);
  $row = mysqli_fetch_assoc($r);

  if(!password_verify($current, $row['password'])){
    $msg = "Current password is incorrect.";
  } elseif($new !== $new2){
    $msg = "New passwords do not match.";
  } elseif(strlen($new) < 6){
    $msg = "New password must be >= 6 chars.";
  } else {
    $hp = password_hash($new, PASSWORD_DEFAULT);
    $u2 = mysqli_prepare($conn,"UPDATE users SET password=? WHERE id=?");
    mysqli_stmt_bind_param($u2,"si",$hp,$uid);
    mysqli_stmt_execute($u2);
    $msg = "Password changed successfully.";
  }
}

$q = mysqli_prepare($conn,"SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($q,"i",$uid);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);
$user = mysqli_fetch_assoc($res);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Edit Profile</title>
<link rel="stylesheet" href="style.css"></head><body>
<nav class="navbar"><div class="logo">🌸 <span class="logo-text">Flower 'n GO</span></div></nav>

<div style="max-width:760px;margin:40px auto;padding:18px">
  <div class="form-card">
    <h3>Edit profile</h3>
    <?php if($msg): ?><div class="success"><?=htmlspecialchars($msg)?></div><?php endif; ?>

    <form method="POST">
      <div class="field"><input type="text" name="name" value="<?=htmlspecialchars($user['name'])?>" required></div>
      <div class="field"><input type="text" name="location" value="<?=htmlspecialchars($user['location'])?>"></div>
      <div class="field"><input type="date" name="birth" value="<?=htmlspecialchars($user['birth '])?></div>
      <div class="field">
        <select name="gender">
          <option value="">Gender</option>
          <option <?= $user['gender']=='Male' ? 'selected':'' ?>>Male</option>
          <option <?= $user['gender']=='Female' ? 'selected':'' ?>>Female</option>
          <option <?= $user['gender']=='Other' ? 'selected':'' ?>>Other</option>
        </select>
      </div>
      <div class="row">
        <div style="flex:1" class="field"><input type="text" name="height" value="<?=htmlspecialchars($user['height'])?>" placeholder="Height"></div>
        <div style="flex:1" class="field"><input type="text" name="weight" value="<?=htmlspecialchars($user['weight'])?>" placeholder="Weight"></div>
      </div>

      <button class="btn-primary" name="update">Save changes</button>
    </form>

    <hr style="margin:18px 0">

    <h4>Change password</h4>
    <form method="POST">
      <div class="field"><input type="password" name="currentpw" placeholder="Current password"></div>
      <div class="field"><input type="password" name="newpw" placeholder="New password"></div>
      <div class="field"><input type="password" name="newpw2" placeholder="Confirm new"></div>
      <button class="btn-primary" name="changepw">Change password</button>
    </form>
  </div>
</div>
</body></html>
