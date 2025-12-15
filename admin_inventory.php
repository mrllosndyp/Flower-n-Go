<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) header("Location: sign_in.php");
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT user_type FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);
if ($user['user_type'] != 'admin') header("Location: dashboard.php");

if ($_POST) {
    if(isset($_POST['update'])) {
        $id = intval($_POST['id']);
        $stock = intval($_POST['stock']);
        mysqli_query($conn, "UPDATE products SET stock = $stock WHERE id = $id");
        header("Location: admin_inventory.php?updated=true");
        exit();
    }
}


$products = mysqli_query($conn, "SELECT id, name, price, COALESCE(stock,0) as stock, is_active FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory | Flower 'n Go</title>
    <style>
        :root { --bg:#0A0A0A; --card:#1A1A1A; --accent:#D4AF37; }
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial; }
        body { background:var(--bg); color:#FFF; padding:20px; }
        .back-btn { display:inline-block; background:#333; color:#FFF; padding:10px 20px; text-decoration:none; border-radius:5px; margin-bottom:20px; }
        .back-btn:hover { background:#444; }
        table { width:100%; background:var(--card); border-radius:10px; overflow:hidden; }
        th { background:#111; padding:15px; text-align:left; }
        td { padding:15px; border-bottom:1px solid #333; }
        .stock-input { width:80px; padding:8px; background:#111; border:1px solid #444; color:#FFF; border-radius:5px; }
        .update-btn { background:var(--accent); color:#000; border:none; padding:8px 15px; border-radius:5px; cursor:pointer; }
        .stock-badge { padding:4px 10px; border-radius:20px; font-size:12px; }
        .green { background:#10B98120; color:#10B981; }
        .yellow { background:#F59E0B20; color:#F59E0B; }
        .red { background:#EF444420; color:#EF4444; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
    </style>
</head>
<body>
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    
    <div class="header">
        <h1>📦 Inventory Management</h1>
        <div style="background:#1A1A1A; padding:15px; border-radius:10px;">
            <?php
       
            $stats_query = mysqli_query($conn, "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN stock <= 5 AND stock > 0 THEN 1 ELSE 0 END) as low,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as `out`
                FROM products WHERE is_active = 1");
            
           
            if($stats_query) {
                $stats = mysqli_fetch_assoc($stats_query);
            } else {
               
                $stats = ['total' => 0, 'low' => 0, 'out' => 0];
            }
            ?>
            <strong>📊 Stats:</strong> 
            <span style="color:#10B981;"><?php echo $stats['total'] ?? 0; ?> total</span> | 
            <span style="color:#F59E0B;"><?php echo $stats['low'] ?? 0; ?> low stock</span> | 
            <span style="color:#EF4444;"><?php echo $stats['out'] ?? 0; ?> out of stock</span>
        </div>
    </div>

    <?php if(isset($_GET['updated'])): ?>
        <div style="background:#10B98120; color:#10B981; padding:10px; border-radius:5px; margin-bottom:20px;">
            ✅ Stock updated successfully!
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php if($products && mysqli_num_rows($products) > 0): ?>
                <?php while($p = mysqli_fetch_assoc($products)): 
                    $status = 'green'; $text = 'Good';
                    if($p['stock'] <= 5 && $p['stock'] > 0) { $status = 'yellow'; $text = 'Low'; }
                    if($p['stock'] == 0) { $status = 'red'; $text = 'Out'; }
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td>₱<?php echo number_format($p['price'],2); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="number" name="stock" value="<?php echo $p['stock']; ?>" class="stock-input" min="0">
                            <button type="submit" name="update" class="update-btn">Update</button>
                        </form>
                    </td>
                    <td><span class="stock-badge <?php echo $status; ?>"><?php echo $text; ?></span></td>
                    <td>
                        <?php if($p['is_active']): ?>
                            <span style="color:#10B981;">Active</span>
                        <?php else: ?>
                            <span style="color:#EF4444;">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:30px; color:#999;">
                        No products found
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align:center; margin-top:30px; color:#666;">
        <small>Quick Actions:</small>
        <a href="product_detail.php" style="color:#D4AF37; margin-left:15px;">Add New Product</a> | 
        <a href="admin_dashboard.php" style="color:#D4AF37; margin-left:15px;">View Dashboard</a>
    </div>
</body>
</html>