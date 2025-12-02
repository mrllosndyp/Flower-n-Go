<?php
session_start();
include "db.php";
$user = $_SESSION['user_id'];

$q = mysqli_query($conn,"
SELECT p.name,p.price,p.image,c.quantity,c.product_id 
FROM shopping_cart c 
JOIN products p ON c.product_id=p.id
WHERE c.user_id=$user");
?>

<!DOCTYPE html>
<html>
<head>
<title>Your Cart</title>
<style>
body{background:#111;color:white;font-family:sans-serif}
.cart{max-width:800px;margin:40px auto}
.item{display:flex;background:#222;padding:12px;margin-bottom:10px;border-radius:8px}
.item img{width:80px;border-radius:10px;margin-right:12px}
.total{color:gold;font-size:20px;margin-top:15px}
</style>
</head>
<body>

<div class="cart">
<h2>Your Cart</h2>

<?php $sum=0; while($row = mysqli_fetch_assoc($q)):
$sum += $row['price'] * $row['quantity'];
?>

<div class="item">
<img src="<?php echo $row['image']; ?>">
<div>
<h3><?php echo $row['name']; ?></h3>
<p>Quantity: <?php echo $row['quantity']; ?></p>
<p>₱<?php echo $row['price']; ?></p>
<a href="remove_cart.php?id=<?php echo $row['product_id']; ?>" class="delete">❌ Remove</a>

</div>
</div>

<?php endwhile; ?>

<div class="total">Total: ₱<?php echo $sum; ?></div>

<a href="checkout.php" class="checkout">Checkout</a>

<style>
.checkout{
  display:block;
  background:gold;
  color:black;
  padding:12px;
  text-align:center;
  border-radius:8px;
  text-decoration:none;
  margin-top:15px;
}
</style>

</div>

</body>
</html>
