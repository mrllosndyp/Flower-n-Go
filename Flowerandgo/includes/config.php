<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'flower_shop');

// Create connection
$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Function to sanitize input
function sanitize($data) {
    global $connection;
    return mysqli_real_escape_string($connection, trim($data));
}

// Function to redirect
function redirect($page) {
    header("Location: $page");
    exit();
}

// Brand name
define('BRAND_NAME', "Flower n' Go");
?>