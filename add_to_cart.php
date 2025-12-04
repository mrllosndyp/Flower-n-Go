<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("error: Please login first");
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validate inputs
if ($product_id <= 0) {
    die("error: Invalid product");
}

if ($quantity <= 0) {
    die("error: Invalid quantity");
}

// Check if product exists
$check_product = mysqli_query($conn, "SELECT id, stock FROM products WHERE id = $product_id AND is_active = 1");
if (mysqli_num_rows($check_product) == 0) {
    die("error: Product not available");
}

$product = mysqli_fetch_assoc($check_product);

// Check stock
if ($product['stock'] > 0 && $quantity > $product['stock']) {
    die("error: Only " . $product['stock'] . " items available");
}

// Check if already in cart
$check_cart = mysqli_query($conn, "SELECT id, quantity FROM shopping_cart WHERE user_id = $user_id AND product_id = $product_id");

if (mysqli_num_rows($check_cart) > 0) {
    // Update existing
    $cart_item = mysqli_fetch_assoc($check_cart);
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    // Check stock again for update
    if ($product['stock'] > 0 && $new_quantity > $product['stock']) {
        die("error: Only " . $product['stock'] . " items available in total");
    }
    
    mysqli_query($conn, "UPDATE shopping_cart SET quantity = $new_quantity WHERE id = {$cart_item['id']}");
} else {
    // Insert new
    mysqli_query($conn, "INSERT INTO shopping_cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
}

echo "success";

