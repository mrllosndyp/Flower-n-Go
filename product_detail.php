<?php
session_start();
include "db.php";

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$product_sql = "SELECT * FROM products WHERE id = $product_id AND is_active = 1";
$product_result = mysqli_query($conn, $product_sql);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    header("Location: dashboard.php");
    exit();
}

$original_price = $product['price'] * 1.25;
$discount_percent = 20;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=htmlspecialchars($product['name'])?> | Flower 'n GO</title>
<link rel="stylesheet" href="style.css">
<style>

.product-detail-page {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}

.breadcrumb {
    color: #bfbfbf;
    margin-bottom: 30px;
    font-size: 14px;
}

.breadcrumb a {
    color: #D4AF37;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.product-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.product-image {
    border-radius: 15px;
    overflow: hidden;
    background: #1b120d;
    padding: 20px;
    border: 1px solid rgba(212,175,55,0.2);
}

.product-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 10px;
}

.product-title {
    font-size: 32px;
    color: #fff;
    margin-bottom: 10px;
}

.product-rating {
    color: #D4AF37;
    margin-bottom: 15px;
}

.price-container {
    margin: 20px 0;
}

.current-price {
    font-size: 36px;
    color: #D4AF37;
    font-weight: bold;
}

.original-price {
    color: #888;
    text-decoration: line-through;
    margin-left: 10px;
    font-size: 20px;
}

.discount-badge {
    background: #d32f2f;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
    margin-left: 15px;
    display: inline-block;
}

.delivery-info {
    background: rgba(212,175,55,0.1);
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
    border: 1px solid rgba(212,175,55,0.2);
}

.description {
    color: #bfbfbf;
    line-height: 1.6;
    margin: 25px 0;
}

.delivery-options {
    background: #1b120d;
    padding: 25px;
    border-radius: 12px;
    margin: 25px 0;
    border: 1px solid rgba(212,175,55,0.2);
}

.section-title {
    color: #D4AF37;
    margin-bottom: 15px;
    font-size: 20px;
}

.date-options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.date-option {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.2);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
}

.date-option:hover {
    background: rgba(212,175,55,0.1);
}

.date-option.active {
    background: #D4AF37;
    color: #000;
    font-weight: bold;
}

.time-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.time-option {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.2);
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
}

.time-option:hover {
    background: rgba(212,175,55,0.1);
}

.time-option.selected {
    background: #D4AF37;
    color: #000;
    font-weight: bold;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 25px 0;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #D4AF37;
    border: none;
    color: #000;
    font-size: 20px;
    cursor: pointer;
    transition: 0.3s;
}

.quantity-btn:hover {
    background: #ffdc73;
}

.quantity-input {
    width: 70px;
    text-align: center;
    background: #1b120d;
    border: 1px solid rgba(212,175,55,0.2);
    color: #fff;
    padding: 12px;
    border-radius: 8px;
    font-size: 18px;
}

.total-display {
    background: #1b120d;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    border: 1px solid rgba(212,175,55,0.2);
    font-size: 22px;
    color: #D4AF37;
    font-weight: bold;
}

.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 25px 0;
}

.action-btn {
    padding: 18px;
    border-radius: 10px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    text-align: center;
    text-decoration: none;
    display: block;
}

.btn-cart {
    background: #D4AF37;
    color: #000;
    border: none;
}

.btn-cart:hover {
    background: #ffdc73;
    box-shadow: 0 0 15px rgba(212,175,55,0.5);
}

.btn-buy {
    background: transparent;
    color: #D4AF37;
    border: 2px solid #D4AF37;
}

.btn-buy:hover {
    background: #D4AF37;
    color: #000;
}

.additional-info {
    background: #1b120d;
    padding: 25px;
    border-radius: 12px;
    margin: 30px 0;
    border: 1px solid rgba(212,175,55,0.2);
}

.additional-info h3 {
    color: #D4AF37;
    margin-bottom: 15px;
}

.related-section {
    margin: 50px 0;
}

.related-title {
    color: #D4AF37;
    font-size: 24px;
    margin-bottom: 25px;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.related-card {
    background: #1b120d;
    border-radius: 12px;
    overflow: hidden;
    transition: 0.3s;
    cursor: pointer;
    border: 1px solid rgba(212,175,55,0.1);
}

.related-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(212,175,55,0.2);
    border-color: #D4AF37;
}

.related-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.related-card-content {
    padding: 15px;
}

.related-card h4 {
    color: #fff;
    margin-bottom: 8px;
    font-size: 16px;
}

.related-price {
    color: #D4AF37;
    font-size: 18px;
    font-weight: bold;
}

@media (max-width: 900px) {
    .product-grid {
        grid-template-columns: 1fr;
    }
    
    .related-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .date-options {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
}

.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.popup-box {
    background: #1b120d;
    padding: 40px;
    border-radius: 15px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    border: 2px solid #D4AF37;
    animation: popupShow 0.3s ease;
}

@keyframes popupShow {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.popup-icon {
    font-size: 60px;
    color: #4CAF50;
    margin-bottom: 20px;
}

.popup-title {
    color: #D4AF37;
    font-size: 24px;
    margin-bottom: 10px;
}

.popup-message {
    color: #bfbfbf;
    margin-bottom: 25px;
}

.popup-buttons {
    display: flex;
    gap: 15px;
}

.popup-btn {
    flex: 1;
    padding: 12px;
    border-radius: 8px;
    border: none;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

.continue-btn {
    background: #666;
    color: white;
}

.continue-btn:hover {
    background: #777;
}

.checkout-btn {
    background: #D4AF37;
    color: #000;
    text-decoration: none;
    text-align: center;
}

.checkout-btn:hover {
    background: #ffdc73;
}

@media (max-width: 600px) {
    .popup-buttons {
        flex-direction: column;
    }
}

@media (max-width: 600px) {
    .related-grid {
        grid-template-columns: 1fr;
    }
    
    .date-options,
    .time-options {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

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

<div class="product-detail-page">
    <div class="breadcrumb">
        <a href="dashboard.php">Home</a> &gt; 
        <a href="dashboard.php#products">Products</a> &gt; 
        <span><?=htmlspecialchars($product['name'])?></span>
    </div>
    
    <!-- MAIN PRODUCT -->
    <div class="product-grid">
        <!-- LEFT: IMAGE -->
        <div class="product-image">
            <img src="<?=htmlspecialchars($product['image'])?>" alt="<?=htmlspecialchars($product['name'])?>">
        </div>
        
        <!-- RIGHT: DETAILS -->
        <div>
            <h1 class="product-title"><?=htmlspecialchars($product['name'])?></h1>
            <div class="product-rating">
                ★★★★★ 4.8 <span style="color:#bfbfbf;font-size:14px">(128 reviews)</span>
            </div>
            
            <!-- PRICE -->
            <div class="price-container">
                <span class="current-price">₱<?=number_format($product['price'], 2)?></span>
                <span class="original-price">₱<?=number_format($original_price, 2)?></span>
                <span class="discount-badge">Save <?=$discount_percent?>%</span>
            </div>
            
            <!-- DELIVERY INFO -->
            <div class="delivery-info">
                <strong>🚚 FREE Delivery</strong> on orders over ₱2,000<br>
                <small>Metro Manila: 2-4 hours | Province: 1-3 days</small>
            </div>
            
            <!-- DESCRIPTION -->
            <div class="description">
                <h3 style="color:#D4AF37;margin-bottom:10px;">DESCRIPTION</h3>
                <?=htmlspecialchars($product['description'])?>
                
                <div style="margin-top:15px;">
                    <strong>What's included:</strong>
                    <ul style="color:#bfbfbf;margin-left:20px;margin-top:5px;">
                        <li>Fresh handpicked flowers</li>
                        <li>Elegant wrapping paper</li>
                        <li>Free greeting card</li>
                        <li>Care instructions</li>
                    </ul>
                </div>
            </div>
            
            <!-- DELIVERY OPTIONS -->
            <div class="delivery-options">
                <h3 class="section-title">Select Delivery Date</h3>
                <div class="date-options">
                    <?php
                    $dates = [
                        ['label' => 'Today', 'date' => date('M d')],
                        ['label' => 'Tomorrow', 'date' => date('M d', strtotime('+1 day'))],
                        ['label' => 'This Friday', 'date' => date('M d', strtotime('next friday'))],
                        ['label' => 'This Saturday', 'date' => date('M d', strtotime('next saturday'))]
                    ];
                    foreach ($dates as $index => $date):
                    ?>
                        <div class="date-option <?= $index === 0 ? 'active' : '' ?>" data-date="<?=strtolower($date['label'])?>">
                            <strong><?=$date['label']?></strong><br>
                            <small><?=$date['date']?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="selectedDate" value="today">
                
                <h3 class="section-title" style="margin-top:20px;">Select Delivery Time</h3>
                <div class="time-options">
                    <?php
                    $times = [
                        ['label' => 'Anytime', 'range' => '8AM - 6PM'],
                        ['label' => 'Morning', 'range' => '10AM - 1PM'],
                        ['label' => 'Afternoon', 'range' => '1PM - 4PM'],
                        ['label' => 'Evening', 'range' => '4PM - 8PM'],
                        ['label' => 'Night', 'range' => '8PM - 11PM']
                    ];
                    foreach ($times as $index => $time):
                    ?>
                        <div class="time-option <?= $index === 0 ? 'selected' : '' ?>" data-time="<?=strtolower($time['label'])?>">
                            <strong><?=$time['label']?></strong><br>
                            <small><?=$time['range']?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="selectedTime" value="anytime">
            </div>
            
            <!-- QUANTITY -->
            <div class="quantity-selector">
                <label style="color:#bfbfbf;font-weight:bold;">Quantity:</label>
                <button class="quantity-btn" type="button" onclick="changeQuantity(-1)">-</button>
                <input class="quantity-input" type="number" id="quantity" value="1" min="1" max="99">
                <button class="quantity-btn" type="button" onclick="changeQuantity(1)">+</button>
                <span style="color:#bfbfbf;margin-left:15px;">In stock: <?=$product['stock'] ?? 10?></span>
            </div>
            
            <!-- TOTAL -->
            <div class="total-display">
                <div style="display:flex;justify-content:space-between;">
                    <span>Total:</span>
                    <span id="totalPrice">₱<?=number_format($product['price'], 2)?></span>
                </div>
            </div>
            
            <!-- ACTION BUTTONS -->
            <div class="action-buttons">
                <form method="POST" class="addCartForm" data-product-id="<?=$product['id']?>">
                    <input type="hidden" name="product_id" value="<?=$product['id']?>">
                    <input type="hidden" name="quantity" id="formQuantity" value="1">
                    <button type="submit" class="action-btn btn-cart">Add to Cart</button>
                </form>
                <button class="action-btn btn-buy" onclick="window.location.href='checkout_final.php'">Buy Now</button>
            </div>
        </div>
    </div>
    
    <!-- RELATED PRODUCTS -->
    <?php
    $related_sql = "SELECT * FROM products WHERE id != $product_id AND is_active = 1 LIMIT 4";
    $related_result = mysqli_query($conn, $related_sql);
    if (mysqli_num_rows($related_result) > 0):
    ?>
        <div class="related-section">
            <h2 class="related-title">Related Products</h2>
            <div class="related-grid">
                <?php while($related = mysqli_fetch_assoc($related_result)): ?>
                    <div class="related-card" onclick="location.href='product_detail.php?id=<?=$related['id']?>'">
                        <img src="<?=htmlspecialchars($related['image'])?>" alt="<?=htmlspecialchars($related['name'])?>">
                        <div class="related-card-content">
                            <h4><?=htmlspecialchars($related['name'])?></h4>
                            <div class="related-price">₱<?=number_format($related['price'], 2)?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<!-- POPUP CONFIRMATION -->
<div id="popup" class="popup-overlay">
    <div class="popup-box">
        <div class="popup-icon">✓</div>
        <h3 class="popup-title">Item Added to Cart!</h3>
        <p class="popup-message">Your item has been successfully added to your shopping cart.</p>
        
        <div class="popup-buttons">
            <button class="popup-btn continue-btn" onclick="closePopup()">Continue Shopping</button>
            <a href="view_cart.php" class="popup-btn checkout-btn">View Cart</a>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="checkout_final.php" style="color:#D4AF37; text-decoration: none;">Proceed to Checkout →</a>
        </div>
    </div>
</div>  

<script>
const productPrice = <?=$product['price']?>;

function changeQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    const formQuantity = document.getElementById('formQuantity');
    const totalPrice = document.getElementById('totalPrice');
    
    let newQuantity = parseInt(quantityInput.value) + change;
    if (newQuantity < 1) newQuantity = 1;
    if (newQuantity > 99) newQuantity = 99;
    
    quantityInput.value = newQuantity;
    formQuantity.value = newQuantity;
    totalPrice.textContent = '₱' + (productPrice * newQuantity).toFixed(2);
}

document.getElementById('quantity').addEventListener('change', function() {
    let quantity = parseInt(this.value);
    if (quantity < 1) quantity = 1;
    if (quantity > 99) quantity = 99;
    
    this.value = quantity;
    document.getElementById('formQuantity').value = quantity;
    document.getElementById('totalPrice').textContent = '₱' + (productPrice * quantity).toFixed(2);
});

document.querySelectorAll('.date-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.date-option').forEach(o => o.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('selectedDate').value = this.dataset.date;
    });
});

document.querySelectorAll('.time-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.time-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('selectedTime').value = this.dataset.time;
    });
});

document.querySelector('.addCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
   
    const selectedDate = document.getElementById('selectedDate').value;
    const selectedTime = document.getElementById('selectedTime').value;
  
    formData.append('delivery_date', selectedDate);
    formData.append('delivery_time', selectedTime);
    
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        if (result === 'success') {
            document.getElementById('popup').style.display = 'flex';
        } else {
            alert('Error adding to cart. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please check your connection.');
    });
});

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

document.getElementById('popup').addEventListener('click', function(e) {
    if (e.target === this) {
        closePopup();
    }
});
</script>

</body>
</html>