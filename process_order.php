<?php
session_start();
include "db.php";

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['direct_order'])) {
    $order = $_SESSION['direct_order'];
    
    // Get customer info
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $zip = mysqli_real_escape_string($conn, $_POST['zip']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // Insert order into database
    $stmt = mysqli_prepare($conn, "INSERT INTO orders (customer_name, email, phone, address, city, zip, payment_method, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($stmt, "sssssssd", $name, $email, $phone, $address, $city, $zip, $payment_method, $order['total']);
    
    if(mysqli_stmt_execute($stmt)) {
        $order_id = mysqli_insert_id($conn);
        
        // Insert order item
        $stmt2 = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "iiid", $order_id, $order['product_id'], $order['quantity'], $order['price']);
        mysqli_stmt_execute($stmt2);
        
        // Update product stock
        $stmt3 = mysqli_prepare($conn, "UPDATE products SET stock = stock - ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt3, "ii", $order['quantity'], $order['product_id']);
        mysqli_stmt_execute($stmt3);
        
        // Clear session
        unset($_SESSION['direct_order']);
        
        // Show success message
        echo "<h2>Order Placed Successfully!</h2>";
        echo "<p>Order ID: #$order_id</p>";
        echo "<p>We'll contact you soon for delivery details.</p>";
        echo '<a href="index.php">Continue Shopping</a>';
    } else {
        echo "Error placing order. Please try again.";
    }
} else {
    header("Location: index.php");
    exit();
}
?>