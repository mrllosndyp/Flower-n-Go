<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = loginUser($email, $password);
    if ($result['success']) {
        redirect('index.php');
    } else {
        $error = $result['message'];
        redirect('index.php?error=' . urlencode($error));
    }
} else {
    redirect('index.php');
}
?>