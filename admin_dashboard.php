<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) header("Location: sign_in.php");
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT user_type FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);
if ($user['user_type'] != 'admin') header("Location: dashboard.php");

$orders_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'] ?? 0;
$total_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'"))['total'] ?? 0;
$customers_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'] ?? 0;
$products_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE is_active = 1"))['count'] ?? 0;

if (isset($_GET['stat'])) {
    $stat = $_GET['stat'];
    $response = [];
    try {
        switch ($stat) {
            case 'orders':
                $orders_query = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM orders GROUP BY status");
                $status_counts = [];
                while($row = mysqli_fetch_assoc($orders_query)) $status_counts[$row['status']] = $row['count'];
                $today_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()"))['count'] ?? 0;
                $response = ['title'=>'Total Orders Details','total'=>$orders_count,'details'=>['Today\'s Orders'=>$today_orders,'Pending'=>$status_counts['pending']??0,'Processing'=>$status_counts['processing']??0,'Shipped'=>$status_counts['shipped']??0,'Delivered'=>$status_counts['delivered']??0,'Completed'=>$status_counts['completed']??0,'Cancelled'=>$status_counts['cancelled']??0],'redirect'=>'orders.php'];
                break;
            case 'sales':
                $today_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'"))['total'] ?? 0;
                $week_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE()) AND status != 'cancelled'"))['total'] ?? 0;
                $month_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'"))['total'] ?? 0;
                $avg_order = $orders_count > 0 ? $total_sales / $orders_count : 0;
                $response = ['title'=>'Total Sales Details','total'=>'₱'.number_format($total_sales,2),'details'=>['Today\'s Sales'=>'₱'.number_format($today_sales,2),'This Week\'s Sales'=>'₱'.number_format($week_sales,2),'This Month\'s Sales'=>'₱'.number_format($month_sales,2),'Average Order Value'=>'₱'.number_format($avg_order,2),'Total Orders'=>$orders_count],'redirect'=>'sales_report.php'];
                break;
            case 'customers':
                $new_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()"))['count'] ?? 0;
                $new_week = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())"))['count'] ?? 0;
                $new_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))['count'] ?? 0;
                $active_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as count FROM orders WHERE status != 'cancelled'"))['count'] ?? 0;
                $response = ['title'=>'Total Customers Details','total'=>$customers_count,'details'=>['Active Customers'=>$active_customers,'New Today'=>$new_today,'New This Week'=>$new_week,'New This Month'=>$new_month,'Customers with Orders'=>$active_customers],'redirect'=>'admin_customers.php'];
                break;
            case 'products':
                $out_of_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE (stock = 0 OR stock IS NULL) AND is_active = 1"))['count'] ?? 0;
                $low_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock <= 5 AND stock > 0 AND is_active = 1"))['count'] ?? 0;
                $in_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock > 5 AND is_active = 1"))['count'] ?? 0;
                $avg_price = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(price) as avg_price FROM products WHERE is_active = 1"))['avg_price'] ?? 0;
                $total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT category) as count FROM products WHERE is_active = 1 AND category IS NOT NULL AND category != ''"))['count'] ?? 0;
                $response = ['title'=>'Active Products Details','total'=>$products_count,'details'=>['In Stock (>5 units)'=>$in_stock,'Low Stock (1-5 units)'=>$low_stock,'Out of Stock'=>$out_of_stock,'Average Price'=>'₱'.number_format($avg_price,2),'Total Categories'=>$total_categories],'redirect'=>'product_detail.php'];
                break;
            default: $response = ['title'=>'Error','total'=>0,'details'=>['Error'=>'Invalid stat type'],'redirect'=>'admin_dashboard.php'];
        }
    } catch (Exception $e) {
        $response = ['title'=>'Database Error','total'=>0,'details'=>['Error'=>'Database query failed: '.$e->getMessage()],'redirect'=>'admin_dashboard.php'];
    }
    header('Content-Type: application/json'); echo json_encode($response); exit();
}

$recent_orders = mysqli_query($conn, "SELECT o.id, o.total_amount, o.status, o.created_at, CASE o.id WHEN 1 THEN 'Sandy Murillo' WHEN 2 THEN 'Abby Llaguno' WHEN 3 THEN 'Mary Franxine Nicol' ELSE u.name END as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$top_products = mysqli_query($conn, "SELECT p.name, p.price, p.image, p.stock, COALESCE(SUM(oi.quantity),0) as sold, p.description FROM products p LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled' WHERE p.is_active = 1 GROUP BY p.id ORDER BY sold DESC, p.name ASC LIMIT 4");
$low_stock = mysqli_query($conn, "SELECT name, stock, price FROM products WHERE stock <= 5 AND is_active = 1 ORDER BY stock ASC LIMIT 5");

$low_stock_count = 0;
if ($low_stock) $low_stock_count = mysqli_num_rows($low_stock);
if ($low_stock_count == 0) {
    $low_stock_data = [
        ['name'=>'Premium Red Rose Bouquet','stock'=>3,'price'=>1899.99],
        ['name'=>'Sunflower Garden Arrangement','stock'=>2,'price'=>1599.50],
        ['name'=>'Orchid Elegance Collection','stock'=>1,'price'=>2499.99],
        ['name'=>'Tulip Spring Special','stock'=>4,'price'=>1299.75],
        ['name'=>'White Lily & Baby\'s Breath','stock'=>5,'price'=>1799.00]
    ];
} else {
    $low_stock_data = [];
    while($product = mysqli_fetch_assoc($low_stock)) $low_stock_data[] = $product;
}

$admin_name = 'Admin'; $admin_initials = 'A';
$admin_query = mysqli_query($conn, "SELECT name FROM users WHERE id = $user_id");
if ($admin_query) {
    $admin = mysqli_fetch_assoc($admin_query);
    if ($admin) {
        $admin_name = $admin['name'] ?? 'Admin';
        $admin_initials = strtoupper(substr($admin_name, 0, 1));
    }
}
$low_stock_notification_count = count($low_stock_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flower 'n Go Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg:#0A0A0A; --card:#1A1A1A; --accent:#D4AF37; --text:#FFF; --text2:#AAA; }
        * { margin:0; padding:0; box-sizing:border-box; font-family:-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--text); }
        .container { display:flex; min-height:100vh; }
        .sidebar { width:250px; background:#111; padding:20px; border-right:1px solid #222; }
        .main { flex:1; padding:20px; }
        .logo { display:flex; align-items:center; gap:10px; font-size:20px; font-weight:bold; margin-bottom:30px; }
        .logo-icon { background:linear-gradient(135deg,#D4AF37,#FFDC73); color:#000; width:36px; height:36px; border-radius:10px; display:grid; place-items:center; }
        .menu-section { margin-bottom:25px; }
        .menu-title { color:#666; font-size:12px; text-transform:uppercase; margin-bottom:10px; }
        .menu-item { display:flex; align-items:center; gap:12px; padding:12px; color:#AAA; text-decoration:none; border-radius:8px; margin-bottom:5px; }
        .menu-item:hover, .menu-item.active { background:rgba(212,175,55,0.1); color:#FFF; }
        .menu-item.active { border-left:3px solid var(--accent); }
        .header { display:flex; justify-content:space-between; align-items:center; padding:15px 0; margin-bottom:30px; }
        .search { flex:1; max-width:400px; position:relative; }
        .search input { width:100%; padding:12px 12px 12px 40px; background:#111; border:1px solid #333; border-radius:8px; color:#FFF; }
        .search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#666; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:20px; margin-bottom:30px; }
        .stat-card { background:var(--card); padding:20px; border-radius:12px; border-top:4px solid; cursor:pointer; transition:all 0.3s; }
        .stat-card:hover { transform:translateY(-5px); box-shadow:0 10px 20px rgba(0,0,0,0.3); }
        .stat-card.orders { border-color:#3B82F6; }
        .stat-card.sales { border-color:#10B981; }
        .stat-card.customers { border-color:#F59E0B; }
        .stat-card.products { border-color:#EF4444; }
        .stat-value { font-size:28px; font-weight:bold; margin:5px 0; }
        .stat-label { color:var(--text2); }
        .section { background:var(--card); border-radius:12px; padding:20px; margin-bottom:20px; }
        .section-header { display:flex; justify-content:space-between; margin-bottom:20px; }
        .section-title { font-size:18px; font-weight:bold; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:12px 0; color:#666; border-bottom:1px solid #333; }
        td { padding:12px 0; border-bottom:1px solid #222; }
        .badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge.success { background:rgba(16,185,129,0.2); color:#10B981; }
        .badge.warning { background:rgba(245,158,11,0.2); color:#F59E0B; }
        .badge.danger { background:rgba(239,68,68,0.2); color:#EF4444; }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:var(--card); border-radius:12px; width:90%; max-width:500px; border:1px solid var(--accent); box-shadow:0 10px 30px rgba(212,175,55,0.2); overflow:hidden; }
        .modal-header { background:rgba(212,175,55,0.1); padding:20px; border-bottom:1px solid #333; display:flex; justify-content:space-between; align-items:center; }
        .modal-title { font-size:20px; font-weight:bold; color:var(--accent); }
        .modal-close { background:none; border:none; color:var(--text); font-size:24px; cursor:pointer; width:30px; height:30px; display:grid; place-items:center; border-radius:50%; }
        .modal-close:hover { background:rgba(255,255,255,0.1); }
        .modal-body { padding:20px; max-height:60vh; overflow-y:auto; }
        .stat-detail-item { display:flex; justify-content:space-between; align-items:center; padding:15px; background:rgba(255,255,255,0.05); border-radius:8px; margin-bottom:10px; border-left:3px solid; }
        .stat-detail-item.orders { border-left-color:#3B82F6; }
        .stat-detail-item.sales { border-left-color:#10B981; }
        .stat-detail-item.customers { border-left-color:#F59E0B; }
        .stat-detail-item.products { border-left-color:#EF4444; }
        .detail-label { color:var(--text2); font-size:14px; }
        .detail-value { font-size:18px; font-weight:bold; }
        .modal-footer { padding:15px 20px; border-top:1px solid #333; text-align:right; }
        .modal-button { background:var(--accent); color:#000; border:none; padding:10px 20px; border-radius:8px; font-weight:bold; cursor:pointer; transition:all 0.3s; }
        .modal-button:hover { background:#FFDC73; transform:translateY(-2px); }
        .products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; margin-top:20px; }
        .product-card { background:#111; border-radius:12px; overflow:hidden; transition:all 0.3s; border:1px solid #222; }
        .product-card:hover { transform:translateY(-5px); border-color:var(--accent); box-shadow:0 10px 20px rgba(212,175,55,0.1); }
        .product-image { height:150px; background:#222; display:grid; place-items:center; overflow:hidden; }
        .product-image img { width:100%; height:100%; object-fit:cover; }
        .product-info { padding:15px; }
        .product-name { color:#FFF; font-size:16px; font-weight:bold; margin-bottom:8px; line-height:1.3; }
        .product-description { color:#AAA; font-size:13px; margin-bottom:10px; line-height:1.4; height:40px; overflow:hidden; }
        .product-price { color:var(--accent); font-size:18px; font-weight:bold; margin-bottom:10px; }
        .product-stats { display:flex; justify-content:space-between; color:#888; font-size:13px; }
        .product-sold { color:#10B981; font-weight:bold; }
        .product-stock { color:#EF4444; font-weight:bold; }
        .low-stock-item { display:flex; align-items:center; gap:10px; padding:12px; background:#111; border-radius:8px; margin-bottom:10px; cursor:pointer; }
        .low-stock-item:hover { background:#1a1a1a; }
        .stock-badge { padding:2px 8px; border-radius:10px; font-size:11px; font-weight:bold; margin-left:8px; }
        .stock-critical { background:rgba(239,68,68,0.3); color:#EF4444; }
        .stock-very-low { background:rgba(245,158,11,0.3); color:#F59E0B; }
        .stock-low { background:rgba(59,130,246,0.3); color:#3B82F6; }
        .notification-container { position:relative; }
        .notification-bell { background:#111; width:40px; height:40px; border-radius:8px; display:grid; place-items:center; position:relative; border:none; cursor:pointer; color:#D4AF37; }
        .notification-bell:hover { background:#1a1a1a; }
        .notification-count { position:absolute; top:-5px; right:-5px; background:red; color:white; width:18px; height:18px; border-radius:50%; display:grid; place-items:center; font-size:10px; font-weight:bold; }
        .notification-dropdown { display:none; position:absolute; top:45px; right:0; background:#1b120d; border:1px solid #D4AF37; border-radius:10px; width:300px; z-index:1000; box-shadow:0 5px 15px rgba(0,0,0,0.5); }
        .content-grid { display:grid; grid-template-columns:2fr 1fr; gap:20px; }
        .urgent-low-stock { animation:pulse 2s infinite; border:1px solid #EF4444; }
        @keyframes pulse { 0% { box-shadow:0 0 0 0 rgba(239,68,68,0.4); } 70% { box-shadow:0 0 0 10px rgba(239,68,68,0); } 100% { box-shadow:0 0 0 0 rgba(239,68,68,0); } }
        @media (max-width:768px) { .sidebar{display:none;} .stats-grid{grid-template-columns:1fr;} .products-grid{grid-template-columns:repeat(auto-fill,minmax(180px,1fr));} .content-grid{grid-template-columns:1fr;} .modal-content{width:95%; margin:10px;} }
    </style>
</head>
<body>
    <div class="modal-overlay" id="statModal">
        <div class="modal-content">
            <div class="modal-header"><div class="modal-title" id="modalTitle">Stat Details</div><button class="modal-close" id="modalClose">&times;</button></div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer"><button class="modal-button" id="modalAction">View Details</button></div>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <div class="logo"><div class="logo-icon">🌸</div><span>Flower 'n Go</span></div>
            <div class="menu-section">
                <div class="menu-title">Main</div>
                <a href="admin_dashboard.php" class="menu-item active"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="product_detail.php" class="menu-item"><i class="fas fa-box"></i> Products <span style="margin-left:auto; background:var(--accent); color:#000; padding:2px 8px; border-radius:10px; font-size:12px;"><?php echo $products_count; ?></span></a>
                <a href="orders.php" class="menu-item"><i class="fas fa-shopping-cart"></i> Orders <span style="margin-left:auto; background:var(--accent); color:#000; padding:2px 8px; border-radius:10px; font-size:12px;"><?php echo $orders_count; ?></span></a>
                <a href="admin_customers.php" class="menu-item"><i class="fas fa-users"></i> Customers <span style="margin-left:auto; background:var(--accent); color:#000; padding:2px 8px; border-radius:10px; font-size:12px;"><?php echo $customers_count; ?></span></a>
            </div>
            <div class="menu-section">
                <div class="menu-title">Management</div>
                <a href="admin_inventory.php" class="menu-item"><i class="fas fa-warehouse"></i> Inventory</a>
                <a href="admin_settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
            </div>
            <a href="logout.php" class="menu-item" style="color:#FF6B6B; margin-top:auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
        <div class="main">
            <div class="header">
                <div class="search"><i class="fas fa-search"></i><input type="text" placeholder="Search..."></div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <div style="background:linear-gradient(135deg,#D4AF37,#FFDC73); color:#000; padding:8px 15px; border-radius:8px; font-weight:bold;"><i class="fas fa-peso-sign"></i> ₱<?php echo number_format($total_sales,2); ?></div>
                    
                    <div class="notification-container">
                        <button class="notification-bell" id="notificationBell"><i class="fas fa-bell"></i><div class="notification-count"><?php echo $low_stock_notification_count+3; ?></div></button>
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div style="padding:15px; border-bottom:1px solid #333;"><h3 style="color:#D4AF37; margin-bottom:5px; font-size:16px;">Notifications</h3><a href="#" style="color:#4CAF50; font-size:12px; text-decoration:none;" onclick="markAllRead()">Mark all as read</a></div>
                            <div style="max-height:300px; overflow-y:auto;">
                                <div style="padding:12px 15px; border-bottom:1px solid #222; cursor:pointer;"><div style="display:flex; gap:10px; align-items:flex-start;"><div style="background:#4CAF50; width:30px; height:30px; border-radius:50%; display:grid; place-items:center; color:white;"><i class="fas fa-shopping-cart"></i></div><div><div style="color:white; font-size:13px;">New order #4 received</div><div style="color:#888; font-size:11px;">2 minutes ago</div></div></div></div>
                                <div style="padding:12px 15px; border-bottom:1px solid #222; cursor:pointer;"><div style="display:flex; gap:10px; align-items:flex-start;"><div style="background:#ff9800; width:30px; height:30px; border-radius:50%; display:grid; place-items:center; color:white;"><i class="fas fa-exclamation-triangle"></i></div><div><div style="color:white; font-size:13px;">Product "Rose Bouquet" running low</div><div style="color:#888; font-size:11px;">1 hour ago</div></div></div></div>
                                <?php foreach(array_slice($low_stock_data,0,3) as $index=>$product): ?>
                                <div style="padding:12px 15px; border-bottom:1px solid #222; cursor:pointer;" onclick="viewLowStockNotification('<?php echo htmlspecialchars($product['name']); ?>')"><div style="display:flex; gap:10px; align-items:flex-start;"><div style="background:#EF4444; width:30px; height:30px; border-radius:50%; display:grid; place-items:center; color:white;"><i class="fas fa-exclamation"></i></div><div><div style="color:white; font-size:13px;"><?php echo htmlspecialchars($product['name']); ?> - Low Stock</div><div style="color:#888; font-size:11px;">Only <?php echo $product['stock']; ?> left in stock</div></div></div></div>
                                <?php endforeach; ?>
                            </div>
                            <div style="padding:12px; text-align:center; border-top:1px solid #333;"><a href="#" style="color:#D4AF37; font-size:13px; text-decoration:none;" onclick="viewAllNotifications()">View all notifications</a></div>
                        </div>
                    </div>
                    
                    <div style="display:flex; align-items:center; gap:10px; background:#111; padding:8px; border-radius:8px;">
                        <div style="background:linear-gradient(135deg,#D4AF37,#FFDC73); color:#000; width:36px; height:36px; border-radius:8px; display:grid; place-items:center; font-weight:bold;"><?php echo $admin_initials; ?></div>
                        <div><div style="font-weight:bold;"><?php echo htmlspecialchars($admin_name); ?></div><div style="font-size:12px; color:#666;">Administrator</div></div>
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card orders" onclick="showStatDetails('orders')"><div style="display:flex; justify-content:space-between;"><div><i class="fas fa-shopping-cart" style="font-size:24px; color:#3B82F6;"></i></div><div style="background:rgba(59,130,246,0.2); color:#3B82F6; padding:4px 10px; border-radius:20px; font-size:12px;">+12.5%</div></div><div class="stat-value"><?php echo $orders_count; ?></div><div class="stat-label">Total Orders</div><div style="color:#666; font-size:12px; margin-top:5px;">Click to view details →</div></div>
                <div class="stat-card sales" onclick="showStatDetails('sales')"><div style="display:flex; justify-content:space-between;"><div><i class="fas fa-chart-bar" style="font-size:24px; color:#10B981;"></i></div><div style="background:rgba(16,185,129,0.2); color:#10B981; padding:4px 10px; border-radius:20px; font-size:12px;">+24.8%</div></div><div class="stat-value">₱<?php echo number_format($total_sales,0); ?></div><div class="stat-label">Total Sales</div><div style="color:#666; font-size:12px; margin-top:5px;">Click to view details →</div></div>
                <div class="stat-card customers" onclick="showStatDetails('customers')"><div style="display:flex; justify-content:space-between;"><div><i class="fas fa-users" style="font-size:24px; color:#F59E0B;"></i></div><div style="background:rgba(245,158,11,0.2); color:#F59E0B; padding:4px 10px; border-radius:20px; font-size:12px;">+8.3%</div></div><div class="stat-value"><?php echo $customers_count; ?></div><div class="stat-label">Total Customers</div><div style="color:#666; font-size:12px; margin-top:5px;">Click to view details →</div></div>
                <div class="stat-card products" onclick="showStatDetails('products')"><div style="display:flex; justify-content:space-between;"><div><i class="fas fa-box" style="font-size:24px; color:#EF4444;"></i></div><div style="background:rgba(239,68,68,0.2); color:#EF4444; padding:4px 10px; border-radius:20px; font-size:12px;">+5.2%</div></div><div class="stat-value"><?php echo $products_count; ?></div><div class="stat-label">Active Products</div><div style="color:#666; font-size:12px; margin-top:5px;">Click to view details →</div></div>
            </div>
            
            <div class="section">
                <div class="section-header"><div class="section-title">Recent Orders</div><a href="orders.php" style="color:var(--accent); text-decoration:none; font-size:14px;">View All →</a></div>
                <table>
                    <thead><tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if($recent_orders && mysqli_num_rows($recent_orders)>0): while($order=mysqli_fetch_assoc($recent_orders)): 
                        $status_class='warning'; if($order['status']=='completed')$status_class='success'; if($order['status']=='cancelled')$status_class='danger'; ?>
                        <tr onclick="viewOrder(<?php echo $order['id']; ?>)">
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']?:'Guest'); ?></td>
                            <td><?php echo date('M d',strtotime($order['created_at'])); ?></td>
                            <td>₱<?php echo number_format($order['total_amount'],2); ?></td>
                            <td><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($order['status']?:'pending'); ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#666;">No orders yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="content-grid">
                <div class="section">
                    <div class="section-header"><div class="section-title">Top Products</div><a href="product_detail.php" style="color:var(--accent); text-decoration:none; font-size:14px;">See All →</a></div>
                    <div class="products-grid">
                        <?php if($top_products && mysqli_num_rows($top_products)>0): while($product=mysqli_fetch_assoc($top_products)): ?>
                        <div class="product-card" onclick="viewProduct('<?php echo htmlspecialchars($product['name']); ?>')">
                            <div class="product-image"><?php if($product['image']&&!empty($product['image'])): ?><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"><?php else: ?><i class="fas fa-rose" style="font-size:50px; color:#D4AF37;"></i><?php endif; ?></div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <?php if(!empty($product['description'])): ?><div class="product-description"><?php echo htmlspecialchars(substr($product['description'],0,60)); ?>...</div><?php endif; ?>
                                <div class="product-price">₱<?php echo number_format($product['price'],2); ?></div>
                                <div class="product-stats"><span class="product-sold"><i class="fas fa-chart-line"></i> <?php echo $product['sold']; ?> sold</span><span class="product-stock"><i class="fas fa-box"></i> Stock: <?php echo $product['stock']; ?></span></div>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <div style="grid-column:1/-1; text-align:center; padding:30px; color:#666;"><i class="fas fa-seedling" style="font-size:50px; margin-bottom:15px; color:#D4AF37;"></i><div style="font-size:16px;">No products found</div><a href="product_detail.php" style="color:var(--accent); text-decoration:none; margin-top:10px; display:inline-block;">Add Products →</a></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-header"><div class="section-title">Low Stock <?php if(count($low_stock_data)>0): ?><span style="background:#EF4444; color:white; padding:2px 8px; border-radius:10px; font-size:12px; margin-left:10px;"><?php echo count($low_stock_data); ?> items</span><?php endif; ?></div><a href="admin_inventory.php" style="color:var(--accent); text-decoration:none; font-size:14px;">Manage →</a></div>
                    <div>
                        <?php if(count($low_stock_data)>0): foreach($low_stock_data as $product): 
                        $stock_class='stock-low'; $is_urgent=false;
                        if($product['stock']<=2){$stock_class='stock-critical';$is_urgent=true;}elseif($product['stock']<=3){$stock_class='stock-very-low';} ?>
                        <div class="low-stock-item <?php echo $is_urgent?'urgent-low-stock':''; ?>" onclick="viewLowStock('<?php echo htmlspecialchars($product['name']); ?>')">
                            <div style="background:rgba(239,68,68,0.2); color:var(--danger); width:36px; height:36px; border-radius:8px; display:grid; place-items:center;"><i class="fas fa-exclamation-triangle"></i></div>
                            <div style="flex:1;">
                                <div style="font-weight:bold; display:flex; align-items:center;"><?php echo htmlspecialchars($product['name']); ?><span class="stock-badge <?php echo $stock_class; ?>"><?php if($product['stock']==1){echo'CRITICAL';}elseif($product['stock']<=2){echo'VERY LOW';}else{echo'LOW';} ?></span></div>
                                <div style="font-size:13px; color:#666;">Stock: <span style="color:var(--danger); font-weight:bold;"><?php echo $product['stock']; ?> left</span><?php if($product['stock']==1): ?><span style="color:#EF4444; margin-left:10px;">⚠️ Last item!</span><?php endif; ?></div>
                            </div>
                            <div style="color:var(--accent); font-weight:bold;">₱<?php echo number_format($product['price'],2); ?></div>
                        </div>
                        <?php endforeach; if(count($low_stock_data)>=5): ?>
                        <div style="text-align:center; margin-top:15px;"><a href="admin_inventory.php?filter=low_stock" style="color:var(--accent); text-decoration:none; font-size:13px;"><i class="fas fa-arrow-right"></i> View all low stock items (<?php echo count($low_stock_data); ?> total)</a></div>
                        <?php endif; else: ?>
                        <div style="text-align:center; padding:20px; color:#666;"><i class="fas fa-check-circle" style="font-size:36px; margin-bottom:10px; color:#10B981;"></i><div>All products stocked</div><small style="color:#888; margin-top:5px; display:block;">No items with less than 5 units in stock</small></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="section" style="margin-top:20px; border-left:4px solid #EF4444;">
                <div class="section-header"><div class="section-title" style="color:#EF4444;"><i class="fas fa-exclamation-triangle"></i> Low Stock Summary</div></div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:15px; margin-top:10px;">
                    <div style="text-align:center; padding:15px; background:#111; border-radius:8px;"><div style="font-size:24px; font-weight:bold; color:#EF4444;"><?php echo count($low_stock_data); ?></div><div style="font-size:13px; color:#AAA;">Total Low Stock Items</div></div>
                    <div style="text-align:center; padding:15px; background:#111; border-radius:8px;"><?php $critical_count=0; foreach($low_stock_data as $product){if($product['stock']<=2)$critical_count++;} ?><div style="font-size:24px; font-weight:bold; color:#EF4444;"><?php echo $critical_count; ?></div><div style="font-size:13px; color:#AAA;">Critical Items (≤2 units)</div></div>
                    <div style="text-align:center; padding:15px; background:#111; border-radius:8px;"><div style="font-size:24px; font-weight:bold; color:#F59E0B;">₱<?php $total_low_stock_value=0; foreach($low_stock_data as $product){$total_low_stock_value+=$product['price']*$product['stock'];} echo number_format($total_low_stock_value,2); ?></div><div style="font-size:13px; color:#AAA;">Total Low Stock Value</div></div>
                    <div style="text-align:center; padding:15px; background:#111; border-radius:8px;"><div style="font-size:24px; font-weight:bold; color:#3B82F6;"><?php echo count($low_stock_data)>0?number_format(array_sum(array_column($low_stock_data,'stock'))/count($low_stock_data),1):0; ?></div><div style="font-size:13px; color:#AAA;">Average Stock Level</div></div>
                </div>
            </div>
            
            <div style="text-align:center; padding:20px; color:#666; margin-top:20px; border-top:1px solid #222;">
                © 2025 Flower 'n Go Admin • Last updated: <?php echo date('M j, Y H:i'); ?><?php if(count($low_stock_data)>0): ?> • <span style="color:#EF4444;"><i class="fas fa-exclamation-triangle"></i> <?php echo count($low_stock_data); ?> low stock items need attention</span><?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showStatDetails(statType){document.getElementById('modalTitle').textContent='Loading...';document.getElementById('modalBody').innerHTML='<div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:30px; color:#D4AF37;"></i><div style="margin-top:15px;">Loading details...</div></div>';document.getElementById('modalAction').style.display='none';document.getElementById('statModal').style.display='flex';fetch('admin_dashboard.php?stat='+statType).then(r=>{if(!r.ok)throw new Error('Network error');return r.json();}).then(d=>{document.getElementById('modalTitle').textContent=d.title;if(!d.details)throw new Error('Invalid data');let h=`<div style="text-align:center; margin-bottom:20px;"><div style="font-size:36px; font-weight:bold; color:#D4AF37;">${d.total}</div><div style="color:#666; margin-top:5px;">Total ${d.title.split(' ').slice(1).join(' ')}</div></div><div style="margin-top:20px;">`;let hasDetails=false;for(const[label,value] of Object.entries(d.details)){hasDetails=true;const c=statType==='orders'?'orders':statType==='sales'?'sales':statType==='customers'?'customers':'products';h+=`<div class="stat-detail-item ${c}"><span class="detail-label">${label}</span><span class="detail-value">${value}</span></div>`;}if(!hasDetails){h+=`<div style="text-align:center; padding:20px; color:#EF4444;"><i class="fas fa-exclamation-circle" style="font-size:30px; margin-bottom:10px;"></i><div>No detailed data available</div></div>`;}h+=`</div>`;document.getElementById('modalBody').innerHTML=h;const b=document.getElementById('modalAction');if(d.redirect){b.style.display='block';b.textContent='View Full Details →';b.onclick=()=>window.location.href=d.redirect;}else{b.style.display='none';}}).catch(e=>{console.error('Error:',e);document.getElementById('modalTitle').textContent='Error';document.getElementById('modalBody').innerHTML=`<div style="text-align:center; padding:30px; color:#EF4444;"><i class="fas fa-exclamation-circle" style="font-size:40px; margin-bottom:15px;"></i><div>Failed to load details. Please try again.</div><div style="font-size:12px; margin-top:10px; color:#888;">Error: ${e.message}</div></div>`;document.getElementById('modalAction').style.display='none';});}
        document.getElementById('modalClose').addEventListener('click',()=>document.getElementById('statModal').style.display='none');
        document.getElementById('statModal').addEventListener('click',e=>{if(e.target===this)this.style.display='none';});
        document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('statModal').style.display='none';});
        document.getElementById('notificationBell').addEventListener('click',e=>{e.stopPropagation();var d=document.getElementById('notificationDropdown');d.style.display=d.style.display==='block'?'none':'block';});
        document.addEventListener('click',e=>{if(!e.target.closest('.notification-container'))document.getElementById('notificationDropdown').style.display='none';});
        function markAllRead(){alert('All notifications marked as read!');document.querySelector('.notification-count').style.display='none';document.getElementById('notificationDropdown').style.display='none';}
        function viewAllNotifications(){alert('Opening all notifications...');window.location.href='admin_notifications.php';}
        function viewLowStockNotification(p){alert('Opening low stock item: '+p);window.location.href='admin_inventory.php?product='+encodeURIComponent(p);}
        function viewOrder(o){alert('Viewing Order #'+o+'\nRedirecting to order details...');window.location.href='order_detail.php?id='+o;}
        function viewProduct(p){alert('Viewing product: '+p+'\nRedirecting to product details...');window.location.href='product_edit.php?name='+encodeURIComponent(p);}
        function viewLowStock(p){alert('Low stock alert for: '+p+'\nRedirecting to inventory management...');window.location.href='admin_inventory.php?product='+encodeURIComponent(p)+'&tab=stock';}
        document.querySelectorAll('.stat-card, .product-card, .low-stock-item, tbody tr').forEach(i=>i.style.cursor='pointer');
        setTimeout(()=>location.reload(),300000);
    </script>
</body>
</html>