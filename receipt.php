<?php
include "db.php";

$id = $_GET['id'];
$order = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM orders WHERE id=$id"));

$items = mysqli_query($conn,"
SELECT p.name,p.price,o.qty
FROM order_items o
JOIN products p ON o.product_id=p.id
WHERE o.order_id=$id");
?>

<!DOCTYPE html>
<html>
<head>
<title>Receipt</title>
<style>
body{background:#111;color:white;font-family:Arial}
.receipt{
  max-width:650px;
  margin:auto;
  background:#1c120d;
  padding:30px;
  margin-top:30px;
  border-radius:12px;
}
.logo{text-align:center;font-size:28px;color:#D4AF37}
.line{border-bottom:1px dashed #555;margin:15px 0}
.item{display:flex;justify-content:space-between}
.total{color:#D4AF37;font-size:20px}
button{
  padding:10px;
  width:100%;
  background:#D4AF37;
  border:none;
  border-radius:10px;
  font-weight:bold;
}
</style>
</head>
<body>

<div class="receipt">
  <div class="logo">🌸 Flower 'n GO</div>
  <p>Order ID: <?= $order['id'] ?></p>
  <p>Status: <?= $order['status'] ?></p>

  <div class="line"></div>

  <?php while($i=mysqli_fetch_assoc($items)): ?>
  <div class="item">
    <span><?= $i['name']?> x<?= $i['qty']?></span>
    <span>₱<?= number_format($i['price']*$i['qty'],2)?></span>
  </div>
  <?php endwhile; ?>

  <div class="line"></div>

  <div class="total">Total: ₱<?= number_format($order['total'],2)?></div>

  <br>
  <button onclick="window.print()">🖨 Print Receipt</button>
</div>

</body>
</html>
