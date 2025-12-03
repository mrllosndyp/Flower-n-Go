<?php session_start(); include "db.php"; ?>
<!DOCTYPE html>
<html>
<head>
<title>Finalize Order</title>
<style>
.container{max-width:900px;margin:auto;background:#111;padding:20px;color:#fff;}
h2{color:#D4AF37;text-align:center}
.box{margin:15px 0;padding:15px;background:#181818;}
.order-row{display:flex;justify-content:space-between;}
.total{font-size:18px;color:#D4AF37;font-weight:bold;text-align:right}
input,select{width:100%;padding:8px;margin-top:5px;background:#222;color:white;border:none}
.pay{display:flex;gap:10px}
.pay label{background:#222;padding:10px;border-radius:6px;cursor:pointer;}
button{background:#D4AF37;border:none;padding:12px;width:100%;margin-top:15px}
</style>
</head>

<body>
    <!-- BACK BUTTON -->
<div class="backBtn" onclick="goBack()">← Back</div>

<div class="container">

<h2>Finalize Your Order</h2>

<div class="box">
<h3>Shipping Details</h3>
<input placeholder="Full Name">
<input placeholder="Address">
<input placeholder="Contact Number">
</div>

<div class="box">
<h3>Order Summary</h3>

<?php
$total = 0;
foreach($_SESSION['cart'] as $pid=>$qty){
$q = mysqli_query($conn,"SELECT * FROM products WHERE id=$pid");
$p = mysqli_fetch_assoc($q);
$sub = $qty * $p['price'];
$total += $sub;

echo "<div class='order-row'>
<span>{$p['name']} x$qty</span>
<span>₱".number_format($sub,2)."</span>
</div>";
}
?>
<div class="total">Total: ₱<?= number_format($total,2) ?></div>
</div>

<div class="box">
<h3>Mode of Payment</h3>
<div class="pay">
<label><input type="radio" name="pay"> GCash</label>
<label><input type="radio" name="pay"> Bank</label>
<label><input type="radio" name="pay" checked> COD</label>
</div>
</div>

<form action="place_order.php" method="POST">
<button>Place Order Now</button>
</form>

</div>
</body>
</html>
