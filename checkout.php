<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
  header("Location: signin.php");
  exit();
}

$user = $_SESSION['user_id'];

// calculate total
$total = 0;
$cart = mysqli_query($conn, "SELECT * FROM shopping_cart WHERE user_id=$user");

while($c = mysqli_fetch_assoc($cart)){
  $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM products WHERE id=".$c['product_id']));
  $total += $p['price'] * $c['quantity'];
}

// create order
mysqli_query($conn,"INSERT INTO orders(user_id,total,status) VALUES($user,$total,'Pending')");
$order_id = mysqli_insert_id($conn);

// move items into order_items
$cart = mysqli_query($conn,"SELECT * FROM shopping_cart WHERE user_id=$user");

while($c = mysqli_fetch_assoc($cart)){
  mysqli_query($conn,"INSERT INTO order_items(order_id,product_id,qty)
  VALUES($order_id,{$c['product_id']},{$c['quantity']})");
}

// clear cart
mysqli_query($conn,"DELETE FROM shopping_cart WHERE user_id=$user");

// redirect to receipt
header("Location: receipt.php?id=$order_id");
exit();
