<?php
include '../inclusion/header.php';
include '../config.php';

// Filters and pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($status) && $status != 'all') {
    $where[] = "o.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($date_from)) {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($search)) {
    $where[] = "(o.order_number LIKE ? OR c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ssss';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN customers c ON o.customer_id = c.id $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// Get orders
$sql = "SELECT o.*, c.full_name, c.email, c.phone,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count,
        (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) as total_quantity
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        $where_clause 
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Order status counts for stats
$status_counts = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY status
");

// Bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (isset($_POST['selected_orders']) && !empty($_POST['selected_orders'])) {
        $selected_ids = implode(',', array_map('intval', $_POST['selected_orders']));
        $new_status = $_POST['bulk_action'];
        
        if (in_array($new_status, ['processing', 'shipped', 'delivered', 'cancelled'])) {
            $conn->query("UPDATE orders SET status = '$new_status', updated_at = NOW() WHERE id IN ($selected_ids)");
            $success = count($_POST['selected_orders']) . " order(s) updated to " . ucfirst($new_status);
            logActivity('bulk_order_update', "Updated " . count($_POST['selected_orders']) . " orders to $new_status");
        }
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Order Management</h1>
    <div class="page-subtitle">
        <i class="bi bi-cart-check me-1"></i> Process and manage customer orders
    </div>
</div>

<!-- Order Stats Cards -->
<div class="row mb-4">
    <?php
    $stats_sql = "SELECT 
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing,
        COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        SUM(total_amount) as today_revenue
        FROM orders 
        WHERE DATE(created_at) = CURDATE()";
    
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();
    ?>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1);">
                <i class="bi bi-clock" style="color: #ffc107;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="stats-label">Pending</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1);">
                <i class="bi bi-gear" style="color: #007bff;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['processing'] ?? 0; ?></div>
            <div class="stats-label">Processing</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(23, 162, 184, 0.1);">
                <i class="bi bi-truck" style="color: #17a2b8;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['shipped'] ?? 0; ?></div>
            <div class="stats-label">Shipped</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1);">
                <i class="bi bi-check-circle" style="color: #28a745;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['delivered'] ?? 0; ?></div>
            <div class="stats-label">Delivered</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1);">
                <i class="bi bi-x-circle" style="color: #dc3545;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['cancelled'] ?? 0; ?></div>
            <div class="stats-label">Cancelled</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 107, 139, 0.1);">
                <i class="bi bi-currency-dollar" style="color: var(--primary-pink);"></i>
            </div>
            <div class="stats-number">₱<?php echo number_format($stats['today_revenue'] ?? 0, 0); ?></div>
            <div class="stats-label">Today's Revenue</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-funnel me-2 flower-icon"></i> Filter Orders</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Order Status</label>
                <select class="form-select form-control-floral" name="status">
                    <option value="all">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control form-control-floral" name="date_from" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control form-control-floral" name="date_to" value="<?php echo $date_to; ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control form-control-floral" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Order #, Customer, Email...">
                </div>
            </div>
            
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-floral">
                        <i class="bi bi-filter me-2"></i> Apply Filters
                    </button>
                    <a href="list_orders.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i> Clear
                    </a>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-outline-primary" onclick="printOrders()">
                            <i class="bi bi-printer me-2"></i> Print List
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Orders Listing -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-receipt me-2 leaf-icon"></i> 
            Orders <span class="badge bg-primary"><?php echo $total_items; ?> total</span>
        </h5>
        <div class="btn-group">
            <button type="button" class="btn btn-floral dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download me-2"></i> Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?export=csv"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
                <li><a class="dropdown-item" href="?export=pdf"><i class="bi bi-file-pdf me-2"></i> PDF</a></li>
                <li><a class="dropdown-item" href="?export=excel"><i class="bi bi-file-excel me-2"></i> Excel</a></li>
            </ul>
        </div>
    </div>
    
    <div class="card-body">
        <form method="POST" id="bulkOrderForm">
            <!-- Bulk Actions -->
            <div class="d-flex gap-2 align-items-center mb-4 p-3 bg-light rounded">
                <select name="bulk_action" class="form-select form-control-floral" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="processing">Mark as Processing</option>
                    <option value="shipped">Mark as Shipped</option>
                    <option value="delivered">Mark as Delivered</option>
                    <option value="cancelled">Mark as Cancelled</option>
                </select>
                <button type="submit" class="btn btn-leaf">Apply</button>
                <div class="form-check ms-3">
                    <input type="checkbox" class="form-check-input" id="selectAllOrders">
                    <label class="form-check-label" for="selectAllOrders">Select All</label>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-floral data-table">
                    <thead>
                        <tr>
                            <th width="50"></th>
                            <th>Order Details</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $result->fetch_assoc()): ?>
                        <tr class="order-row" data-status="<?php echo $order['status']; ?>">
                            <td>
                                <input type="checkbox" name="selected_orders[]" value="<?php echo $order['id']; ?>" class="form-check-input order-checkbox">
                            </td>
                            <td>
                                <div class="fw-bold">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                        #<?php echo $order['order_number']; ?>
                                    </a>
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i> 
                                    <?php echo $order['delivery_type'] === 'pickup' ? 'Store Pickup' : 'Delivery'; ?>
                                </div>
                                <?php if($order['delivery_date']): ?>
                                <div class="small">
                                    <i class="bi bi-calendar me-1"></i> 
                                    <?php echo date('M d', strtotime($order['delivery_date'])); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo $order['full_name'] ?: 'Guest'; ?></div>
                                <div class="small text-muted"><?php echo $order['email']; ?></div>
                                <div class="small"><?php echo $order['phone']; ?></div>
                            </td>
                            <td>
                                <div class="text-center">
                                    <div class="fw-bold"><?php echo $order['item_count']; ?></div>
                                    <div class="small text-muted"><?php echo $order['total_quantity']; ?> items</div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                <div class="small text-muted">
                                    <?php echo $order['payment_method']; ?>
                                    <?php if($order['payment_status'] === 'paid'): ?>
                                    <span class="badge bg-success ms-1">Paid</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $status_config = [
                                    'pending' => ['class' => 'badge-pending', 'icon' => 'clock', 'label' => 'Pending'],
                                    'processing' => ['class' => 'badge-processing', 'icon' => 'gear', 'label' => 'Processing'],
                                    'shipped' => ['class' => 'badge-info', 'icon' => 'truck', 'label' => 'Shipped'],
                                    'delivered' => ['class' => 'badge-delivered', 'icon' => 'check-circle', 'label' => 'Delivered'],
                                    'cancelled' => ['class' => 'badge-cancelled', 'icon' => 'x-circle', 'label' => 'Cancelled']
                                ];
                                $status_info = $status_config[$order['status']] ?? $status_config['pending'];
                                ?>
                                <span class="badge badge-status <?php echo $status_info['class']; ?>">
                                    <i class="bi bi-<?php echo $status_info['icon']; ?> me-1"></i>
                                    <?php echo $status_info['label']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <div><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                    <div class="text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-outline-primary"
                                       data-bs-toggle="tooltip" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="?print=<?php echo $order['id']; ?>" 
                                       target="_blank"
                                       class="btn btn-outline-secondary"
                                       data-bs-toggle="tooltip" title="Print Invoice">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-gear"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item status-change" href="#" data-id="<?php echo $order['id']; ?>" data-status="processing">
                                                    <i class="bi bi-gear text-primary me-2"></i> Mark as Processing
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item status-change" href="#" data-id="<?php echo $order['id']; ?>" data-status="shipped">
                                                    <i class="bi bi-truck text-info me-2"></i> Mark as Shipped
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item status-change" href="#" data-id="<?php echo $order['id']; ?>" data-status="delivered">
                                                    <i class="bi bi-check-circle text-success me-2"></i> Mark as Delivered
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="return cancelOrder(<?php echo $order['id']; ?>)">
                                                    <i class="bi bi-x-circle me-2"></i> Cancel Order
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
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Order pagination">
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

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="statusForm" method="POST">
                    <input type="hidden" name="order_id" id="orderId">
                    <input type="hidden" name="new_status" id="newStatus">
                    
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select class="form-select form-control-floral" id="statusSelect">
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control form-control-floral" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="notify_customer" id="notifyCustomer" checked>
                        <label class="form-check-label" for="notifyCustomer">
                            Notify customer via email
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-floral" onclick="submitStatusChange()">Update Status</button>
            </div>
        </div>
    </div>
</div>

<style>
.order-row[data-status="pending"] { border-left: 4px solid #ffc107; }
.order-row[data-status="processing"] { border-left: 4px solid #007bff; }
.order-row[data-status="shipped"] { border-left: 4px solid #17a2b8; }
.order-row[data-status="delivered"] { border-left: 4px solid #28a745; }
.order-row[data-status="cancelled"] { border-left: 4px solid #dc3545; }

.badge-info { background: #d1ecf1; color: #0c5460; }
</style>

<script>
$(document).ready(function() {
    // Select all orders
    $('#selectAllOrders').change(function() {
        $('.order-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Bulk form submission
    $('#bulkOrderForm').submit(function(e) {
        if (!$('select[name="bulk_action"]').val()) {
            e.preventDefault();
            alert('Please select a bulk action.');
            return false;
        }
        if (!$('.order-checkbox:checked').length) {
            e.preventDefault();
            alert('Please select at least one order.');
            return false;
        }
        return confirm('Are you sure you want to update the selected orders?');
    });
    
    // Status change buttons
    $('.status-change').click(function(e) {
        e.preventDefault();
        const orderId = $(this).data('id');
        const newStatus = $(this).data('status');
        
        $('#orderId').val(orderId);
        $('#newStatus').val(newStatus);
        $('#statusSelect').val(newStatus);
        
        $('#statusModal').modal('show');
    });
    
    // Update status select when changed
    $('#statusSelect').change(function() {
        $('#newStatus').val($(this).val());
    });
});

function submitStatusChange() {
    const form = document.getElementById('statusForm');
    const formData = new FormData(form);
    
    fetch('update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
    
    $('#statusModal').modal('hide');
}

function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        window.location.href = '?cancel=' + orderId;
    }
    return false;
}

function printOrders() {
    window.open('?print=all&<?php echo http_build_query($_GET); ?>', '_blank');
}
</script>

<?php include '../inclusion/footer.php'; ?>