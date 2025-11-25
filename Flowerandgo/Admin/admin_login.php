<?php
require_once '../config.php';

// Check if already logged in as admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    redirect('admin_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $email = sanitize($email);
    $query = "SELECT * FROM users WHERE email = '$email' AND role = 'admin'";
    $result = $connection->query($query);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            redirect('admin_dashboard.php');
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Admin account not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Flower n' Go</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #8b4513, #5a3921);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .admin-login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            color: #333;
        }
        
        .admin-logo {
            text-align: center;
            margin-bottom: 2rem;
            color: #8b4513;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .admin-logo span {
            color: #ff6b9d;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #5a3921;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e8d0b3;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: #8b4513;
            box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.1);
        }
        
        .admin-login-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #8b4513, #5a3921);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .admin-login-btn:hover {
            background: linear-gradient(135deg, #5a3921, #8b4513);
            transform: translateY(-2px);
        }
        
        .error {
            color: #ff4d4d;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-logo">Flower n'<span>Go</span></div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="admin_login.php">
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="admin-login-btn">LOGIN TO ADMIN</button>
        </form>
    </div>
</body>
</html>