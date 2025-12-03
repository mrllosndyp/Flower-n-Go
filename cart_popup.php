<?php
session_start();
include "db.php";
$user=$_SESSION['user_id'];

$q=mysqli_query($conn,"
SELECT p.name, p.price, c.quantity
FROM shopping_cart c
JOIN products p ON c.product_id=p.id
WHERE c.user_id=$user
");
$total=0;
while($r=mysqli_fetch_assoc($q)){
$sub=$r['price']*$r['quantity'];
$total+=$sub;
echo "
<div class='miniRow'>
  {$r['name']} x {$r['quantity']}
  <b>₱".number_format($sub,2)."</b>
</div>";
}
echo "<hr><b>Total: ₱".number_format($total,2)."</b>";
