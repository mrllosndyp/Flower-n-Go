<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
<title>Order Success</title>
<style>
body{background:#111;color:white;text-align:center;padding:50px}
.success-box{max-width:500px;margin:auto;background:#222;padding:40px;border-radius:15px}
h1{color:gold}
</style>
</head>
<body>

<div class="success-box">
  <h1>✅ Order Placed!</h1>
  <p>Thank you for your order.</p>
  <a href="orders.php" style="color:gold">View Orders</a> | 
  <a href="dashboard.php" style="color:gold">Continue Shopping</a>
</div>

</body>
</html>