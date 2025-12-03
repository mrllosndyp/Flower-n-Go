<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form data
$address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
$city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
$postal_code = mysqli_real_escape_string($conn, $_POST['postal_code'] ?? '');
$country = mysqli_real_escape_string($conn, $_POST['country'] ?? '');
$phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cod');
$delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date'] ?? 'today');
$delivery_time = mysqli_real_escape_string($conn, $_POST['delivery_time'] ?? 'anytime');
$instructions = mysqli_real_escape_string($conn, $_POST['instructions'] ?? '');

// Calculate cart total
$cart_total = 0;
$cart_query = "SELECT p.id, p.price, c.quantity 
               FROM shopping_cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_query);

$cart_items = [];
while ($item = mysqli_fetch_assoc($cart_result)) {
    $item_total = $item['price'] * $item['quantity'];
    $cart_total += $item_total;
    $cart_items[] = $item;
}

// Add delivery and service fees
$delivery_fee = 150.00;
$service_fee = 50.00;
$total = $cart_total + $delivery_fee + $service_fee;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert order
    $order_query = "INSERT INTO orders (user_id, status, total, address, city, postal_code, country, phone, payment, delivery_date, delivery_time, instructions) 
                    VALUES ($user_id, 'to_ship', $total, '$address', '$city', '$postal_code', '$country', '$phone', '$payment_method', 
                    NOW() + INTERVAL 1 DAY, '$delivery_time', '$instructions')";
    
    if (!mysqli_query($conn, $order_query)) {
        throw new Exception("Failed to create order: " . mysqli_error($conn));
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Insert order items
    foreach ($cart_items as $item) {
        $item_price = $item['price'];
        $item_quantity = $item['quantity'];
        $item_total = $item_price * $item_quantity;
        
        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                       VALUES ($order_id, {$item['id']}, $item_quantity, $item_price)";
        
        if (!mysqli_query($conn, $item_query)) {
            throw new Exception("Failed to add order item: " . mysqli_error($conn));
        }
    }
    
    // Clear cart
    $clear_cart = "DELETE FROM shopping_cart WHERE user_id = $user_id";
    if (!mysqli_query($conn, $clear_cart)) {
        throw new Exception("Failed to clear cart: " . mysqli_error($conn));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect to success page
    header("Location: order_success.php?id=$order_id");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    die("Error placing order: " . $e->getMessage());
}