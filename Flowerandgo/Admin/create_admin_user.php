<?php
require_once '../config.php';

// This file creates your admin user - run it once then delete it!
$admin_email = 'admin@flowerngo.com';
$admin_password = 'admin123'; // CHANGE THIS TO A STRONG PASSWORD!
$admin_name = 'Admin User';

// Check if admin already exists
$check = $connection->query("SELECT id FROM users WHERE email = '$admin_email'");
if ($check->num_rows == 0) {
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (name, email, password, role, email_verified) 
              VALUES ('$admin_name', '$admin_email', '$hashed_password', 'admin', 1)";
    
    if ($connection->query($query)) {
        echo "✅ Admin user created successfully!<br>";
        echo "Email: $admin_email<br>";
        echo "Password: $admin_password<br>";
        echo "<br>⚠️ DELETE THIS FILE AFTER CREATING YOUR ADMIN ACCOUNT!";
    } else {
        echo "❌ Error creating admin user: " . $connection->error;
    }
} else {
    echo "Admin user already exists!";
}
?>