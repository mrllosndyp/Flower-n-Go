<?php
$conn = new mysqli("localhost", "root", "", "flowerngo");
if($conn->connect_error){
  die("Database error");
}
?>
