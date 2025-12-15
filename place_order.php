<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from session
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        $_SESSION['error'] = "Please login to place an order";
        header("Location: place_order.php");
        exit();
    }

    // Get form data
    $customer_name = $_POST['customer_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $country = $_POST['country'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? '';
    $delivery_time = $_POST['delivery_time'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $payment = $payment_method; // Map payment_method to payment column

    // Calculate total from cart
    $total_amount = 0;
    $cart_items = [];
    
    // Get cart items for this user
    $cart_query = "SELECT sc.*, p.price, p.name 
                   FROM shopping_cart sc 
                   JOIN products p ON sc.product_id = p.id 
                   WHERE sc.user_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    while ($item = $cart_result->fetch_assoc()) {
        $item_total = $item['price'] * $item['quantity'];
        $total_amount += $item_total;
        $cart_items[] = $item;
    }
    $stmt->close();

    if ($total_amount == 0) {
        $_SESSION['error'] = "Your cart is empty";
        header("Location: cart.php");
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert order - FIXED: Using correct column name 'total_amount' not 'total'
        $order_sql = "INSERT INTO orders (
            user_id, customer_name, phone, address, total_amount, 
            payment_method, payment, status, city, postal_code, 
            country, delivery_date, delivery_time, instructions
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param(
            "isssdssssssss", 
            $user_id, 
            $customer_name, 
            $phone, 
            $address, 
            $total_amount,
            $payment_method, 
            $payment, 
            $city, 
            $postal_code, 
            $country,
            $delivery_date, 
            $delivery_time, 
            $instructions
        );
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // Insert order items
        $item_sql = "INSERT INTO order_items (order_id, product_id, customization_ids, quantity, price, total_price, product_name) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($item_sql);
        
        foreach ($cart_items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $stmt->bind_param(
                "iisiids",
                $order_id,
                $item['product_id'],
                $item['customization_ids'],
                $item['quantity'],
                $item['price'],
                $item_total,
                $item['name']
            );
            $stmt->execute();
        }
        $stmt->close();

        // Clear cart
        $clear_cart = "DELETE FROM shopping_cart WHERE user_id = ?";
        $stmt = $conn->prepare($clear_cart);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Success
        $_SESSION['success'] = "Order placed successfully! Order ID: #$order_id";
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Order placement error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to place order: " . $e->getMessage();
        header("Location: checkout.php");
        exit();
    }
} else {
    header("Location: order_success.php");
    exit();
}
?>