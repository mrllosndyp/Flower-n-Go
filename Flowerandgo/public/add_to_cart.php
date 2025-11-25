<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        addToCart($product_id, [], $quantity);
    }
    
    header("Location: products.php");
    exit();
} else {
    redirect('products.php');
}
?>