<?php
require_once 'config.php';

// Function to register a new user with email verification
function registerUser($name, $email, $password, $phone = null, $address = null) {
    global $connection;
    
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE email = '$email'";
    $result = $connection->query($check_query);
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    
    // Insert new user
    $insert_query = "INSERT INTO users (name, email, password, phone, address, email_verification_token) 
                     VALUES ('$name', '$email', '$hashed_password', '$phone', '$address', '$verification_token')";
    
    if ($connection->query($insert_query)) {
        // Send verification email (you would implement actual email sending here)
        // sendVerificationEmail($email, $verification_token);
        return ['success' => true, 'message' => 'Registration successful! Please check your email to verify your account.'];
    } else {
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

// Function to verify email
function verifyEmail($token) {
    global $connection;
    
    $token = sanitize($token);
    $query = "UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE email_verification_token = '$token'";
    
    if ($connection->query($query)) {
        return $connection->affected_rows > 0;
    }
    return false;
}

// Function to login user
function loginUser($email, $password) {
    global $connection;
    
    $email = sanitize($email);
    
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $connection->query($query);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            if (!$user['email_verified']) {
                return ['success' => false, 'message' => 'Please verify your email address'];
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid password'];
        }
    }
    return ['success' => false, 'message' => 'Invalid email'];
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get user info
function getUserInfo() {
    if (isLoggedIn()) {
        global $connection;
        $user_id = $_SESSION['user_id'];
        $query = "SELECT * FROM users WHERE id = $user_id";
        $result = $connection->query($query);
        return $result->fetch_assoc();
    }
    return false;
}

// Function to update user profile
function updateUserProfile($user_id, $name, $email, $phone = null, $address = null, $city = null, $postal_code = null, $country = null) {
    global $connection;
    
    $user_id = (int)$user_id;
    $name = sanitize($name);
    $email = sanitize($email);
    
    $query = "UPDATE users SET name = '$name', email = '$email'";
    
    if ($phone) $query .= ", phone = '" . sanitize($phone) . "'";
    if ($address) $query .= ", address = '" . sanitize($address) . "'";
    if ($city) $query .= ", city = '" . sanitize($city) . "'";
    if ($postal_code) $query .= ", postal_code = '" . sanitize($postal_code) . "'";
    if ($country) $query .= ", country = '" . sanitize($country) . "'";
    
    $query .= " WHERE id = $user_id";
    
    return $connection->query($query);
}

// Function to get all categories
function getCategories() {
    global $connection;
    $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
    $result = $connection->query($query);
    $categories = array();
    
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Function to get products by category
function getProductsByCategory($category_id) {
    global $connection;
    $category_id = (int)$category_id;
    $query = "SELECT * FROM products WHERE category_id = $category_id AND is_active = 1 ORDER BY name";
    $result = $connection->query($query);
    $products = array();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Function to get all products
function getProducts() {
    global $connection;
    $query = "SELECT p.*, c.name as category_name FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.is_active = 1 ORDER BY p.id DESC";
    $result = $connection->query($query);
    $products = array();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Function to get product by ID
function getProductById($id) {
    global $connection;
    $id = (int)$id;
    $query = "SELECT p.*, c.name as category_name FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = $id AND p.is_active = 1";
    $result = $connection->query($query);
    return $result->fetch_assoc();
}

// Function to get customizations by type
function getCustomizationsByType($type) {
    global $connection;
    $type = sanitize($type);
    $query = "SELECT * FROM customizations WHERE type = '$type' AND is_active = 1 ORDER BY name";
    $result = $connection->query($query);
    $customizations = array();
    
    while ($row = $result->fetch_assoc()) {
        $customizations[] = $row;
    }
    
    return $customizations;
}

// Function to add to cart
function addToCart($product_id, $customization_ids = [], $quantity = 1) {
    global $connection;
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    $product_id = (int)$product_id;
    $customization_ids_json = json_encode($customization_ids);
    
    $cart_key = $product_id . '_' . $customization_ids_json;
    
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cart_key] = array(
            'product_id' => $product_id,
            'customization_ids' => $customization_ids,
            'quantity' => $quantity,
            'added_at' => time()
        );
    }
}

// Function to get cart items
function getCartItems() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return array();
    }
    
    global $connection;
    $cart_items = array();
    
    foreach ($_SESSION['cart'] as $cart_key => $cart_item) {
        $product = getProductById($cart_item['product_id']);
        if ($product) {
            $product['quantity'] = $cart_item['quantity'];
            $product['customization_ids'] = $cart_item['customization_ids'];
            $product['cart_key'] = $cart_key;
            
            // Calculate total price including customizations
            $total_price = $product['price'] * $product['quantity'];
            
            foreach ($cart_item['customization_ids'] as $custom_id) {
                $custom = getCustomizationById($custom_id);
                if ($custom) {
                    $total_price += $custom['price'] * $product['quantity'];
                }
            }
            
            $product['total_price'] = $total_price;
            $cart_items[] = $product;
        }
    }
    
    return $cart_items;
}

// Function to get customization by ID
function getCustomizationById($id) {
    global $connection;
    $id = (int)$id;
    $query = "SELECT * FROM customizations WHERE id = $id AND is_active = 1";
    $result = $connection->query($query);
    return $result->fetch_assoc();
}

// Function to remove from cart
function removeFromCart($cart_key) {
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
    }
}

// Function to clear cart
function clearCart() {
    unset($_SESSION['cart']);
}

// Function to get cart total
function getCartTotal() {
    $total = 0;
    $cart_items = getCartItems();
    
    foreach ($cart_items as $item) {
        $total += $item['total_price'];
    }
    
    return $total;
}

// Function to create order
function createOrder($user_id, $shipping_address, $city, $postal_code, $country, $notes = null, $delivery_date = null) {
    global $connection;
    
    $user_id = (int)$user_id;
    $total_amount = getCartTotal();
    
    $shipping_address = sanitize($shipping_address);
    $city = sanitize($city);
    $postal_code = sanitize($postal_code);
    $country = sanitize($country);
    if ($notes) $notes = sanitize($notes);
    
    $query = "INSERT INTO orders (user_id, total_amount, shipping_address, city, postal_code, country, notes, delivery_date) 
              VALUES ($user_id, $total_amount, '$shipping_address', '$city', '$postal_code', '$country', '$notes', '$delivery_date')";
    
    if ($connection->query($query)) {
        $order_id = $connection->insert_id;
        
        // Add order items
        $cart_items = getCartItems();
        foreach ($cart_items as $item) {
            $customization_ids_json = json_encode($item['customization_ids']);
            $price = $item['price'];
            $quantity = $item['quantity'];
            $total_price = $item['total_price'] / $quantity; // Price per item including customizations
            
            $insert_item = "INSERT INTO order_items (order_id, product_id, customization_ids, quantity, price, total_price) 
                           VALUES ($order_id, {$item['id']}, '$customization_ids_json', $quantity, $price, $total_price)";
            $connection->query($insert_item);
        }
        
        // Clear cart after order
        clearCart();
        
        return $order_id;
    }
    
    return false;
}

// Function to get user orders
function getUserOrders($user_id) {
    global $connection;
    $user_id = (int)$user_id;
    $query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC";
    $result = $connection->query($query);
    $orders = array();
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}

// Function to get order details
function getOrderDetails($order_id) {
    global $connection;
    $order_id = (int)$order_id;
    
    $query = "SELECT o.*, oi.*, p.name as product_name, p.image as product_image 
              FROM orders o 
              JOIN order_items oi ON o.id = oi.order_id 
              JOIN products p ON oi.product_id = p.id 
              WHERE o.id = $order_id";
    
    $result = $connection->query($query);
    $items = array();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

// Function to get user wishlist
function getUserWishlist($user_id) {
    global $connection;
    $user_id = (int)$user_id;
    
    $query = "SELECT w.*, p.* FROM wishlists w 
              JOIN products p ON w.product_id = p.id 
              WHERE w.user_id = $user_id";
    
    $result = $connection->query($query);
    $wishlist = array();
    
    while ($row = $result->fetch_assoc()) {
        $wishlist[] = $row;
    }
    
    return $wishlist;
}

// Function to add to wishlist
function addToWishlist($user_id, $product_id) {
    global $connection;
    
    $user_id = (int)$user_id;
    $product_id = (int)$product_id;
    
    $check_query = "SELECT id FROM wishlists WHERE user_id = $user_id AND product_id = $product_id";
    $result = $connection->query($check_query);
    
    if ($result->num_rows == 0) {
        $query = "INSERT INTO wishlists (user_id, product_id) VALUES ($user_id, $product_id)";
        return $connection->query($query);
    }
    
    return false; // Already in wishlist
}

// Function to remove from wishlist
function removeFromWishlist($user_id, $product_id) {
    global $connection;
    
    $user_id = (int)$user_id;
    $product_id = (int)$product_id;
    
    $query = "DELETE FROM wishlists WHERE user_id = $user_id AND product_id = $product_id";
    return $connection->query($query);
}

// Function to get related products
function getRelatedProducts($product_id, $category_id) {
    global $connection;
    $product_id = (int)$product_id;
    $category_id = (int)$category_id;
    
    $query = "SELECT * FROM products WHERE category_id = $category_id AND id != $product_id AND is_active = 1 LIMIT 4";
    $result = $connection->query($query);
    $products = array();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Function to add review
function addReview($user_id, $product_id, $rating, $comment) {
    global $connection;
    
    $user_id = (int)$user_id;
    $product_id = (int)$product_id;
    $rating = (int)$rating;
    $comment = sanitize($comment);
    
    $query = "INSERT INTO reviews (user_id, product_id, rating, comment) VALUES ($user_id, $product_id, $rating, '$comment')";
    return $connection->query($query);
}

// Function to get product reviews
function getProductReviews($product_id) {
    global $connection;
    $product_id = (int)$product_id;
    
    $query = "SELECT r.*, u.name as user_name FROM reviews r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.product_id = $product_id ORDER BY r.created_at DESC";
    
    $result = $connection->query($query);
    $reviews = array();
    
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    return $reviews;
}

// Function to get average rating
function getAverageRating($product_id) {
    global $connection;
    $product_id = (int)$product_id;
    
    $query = "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = $product_id";
    $result = $connection->query($query);
    $row = $result->fetch_assoc();
    
    return round($row['avg_rating'], 1);
}
?>