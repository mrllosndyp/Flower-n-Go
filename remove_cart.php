<?php
session_start();
include "db.php";

$id = $_GET['id'];
$user = $_SESSION['user_id'];

$conn->query("DELETE FROM shopping_cart WHERE user_id=$user AND product_id=$id");

header("Location: view_cart.php");
