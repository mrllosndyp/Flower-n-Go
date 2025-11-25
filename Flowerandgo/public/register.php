<?php
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $terms = isset($_POST['terms']) ? $_POST['terms'] : '';
    
    if (empty($terms)) {
        $error = "You must agree to the Terms of Service and Privacy Policy";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        $result = registerUser($name, $email, $password, $phone, $address);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo BRAND_NAME; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main id="main-content">
        <section id="login-section" class="auth-section">
            <div class="login-container">
                <div class="login-image">
                    <img src="FLOWERLOGIN.png" alt="Pink Peony Flower">
                </div>
                <div class="login-form">
                    <div class="form-header">
                        <div class="logo"><?php echo BRAND_NAME; ?></div>
                        <nav class="form-nav">
                            <a href="index.php">Home</a>
                            <a href="index.php#shop">Shop</a>
                            <a href="index.php#account">Account</a>
                            <div class="user-actions">
                            </div>
                        </nav>
                    </div>

                    <h2>Create Account</h2>
                    
                    <?php if ($error): ?>
                        <div style="color: red; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div style="color: green; margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <!-- Social Login Options -->
                    <div class="social-login">
                        <h3>Continue with</h3>
                        <div class="social-buttons">
                            <a href="social_login.php?provider=google" class="social-btn google">
                                <span class="social-icon">G</span> Google
                            </a>
                            <a href="social_login.php?provider=facebook" class="social-btn facebook">
                                <span class="social-icon">f</span> Facebook
                            </a>
                            <a href="social_login.php?provider=apple" class="social-btn apple">
                                <span class="social-icon">A</span> Apple
                            </a>
                        </div>
                        <div class="divider">or</div>
                    </div>
                    
                    <form method="POST" action="register.php">
                        <div class="input-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="phone">Phone (Optional)</label>
                            <input type="tel" id="phone" name="phone" placeholder="Enter your phone number">
                        </div>
                        
                        <div class="input-group">
                            <label for="address">Address (Optional)</label>
                            <textarea id="address" name="address" placeholder="Enter your address" rows="2"></textarea>
                        </div>
                        
                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                        
                        <div class="terms">
                            <input type="checkbox" id="terms" name="terms">
                            <label for="terms">I agree to the Terms of Service and Privacy Policy</label>
                        </div>
                        
                        <button type="submit" class="login-btn">CREATE ACCOUNT</button>
                    </form>
                    
                    <div class="forgot-signup">
                        <p>Already have an account? <a href="index.php">Sign in</a></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = event.target;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '👁️‍🗨️';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>