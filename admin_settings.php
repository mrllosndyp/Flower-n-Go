<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: sign_in.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT user_type FROM users WHERE id = '$user_id'");
if(!$user_query || mysqli_num_rows($user_query) == 0) {
    header("Location: sign_in.php");
    exit();
}

$user = mysqli_fetch_assoc($user_query);
if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = "";
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $store_email = mysqli_real_escape_string($conn, $_POST['store_email']);
    
    
    $message = "<div style='background:#10B98120; color:#10B981; padding:10px; border-radius:5px; margin-bottom:20px;'>
                 ✅ Settings saved successfully!
               </div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - Flower 'n Go</title>
    <style>
        :root { --bg:#0A0A0A; --card:#1A1A1A; --accent:#D4AF37; --text:#FFF; }
        body { background:var(--bg); color:var(--text); font-family:Arial; padding:20px; }
        .container { max-width:800px; margin:0 auto; }
        .back-btn { display:inline-block; background:#333; color:#FFF; padding:10px 20px; text-decoration:none; border-radius:5px; margin-bottom:20px; }
        .back-btn:hover { background:#444; }
        .setting-card { background:var(--card); padding:20px; border-radius:10px; margin-bottom:20px; border:1px solid #333; }
        .setting-group { margin-bottom:15px; }
        label { display:block; margin-bottom:5px; color:#AAA; }
        input, select, textarea { width:100%; padding:10px; background:#111; border:1px solid #444; border-radius:5px; color:#FFF; }
        .btn-save { background:var(--accent); color:#000; padding:12px 30px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; font-size:16px; }
        .btn-save:hover { background:#FFDC73; }
        h1 { color:var(--accent); margin-bottom:20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <h1>⚙️ Admin Settings</h1>
        
        <?php echo $message; ?>
        
        <form method="POST">
            <div class="setting-card">
                <h3 style="color:var(--accent); margin-top:0; border-bottom:1px solid #333; padding-bottom:10px;">Store Information</h3>
                <div class="setting-group">
                    <label>Store Name</label>
                    <input type="text" name="store_name" value="Flower 'n Go" required>
                </div>
                <div class="setting-group">
                    <label>Contact Email</label>
                    <input type="email" name="store_email" value="contact@flowerngo.com" required>
                </div>
                <div class="setting-group">
                    <label>Contact Phone</label>
                    <input type="text" name="store_phone" value="+63 912 345 6789">
                </div>
            </div>
            
            <div class="setting-card">
                <h3 style="color:var(--accent); margin-top:0; border-bottom:1px solid #333; padding-bottom:10px;">Order Settings</h3>
                <div class="setting-group">
                    <label>Default Order Status</label>
                    <select name="default_status">
                        <option value="pending" selected>Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="setting-group">
                    <label>Send Order Emails</label>
                    <select name="send_emails">
                        <option value="1" selected>Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            
            <div class="setting-card">
                <h3 style="color:var(--accent); margin-top:0; border-bottom:1px solid #333; padding-bottom:10px;">Low Stock Alerts</h3>
                <div class="setting-group">
                    <label>Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" value="5" min="1">
                    <small style="color:#888;">Alert when stock is below this number</small>
                </div>
            </div>
            
            <button type="submit" class="btn-save">
                💾 Save Settings
            </button>
        </form>
    </div>
</body>
</html>