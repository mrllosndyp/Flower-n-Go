<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$products = getProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo BRAND_NAME; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><?php echo BRAND_NAME; ?></div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Shop</a>
                <a href="cart.php">Cart (<?php echo count($_SESSION['cart'] ?? []); ?>)</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <h1>Our Flower Collection</h1>
        
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                <form method="POST" action="add_to_cart.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .product-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            background: white;
        }
        
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #8b4513;
            margin: 1rem 0;
        }
        
        .add-to-cart-btn {
            background-color: #e8d0b3;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .add-to-cart-btn:hover {
            background-color: #d4b896;
        }
    </style>
</body>
</html>