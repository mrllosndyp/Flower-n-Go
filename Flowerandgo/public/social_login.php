<?php
require_once 'functions.php';

// This is a placeholder for social login functionality
// In a real implementation, you would integrate with OAuth providers
// For now, we'll simulate social login

if (isset($_GET['provider'])) {
    $provider = $_GET['provider'];
    
    // Simulate social login - in real implementation you would:
    // 1. Redirect to OAuth provider (Google, Facebook, Apple)
    // 2. Handle callback
    // 3. Get user info from provider
    // 4. Check if user exists in database
    // 5. Create user if doesn't exist
    // 6. Log user in
    
    switch ($provider) {
        case 'google':
            $email = 'google_user@example.com';
            $name = 'Google User';
            break;
        case 'facebook':
            $email = 'facebook_user@example.com';
            $name = 'Facebook User';
            break;
        case 'apple':
            $email = 'apple_user@example.com';
            $name = 'Apple User';
            break;
        default:
            redirect('index.php');
    }
    
    // Check if user exists
    $check_query = "SELECT id FROM users WHERE email = '$email'";
    $result = $connection->query($check_query);
    
    if ($result->num_rows == 0) {
        // Create new user with random password for social login
        $password = bin2hex(random_bytes(16)); // Random password for social login
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (name, email, password, email_verified) 
                         VALUES ('$name', '$email', '$hashed_password', 1)";
        
        if ($connection->query($insert_query)) {
            $user_id = $connection->insert_id;
        }
    } else {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = 'customer';
    
    redirect('index.php');
} else {
    redirect('index.php');
}
?>