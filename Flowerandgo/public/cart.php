<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['remove'])) {
        removeFromCart($_POST['cart_key']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo BRAND_NAME; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><?php echo BRAND_NAME; ?></div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Shop</a>
                <a href="index.php#account">Account</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <h1>Your Shopping Cart</h1>
        
        <?php
        $cart_items = getCartItems();
        if (empty($cart_items)):
        ?>
            <p>Your cart is empty.</p>
            <a href="products.php">Continue Shopping</a>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="cart_key" value="<?php echo $item['cart_key']; ?>">
                                <button type="submit" name="remove" class="remove-btn">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total:</strong></td>
                        <td><strong>$<?php echo number_format(getCartTotal(), 2); ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="cart-actions">
                <a href="products.php" class="continue-btn">Continue Shopping</a>
                <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </main>

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .remove-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .cart-actions {
            margin: 2rem 0;
            display: flex;
            gap: 1rem;
        }
        
        .continue-btn, .checkout-btn {
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .continue-btn {
            background-color: #6c757d;
            color: white;
        }
        
        .checkout-btn {
            background-color: #28a745;
            color: white;
        }
    </style>
</body>
</html>