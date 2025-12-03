<?php
include 'inclusion/header.php';
include 'config.php';

// Get statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM bouquets WHERE status='available'");
$stats['bouquets'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['today_orders'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status='delivered' AND MONTH(created_at) = MONTH(CURDATE())");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM customers");
$stats['customers'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM bouquets WHERE stock < 10");
$stats['low_stock'] = $result->fetch_assoc()['total'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, c.full_name, c.email 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Best selling bouquets
$best_sellers = $conn->query("
    SELECT b.name, b.image, SUM(oi.quantity) as total_sold, b.price
    FROM order_items oi
    JOIN bouquets b ON oi.bouquet_id = b.id
    WHERE MONTH(oi.created_at) = MONTH(CURDATE())
    GROUP BY b.id
    ORDER BY total_sold DESC
    LIMIT 5
");
?>

<div class="page-header">
    <h1 class="page-title">Dashboard Overview</h1>
    <div class="page-subtitle">
        <i class="bi bi-calendar-check me-1"></i> Welcome back, <?php echo $_SESSION['name']; ?>!
        <span class="mx-2">•</span>
        <span id="live-clock"></span>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 107, 139, 0.1);">
                <i class="bi bi-flower2" style="color: var(--primary-pink);"></i>
            </div>
            <div class="stats-number"><?php echo $stats['bouquets']; ?></div>
            <div class="stats-label">Available Bouquets</div>
            <a href="products/list_bouquets.php" class="btn btn-sm btn-floral mt-2">View All</a>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(46, 139, 87, 0.1);">
                <i class="bi bi-cart-check" style="color: var(--leaf-green);"></i>
            </div>
            <div class="stats-number"><?php echo $stats['today_orders']; ?></div>
            <div class="stats-label">Today's Orders</div>
            <a href="orders/list_orders.php" class="btn btn-sm btn-leaf mt-2">Process</a>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 215, 0, 0.1);">
                <i class="bi bi-currency-dollar" style="color: var(--gold);"></i>
            </div>
            <div class="stats-number">₱<?php echo number_format($stats['monthly_revenue'], 2); ?></div>
            <div class="stats-label">Monthly Revenue</div>
            <a href="reports/sales_report.php" class="btn btn-sm btn-floral mt-2">Reports</a>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(230, 230, 250, 0.1);">
                <i class="bi bi-people" style="color: var(--lavender);"></i>
            </div>
            <div class="stats-number"><?php echo $stats['customers']; ?></div>
            <div class="stats-label">Total Customers</div>
            <a href="customers/list_customers.php" class="btn btn-sm btn-leaf mt-2">Manage</a>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <div class="col-md-8">
        <!-- Recent Orders -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 leaf-icon"></i> Recent Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td><a href="orders/view_order.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">#FLW<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <?php echo strtoupper(substr($order['full_name'] ?: 'C', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo $order['full_name'] ?: 'Guest'; ?></div>
                                            <small class="text-muted"><?php echo $order['email']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'pending' => 'badge-pending',
                                        'processing' => 'badge-processing',
                                        'delivered' => 'badge-delivered',
                                        'cancelled' => 'badge-cancelled'
                                    ];
                                    ?>
                                    <span class="badge badge-status <?php echo $status_class[$order['status']] ?? 'badge-pending'; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Revenue Chart -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2 flower-icon"></i> Revenue Trend (Last 7 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="150"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Best Sellers -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-trophy me-2 flower-icon"></i> Best Sellers This Month</h5>
            </div>
            <div class="card-body">
                <?php while($item = $best_sellers->fetch_assoc()): ?>
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <div class="flex-shrink-0">
                        <?php if($item['image']): ?>
                        <img src="../uploads/bouquets/thumbs/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="rounded" width="60" height="60">
                        <?php else: ?>
                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-flower2 text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">₱<?php echo number_format($item['price'], 2); ?></small>
                            <span class="badge bg-primary"><?php echo $item['total_sold']; ?> sold</span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-lightning me-2 leaf-icon"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="products/add_bouquet.php" class="btn btn-floral">
                        <i class="bi bi-plus-circle me-2"></i> Add New Bouquet
                    </a>
                    <a href="orders/list_orders.php?filter=pending" class="btn btn-leaf">
                        <i class="bi bi-clock me-2"></i> Process Pending Orders
                    </a>
                    <a href="inventory/stock_levels.php" class="btn btn-floral">
                        <i class="bi bi-exclamation-triangle me-2"></i> Check Low Stock (<?php echo $stats['low_stock']; ?>)
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-heart-pulse me-2 flower-icon"></i> System Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Server Load</span>
                        <span class="text-success">Normal</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: 45%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Database</span>
                        <span class="text-success">Connected</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Storage</span>
                        <span class="text-warning">75% used</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: 75%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Revenue (₱)',
            data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
            borderColor: '#ff6b8b',
            backgroundColor: 'rgba(255, 107, 139, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    drawBorder: false
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php include 'inclusion/footer.php'; ?>