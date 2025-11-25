<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if (empty($_SESSION['cart'])) {
    redirect('cart.php');
}

$user = getUserInfo();
$cart_items = getCartItems();
$total_amount = getCartTotal();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = $_POST['shipping_address'];
    $city = $_POST['city'];
    $postal_code = $_POST['postal_code'];
    $country = $_POST['country'];
    $notes = $_POST['notes'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? null;
    
    $order_id = createOrder($user['id'], $shipping_address, $city, $postal_code, $country, $notes, $delivery_date);
    
    if ($order_id) {
        $success = "Order placed successfully! Order ID: $order_id";
    } else {
        $error = "Failed to place order";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo BRAND_NAME; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><?php echo BRAND_NAME; ?></div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Shop</a>
                <a href="cart.php">Cart</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <h1>Checkout</h1>
        
        <?php if (isset($success)): ?>
            <div style="color: green; margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
            <a href="index.php" class="login-btn">Continue Shopping</a>
        <?php else: ?>
            <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="checkout-container">
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <p><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></p>
                            <p>$<?php echo number_format($item['total_price'], 2); ?></p>
                        </div>
                    <?php endforeach; ?>
                    <div class="total">
                        <strong>Total: $<?php echo number_format($total_amount, 2); ?></strong>
                    </div>
                </div>
                
                <form method="POST" action="checkout.php" class="checkout-form">
                    <h3>Shipping Information</h3>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="shipping_address">Address</label>
                            <input type="text" id="shipping_address" name="shipping_address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="postal_code">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country'] ?? 'USA'); ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="delivery_date">Delivery Date (Optional)</label>
                            <input type="date" id="delivery_date" name="delivery_date">
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="notes">Special Instructions (Optional)</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="login-btn">Place Order</button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <style>
        .checkout-container {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .cart-summary {
            flex: 1;
            background: #f9f4e8;
            padding: 1rem;
            border-radius: 10px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e8d0b3;
        }
        
        .checkout-form {
            flex: 2;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row .input-group {
            flex: 1;
        }
        
        .input-group input,
        .input-group textarea {
            width: 100%;
        }
    </style>
</body>
</html>