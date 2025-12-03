<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT name FROM users WHERE id='$user_id'");
$user_row = mysqli_fetch_assoc($user_query);
$user_name = $user_row['name'];

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = $search ? "AND (name LIKE '%$search%' OR description LIKE '%$search%')" : "";

// Fetch active products
$products_sql = "SELECT * FROM products WHERE is_active = 1 $where_clause ORDER BY id DESC";
$products_result = mysqli_query($conn, $products_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Flower 'n GO</title>
<link rel="stylesheet" href="style.css">
<style>
/* DASHBOARD STYLES */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* HEADER */
.dashboard-header {
    background: #121212;
    padding: 15px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 2px solid rgba(212,175,55,0.3);
}

.dashboard-logo {
    font-size: 24px;
    color: #D4AF37;
    font-weight: bold;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 500px;
    margin: 0 40px;
}

.search-box input {
    width: 100%;
    padding: 12px 20px;
    padding-left: 45px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(212,175,55,0.3);
    border-radius: 25px;
    color: white;
    font-size: 15px;
}

.search-box input:focus {
    outline: none;
    border-color: #D4AF37;
    box-shadow: 0 0 10px rgba(212,175,55,0.2);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #D4AF37;
}

.user-nav {
    display: flex;
    align-items: center;
    gap: 25px;
}

.user-nav a {
    color: white;
    text-decoration: none;
    font-size: 15px;
    transition: 0.3s;
}

.user-nav a:hover {
    color: #D4AF37;
}

.logout-btn {
    background: #D4AF37;
    color: #000;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: bold;
    text-decoration: none;
}

.logout-btn:hover {
    background: #ffdc73;
}

/* HERO SECTION */
.dashboard-hero {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(to right, #1b120d, #0d0a08);
    border-radius: 15px;
    margin: 30px 0;
    border: 1px solid rgba(212,175,55,0.2);
}

.dashboard-hero h1 {
    font-size: 36px;
    color: #D4AF37;
    margin-bottom: 15px;
}

.dashboard-hero p {
    color: #bfbfbf;
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto;
}

/* PRODUCTS SECTION */
.products-section {
    margin: 40px 0;
}

.section-title {
    font-size: 28px;
    color: #fff;
    margin-bottom: 30px;
    padding-left: 15px;
    border-left: 4px solid #D4AF37;
}

/* PRODUCT GRID */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

.product-card {
    background: #1b120d;
    border-radius: 15px;
    overflow: hidden;
    transition: 0.3s;
    border: 1px solid rgba(212,175,55,0.1);
    position: relative;
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(212,175,55,0.2);
    border-color: #D4AF37;
}

/* DISCOUNT BADGE */
.discount-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #d32f2f;
    color: white;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
    z-index: 2;
}

.product-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    transition: 0.5s;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-info {
    padding: 20px;
}

.product-name {
    color: #fff;
    font-size: 18px;
    margin-bottom: 10px;
    height: 50px;
    overflow: hidden;
}

.product-description {
    color: #bfbfbf;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 15px;
    height: 60px;
    overflow: hidden;
}

/* PRICE SECTION */
.price-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}

.current-price {
    font-size: 22px;
    color: #D4AF37;
    font-weight: bold;
}

.original-price {
    color: #888;
    text-decoration: line-through;
    font-size: 16px;
}

/* RATING */
.product-rating {
    color: #D4AF37;
    margin-bottom: 15px;
    font-size: 14px;
}

/* ADD TO CART FORM */
.add-cart-form {
    margin-top: 15px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 15px;
}

.qty-btn {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #D4AF37;
    border: none;
    color: #000;
    font-size: 18px;
    cursor: pointer;
    transition: 0.3s;
}

.qty-btn:hover {
    background: #ffdc73;
}

.qty-input {
    width: 60px;
    text-align: center;
    background: transparent;
    border: 1px solid rgba(212,175,55,0.3);
    color: white;
    padding: 8px;
    border-radius: 8px;
}

.add-to-cart-btn {
    width: 100%;
    background: #D4AF37;
    color: #000;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

.add-to-cart-btn:hover {
    background: #ffdc73;
    box-shadow: 0 0 15px rgba(212,175,55,0.3);
}

/* POPUP CONFIRMATION */
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

/* FOOTER */
.dashboard-footer {
    text-align: center;
    background: #1b120d;
    color: #bfbfbf;
    padding: 25px;
    margin-top: 60px;
    border-top: 1px solid rgba(212,175,55,0.2);
}

/* BACK BUTTON STYLE */
.back-btn {
    background: #D4AF37;
    color: #000;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    margin: 10px 0;
    transition: 0.3s;
}

.back-btn:hover {
    background: #ffdc73;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 900px) {
    .dashboard-header {
        flex-direction: column;
        gap: 20px;
        padding: 20px;
    }
    
    .search-box {
        margin: 0;
        max-width: 100%;
    }
    
    .user-nav {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
        gap: 15px;
    }
}

@media (max-width: 600px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .popup-buttons {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="dashboard-header">
    <div class="dashboard-logo">🌸 Flower 'n GO</div>
    
    <form method="GET" action="dashboard.php" class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Search flowers...">
    </form>
    
    <div class="user-nav">
        <span style="color:#D4AF37">Hello, <strong><?=htmlspecialchars($user_name)?></strong></span>
        <a href="profile.php">Profile</a>
        <a href="view_cart.php">Cart</a>
        <a href="orders.php">Orders</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<div class="dashboard-container">
    <!-- HERO -->
    <section class="dashboard-hero">
        <h1>Welcome to Flower 'n GO</h1>
        <p>Discover beautiful handcrafted flower arrangements for every occasion. Customize your bouquet today!</p>
        <?php if($search): ?>
            <p style="color:#D4AF37;margin-top:10px;">Search results for: "<?=htmlspecialchars($search)?>"</p>
        <?php endif; ?>
    </section>
    
    <!-- PRODUCTS -->
    <section class="products-section">
        <h2 class="section-title">Available Flowers</h2>
        
        <div class="products-grid">
            <?php while($product = mysqli_fetch_assoc($products_result)): 
                // Calculate discount display
                $original_price = $product['price'] * 1.25;
                $discount_percent = 20;
            ?>
                <!-- CLICKABLE PRODUCT CARD -->
                <div class="product-card" onclick="window.location.href='product_detail.php?id=<?=$product['id']?>'">
                    <div class="discount-badge">Save <?=$discount_percent?>%</div>
                    <img src="<?=htmlspecialchars($product['image'])?>" alt="<?=htmlspecialchars($product['name'])?>" class="product-image">
                    
                    <div class="product-info">
                        <h3 class="product-name"><?=htmlspecialchars($product['name'])?></h3>
                        <p class="product-description"><?=htmlspecialchars(substr($product['description'], 0, 100))?>...</p>
                        
                        <div class="price-section">
                            <span class="current-price">₱<?=number_format($product['price'], 2)?></span>
                            <span class="original-price">₱<?=number_format($original_price, 2)?></span>
                        </div>
                        
                        <div class="product-rating">★★★★★ 4.8</div>
                        
                        <!-- ADD TO CART FORM (STOPS PROPAGATION) -->
                        <form class="add-cart-form" data-product-id="<?=$product['id']?>" onclick="event.stopPropagation()">
                            <input type="hidden" name="product_id" value="<?=$product['id']?>">
                            
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn" onclick="event.stopPropagation(); changeQty(this, -1)">-</button>
                                <input type="number" name="quantity" value="1" min="1" class="qty-input" onclick="event.stopPropagation()">
                                <button type="button" class="qty-btn" onclick="event.stopPropagation(); changeQty(this, 1)">+</button>
                            </div>
                            
                            <button type="submit" class="add-to-cart-btn" onclick="event.stopPropagation()">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
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

<!-- FOOTER -->
<footer class="dashboard-footer">
    © 2025 Flower 'n GO — All Rights Reserved
</footer>

<script>
// Change quantity in product cards
function changeQty(btn, change) {
    const form = btn.closest('.add-cart-form');
    const input = form.querySelector('.qty-input');
    let newVal = parseInt(input.value) + change;
    if (newVal < 1) newVal = 1;
    input.value = newVal;
}

// Add to cart with AJAX
document.querySelectorAll('.add-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
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
});

// Close popup
function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

// Close popup when clicking outside
document.getElementById('popup').addEventListener('click', function(e) {
    if (e.target === this) {
        closePopup();
    }
});
</script>
</body>
</html>