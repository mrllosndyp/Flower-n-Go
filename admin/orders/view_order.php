<?php
include '../inclusion/header.php';
include '../config.php';

if (!isset($_GET['id'])) {
    header("Location: list_orders.php");
    exit;
}

$order_id = (int)$_GET['id'];

// Fetch order details
$order_sql = "
    SELECT o.*, c.*, 
    d.driver_name, d.driver_phone, d.vehicle_number,
    a.address_line1, a.address_line2, a.city, a.province, a.zip_code, a.landmark
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN delivery_drivers d ON o.driver_id = d.id
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ?
";

$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header("Location: list_orders.php");
    exit;
}

$order = $order_result->fetch_assoc();

// Fetch order items
$items_sql = "
    SELECT oi.*, b.name as bouquet_name, b.image, b.price as unit_price, 
    (b.price * oi.quantity) as subtotal
    FROM order_items oi
    JOIN bouquets b ON oi.bouquet_id = b.id
    WHERE oi.order_id = ?
";

$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Fetch order history
$history_sql = "SELECT * FROM order_history WHERE order_id = ? ORDER BY created_at DESC";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $order_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    $notes = trim($_POST['notes']);
    $notify_customer = isset($_POST['notify_customer']) ? 1 : 0;
    
    // Update order status
    $update_stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    
    if ($update_stmt->execute()) {
        // Log to order history
        $history_stmt = $conn->prepare("
            INSERT INTO order_history (order_id, status, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $history_stmt->bind_param("isss", $order_id, $new_status, $notes, $_SESSION['name']);
        $history_stmt->execute();
        
        // Send notification if requested
        if ($notify_customer) {
            // Send email notification code here
        }
        
        $success = "Order status updated to " . ucfirst($new_status);
        logActivity('update_order_status', "Updated order #{$order['order_number']} to $new_status");
        
        // Refresh order data
        $order_stmt->execute();
        $order = $order_stmt->get_result()->fetch_assoc();
    } else {
        $error = "Failed to update order status.";
    }
}

// Calculate totals
$subtotal = $order['total_amount'] - $order['shipping_fee'] - $order['tax_amount'];
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">Order #<?php echo $order['order_number']; ?></h1>
            <div class="page-subtitle">
                <i class="bi bi-calendar me-1"></i> 
                <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?>
                <span class="mx-2">•</span>
                <span class="badge badge-status badge-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        </div>
        <div>
            <a href="list_orders.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-2"></i> Back to Orders
            </a>
            <a href="?print=<?php echo $order_id; ?>" target="_blank" class="btn btn-floral">
                <i class="bi bi-printer me-2"></i> Print Invoice
            </a>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
<div class="alert alert-success alert-floral d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem;"></i>
    <div><?php echo $success; ?></div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Left Column -->
    <div class="col-md-8">
        <!-- Order Items Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-basket me-2 flower-icon"></i> Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="80">Image</th>
                                <th>Bouquet</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if($item['image']): ?>
                                    <img src="../../uploads/bouquets/thumbs/<?php echo $item['image']; ?>" 
                                         alt="<?php echo $item['bouquet_name']; ?>" 
                                         class="rounded" 
                                         width="60" 
                                         height="60">
                                    <?php else: ?>
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px;">
                                        <i class="bi bi-flower2 text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo $item['bouquet_name']; ?></div>
                                    <div class="small text-muted">
                                        <?php if($item['vase_included']): ?>
                                        <span class="badge bg-info text-white">With Vase</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary" style="font-size: 1rem;">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                </td>
                                <td class="text-end">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-end fw-bold">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Order Timeline -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 leaf-icon"></i> Order Timeline</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php while($history = $history_result->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <div class="fw-bold"><?php echo ucfirst($history['status']); ?></div>
                                <div class="text-muted small">
                                    <?php echo date('M d, h:i A', strtotime($history['created_at'])); ?>
                                </div>
                            </div>
                            <?php if($history['notes']): ?>
                            <div class="mt-1"><?php echo $history['notes']; ?></div>
                            <?php endif; ?>
                            <div class="small text-muted mt-1">
                                <i class="bi bi-person me-1"></i> <?php echo $history['created_by']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <!-- Initial order placed -->
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <div class="fw-bold">Order Placed</div>
                                <div class="text-muted small">
                                    <?php echo date('M d, h:i A', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <div class="small text-muted mt-1">
                                Customer placed the order
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-md-4">
        <!-- Order Summary Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-receipt me-2 flower-icon"></i> Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6 text-muted">Subtotal:</div>
                    <div class="col-6 text-end">₱<?php echo number_format($subtotal, 2); ?></div>
                </div>
                
                <?php if($order['shipping_fee'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Shipping Fee:</div>
                    <div class="col-6 text-end">₱<?php echo number_format($order['shipping_fee'], 2); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if($order['tax_amount'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Tax:</div>
                    <div class="col-6 text-end">₱<?php echo number_format($order['tax_amount'], 2); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if($order['discount_amount'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Discount:</div>
                    <div class="col-6 text-end text-success">-₱<?php echo number_format($order['discount_amount'], 2); ?></div>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="row mb-3">
                    <div class="col-6 fw-bold">Total Amount:</div>
                    <div class="col-6 text-end fw-bold text-primary fs-5">
                        ₱<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6 text-muted">Payment Method:</div>
                    <div class="col-6 text-end">
                        <span class="badge bg-light text-dark">
                            <?php echo ucfirst($order['payment_method']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-6 text-muted">Payment Status:</div>
                    <div class="col-6 text-end">
                        <span class="badge <?php echo $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-person me-2 leaf-icon"></i> Customer Information</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <div class="user-avatar me-3">
                        <?php echo strtoupper(substr($order['full_name'] ?: 'C', 0, 1)); ?>
                    </div>
                    <div>
                        <div class="fw-bold"><?php echo $order['full_name']; ?></div>
                        <div class="small text-muted"><?php echo $order['email']; ?></div>
                        <div class="small"><?php echo $order['phone']; ?></div>
                    </div>
                </div>
                
                <?php if($order['address_line1']): ?>
                <hr>
                <h6 class="mb-2">Shipping Address</h6>
                <address class="small">
                    <?php echo $order['address_line1']; ?><br>
                    <?php if($order['address_line2']): echo $order['address_line2'] . '<br>'; endif; ?>
                    <?php echo $order['city'] . ', ' . $order['province']; ?><br>
                    <?php echo $order['zip_code']; ?><br>
                    <?php if($order['landmark']): echo 'Near: ' . $order['landmark']; endif; ?>
                </address>
                <?php endif; ?>
                
                <?php if($order['special_instructions']): ?>
                <div class="mt-3">
                    <h6 class="mb-2">Special Instructions</h6>
                    <div class="alert alert-light small">
                        <?php echo $order['special_instructions']; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Delivery Information -->
        <?php if($order['delivery_type'] === 'delivery'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-truck me-2 flower-icon"></i> Delivery Information</h5>
            </div>
            <div class="card-body">
                <?php if($order['delivery_date']): ?>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Delivery Date:</div>
                    <div class="col-6 text-end">
                        <?php echo date('F d, Y', strtotime($order['delivery_date'])); ?>
                    </div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-6 text-muted">Time Slot:</div>
                    <div class="col-6 text-end">
                        <?php echo $order['delivery_time_slot']; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($order['driver_name']): ?>
                <hr>
                <h6 class="mb-2">Assigned Driver</h6>
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-badge me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <div class="fw-bold"><?php echo $order['driver_name']; ?></div>
                        <div class="small text-muted"><?php echo $order['driver_phone']; ?></div>
                        <?php if($order['vehicle_number']): ?>
                        <div class="small">Vehicle: <?php echo $order['vehicle_number']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="bi bi-person-x text-muted" style="font-size: 2rem;"></i>
                    <div class="mt-2">No driver assigned yet</div>
                    <button class="btn btn-sm btn-floral mt-2">Assign Driver</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Update Status Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-pencil me-2 leaf-icon"></i> Update Order Status</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select class="form-select form-control-floral" name="new_status" required>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control form-control-floral" name="notes" rows="3" placeholder="Add notes about this status change..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="notify_customer" id="notifyCustomer" checked>
                        <label class="form-check-label" for="notifyCustomer">
                            Notify customer via email
                        </label>
                    </div>
                    
                    <button type="submit" name="update_status" class="btn btn-floral w-100">
                        <i class="bi bi-save me-2"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Order Actions Footer -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body text-center">
        <div class="btn-group" role="group">
            <a href="?resend_invoice=<?php echo $order_id; ?>" class="btn btn-outline-primary">
                <i class="bi bi-envelope me-2"></i> Resend Invoice
            </a>
            <a href="?refund=<?php echo $order_id; ?>" class="btn btn-outline-warning">
                <i class="bi bi-arrow-clockwise me-2"></i> Process Refund
            </a>
            <a href="?duplicate=<?php echo $order_id; ?>" class="btn btn-outline-success">
                <i class="bi bi-files me-2"></i> Duplicate Order
            </a>
            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                <i class="bi bi-trash me-2"></i> Cancel Order
            </button>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-pink);
    border: 2px solid white;
    box-shadow: 0 0 0 3px var(--secondary-pink);
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    border-left: 3px solid var(--primary-pink);
}

.badge-pending { background: #fff3cd; color: #856404; }
.badge-processing { background: #cce5ff; color: #004085; }
.badge-shipped { background: #d1ecf1; color: #0c5460; }
.badge-delivered { background: #d4edda; color: #155724; }
.badge-cancelled { background: #f8d7da; color: #721c24; }
</style>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        window.location.href = '?cancel=<?php echo $order_id; ?>';
    }
}

// Print invoice
function printInvoice() {
    window.open('print_invoice.php?id=<?php echo $order_id; ?>', '_blank');
}

// Update status with AJAX
$('form').submit(function(e) {
    if ($(this).find('button[name="update_status"]').length) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.post('update_status.php', formData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    }
});
</script>

<?php include '../inclusion/footer.php'; ?>