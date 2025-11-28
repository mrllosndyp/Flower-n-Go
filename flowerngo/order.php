<?php
session_start();
include "db.php";
if(!isset($_SESSION['user'])) header("Location: signin.php");

if(isset($_POST['buy'])){
  $p = $_POST['product'];
  $price = $_POST['price'];
  $uid = $_SESSION['id'];

  $conn->query("INSERT INTO orders(customer_id,product,price)
                VALUES('$uid','$p','$price')");
  echo "✅ Order placed!";
}
?>

<h2>Order Flowers</h2>

<form method="POST">
  <select name="product">
    <option value="Sunflower">Sunflower</option>
    <option value="Stargazer">Stargazer</option>
    <option value="Tulips">Tulips</option>
  </select>
  <input type="hidden" name="price" value="18.99">
  <button name="buy">Place Order</button>
</form>
