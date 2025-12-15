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
.products {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    max-width: 1150px;
    margin: auto;
}

.productPage {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    max-width: 1000px;
    margin: 50px auto;
    padding: 20px;
}

.mainImg {
    width: 100%;
    max-width: 500px;
    height: auto;
    border-radius: 10px;
}

.quantity-input {
    width: 80px;
    padding: 8px;
    margin: 10px 0;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.buy-now-btn, .add-to-cart-btn {
    padding: 12px 24px;
    margin: 10px 10px 10px 0;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s;
}

.buy-now-btn {
    background-color: #28a745;
    color: white;
}

.buy-now-btn:hover {
    background-color: #218838;
}

.add-to-cart-btn {
    background-color: #007bff;
    color: white;
}

.add-to-cart-btn:hover {
    background-color: #0056b3;
}
</style>

<div class="productPage">
    <div>
        <img src="<?= htmlspecialchars($p['image']) ?>" class="mainImg" alt="<?= htmlspecialchars($p['name']) ?>">
    </div>

    <div>
        <h2><?= htmlspecialchars($p['name']) ?></h2>
        <h3>₱<?= number_format($p['price'], 2) ?></h3>
        
        <!-- Buy Now Form -->
        <form action="checkout_final.php" method="POST" id="buyNowForm">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            
            <label>Quantity:</label>
            <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?? 99 ?>" class="quantity-input">
            
            <br><br>
            <button type="submit" name="buy_now" class="buy-now-btn">Buy Now</button>
        </form>
        
        <!-- Add to Cart Button (Keep your existing functionality) -->
        <button class="add-to-cart-btn">Add to Cart</button>
        
        <h4>Description</h4>
        <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
    </div>
</div>