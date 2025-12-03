<?php
include '../inclusion/header.php';
include '../config.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Search and filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(b.name LIKE ? OR b.description LIKE ? OR b.primary_flowers LIKE ?)";
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

if (!empty($min_price)) {
    $where[] = "b.price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if (!empty($max_price)) {
    $where[] = "b.price <= ?";
    $params[] = $max_price;
    $types .= 'd';
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

// Get bouquets
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM order_items oi WHERE oi.bouquet_id = b.id) as total_sold,
        (SELECT COUNT(*) FROM reviews r WHERE r.bouquet_id = b.id) as review_count
        FROM bouquets b 
        $where_clause 
        ORDER BY b.created_at DESC 
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
$categories = [];
while($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat['category'];
}

// Bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (isset($_POST['selected_bouquets']) && !empty($_POST['selected_bouquets'])) {
        $selected_ids = implode(',', array_map('intval', $_POST['selected_bouquets']));
        
        switch ($_POST['bulk_action']) {
            case 'activate':
                $conn->query("UPDATE bouquets SET status='available' WHERE id IN ($selected_ids)");
                $success = "Selected bouquets have been activated.";
                break;
            case 'deactivate':
                $conn->query("UPDATE bouquets SET status='out_of_stock' WHERE id IN ($selected_ids)");
                $success = "Selected bouquets have been deactivated.";
                break;
            case 'delete':
                $conn->query("UPDATE bouquets SET status='deleted' WHERE id IN ($selected_ids)");
                $success = "Selected bouquets have been deleted.";
                break;
        }
        logActivity('bulk_update', "Performed bulk action: " . $_POST['bulk_action'] . " on " . count($_POST['selected_bouquets']) . " bouquets");
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Bouquet Management</h1>
    <div class="page-subtitle">
        <i class="bi bi-flower2 me-1"></i> Manage your flower arrangements inventory
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
<div class="alert alert-success alert-floral d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem;"></i>
    <div><?php echo $success; ?></div>
</div>
<?php endif; ?>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-funnel me-2 flower-icon"></i> Filter Bouquets</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control form-control-floral" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, description, flowers...">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select class="form-select form-control-floral" name="category">
                    <option value="all">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                        <?php echo $cat; ?>
                    </option>
                    <?php endforeach; ?>
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
                <label class="form-label">Min Price (₱)</label>
                <input type="number" class="form-control form-control-floral" name="min_price" value="<?php echo $min_price; ?>" placeholder="0" step="0.01">
            </div>
            <div class="col-md-2">
                <label class="form-label">Max Price (₱)</label>
                <input type="number" class="form-control form-control-floral" name="max_price" value="<?php echo $max_price; ?>" placeholder="10000" step="0.01">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-floral w-100">
                    <i class="bi bi-filter"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bouquets Listing -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-grid me-2 leaf-icon"></i> 
            Bouquets <span class="badge bg-primary"><?php echo $total_items; ?> total</span>
        </h5>
        <div>
            <a href="add_bouquet.php" class="btn btn-floral">
                <i class="bi bi-plus-circle me-2"></i> Add New Bouquet
            </a>
        </div>
    </div>
    <div class="card-body">
        
        <!-- Bulk Actions -->
        <form method="POST" id="bulkForm" class="mb-4">
            <div class="d-flex gap-2 align-items-center mb-3">
                <select name="bulk_action" class="form-select form-control-floral" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate Selected</option>
                    <option value="deactivate">Deactivate Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-leaf">Apply</button>
                <div class="ms-auto">
                    <a href="?export=csv" class="btn btn-outline-primary">
                        <i class="bi bi-download me-2"></i> Export CSV
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-floral data-table">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th width="80">Image</th>
                            <th>Bouquet Details</th>
                            <th>Price & Stock</th>
                            <th>Sales</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($bouquet = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_bouquets[]" value="<?php echo $bouquet['id']; ?>" class="form-check-input bouquet-checkbox">
                            </td>
                            <td>
                                <?php if($bouquet['image']): ?>
                                <img src="../../uploads/bouquets/thumbs/<?php echo $bouquet['image']; ?>" 
                                     alt="<?php echo $bouquet['name']; ?>" 
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
                                <div class="fw-bold mb-1"><?php echo htmlspecialchars($bouquet['name']); ?></div>
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-tag me-1"></i> <?php echo $bouquet['category'] ?: 'Uncategorized'; ?>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 250px;">
                                    <?php 
                                    $desc = strip_tags($bouquet['description']);
                                    echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                    ?>
                                </div>
                                <div class="small mt-1">
                                    <?php if($bouquet['primary_flowers']): ?>
                                    <span class="badge bg-light text-dark me-1">
                                        <i class="bi bi-flower1 me-1"></i> <?php echo $bouquet['primary_flowers']; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if($bouquet['has_vase']): ?>
                                    <span class="badge bg-info text-white">
                                        <i class="bi bi-flower3 me-1"></i> With Vase
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">₱<?php echo number_format($bouquet['price'], 2); ?></div>
                                <div class="small">
                                    Stock: <span class="fw-bold <?php echo $bouquet['stock'] < 10 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $bouquet['stock']; ?>
                                    </span>
                                </div>
                                <?php if($bouquet['has_vase'] && $bouquet['vase_charge'] > 0): ?>
                                <div class="small text-muted">
                                    +₱<?php echo number_format($bouquet['vase_charge'], 2); ?> vase
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <div class="text-success">
                                        <i class="bi bi-cart-check me-1"></i> 
                                        <?php echo $bouquet['total_sold']; ?> sold
                                    </div>
                                    <?php if($bouquet['review_count'] > 0): ?>
                                    <div class="text-warning">
                                        <i class="bi bi-star me-1"></i> 
                                        <?php echo $bouquet['review_count']; ?> reviews
                                    </div>
                                    <?php endif; ?>
                                    <?php if($bouquet['is_best_seller']): ?>
                                    <span class="badge bg-warning text-dark mt-1">
                                        <i class="bi bi-trophy me-1"></i> Best Seller
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'available' => ['class' => 'badge-available', 'label' => 'Available'],
                                    'limited' => ['class' => 'badge-pending', 'label' => 'Limited'],
                                    'out_of_stock' => ['class' => 'badge-cancelled', 'label' => 'Out of Stock'],
                                    'preorder' => ['class' => 'badge-processing', 'label' => 'Pre-order'],
                                    'deleted' => ['class' => 'badge-secondary', 'label' => 'Deleted']
                                ];
                                $status_info = $status_badges[$bouquet['status']] ?? $status_badges['available'];
                                ?>
                                <span class="badge badge-status <?php echo $status_info['class']; ?>">
                                    <?php echo $status_info['label']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="edit_bouquet.php?id=<?php echo $bouquet['id']; ?>" 
                                       class="btn btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?duplicate=<?php echo $bouquet['id']; ?>" 
                                       class="btn btn-outline-success"
                                       data-bs-toggle="tooltip" title="Duplicate">
                                        <i class="bi bi-files"></i>
                                    </a>
                                    <a href="#" 
                                       class="btn btn-outline-danger delete-btn"
                                       data-id="<?php echo $bouquet['id']; ?>"
                                       data-name="<?php echo htmlspecialchars($bouquet['name']); ?>"
                                       data-bs-toggle="tooltip" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <a href="../../bouquet.php?id=<?php echo $bouquet['id']; ?>" 
                                       target="_blank"
                                       class="btn btn-outline-info"
                                       data-bs-toggle="tooltip" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
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
        <nav aria-label="Bouquet pagination">
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="deleteBouquetName" class="fw-bold"></span>"?</p>
                <p class="text-muted small">This action will move the bouquet to the deleted section. It can be restored later.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete Bouquet</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Select all checkboxes
    $('#selectAll').change(function() {
        $('.bouquet-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Bulk form submission
    $('#bulkForm').submit(function(e) {
        if (!$('select[name="bulk_action"]').val()) {
            e.preventDefault();
            alert('Please select a bulk action.');
            return false;
        }
        if (!$('.bouquet-checkbox:checked').length) {
            e.preventDefault();
            alert('Please select at least one bouquet.');
            return false;
        }
        return confirm('Are you sure you want to perform this bulk action?');
    });
    
    // Delete confirmation
    $('.delete-btn').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#deleteBouquetName').text(name);
        $('#confirmDelete').attr('href', '?delete=' + id);
        $('#deleteModal').modal('show');
    });
    
    // Real-time stock warning
    $('.bouquet-checkbox').change(function() {
        var row = $(this).closest('tr');
        var stock = parseInt(row.find('.text-success, .text-danger').text());
        if (stock < 5) {
            row.addClass('table-warning');
        } else {
            row.removeClass('table-warning');
        }
    });
});
</script>

<?php include '../inclusion/footer.php'; ?>