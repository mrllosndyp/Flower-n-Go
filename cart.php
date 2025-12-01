<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'];

$sql = "
SELECT products.name, products.image, products.price, shopping_cart.quantity
FROM shopping_cart
JOIN products ON shopping_cart.product_id = products.id
WHERE shopping_cart.user_id = $user_id
";

$result = $conn->query($sql);
$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Your Cart</title>
<style>
body{background:#0d0a08;color:white;font-family:Arial;padding:40px}
.cart{max-width:800px;margin:auto}
.item{display:flex;margin-bottom:20px;background:#1b120d;padding:15px;border-radius:12px}
.item img{width:80px;height:80px;object-fit:cover;margin-right:15px}
.total{margin-top:20px;font-size:20px;color:gold}
a{color:gold;text-decoration:none}
</style>
</head>
<body>

<div class="cart">
<h1>Your Cart</h1>

<?php while($row = $result->fetch_assoc()):
  $sum = $row['price'] * $row['quantity'];
  $total += $sum;
?>
<div class="item">
  <img src="<?= $row['image'] ?>">
  <div>
    <h3><?= $row['name'] ?></h3>
    <p>Qty: <?= $row['quantity'] ?></p>
    <p>₱<?= number_format($sum,2) ?></p>
  </div>
</div>
<?php endwhile; ?>

<div class="total">TOTAL: ₱<?= number_format($total,2) ?></div>
<br>
<a href="dashboard.php">⬅ Continue Shopping</a>
</div>

</body>
</html>
