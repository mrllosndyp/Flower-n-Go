<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
  header("Location: signin.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$qty = (int)$_POST['quantity'];

// check if exists in cart
$check = $conn->prepare("SELECT * FROM shopping_cart WHERE user_id=? AND product_id=?");
$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$result = $check->get_result();

if($result->num_rows > 0){
  $conn->query("UPDATE shopping_cart 
                SET quantity = quantity + $qty 
                WHERE user_id=$user_id AND product_id=$product_id");
} else {
  $stmt = $conn->prepare("INSERT INTO shopping_cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
  $stmt->bind_param("iii", $user_id, $product_id, $qty);
  $stmt->execute();
}

header("Location: dashboard.php");
exit();
