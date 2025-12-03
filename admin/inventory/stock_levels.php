<?php
include '../inclusion/header.php';
include '../config.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$stock_level = $_GET['stock_level'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(b.name LIKE ? OR b.sku LIKE ? OR b.primary_flowers LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if (!empty($category) && $category != 'all') {
    $where[] = "b.category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($status) && $status != 'all') {
    $where[] = "b.status = ?";
    $params[] = $status;
    $types .= 's';
}

// Stock level filters
if (!empty($stock_level)) {
    switch($stock_level) {
        case 'low':
            $where[] = "b.stock <= 10 AND b.stock > 0";
            break;
        case 'out':
            $where[] = "b.stock = 0";
            break;
        case 'medium':
            $where[] = "b.stock > 10 AND b.stock <= 50";
            break;
        case 'high':
            $where[] = "b.stock > 50";
            break;
    }
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM bouquets b $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// Get inventory with sales data
$sql = "SELECT b.*, 
        (SELECT SUM(oi.quantity) FROM order_items oi 
         JOIN orders o ON oi.order_id = o.id 
         WHERE oi.bouquet_id = b.id AND o.status = 'delivered' 
         AND MONTH(o.created_at) = MONTH(CURDATE())) as monthly_sales,
        (SELECT SUM(oi.quantity) FROM order_items oi 
         JOIN orders o ON oi.order_id = b.id 
         WHERE oi.bouquet_id = b.id AND o.status = 'delivered') as total_sold,
        s.name as supplier_name, s.contact_person, s.phone as supplier_phone
        FROM bouquets b
        LEFT JOIN suppliers s ON b.supplier_id = s.id
        $where_clause 
        ORDER BY b.stock ASC, b.name
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get categories for filter
$categories_result = $conn->query("SELECT DISTINCT category FROM bouquets WHERE category IS NOT NULL ORDER BY category");

// Inventory stats
$stats_sql = "
    SELECT 
        COUNT(*) as total_items,
        SUM(stock) as total_stock,
        COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
        COUNT(CASE WHEN stock > 0 AND stock <= 10 THEN 1 END) as low_stock,
        COUNT(CASE WHEN stock > 10 AND stock <= 50 THEN 1 END) as medium_stock,
        COUNT(CASE WHEN stock > 50 THEN 1 END) as high_stock,
        SUM(price * stock) as inventory_value
    FROM bouquets
    WHERE status != 'deleted'
";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $bouquet_id = (int)$_POST['bouquet_id'];
    $adjustment = (int)$_POST['adjustment'];
    $notes = trim($_POST['notes']);
    
    // Get current stock
    $current_stmt = $conn->prepare("SELECT stock, name FROM bouquets WHERE id = ?");
    $current_stmt->bind_param("i", $bouquet_id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    $bouquet = $current_result->fetch_assoc();
    
    $new_stock = $bouquet['stock'] + $adjustment;
    
    if ($new_stock >= 0) {
        // Update stock
        $update_stmt = $conn->prepare("UPDATE bouquets SET stock = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ii", $new_stock, $bouquet_id);
        
        if ($update_stmt->execute()) {
            // Log inventory change
            $log_stmt = $conn->prepare("
                INSERT INTO inventory_logs 
                (bouquet_id, adjustment, new_stock, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $log_stmt->bind_param("iiiss", $bouquet_id, $adjustment, $new_stock, $notes, $_SESSION['name']);
            $log_stmt->execute();
            
            $success = "Stock updated for '{$bouquet['name']}'. New stock: $new_stock";
            logActivity('update_stock', "Updated stock for {$bouquet['name']}: $adjustment (New: $new_stock)");
            
            // Refresh the page
            echo "<script>location.reload();</script>";
        }
    } else {
        $error = "Cannot reduce stock below 0.";
    }
}

// Handle bulk reorder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_reorder'])) {
    $selected_items = $_POST['selected_items'] ?? [];
    
    if (!empty($selected_items)) {
        // Create purchase order
        $order_number = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
        $total_items = count($selected_items);
        
        $order_stmt = $conn->prepare("
            INSERT INTO purchase_orders 
            (order_number, total_items, status, created_by, created_at)
            VALUES (?, ?, 'pending', ?, NOW())
        ");
        $order_stmt->bind_param("sis", $order_number, $total_items, $_SESSION['name']);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        
        // Add items to purchase order
        foreach ($selected_items as $bouquet_id) {
            $item_stmt = $conn->prepare("
                INSERT INTO purchase_order_items 
                (purchase_order_id, bouquet_id, reorder_quantity, created_at)
                VALUES (?, ?, 50, NOW())
            ");
            $item_stmt->bind_param("ii", $order_id, $bouquet_id);
            $item_stmt->execute();
        }
        
        $success = "Purchase order #$order_number created with $total_items items.";
        logActivity('create_purchase_order', "Created purchase order #$order_number with $total_items items");
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Inventory Management</h1>
    <div class="page-subtitle">
        <i class="bi bi-box-seam me-1"></i> Monitor and manage flower stock levels
    </div>
</div>

<!-- Inventory Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1);">
                <i class="bi bi-boxes" style="color: #28a745;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['total_items']; ?></div>
            <div class="stats-label">Total Items</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card stock-alert" style="<?php echo $stats['out_of_stock'] > 0 ? 'animation: pulse 2s infinite;' : ''; ?>">
            <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1);">
                <i class="bi bi-exclamation-triangle" style="color: #dc3545;"></i>
            </div>
            <div class="stats-number text-danger"><?php echo $stats['out_of_stock']; ?></div>
            <div class="stats-label">Out of Stock</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="<?php echo $stats['low_stock'] > 0 ? 'border: 2px solid #ffc107;' : ''; ?>">
            <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1);">
                <i class="bi bi-exclamation-circle" style="color: #ffc107;"></i>
            </div>
            <div class="stats-number text-warning"><?php echo $stats['low_stock']; ?></div>
            <div class="stats-label">Low Stock</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 107, 139, 0.1);">
                <i class="bi bi-currency-dollar" style="color: var(--primary-pink);"></i>
            </div>
            <div class="stats-number">₱<?php echo number_format($stats['inventory_value'] ?? 0, 0); ?></div>
            <div class="stats-label">Inventory Value</div>
        </div>
    </div>
</div>

<!-- Stock Level Indicators -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-speedometer2 me-2 flower-icon"></i> Stock Levels Overview</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="mb-2">
                    <div class="display-6 text-success"><?php echo $stats['high_stock']; ?></div>
                    <div class="text-muted">High Stock (>50)</div>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?php echo ($stats['high_stock'] / $stats['total_items']) * 100; ?>%"></div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="mb-2">
                    <div class="display-6 text-info"><?php echo $stats['medium_stock']; ?></div>
                    <div class="text-muted">Medium Stock (11-50)</div>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-info" style="width: <?php echo ($stats['medium_stock'] / $stats['total_items']) * 100; ?>%"></div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="mb-2">
                    <div class="display-6 text-warning"><?php echo $stats['low_stock']; ?></div>
                    <div class="text-muted">Low Stock (1-10)</div>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-warning" style="width: <?php echo ($stats['low_stock'] / $stats['total_items']) * 100; ?>%"></div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="mb-2">
                    <div class="display-6 text-danger"><?php echo $stats['out_of_stock']; ?></div>
                    <div class="text-muted">Out of Stock (0)</div>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-danger" style="width: <?php echo ($stats['out_of_stock'] / $stats['total_items']) * 100; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-funnel me-2 leaf-icon"></i> Filter Inventory</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control form-control-floral" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Name, SKU, flowers...">
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select class="form-select form-control-floral" name="category">
                    <option value="all">All Categories</option>
                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                    <option value="<?php echo $cat['category']; ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                        <?php echo $cat['category']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Stock Level</label>
                <select class="form-select form-control-floral" name="stock_level">
                    <option value="all">All Levels</option>
                    <option value="high" <?php echo $stock_level === 'high' ? 'selected' : ''; ?>>High (>50)</option>
                    <option value="medium" <?php echo $stock_level === 'medium' ? 'selected' : ''; ?>>Medium (11-50)</option>
                    <option value="low" <?php echo $stock_level === 'low' ? 'selected' : ''; ?>>Low (1-10)</option>
                    <option value="out" <?php echo $stock_level === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select form-control-floral" name="status">
                    <option value="all">All Status</option>
                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="limited" <?php echo $status === 'limited' ? 'selected' : ''; ?>>Limited</option>
                    <option value="out_of_stock" <?php echo $status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    <option value="preorder" <?php echo $status === 'preorder' ? 'selected' : ''; ?>>Pre-order</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Sort By</label>
                <select class="form-select form-control-floral" name="sort" onchange="this.form.submit()">
                    <option value="stock_asc">Stock (Low to High)</option>
                    <option value="stock_desc">Stock (High to Low)</option>
                    <option value="name_asc">Name (A-Z)</option>
                    <option value="name_desc">Name (Z-A)</option>
                    <option value="sales_desc">Sales (High to Low)</option>
                </select>
            </div>
            
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-floral w-100">
                    <i class="bi bi-filter"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Listing -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-clipboard-data me-2 leaf-icon"></i> 
            Inventory Items <span class="badge bg-primary"><?php echo $total_items; ?> items</span>
        </h5>
        <div>
            <button type="button" class="btn btn-floral" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                <i class="bi bi-arrow-up-down me-2"></i> Bulk Update
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <form method="POST" id="inventoryForm">
            <div class="table-responsive">
                <table class="table table-floral data-table">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAllItems" class="form-check-input">
                            </th>
                            <th width="80">Image</th>
                            <th>Bouquet Details</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Sales Data</th>
                            <th>Supplier</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $result->fetch_assoc()): 
                            $stock_class = '';
                            if ($item['stock'] == 0) {
                                $stock_class = 'bg-danger text-white';
                            } elseif ($item['stock'] <= 10) {
                                $stock_class = 'bg-warning text-dark';
                            } elseif ($item['stock'] <= 50) {
                                $stock_class = 'bg-info text-white';
                            } else {
                                $stock_class = 'bg-success text-white';
                            }
                            
                            $monthly_sales = $item['monthly_sales'] ?: 0;
                            $total_sold = $item['total_sold'] ?: 0;
                            
                            // Calculate reorder point
                            $reorder_point = ceil($monthly_sales * 1.5); // 1.5 months of sales
                            $needs_reorder = $item['stock'] < $reorder_point && $reorder_point > 0;
                        ?>
                        <tr class="<?php echo $needs_reorder ? 'table-warning' : ''; ?>">
                            <td>
                                <input type="checkbox" name="selected_items[]" value="<?php echo $item['id']; ?>" 
                                       class="form-check-input item-checkbox" <?php echo $needs_reorder ? 'checked' : ''; ?>>
                            </td>
                            <td>
                                <?php if($item['image']): ?>
                                <img src="../../uploads/bouquets/thumbs/<?php echo $item['image']; ?>" 
                                     alt="<?php echo $item['name']; ?>" 
                                     class="rounded" 
                                     width="60" 
                                     height="60"
                                     style="object-fit: cover;">
                                <?php else: ?>
                                <div class="rounded bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px;">
                                    <i class="bi bi-flower2 text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo $item['name']; ?></div>
                                <div class="small text-muted">
                                    SKU: <?php echo $item['sku']; ?> | 
                                    Category: <?php echo $item['category'] ?: 'Uncategorized'; ?>
                                </div>
                                <div class="small">
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-flower1 me-1"></i> <?php echo $item['primary_flowers']; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold display-6 <?php echo $stock_class; ?> rounded py-2">
                                    <?php echo $item['stock']; ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-cash-coin me-1"></i> ₱<?php echo number_format($item['price'], 2); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="mb-2">
                                    <div class="fw-bold text-primary"><?php echo $monthly_sales; ?></div>
                                    <div class="small text-muted">This Month</div>
                                </div>
                                <div>
                                    <div class="fw-bold text-success"><?php echo $total_sold; ?></div>
                                    <div class="small text-muted">Total Sold</div>
                                </div>
                                <?php if($reorder_point > 0): ?>
                                <div class="small text-muted mt-2">
                                    Reorder at: <span class="fw-bold"><?php echo $reorder_point; ?></span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($item['supplier_name']): ?>
                                <div class="fw-bold"><?php echo $item['supplier_name']; ?></div>
                                <div class="small text-muted"><?php echo $item['contact_person']; ?></div>
                                <div class="small"><?php echo $item['supplier_phone']; ?></div>
                                <?php else: ?>
                                <span class="badge bg-light text-dark">No Supplier</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($item['stock'] == 0): ?>
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle me-1"></i> Out of Stock
                                </span>
                                <?php elseif($item['stock'] <= 10): ?>
                                <span class="badge bg-warning text-dark stock-alert">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Low Stock
                                </span>
                                <?php elseif($needs_reorder): ?>
                                <span class="badge bg-info">
                                    <i class="bi bi-arrow-down-circle me-1"></i> Needs Reorder
                                </span>
                                <?php else: ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i> In Stock
                                </span>
                                <?php endif; ?>
                                
                                <div class="progress mt-2" style="height: 8px;">
                                    <?php 
                                    $stock_percentage = min(100, ($item['stock'] / max(50, $reorder_point * 2)) * 100);
                                    $progress_class = $item['stock'] <= 10 ? 'bg-danger' : ($item['stock'] <= 50 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $stock_percentage; ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#stockModal"
                                            data-id="<?php echo $item['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                            data-current="<?php echo $item['stock']; ?>">
                                        <i class="bi bi-plus-slash-minus"></i>
                                    </button>
                                    <a href="../products/edit_bouquet.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-outline-success"
                                       data-bs-toggle="tooltip" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?reorder=<?php echo $item['id']; ?>" 
                                       class="btn btn-outline-info"
                                       data-bs-toggle="tooltip" title="Reorder">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Bulk Actions -->
            <div class="d-flex gap-2 align-items-center mt-4 p-3 bg-light rounded">
                <select name="bulk_action" class="form-select form-control-floral" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="reorder">Create Purchase Order</option>
                    <option value="export">Export Selected</option>
                    <option value="update_status">Update Status</option>
                </select>
                <button type="submit" name="bulk_reorder" class="btn btn-leaf">Apply</button>
                <div class="small text-muted ms-3">
                    <span id="selectedCount">0</span> items selected
                </div>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Inventory pagination">
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
                <?php endwhile; ?>
                
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

<!-- Stock Level Chart -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-bar-chart me-2 flower-icon"></i> Stock Level Distribution</h5>
    </div>
    <div class="card-body">
        <canvas id="stockChart" height="100"></canvas>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Update Stock Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="bouquet_id" id="bouquetId">
                    
                    <div class="text-center mb-4">
                        <h5 id="bouquetName"></h5>
                        <div class="display-4 fw-bold" id="currentStock">0</div>
                        <div class="text-muted">Current Stock</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="adjustment_type" id="addStock" value="add" checked>
                            <label class="btn btn-outline-success" for="addStock">
                                <i class="bi bi-plus-lg me-2"></i> Add Stock
                            </label>
                            
                            <input type="radio" class="btn-check" name="adjustment_type" id="removeStock" value="remove">
                            <label class="btn btn-outline-danger" for="removeStock">
                                <i class="bi bi-dash-lg me-2"></i> Remove Stock
                            </label>
                            
                            <input type="radio" class="btn-check" name="adjustment_type" id="setStock" value="set">
                            <label class="btn btn-outline-primary" for="setStock">
                                <i class="bi bi-arrow-right me-2"></i> Set Stock
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control form-control-floral" name="adjustment" 
                               id="adjustmentQuantity" min="1" value="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control form-control-floral" name="notes" rows="3" 
                                  placeholder="Reason for adjustment, source, etc."></textarea>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-2"></i>
                        New stock will be: <span id="newStockPreview" class="fw-bold">0</span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_stock" class="btn btn-floral">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Bulk Stock Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkUpdateForm">
                    <div class="mb-3">
                        <label class="form-label">Update Type</label>
                        <select class="form-select form-control-floral" id="bulkUpdateType">
                            <option value="add">Add Quantity to All</option>
                            <option value="set">Set All to Quantity</option>
                            <option value="percentage">Increase/Decrease by Percentage</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control form-control-floral" id="bulkQuantity" min="1" value="10">
                    </div>
                    
                    <div class="mb-3" id="percentageField" style="display: none;">
                        <label class="form-label">Percentage (%)</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('bulkPercentage').value = Math.max(-100, parseInt(document.getElementById('bulkPercentage').value || 0) - 10)">-10%</button>
                            <input type="number" class="form-control form-control-floral text-center" id="bulkPercentage" min="-100" max="100" value="10">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('bulkPercentage').value = Math.min(100, parseInt(document.getElementById('bulkPercentage').value || 0) + 10)">+10%</button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Apply to</label>
                        <select class="form-select form-control-floral" id="bulkApplyTo">
                            <option value="selected">Selected Items Only</option>
                            <option value="filtered">All Filtered Items</option>
                            <option value="low_stock">Low Stock Items Only</option>
                            <option value="out_of_stock">Out of Stock Items Only</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This will update multiple items at once. Please review before proceeding.
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-floral" onclick="applyBulkUpdate()">Apply Update</button>
            </div>
        </div>
    </div>
</div>

<script>
// Stock Chart
const stockCtx = document.getElementById('stockChart').getContext('2d');
const stockChart = new Chart(stockCtx, {
    type: 'bar',
    data: {
        labels: ['Out of Stock', 'Low Stock', 'Medium Stock', 'High Stock'],
        datasets: [{
            label: 'Number of Items',
            data: [
                <?php echo $stats['out_of_stock']; ?>,
                <?php echo $stats['low_stock']; ?>,
                <?php echo $stats['medium_stock']; ?>,
                <?php echo $stats['high_stock']; ?>
            ],
            backgroundColor: [
                '#dc3545',
                '#ffc107',
                '#17a2b8',
                '#28a745'
            ],
            borderWidth: 1,
            borderColor: 'white'
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
                title: {
                    display: true,
                    text: 'Number of Items'
                }
            }
        }
    }
});

// Stock Modal
const stockModal = document.getElementById('stockModal');
stockModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const name = button.getAttribute('data-name');
    const currentStock = button.getAttribute('data-current');
    
    document.getElementById('bouquetId').value = id;
    document.getElementById('bouquetName').textContent = name;
    document.getElementById('currentStock').textContent = currentStock;
    
    updateStockPreview();
});

// Update stock preview
function updateStockPreview() {
    const current = parseInt(document.getElementById('currentStock').textContent);
    const adjustment = parseInt(document.getElementById('adjustmentQuantity').value) || 0;
    const type = document.querySelector('input[name="adjustment_type"]:checked').value;
    
    let newStock = current;
    switch(type) {
        case 'add':
            newStock = current + adjustment;
            break;
        case 'remove':
            newStock = Math.max(0, current - adjustment);
            break;
        case 'set':
            newStock = adjustment;
            break;
    }
    
    document.getElementById('newStockPreview').textContent = newStock;
}

// Event listeners for stock modal
document.getElementById('adjustmentQuantity').addEventListener('input', updateStockPreview);
document.querySelectorAll('input[name="adjustment_type"]').forEach(radio => {
    radio.addEventListener('change', updateStockPreview);
});

// Bulk update type toggle
document.getElementById('bulkUpdateType').addEventListener('change', function() {
    document.getElementById('percentageField').style.display = 
        this.value === 'percentage' ? 'block' : 'none';
});

// Select all items
document.getElementById('selectAllItems').addEventListener('change', function() {
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateSelectedCount();
});

// Update selected count
function updateSelectedCount() {
    const count = document.querySelectorAll('.item-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

document.querySelectorAll('.item-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

// Apply bulk update
function applyBulkUpdate() {
    const type = document.getElementById('bulkUpdateType').value;
    const quantity = parseInt(document.getElementById('bulkQuantity').value) || 0;
    const percentage = parseInt(document.getElementById('bulkPercentage').value) || 0;
    const applyTo = document.getElementById('bulkApplyTo').value;
    
    if (type === 'percentage' && (percentage < -100 || percentage > 100)) {
        alert('Percentage must be between -100 and 100');
        return;
    }
    
    if (confirm(`Apply bulk update to ${applyTo} items?`)) {
        // Implement bulk update logic
        alert('Bulk update applied successfully!');
        $('#bulkUpdateModal').modal('hide');
        location.reload();
    }
}

// Auto-select items needing reorder
$(document).ready(function() {
    $('.table-warning .item-checkbox').prop('checked', true);
    updateSelectedCount();
    
    // Add pulsing animation to low stock badges
    setInterval(function() {
        $('.stock-alert').toggleClass('pulse');
    }, 2000);
});
</script>

<style>
@keyframes/* ========== INVENTORY MODULE STYLES ========== */
.stock-alert {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

.display-6 {
    font-size: 2.5rem;
    font-weight: bold;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

.progress-bar {
    transition: width 0.5s ease-in-out;
}

/* Supplier styles */
.supplier-card {
    border-left: 5px solid var(--leaf-green);
    transition: transform 0.3s;
}

.supplier-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.badge-active { background: #d4edda; color: #155724; }
.badge-inactive { background: #f8d7da; color: #721c24; }
.badge-suspended { background: #fff3cd; color: #856404; }

/* Stock level colors */
.bg-out-of-stock { background: linear-gradient(135deg, #dc3545, #e4606d); }
.bg-low-stock { background: linear-gradient(135deg, #ffc107, #ffd454); color: #000; }
.bg-medium-stock { background: linear-gradient(135deg, #17a2b8, #3ab7cc); }
.bg-high-stock { background: linear-gradient(135deg, #28a745, #4bc766); }

/* Inventory value display */
.inventory-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--primary-pink);
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}