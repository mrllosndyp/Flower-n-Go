<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$cart_sql = "
SELECT p.id, p.name, p.price, p.image, c.quantity, 
       (p.price * c.quantity) as subtotal
FROM shopping_cart c
JOIN products p ON c.product_id = p.id
WHERE c.user_id = $user_id
";
$cart_result = mysqli_query($conn, $cart_sql);
$cart_items = [];
$total = 0;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $cart_items[] = $item;
    $total += $item['subtotal'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Your Cart | Flower 'n GO</title>
<link rel="stylesheet" href="style.css">
<style>
/* CART SPECIFIC STYLES */
.cart-container {
    max-width:1000px;
    margin:30px auto;
    padding:0 40px;
}

.cart-title {
    color:#D4AF37;
    font-size:28px;
    margin-bottom:20px;
    text-align:center;
}

.empty-cart {
    text-align:center;
    padding:50px;
    background:#1b120d;
    border-radius:12px;
    color:#bfbfbf;
    border:1px solid rgba(212,175,55,0.2);
}
.empty-cart a {
    color:#D4AF37;
    text-decoration:none;
    font-weight:bold;
    display:inline-block;
    margin-top:15px;
    padding:10px 20px;
    border:2px solid #D4AF37;
    border-radius:8px;
    transition:0.3s;
}
.empty-cart a:hover {
    background:#D4AF37;
    color:#000;
}

.cart-item {
    background:#1b120d;
    border-radius:12px;
    padding:20px;
    margin-bottom:15px;
    display:flex;
    gap:20px;
    align-items:center;
    border:1px solid rgba(212,175,55,0.2);
    transition:0.3s;
}
.cart-item:hover {
    border-color:#D4AF37;
    box-shadow:0 5px 15px rgba(212,175,55,0.1);
}

.cart-item-image {
    width:100px;
    height:100px;
    border-radius:8px;
    overflow:hidden;
    flex-shrink:0;
}
.cart-item-image img {
    width:100%;
    height:100%;
    object-fit:cover;
}

.cart-item-details {
    flex:1;
}
.cart-item-name {
    color:#fff;
    font-size:18px;
    margin-bottom:5px;
}
.cart-item-price {
    color:#D4AF37;
    font-weight:bold;
}

.cart-item-quantity {
    display:flex;
    align-items:center;
    gap:10px;
}
.quantity-btn {
    width:30px;
    height:30px;
    border-radius:50%;
    background:#D4AF37;
    border:none;
    color:#000;
    cursor:pointer;
    font-weight:bold;
    transition:0.3s;
}
.quantity-btn:hover {
    background:#ffdc73;
}
.quantity-input {
    width:50px;
    text-align:center;
    background:transparent;
    border:1px solid rgba(212,175,55,0.2);
    color:#fff;
    padding:5px;
    border-radius:6px;
}

.cart-item-subtotal {
    color:#D4AF37;
    font-weight:bold;
    font-size:18px;
    min-width:100px;
    text-align:right;
}

.remove-btn {
    background:#c0392b;
    color:white;
    border:none;
    padding:8px 15px;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
    font-weight:bold;
}
.remove-btn:hover {
    background:#e74c3c;
}

/* ORDER SUMMARY */
.order-summary {
    background:#1b120d;
    border-radius:12px;
    padding:25px;
    margin-top:30px;
    border:1px solid rgba(212,175,55,0.2);
}

.summary-title {
    color:#D4AF37;
    font-size:20px;
    margin-bottom:15px;
    padding-bottom:10px;
    border-bottom:1px solid rgba(212,175,55,0.2);
}

.summary-row {
    display:flex;
    justify-content:space-between;
    margin:10px 0;
    color:#bfbfbf;
}

.summary-total {
    display:flex;
    justify-content:space-between;
    margin-top:20px;
    padding-top:15px;
    border-top:2px solid #D4AF37;
    font-size:22px;
    color:#D4AF37;
    font-weight:bold;
}

/* ACTION BUTTONS */
.cart-actions {
    display:flex;
    justify-content:space-between;
    margin-top:30px;
    gap:20px;
}

.btn-continue {
    background:transparent;
    color:#D4AF37;
    border:2px solid #D4AF37;
    padding:12px 25px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
    transition:0.3s;
    text-align:center;
    flex:1;
}
.btn-continue:hover {
    background:#D4AF37;
    color:#000;
}

.btn-checkout {
    background:#D4AF37;
    color:#000;
    border:none;
    padding:12px 30px;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
    flex:1;
}
.btn-checkout:hover {
    background:#ffdc73;
    box-shadow:0 0 15px rgba(212,175,55,0.5);
}

.cart-item-variant {
    color:#bfbfbf;
    font-size:14px;
    margin-top:5px;
}

@media (max-width: 768px) {
    .cart-item {
        flex-direction:column;
        text-align:center;
    }
    .cart-item-quantity {
        justify-content:center;
    }
    .cart-item-subtotal {
        text-align:center;
        min-width:auto;
    }
    .cart-actions {
        flex-direction:column;
    }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="logo">🌸 <span class="logo-text">Flower 'n GO</span></div>
  <div class="nav-links">
    <a href="dashboard.php">Home</a>
    <a href="#products">Products</a>
    <a href="view_cart.php">Cart</a>
    <a href="orders.php">Orders</a>
  </div>
  <?php if(isset($_SESSION['user'])): ?>
      <span style="color:#D4AF37">Hello, <?=$_SESSION['user']?></span>
  <?php endif ?>
</nav>

<div class="cart-container">
    <h1 class="cart-title">🛒 Your Shopping Cart</h1>
    
    <?php if (empty($cart_items)): ?>
    <div class="empty-cart">
        <h3>Your cart is empty</h3>
        <p>Looks like you haven't added any items yet.</p>
        <br>
        <a href="dashboard.php">← Continue Shopping</a>
    </div>
    <?php else: ?>
    
    <!-- CART ITEMS -->
    <div id="cartItems">
        <?php foreach($cart_items as $item): ?>
        <div class="cart-item" id="item-<?php echo $item['id']; ?>">
            <div class="cart-item-image">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
            </div>
            
            <div class="cart-item-details">
                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                <div class="cart-item-variant">Variant: Standard</div>
            </div>
            
            <div class="cart-item-quantity">
                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                <input type="number" class="quantity-input" id="qty-<?php echo $item['id']; ?>" 
                       value="<?php echo $item['quantity']; ?>" min="1" 
                       onchange="updateQuantityInput(<?php echo $item['id']; ?>)">
                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
            </div>
            
            <div class="cart-item-subtotal" id="subtotal-<?php echo $item['id']; ?>">
                ₱<?php echo number_format($item['subtotal'], 2); ?>
            </div>
            
            <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">Remove</button>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- ORDER SUMMARY -->
    <div class="order-summary">
        <h3 class="summary-title">Order Summary</h3>
        
        <div class="summary-row">
            <span>Subtotal</span>
            <span id="cartSubtotal">₱<?php echo number_format($total, 2); ?></span>
        </div>
        
        <div class="summary-row">
            <span>Delivery Fee</span>
            <span>₱150.00</span>
        </div>
        
        <div class="summary-row">
            <span>Service Fee</span>
            <span>₱50.00</span>
        </div>
        
        <div class="summary-total">
            <span>Total</span>
            <span id="cartTotal">₱<?php echo number_format($total + 200, 2); ?></span>
        </div>
    </div>
    
    <!-- ACTION BUTTONS -->
    <div class="cart-actions">
        <a href="dashboard.php" class="btn-continue">← Continue Shopping</a>
        <button class="btn-checkout" onclick="proceedToCheckout()">Proceed to Checkout</button>
    </div>
    
    <?php endif; ?>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>About Flower 'n GO</h3>
            <span class="footer-line"></span>
            <p>Creating handmade floral happiness with passion and care.</p>
        </div>
        <div class="footer-col">
            <h3>Quick Links</h3>
            <span class="footer-line"></span>
            <ul>
                <li><a href="#products">Ready bouquets</a></li>
                <li><a href="#">How to order</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Contact</h3>
            <span class="footer-line"></span>
            <ul>
                <li>📍 Polangui, Albay</li>
                <li>📞 0966-395-6793</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        © 2025 Flower 'n GO
    </div>
</footer>

<script>
function goBack() {
    window.history.back();
}

function updateQuantity(productId, change) {
    const input = document.getElementById('qty-' + productId);
    let newQty = parseInt(input.value) + change;
    if (newQty < 1) newQty = 1;
    
    input.value = newQty;
    updateCart(productId, newQty);
}

function updateQuantityInput(productId) {
    const input = document.getElementById('qty-' + productId);
    let newQty = parseInt(input.value);
    if (newQty < 1) newQty = 1;
    
    input.value = newQty;
    updateCart(productId, newQty);
}

function updateCart(productId, quantity) {
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('subtotal-' + productId).textContent = '₱' + data.subtotal.toFixed(2);
            document.getElementById('cartSubtotal').textContent = '₱' + data.cartTotal.toFixed(2);
            document.getElementById('cartTotal').textContent = '₱' + (data.cartTotal + 200).toFixed(2);
        }
    });
}

function removeItem(productId) {
    if (confirm('Remove this item from cart?')) {
        window.location.href = 'remove_cart.php?id=' + productId;
    }
}

function proceedToCheckout() {
    window.location.href = 'checkout_final.php';
}
</script>

</body>
</html>