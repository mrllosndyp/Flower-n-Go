<?php
require_once 'functions.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    if (verifyEmail($token)) {
        $message = "Email verified successfully! You can now login.";
        $message_type = "success";
    } else {
        $message = "Invalid or expired verification token.";
        $message_type = "error";
    }
} else {
    $message = "No verification token provided.";
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo BRAND_NAME; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main id="main-content">
        <section id="login-section" class="auth-section">
            <div class="login-container">
                <div class="login-form">
                    <div class="form-header">
                        <div class="logo"><?php echo BRAND_NAME; ?></div>
                    </div>
                    
                    <h2>Email Verification</h2>
                    
                    <div style="<?php echo $message_type === 'success' ? 'color: green;' : 'color: red;'; ?> margin: 2rem 0; text-align: center;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    
                    <?php if ($message_type === 'success'): ?>
                        <div style="text-align: center; margin-top: 2rem;">
                            <a href="index.php" class="login-btn">Go to Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>