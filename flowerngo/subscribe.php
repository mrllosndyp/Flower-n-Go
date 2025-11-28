<?php
// subscribe.php
if(isset($_POST['subscribe'])){

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo "<script>alert('❌ Invalid email address'); window.history.back();</script>";
        exit;
    }

    $file = "newsletter.txt";

    // Prevent duplicates
    if(file_exists($file)){
        $existing = file($file, FILE_IGNORE_NEW_LINES);
        if(in_array($email, $existing)){
            echo "<script>alert('✅ You are already subscribed.'); window.history.back();</script>";
            exit;
        }
    }

    // Save email
    file_put_contents($file, $email.PHP_EOL, FILE_APPEND);

    echo "<script>
            alert('🎉 Subscription successful!');
            window.location.href='index.php';
          </script>";
}
?>
