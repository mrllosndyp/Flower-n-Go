<?php
include '../inclusion/header.php';
include '../config.php';

if (!isset($_GET['id'])) {
    header("Location: list_customers.php");
    exit;
}

$customer_id = (int)$_GET['id'];

// Fetch customer details
$customer_sql = "
    SELECT c.*, 
    (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as total_orders,
    (SELECT SUM(o.total_amount) FROM orders o WHERE o.customer_id = c.id AND o.status = 'delivered') as total_spent,
    (SELECT AVG(o.total_amount) FROM orders o WHERE o.customer_id = c.id) as avg_order_value,
    (SELECT MAX(o.created_at) FROM orders o WHERE o.customer_id = c.id) as last_order_date,
    (SELECT MIN(o.created_at) FROM orders o WHERE o.customer_id = c.id) as first_order_date
    FROM customers c 
    WHERE c.id = ?
";

$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();

if ($customer_result->num_rows === 0) {
    header("Location: list_customers.php");
    exit;
}

$customer = $customer_result->fetch_assoc();

// Fetch recent orders
$orders_sql = "
    SELECT o.*, 
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
    FROM orders o 
    WHERE o.customer_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 10
";

$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $customer_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Fetch favorite bouquets
$favorites_sql = "
    SELECT b.name, b.image, b.category, COUNT(oi.id) as purchase_count
    FROM order_items oi
    JOIN bouquets b ON oi.bouquet_id = b.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.customer_id = ?
    GROUP BY b.id
    ORDER BY purchase_count DESC
    LIMIT 5
";

$favorites_stmt = $conn->prepare($favorites_sql);
$favorites_stmt->bind_param("i", $customer_id);
$favorites_stmt->execute();
$favorites_result = $favorites_stmt->get_result();

// Fetch addresses
$addresses_sql = "SELECT * FROM addresses WHERE customer_id = ?";
$addresses_stmt = $conn->prepare($addresses_sql);
$addresses_stmt->bind_param("i", $customer_id);
$addresses_stmt->execute();
$addresses_result = $addresses_stmt->get_result();

// Handle customer update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $loyalty_tier = $_POST['loyalty_tier'];
    $loyalty_points = (int)$_POST['loyalty_points'];
    $notes = trim($_POST['notes']);
    
    $update_stmt = $conn->prepare("
        UPDATE customers SET 
        full_name = ?, email = ?, phone = ?, status = ?, 
        loyalty_tier = ?, loyalty_points = ?, notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $update_stmt->bind_param(
        "sssssisi",
        $full_name, $email, $phone, $status,
        $loyalty_tier, $loyalty_points, $notes, $customer_id
    );
    
    if ($update_stmt->execute()) {
        $success = "Customer updated successfully!";
        // Refresh customer data
        $customer_stmt->execute();
        $customer = $customer_stmt->get_result()->fetch_assoc();
        logActivity('update_customer', "Updated customer: " . $full_name . " (ID: " . $customer_id . ")");
    } else {
        $error = "Failed to update customer.";
    }
}

$initials = strtoupper(substr($customer['full_name'] ?: 'C', 0, 2));
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">Customer Details</h1>
            <div class="page-subtitle">
                <i class="bi bi-person-circle me-1"></i> 
                View and manage customer information
            </div>
        </div>
        <div>
            <a href="list_customers.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-2"></i> Back to Customers
            </a>
            <button type="button" class="btn btn-floral" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                <i class="bi bi-envelope me-2"></i> Send Message
            </button>
        </div>
    </div>
</div>

<!-- Customer Profile Header -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="customer-avatar-lg mb-3">
                    <?php echo $initials; ?>
                </div>
                <div class="loyalty-badge">
                    <?php echo ucfirst($customer['loyalty_tier']); ?> Member
                </div>
            </div>
            
            <div class="col-md-5">
                <h3 class="mb-2"><?php echo $customer['full_name']; ?></h3>
                <div class="mb-2">
                    <i class="bi bi-envelope me-2 text-muted"></i>
                    <?php echo $customer['email']; ?>
                </div>
                <div class="mb-2">
                    <i class="bi bi-telephone me-2 text-muted"></i>
                    <?php echo $customer['phone']; ?>
                </div>
                <div>
                    <span class="badge badge-status badge-<?php echo $customer['status']; ?>">
                        <?php echo ucfirst($customer['status']); ?>
                    </span>
                    <span class="badge bg-light text-dark ms-2">
                        Customer Since: <?php echo date('M Y', strtotime($customer['created_at'])); ?>
                    </span>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="display-6 text-primary"><?php echo $customer['total_orders']; ?></div>
                        <div class="text-muted">Total Orders</div>
                    </div>
                    <div class="col-4">
                        <div class="display-6 text-success">₱<?php echo number_format($customer['total_spent'] ?: 0, 0); ?></div>
                        <div class="text-muted">Total Spent</div>
                    </div>
                    <div class="col-4">
                        <div class="display-6 text-warning">₱<?php echo number_format($customer['avg_order_value'] ?: 0, 0); ?></div>
                        <div class="text-muted">Avg. Order</div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-6">
                        <div class="small">
                            <i class="bi bi-calendar-check me-1"></i>
                            First Order: 
                            <?php echo $customer['first_order_date'] ? date('M d, Y', strtotime($customer['first_order_date'])) : 'N/A'; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="small">
                            <i class="bi bi-clock-history me-1"></i>
                            Last Order: 
                            <?php echo $customer['last_order_date'] ? date('M d, Y', strtotime($customer['last_order_date'])) : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-md-8">
        <!-- Recent Orders -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-cart-check me-2 flower-icon"></i> Recent Orders</h5>
            </div>
            <div class="card-body">
                <?php if ($orders_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="../orders/view_order.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                        #<?php echo $order['order_number']; ?>
                                    </a>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td class="fw-bold">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-status badge-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../orders/view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-cart-x" style="font-size: 3rem; color: #ddd;"></i>
                    <h5 class="mt-3">No Orders Yet</h5>
                    <p class="text-muted">This customer hasn't placed any orders yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Favorite Bouquets -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-heart me-2 leaf-icon"></i> Favorite Bouquets</h5>
            </div>
            <div class="card-body">
                <?php if ($favorites_result->num_rows > 0): ?>
                <div class="row">
                    <?php while($bouquet = $favorites_result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <?php if($bouquet['image']): ?>
                                <img src="../../uploads/bouquets/thumbs/<?php echo $bouquet['image']; ?>" 
                                     alt="<?php echo $bouquet['name']; ?>" 
                                     class="rounded mb-2" 
                                     width="80" 
                                     height="80">
                                <?php else: ?>
                                <div class="rounded bg-light d-flex align-items-center justify-content-center mb-2" 
                                     style="width: 80px; height: 80px; margin: 0 auto;">
                                    <i class="bi bi-flower2 text-muted"></i>
                                </div>
                                <?php endif; ?>
                                
                                <h6 class="mb-1"><?php echo $bouquet['name']; ?></h6>
                                <div class="small text-muted mb-2"><?php echo $bouquet['category']; ?></div>
                                <span class="badge bg-primary">
                                    <i class="bi bi-cart-check me-1"></i> 
                                    <?php echo $bouquet['purchase_count']; ?> purchases
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-flower2" style="font-size: 3rem; color: #ddd;"></i>
                    <p class="text-muted mt-2">No favorite bouquets yet</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-md-4">
        <!-- Edit Customer Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-pencil me-2 flower-icon"></i> Edit Customer</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control form-control-floral" name="full_name" 
                               value="<?php echo htmlspecialchars($customer['full_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control form-control-floral" name="email" 
                               value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control form-control-floral" name="phone" 
                               value="<?php echo htmlspecialchars($customer['phone']); ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select class="form-select form-control-floral" name="status">
                                <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="blocked" <?php echo $customer['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                            </select>
                        </div>
                        
                        <div class="col-6">
                            <label class="form-label">Loyalty Tier</label>
                            <select class="form-select form-control-floral" name="loyalty_tier">
                                <option value="none" <?php echo $customer['loyalty_tier'] === 'none' ? 'selected' : ''; ?>>None</option>
                                <option value="bronze" <?php echo $customer['loyalty_tier'] === 'bronze' ? 'selected' : ''; ?>>Bronze</option>
                                <option value="silver" <?php echo $customer['loyalty_tier'] === 'silver' ? 'selected' : ''; ?>>Silver</option>
                                <option value="gold" <?php echo $customer['loyalty_tier'] === 'gold' ? 'selected' : ''; ?>>Gold</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Loyalty Points</label>
                        <input type="number" class="form-control form-control-floral" name="loyalty_points" 
                               value="<?php echo $customer['loyalty_points']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control form-control-floral" name="notes" rows="3"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-floral w-100">
                        <i class="bi bi-save me-2"></i> Update Customer
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Customer Addresses -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-geo-alt me-2 leaf-icon"></i> Saved Addresses</h5>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="card-body">
                <?php if ($addresses_result->num_rows > 0): ?>
                <div class="list-group">
                    <?php while($address = $addresses_result->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold"><?php echo $address['address_type']; ?></div>
                                <div class="small">
                                    <?php echo $address['address_line1']; ?><br>
                                    <?php if($address['address_line2']): echo $address['address_line2'] . '<br>'; endif; ?>
                                    <?php echo $address['city'] . ', ' . $address['province']; ?><br>
                                    <?php echo $address['zip_code']; ?>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" 
                                        data-bs-toggle="tooltip" title="Edit Address">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger" 
                                        data-bs-toggle="tooltip" title="Delete Address"
                                        onclick="return confirm('Delete this address?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="bi bi-geo text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">No saved addresses</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-lightning me-2 flower-icon"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../orders/list_orders.php?customer=<?php echo $customer_id; ?>" class="btn btn-floral">
                        <i class="bi bi-cart me-2"></i> View All Orders
                    </a>
                    <button class="btn btn-leaf" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                        <i class="bi bi-envelope me-2"></i> Send Message
                    </button>
                    <button class="btn btn-outline-primary" onclick="createOrderForCustomer()">
                        <i class="bi bi-plus-circle me-2"></i> Create New Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Send Message to <?php echo $customer['full_name']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="messageForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Message Type</label>
                        <select class="form-select form-control-floral" id="messageType">
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control form-control-floral" id="messageSubject" 
                               placeholder="Subject line">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control form-control-floral" id="messageContent" rows="5" 
                                  placeholder="Type your message here..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="includePromo" checked>
                        <label class="form-check-label" for="includePromo">
                            Include special promotion (10% off next order)
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-floral" onclick="sendMessage()">
                        <i class="bi bi-send me-2"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addressForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Address Type</label>
                        <select class="form-select form-control-floral" name="address_type">
                            <option value="Home">Home</option>
                            <option value="Office">Office</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" class="form-control form-control-floral" name="address_line1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address Line 2 (Optional)</label>
                        <input type="text" class="form-control form-control-floral" name="address_line2">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control form-control-floral" name="city" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control form-control-floral" name="province" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Zip Code</label>
                            <input type="text" class="form-control form-control-floral" name="zip_code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Landmark (Optional)</label>
                            <input type="text" class="form-control form-control-floral" name="landmark">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-floral">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.customer-avatar-lg {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-pink) 0%, #ff8e8e 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
    margin: 0 auto;
    box-shadow: 0 5px 15px rgba(255, 107, 139, 0.3);
}

.loyalty-badge {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #856404;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
}
</style>

<script>
function sendMessage() {
    const type = document.getElementById('messageType').value;
    const subject = document.getElementById('messageSubject').value;
    const content = document.getElementById('messageContent').value;
    const includePromo = document.getElementById('includePromo').checked;
    
    if (!content.trim()) {
        alert('Please enter a message.');
        return;
    }
    
    // Simulate sending message
    alert('Message sent to customer via ' + type.toUpperCase());
    $('#sendMessageModal').modal('hide');
    
    // Clear form
    document.getElementById('messageSubject').value = '';
    document.getElementById('messageContent').value = '';
}

function createOrderForCustomer() {
    window.location.href = '../orders/add_order.php?customer_id=<?php echo $customer_id; ?>';
}

// Address form submission
document.getElementById('addressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Implement address saving
    alert('Address saved successfully!');
    $('#addAddressModal').modal('hide');
    location.reload();
});

// Calculate customer lifetime value
$(document).ready(function() {
    const totalSpent = <?php echo $customer['total_spent'] ?: 0; ?>;
    const orderCount = <?php echo $customer['total_orders'] ?: 0; ?>;
    
    if (orderCount > 0) {
        const avgOrder = totalSpent / orderCount;
        const loyaltyScore = Math.min(100, Math.floor(totalSpent / 100) + (orderCount * 5));
        
        // Update UI with calculated values
        $('.loyalty-badge').append(` <small>(${loyaltyScore} pts)</small>`);
    }
});
</script>

<?php include '../inclusion/footer.php'; ?>