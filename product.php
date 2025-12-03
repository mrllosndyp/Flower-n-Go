<?php
include "db.php";

// Validate and sanitize ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Invalid product ID");
}

// Use prepared statement
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND is_active = 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($result);

if (!$p) {
    die("Product not found or inactive");
}
?>
<style>
.products{
 display:grid;
 grid-template-columns:repeat(3,1fr);
 gap:20px;
 max-width:1150px;
 margin:auto;
}
</style>

<div class="productPage">
 <div>
   <img src="<?= htmlspecialchars($p['image']) ?>" class="mainImg">
 </div>

 <div>
   <h2><?= htmlspecialchars($p['name']) ?></h2>
   <h3>₱<?= number_format($p['price'],2) ?></h3>

   <label>Quantity:</label>
   <input type="number" value="1">

   <br><br>
   <button>Add to Cart</button>

   <h4>Description</h4>
   <?= htmlspecialchars($p['description']) ?>
 </div>
</div>