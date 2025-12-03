<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Fetch cart items
$cart_sql = "SELECT p.id, p.name, p.price, c.quantity, 
                   (p.price * c.quantity) as subtotal
            FROM shopping_cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_sql);

$cart_items = [];
$subtotal = 0;
while ($item = mysqli_fetch_assoc($cart_result)) {
    $cart_items[] = $item;
    $subtotal += $item['subtotal'];
}

// Calculate totals
$delivery_fee = 150.00;
$service_fee = 50.00;
$total = $subtotal + $delivery_fee + $service_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | Flower 'n GO</title>
<link rel="stylesheet" href="style.css">
<style>
/* CHECKOUT PAGE STYLES */
.checkout-page {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.checkout-header {
    color: #D4AF37;
    text-align: center;
    margin-bottom: 40px;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
}

/* LEFT COLUMN - FORM */
.checkout-form {
    background: #1b120d;
    border-radius: 15px;
    padding: 30px;
    border: 1px solid rgba(212,175,55,0.2);
}

.section-title {
    color: #D4AF37;
    font-size: 22px;
    margin: 25px 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(212,175,55,0.2);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #bfbfbf;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.2);
    border-radius: 8px;
    color: white;
    font-size: 15px;
    transition: 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #D4AF37;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* DELIVERY OPTIONS */
.delivery-options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 15px 0;
}

.delivery-option {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.2);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
}

.delivery-option:hover {
    background: rgba(212,175,55,0.1);
}

.delivery-option.active {
    background: #D4AF37;
    color: #000;
    font-weight: bold;
}

/* TIME SLOTS */
.time-slots {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin: 15px 0;
}

.time-slot {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.2);
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
}

.time-slot:hover {
    background: rgba(212,175,55,0.1);
}

.time-slot.selected {
    background: #D4AF37;
    color: #000;
    font-weight: bold;
}

/* PAYMENT METHODS */
.payment-methods {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.payment-method {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.2);
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
}

.payment-method:hover {
    background: rgba(212,175,55,0.1);
}

.payment-method.active {
    background: #D4AF37;
    color: #000;
    font-weight: bold;
}

.payment-icon {
    font-size: 28px;
    margin-bottom: 10px;
}

/* RIGHT COLUMN - ORDER SUMMARY */
.order-summary {
    background: #1b120d;
    border-radius: 15px;
    padding: 30px;
    border: 1px solid rgba(212,175,55,0.2);
    position: sticky;
    top: 20px;
    height: fit-content;
}

.order-items {
    max-height: 300px;
    overflow-y: auto;
    margin: 20px 0;
    padding-right: 10px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.order-item-name {
    color: #bfbfbf;
    font-size: 15px;
}

.order-item-price {
    color: #D4AF37;
    font-weight: bold;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.summary-total {
    display: flex;
    justify-content: space-between;
    padding: 20px 0;
    margin-top: 15px;
    border-top: 2px solid rgba(212,175,55,0.3);
    font-size: 22px;
    color: #D4AF37;
    font-weight: bold;
}

/* BUTTONS */
.checkout-btn {
    display: block;
    width: 100%;
    background: #D4AF37;
    color: #000;
    padding: 16px;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 20px;
    text-align: center;
    text-decoration: none;
}

.checkout-btn:hover {
    background: #ffdc73;
    box-shadow: 0 0 20px rgba(212,175,55,0.5);
}

.continue-link {
    display: block;
    text-align: center;
    color: #D4AF37;
    margin-top: 20px;
    text-decoration: none;
}

.continue-link:hover {
    text-decoration: underline;
}

@media (max-width: 900px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
    
    .delivery-options {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .time-slots {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .delivery-options,
    .time-slots,
    .payment-methods {
        grid-template-columns: 1fr;
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

<div class="checkout-page">
    <h1 class="checkout-header">Checkout</h1>
    
    <div class="checkout-grid">
        <!-- LEFT: FORM -->
        <div class="checkout-form">
            <form action="place_order.php" method="POST" id="checkoutForm">
                <!-- BILLING INFORMATION -->
                <h3 class="section-title">Billing Information</h3>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?=htmlspecialchars($user['name'])?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?=htmlspecialchars($user['email'])?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?=htmlspecialchars($user['phone'])?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="4" required><?=htmlspecialchars($user['address'])?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?=htmlspecialchars($user['city'])?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" value="<?=htmlspecialchars($user['postal_code'])?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" value="<?=htmlspecialchars($user['country'])?>" required>
                </div>
                
                <!-- DELIVERY DATE -->
                <h3 class="section-title">Select Delivery Date</h3>
                <div class="delivery-options">
                    <div class="delivery-option active" data-date="today">
                        <strong>Today</strong><br>
                        <small><?=date('M d')?></small>
                    </div>
                    <div class="delivery-option" data-date="tomorrow">
                        <strong>Tomorrow</strong><br>
                        <small><?=date('M d', strtotime('+1 day'))?></small>
                    </div>
                    <div class="delivery-option" data-date="friday">
                        <strong>This Friday</strong><br>
                        <small><?=date('M d', strtotime('next friday'))?></small>
                    </div>
                    <div class="delivery-option" data-date="saturday">
                        <strong>This Saturday</strong><br>
                        <small><?=date('M d', strtotime('next saturday'))?></small>
                    </div>
                </div>
                <input type="hidden" name="delivery_date" id="deliveryDate" value="today">
                
                <!-- DELIVERY TIME -->
                <h3 class="section-title">Select Delivery Time</h3>
                <div class="time-slots">
                    <div class="time-slot selected" data-time="anytime">Anytime<br><small>8AM - 6PM</small></div>
                    <div class="time-slot" data-time="10am-1pm">Morning<br><small>10AM - 1PM</small></div>
                    <div class="time-slot" data-time="1pm-4pm">Afternoon<br><small>1PM - 4PM</small></div>
                    <div class="time-slot" data-time="4pm-6pm">Evening<br><small>4PM - 6PM</small></div>
                    <div class="time-slot" data-time="8pm-11pm">Night<br><small>8PM - 11PM</small></div>
                </div>
                <input type="hidden" name="delivery_time" id="deliveryTime" value="anytime">
                
                <!-- PAYMENT METHOD -->
                <h3 class="section-title">Payment Method</h3>
                <div class="payment-methods">
                    <div class="payment-method active" data-method="cod">
                        <div class="payment-icon">💵</div>
                        <div>Cash on Delivery</div>
                    </div>
                    <div class="payment-method" data-method="gcash">
                        <div class="payment-icon">📱</div>
                        <div>GCash</div>
                    </div>
                    <div class="payment-method" data-method="card">
                        <div class="payment-icon">💳</div>
                        <div>Credit/Debit Card</div>
                    </div>
                    <div class="payment-method" data-method="bank">
                        <div class="payment-icon">🏦</div>
                        <div>Bank Transfer</div>
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="paymentMethod" value="cod">
                
                <!-- SPECIAL INSTRUCTIONS -->
                <div class="form-group">
                    <label>Special Instructions (Optional)</label>
                    <textarea name="instructions" rows="3" placeholder="Add delivery notes or gift message"></textarea>
                </div>
            </form>
        </div>
        
        <!-- RIGHT: ORDER SUMMARY -->
        <div class="order-summary">
            <h3 style="color:#D4AF37; margin-bottom: 20px;">Order Summary</h3>
            
            <div class="order-items">
                <?php if(empty($cart_items)): ?>
                    <p style="text-align:center; color:#bfbfbf;">Your cart is empty</p>
                <?php else: ?>
                    <?php foreach($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="order-item-name">
                                <?=htmlspecialchars($item['name'])?> × <?=$item['quantity']?>
                            </div>
                            <div class="order-item-price">
                                ₱<?=number_format($item['subtotal'], 2)?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="summary-row">
                <span>Subtotal</span>
                <span>₱<?=number_format($subtotal, 2)?></span>
            </div>
            <div class="summary-row">
                <span>Delivery Fee</span>
                <span>₱<?=number_format($delivery_fee, 2)?></span>
            </div>
            <div class="summary-row">
                <span>Service Fee</span>
                <span>₱<?=number_format($service_fee, 2)?></span>
            </div>
            
            <div class="summary-total">
                <span>Total</span>
                <span>₱<?=number_format($total, 2)?></span>
            </div>
            
            <button type="submit" form="checkoutForm" class="checkout-btn">
                Place Order Now
            </button>
            
            <a href="view_cart.php" class="continue-link">← Back to Cart</a>
        </div>
    </div>
</div>

<script>
// Delivery date selection
document.querySelectorAll('.delivery-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.delivery-option').forEach(o => o.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('deliveryDate').value = this.dataset.date;
    });
});

// Delivery time selection
document.querySelectorAll('.time-slot').forEach(slot => {
    slot.addEventListener('click', function() {
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('deliveryTime').value = this.dataset.time;
    });
});

// Payment method selection
document.querySelectorAll('.payment-method').forEach(method => {
    method.addEventListener('click', function() {
        document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('paymentMethod').value = this.dataset.method;
    });
});
</script>

</body>
</html>