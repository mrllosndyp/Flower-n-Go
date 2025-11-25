<?php
require_once '../config.php';
require_once '../functions.php';

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('admin_login.php');
}

// Get dashboard statistics
$total_orders = $connection->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $connection->query("SELECT SUM(total_amount) as total FROM orders")->fetch_assoc()['total'];
$total_products = $connection->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_users = $connection->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Recent orders
$recent_orders = $connection->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flower n' Go</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f4e8;
            color: #5a3921;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #8b4513, #5a3921);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .admin-header a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid white;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .admin-header a:hover {
            background: white;
            color: #8b4513;
        }
        
        .admin-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        .admin-sidebar {
            width: 250px;
            background: white;
            padding: 2rem 0;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar a {
            display: block;
            padding: 1rem 2rem;
            color: #5a3921;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: #f0e6d2;
            border-left: 4px solid #8b4513;
            color: #8b4513;
        }
        
        .admin-main {
            flex: 1;
            padding: 2rem;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #8b4513;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .stat-card p {
            color: #5a3921;
            font-weight: 500;
        }
        
        .recent-orders h2 {
            margin-bottom: 1.5rem;
            color: #8b4513;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e8d0b3;
        }
        
        th {
            background: #f0e6d2;
            color: #8b4513;
            font-weight: 600;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .status-pending { color: #ff9800; }
        .status-processing { color: #2196F3; }
        .status-delivered { color: #4CAF50; }
        .status-cancelled { color: #f44336; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="logo">Flower n' Go Admin</div>
        <a href="../logout.php">Logout</a>
    </header>
    
    <div class="admin-container">
        <aside class="admin-sidebar">
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="manage_orders.php">Manage Orders</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_customizations.php">Manage Customizations</a>
        </aside>
        
        <main class="admin-main">
            <h1>Dashboard</h1>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo $total_orders; ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card">
                    <h3>$<?php echo number_format($total_revenue ?? 0, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Products</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Users</p>
                </div>
            </div>
            
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td class="status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>