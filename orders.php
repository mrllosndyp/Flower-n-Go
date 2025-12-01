<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
  header("Location: signin.php");
  exit();
}

$user = $_SESSION['user_id'];

$orders = mysqli_query($conn,"SELECT * FROM orders WHERE user_id=$user ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>My Orders</title>
<style>
body{background:#0d0a08;color:white;font-family:Arial}
.box{
  width:90%;
  max-width:900px;
  margin:auto;
}
.order{
  background:#1b120d;
  margin:20px 0;
  padding:20px;
  border-radius:12px;
}
.order h3{color:#D4AF37}
.row{display:flex;justify-content:space-between}
.btn{
  background:#D4AF37;
  padding:6px 12px;
  border-radius:15px;
  color:black;
  text-decoration:none;
}
</style>
</head>

<body>
<div class="box">

<h2>📦 My Orders</h2>

<?php while($o = mysqli_fetch_assoc($orders)): ?>
<div class="order">
  <h3>Order #<?= $o['id'] ?></h3>
  <div class="row">
    <span>Status: <?= $o['status'] ?></span>
    <span>Total: ₱<?= number_format($o['total'],2) ?></span>
  </div>
  <br>
  <a href="receipt.php?id=<?= $o['id'] ?>" class="btn">View Receipt</a>
</div>
<?php endwhile; ?>

</div>
</body>
</html>
