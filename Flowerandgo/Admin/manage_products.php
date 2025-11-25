<?php
require_once '../config.php';
require_once '../functions.php';

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('admin_login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = (int)$_POST['category_id'];
        $stock = (int)$_POST['stock'];
        
        // Handle image upload (simplified - you can enhance this)
        $image = sanitize($_POST['image_url']);
        
        $query = "INSERT INTO products (name, description, price, image, category_id, stock) 
                  VALUES ('$name', '$description', $price, '$image', $category_id, $stock)";
        
        if ($connection->query($query)) {
            $success = "Product added successfully!";
        } else {
            $error = "Error adding product: " . $connection->error;
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $product_id = (int)$_POST['product_id'];
        $query = "DELETE FROM products WHERE id = $product_id";
        if ($connection->query($query)) {
            $delete_success = "Product deleted successfully!";
        }
    }
}

// Get all products and categories
$products = getProducts();
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Flower n' Go</title>
    <style>
        /* Same header/sidebar styles as dashboard */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f4e8;
            color: #5a3921;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #8b4513, #5a3921);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .admin-header a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid white;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .admin-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        .admin-sidebar {
            width: 250px;
            background: white;
            padding: 2rem 0;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar a {
            display: block;
            padding: 1rem 2rem;
            color: #5a3921;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .admin-sidebar a:hover {
            background: #f0e6d2;
            border-left: 4px solid #8b4513;
            color: #8b4513;
        }
        
        .admin-main {
            flex: 1;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .add-product-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e8d0b3;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8b4513, #5a3921);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .products-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .product-item {
            display: flex;
            padding: 1.5rem;
            border-bottom: 1px solid #e8d0b3;
            align-items: center;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 1.5rem;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-info h3 {
            margin-bottom: 0.5rem;
            color: #8b4513;
        }
        
        .product-price {
            font-weight: bold;
            color: #5a3921;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="logo">Flower n' Go Admin</div>
        <a href="../logout.php">Logout</a>
    </header>
    
    <div class="admin-container">
        <aside class="admin-sidebar">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_products.php" class="active">Manage Products</a>
            <a href="manage_orders.php">Manage Orders</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_customizations.php">Manage Customizations</a>
        </aside>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Manage Products</h1>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($delete_success)): ?>
                <div class="message success"><?php echo htmlspecialchars($delete_success); ?></div>
            <?php endif; ?>
            
            <div class="add-product-form">
                <h2>Add New Product</h2>
                <form method="POST" action="manage_products.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" id="stock" name="stock" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image_url">Image URL (or upload path)</label>
                        <input type="text" id="image_url" name="image_url" placeholder="https://example.com/image.jpg" required>
                    </div>
                    
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </form>
            </div>
            
            <div class="products-list">
                <h2>Existing Products</h2>
                <?php foreach ($products as $product): ?>
                <div class="product-item">
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['stock']; ?>)</p>
                    </div>
                    <div class="product-actions">
                        <form method="POST" action="manage_products.php" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" name="delete_product" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this product?')">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>