<?php
include '../inclusion/header.php';
include '../config.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$loyalty_tier = $_GET['loyalty_tier'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if (!empty($status) && $status != 'all') {
    $where[] = "c.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($loyalty_tier) && $loyalty_tier != 'all') {
    $where[] = "c.loyalty_tier = ?";
    $params[] = $loyalty_tier;
    $types .= 's';
}

if (!empty($date_from)) {
    $where[] = "DATE(c.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where[] = "DATE(c.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM customers c $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// Get customers with stats
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count,
        (SELECT SUM(o.total_amount) FROM orders o WHERE o.customer_id = c.id AND o.status = 'delivered') as total_spent,
        (SELECT MAX(o.created_at) FROM orders o WHERE o.customer_id = c.id) as last_order_date
        FROM customers c 
        $where_clause 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Customer stats
$stats_sql = "
    SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
        COUNT(CASE WHEN loyalty_tier = 'gold' THEN 1 END) as gold_members,
        COUNT(CASE WHEN loyalty_tier = 'silver' THEN 1 END) as silver_members,
        COUNT(CASE WHEN loyalty_tier = 'bronze' THEN 1 END) as bronze_members,
        (SELECT COUNT(DISTINCT customer_id) FROM orders WHERE DATE(created_at) = CURDATE()) as new_orders_today
    FROM customers
";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<div class="page-header">
    <h1 class="page-title">Customer Management</h1>
    <div class="page-subtitle">
        <i class="bi bi-people me-1"></i> Manage your valued flower customers
    </div>
</div>

<!-- Customer Stats -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1);">
                <i class="bi bi-people-fill" style="color: #28a745;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['total_customers']; ?></div>
            <div class="stats-label">Total Customers</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1);">
                <i class="bi bi-person-check" style="color: #007bff;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['active_customers']; ?></div>
            <div class="stats-label">Active</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1);">
                <i class="bi bi-trophy" style="color: #ffc107;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['gold_members']; ?></div>
            <div class="stats-label">Gold Members</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(108, 117, 125, 0.1);">
                <i class="bi bi-award" style="color: #6c757d;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['silver_members']; ?></div>
            <div class="stats-label">Silver Members</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1);">
                <i class="bi bi-gem" style="color: #dc3545;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['bronze_members']; ?></div>
            <div class="stats-label">Bronze Members</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 107, 139, 0.1);">
                <i class="bi bi-cart-plus" style="color: var(--primary-pink);"></i>
            </div>
            <div class="stats-number"><?php echo $stats['new_orders_today']; ?></div>
            <div class="stats-label">Orders Today</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-funnel me-2 flower-icon"></i> Filter Customers</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control form-control-floral" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Name, email, phone...">
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select form-control-floral" name="status">
                    <option value="all">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Loyalty Tier</label>
                <select class="form-select form-control-floral" name="loyalty_tier">
                    <option value="all">All Tiers</option>
                    <option value="gold" <?php echo $loyalty_tier === 'gold' ? 'selected' : ''; ?>>Gold</option>
                    <option value="silver" <?php echo $loyalty_tier === 'silver' ? 'selected' : ''; ?>>Silver</option>
                    <option value="bronze" <?php echo $loyalty_tier === 'bronze' ? 'selected' : ''; ?>>Bronze</option>
                    <option value="none" <?php echo $loyalty_tier === 'none' ? 'selected' : ''; ?>>None</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control form-control-floral" name="date_from" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control form-control-floral" name="date_to" value="<?php echo $date_to; ?>">
            </div>
            
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-floral w-100">
                    <i class="bi bi-filter"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Customers Listing -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-people-fill me-2 leaf-icon"></i> 
            Customers <span class="badge bg-primary"><?php echo $total_items; ?> total</span>
        </h5>
        <div>
            <a href="?export=csv" class="btn btn-outline-primary">
                <i class="bi bi-download me-2"></i> Export
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-floral data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Orders & Spending</th>
                        <th>Loyalty</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($customer = $result->fetch_assoc()): 
                        $initials = strtoupper(substr($customer['full_name'] ?: 'C', 0, 2));
                        $last_order = $customer['last_order_date'] ? date('M d, Y', strtotime($customer['last_order_date'])) : 'Never';
                        $total_spent = $customer['total_spent'] ?: 0;
                    ?>
                    <tr class="customer-row">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo $initials; ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo $customer['full_name']; ?></div>
                                    <div class="small text-muted">ID: C<?php echo str_pad($customer['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <div><i class="bi bi-envelope me-1"></i> <?php echo $customer['email']; ?></div>
                                <div><i class="bi bi-telephone me-1"></i> <?php echo $customer['phone']; ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="fw-bold text-primary"><?php echo $customer['order_count']; ?></div>
                                        <div class="small text-muted">Orders</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="fw-bold text-success">₱<?php echo number_format($total_spent, 0); ?></div>
                                        <div class="small text-muted">Spent</div>
                                    </div>
                                </div>
                            </div>
                            <div class="small text-center text-muted mt-1">
                                Last: <?php echo $last_order; ?>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $loyalty_classes = [
                                'gold' => 'bg-warning text-dark',
                                'silver' => 'bg-secondary',
                                'bronze' => 'bg-danger',
                                'none' => 'bg-light text-dark'
                            ];
                            $loyalty_class = $loyalty_classes[$customer['loyalty_tier']] ?? 'bg-light text-dark';
                            ?>
                            <span class="badge <?php echo $loyalty_class; ?>">
                                <i class="bi bi-<?php echo $customer['loyalty_tier'] === 'gold' ? 'trophy' : ($customer['loyalty_tier'] === 'silver' ? 'award' : 'gem'); ?> me-1"></i>
                                <?php echo ucfirst($customer['loyalty_tier']); ?>
                            </span>
                            <?php if($customer['loyalty_points'] > 0): ?>
                            <div class="small mt-1">
                                <i class="bi bi-star-fill text-warning"></i> 
                                <?php echo number_format($customer['loyalty_points']); ?> points
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $status_classes = [
                                'active' => 'badge-available',
                                'inactive' => 'badge-secondary',
                                'blocked' => 'badge-cancelled'
                            ];
                            ?>
                            <span class="badge badge-status <?php echo $status_classes[$customer['status']] ?? 'badge-available'; ?>">
                                <?php echo ucfirst($customer['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="small">
                                <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="customer_details.php?id=<?php echo $customer['id']; ?>" 
                                   class="btn btn-outline-primary"
                                   data-bs-toggle="tooltip" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="#" 
                                   class="btn btn-outline-success"
                                   data-bs-toggle="tooltip" title="Send Message">
                                    <i class="bi bi-envelope"></i>
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-gear"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                                <i class="bi bi-pencil me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <?php if($customer['status'] === 'active'): ?>
                                            <a class="dropdown-item text-warning" href="?deactivate=<?php echo $customer['id']; ?>" onclick="return confirm('Deactivate this customer?')">
                                                <i class="bi bi-person-x me-2"></i> Deactivate
                                            </a>
                                            <?php else: ?>
                                            <a class="dropdown-item text-success" href="?activate=<?php echo $customer['id']; ?>" onclick="return confirm('Activate this customer?')">
                                                <i class="bi bi-person-check me-2"></i> Activate
                                            </a>
                                            <?php endif; ?>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="return confirmDelete(<?php echo $customer['id']; ?>, '<?php echo addslashes($customer['full_name']); ?>')">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Customer pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Customer Segmentation -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-pie-chart me-2 flower-icon"></i> Customer Segmentation</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <canvas id="customerSegmentationChart" height="200"></canvas>
            </div>
            <div class="col-md-4">
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-warning me-2">●</span>
                            Gold Members
                        </div>
                        <span class="badge bg-light text-dark"><?php echo $stats['gold_members']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-2">●</span>
                            Silver Members
                        </div>
                        <span class="badge bg-light text-dark"><?php echo $stats['silver_members']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-danger me-2">●</span>
                            Bronze Members
                        </div>
                        <span class="badge bg-light text-dark"><?php echo $stats['bronze_members']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light me-2">●</span>
                            No Tier
                        </div>
                        <span class="badge bg-light text-dark">
                            <?php echo $stats['total_customers'] - ($stats['gold_members'] + $stats['silver_members'] + $stats['bronze_members']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Customer Segmentation Chart
const ctx = document.getElementById('customerSegmentationChart').getContext('2d');
const customerChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Gold Members', 'Silver Members', 'Bronze Members', 'No Tier'],
        datasets: [{
            data: [
                <?php echo $stats['gold_members']; ?>,
                <?php echo $stats['silver_members']; ?>,
                <?php echo $stats['bronze_members']; ?>,
                <?php echo $stats['total_customers'] - ($stats['gold_members'] + $stats['silver_members'] + $stats['bronze_members']); ?>
            ],
            backgroundColor: [
                '#ffc107',
                '#6c757d',
                '#dc3545',
                '#e9ecef'
            ],
            borderWidth: 2,
            borderColor: 'white'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed + ' customers';
                        return label;
                    }
                }
            }
        }
    }
});

function editCustomer(id) {
    // Implement edit functionality
    alert('Edit customer ' + id);
}

function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete customer "' + name + '"? This action cannot be undone.')) {
        window.location.href = '?delete=' + id;
    }
    return false;
}

// Highlight VIP customers
$(document).ready(function() {
    $('.customer-row').each(function() {
        const spent = parseInt($(this).find('.text-success').text().replace('₱', '').replace(',', ''));
        const tier = $(this).find('.badge').text().toLowerCase();
        
        if (tier.includes('gold') || spent > 10000) {
            $(this).addClass('table-warning');
        }
    });
});
</script>

<?php include '../inclusion/footer.php'; ?>