<?php
require_once 'functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // User is logged in, show homepage
    $user_info = getUserInfo();
} else {
    // User is not logged in, show login page
    $show_login = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo BRAND_NAME; ?> - Custom Flower Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php if (isset($show_login) && $show_login): ?>
        <!-- Login Screen (Match Second Image Exactly) -->
        <main id="main-content">
            <section id="login-section" class="auth-section">
                <div class="login-container">
                    <div class="login-image">
                        <img src="FLOWERLOGIN.png" alt="Pink Peony Flower">
                    </div>
                    <div class="login-form">
                        <!-- LOGO CENTERED AND PROMINENT -->
                        <div class="logo-container">
                            <h1 class="logo"><?php echo BRAND_NAME; ?></h1>
                        </div>

                        <h2>Sign In</h2>
                        
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
                        
                        <form id="login-form" method="POST" action="login.php">
                            <div class="input-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                            </div>
                            
                            <div class="input-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility()">👁️</span>
                            </div>
                            
                            <div class="remember-me">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Remember me</label>
                            </div>
                            
                            <div class="terms">
                                <input type="checkbox" id="terms" name="terms">
                                <label for="terms">I agree to the Terms of Service and Privacy Policy</label>
                            </div>
                            
                            <button type="submit" class="login-btn">SIGN IN</button>
                        </form>
                        
                        <div class="forgot-signup">
                            <a href="#" id="forgot-password">Forgot password?</a>
                            <p>Don't have an account? <a href="register.php">Sign up</a></p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    <?php else: ?>
        <!-- Homepage (shown after login) -->
        <main id="main-content">
            <header>
                <nav>
                    <div class="logo"><?php echo BRAND_NAME; ?></div>
                    <div class="nav-links">
                        <a href="#home">Home</a>
                        <a href="#shop">Shop</a>
                        <a href="#account">Account</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </nav>
            </header>

            <section id="homepage">
                <h1>Welcome, <?php echo htmlspecialchars($user_info['name']); ?>! Design Your Perfect Bouquet</h1>
                <div class="bouquet-options">
                    <div class="option-card" onclick="showPreMadeBouquets()">
                        <h3>Pre-made Bouquets</h3>
                        <p>Choose from our beautiful ready-made arrangements</p>
                    </div>
                    <div class="option-card" onclick="showCustomizeBouquet()">
                        <h3>Customize Your Bouquet</h3>
                        <p>Create your own unique flower arrangement</p>
                    </div>
                </div>
            </section>

            <!-- Customization Interface -->
            <section id="customization-section" class="hidden">
                <div class="customization-steps">
                    <div class="step active" id="wrapper-step">
                        <h3>Step 1: Choose Wrapper</h3>
                        <div class="wrapper-options">
                            <!-- Wrapper options will go here -->
                        </div>
                    </div>
                    
                    <div class="step" id="flower-step">
                        <h3>Step 2: Choose Main Flower</h3>
                        <div class="flower-options">
                            <!-- Flower options will go here -->
                        </div>
                    </div>
                    
                    <div class="step" id="addons-step">
                        <h3>Step 3: Add Extras</h3>
                        <div class="addons-container">
                            <div class="addons-list">
                                <!-- Add-ons checkboxes will go here -->
                            </div>
                            <div class="order-actions">
                                <!-- Buy and Add to Cart buttons -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    <?php endif; ?>

    <script src="script.js"></script>
</body>
</html>