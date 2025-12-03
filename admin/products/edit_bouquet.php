<?php
include '../inclusion/header.php';
include '../config.php';

if (!isset($_GET['id'])) {
    header("Location: list_bouquets.php");
    exit;
}

$id = (int)$_GET['id'];
$error = "";
$success = "";

// Fetch bouquet data
$stmt = $conn->prepare("SELECT * FROM bouquets WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list_bouquets.php");
    exit;
}

$bouquet = $result->fetch_assoc();

// Bouquet options (same as add)
$sizes = ['Small (10-15 stems)', 'Medium (20-25 stems)', 'Large (30-35 stems)', 'XL (40-50 stems)'];
$occasions = ['Birthday', 'Anniversary', 'Wedding', 'Valentine\'s', 'Get Well', 'Sympathy', 'Congratulations', 'Just Because'];
$color_schemes = ['Red & White', 'Pastel', 'Vibrant', 'Monochrome', 'Romantic', 'Spring Mix', 'Autumn Tones'];
$categories = ['Anniversary', 'Birthday', 'Wedding', 'Romantic', 'Sympathy', 'Graduation', 'Corporate', 'Seasonal'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $occasion = $_POST['occasion'];
    $color_scheme = $_POST['color_scheme'];
    $size = $_POST['size'];
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];
    $status = $_POST['status'];
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
    $has_vase = isset($_POST['has_vase']) ? 1 : 0;
    $vase_charge = (float) ($_POST['vase_charge'] ?? 0);
    $primary_flowers = trim($_POST['primary_flowers']);
    $filler_flowers = trim($_POST['filler_flowers']);
    $foliage = trim($_POST['foliage']);
    $delivery_ready = isset($_POST['delivery_ready']) ? 1 : 0;

    if (empty($name) || $price <= 0) {
        $error = "Please fill in required fields properly.";
    } else {
        // Handle image upload
        $image_name = $bouquet['image'];
        
        if (!empty($_FILES['image']['name'])) {
            $targetDir = "../../uploads/bouquets/";
            
            // Delete old image if exists
            if ($image_name && file_exists($targetDir . $image_name)) {
                unlink($targetDir . $image_name);
                unlink($targetDir . 'thumbs/' . $image_name);
                unlink($targetDir . 'display_' . $image_name);
            }
            
            $original = basename($_FILES['image']['name']);
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            
            if (in_array($ext, $allowed)) {
                $unique_id = uniqid('bouquet_', true);
                $image_name = $unique_id . '.' . $ext;
                $targetFile = $targetDir . $image_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    // Create thumbnails
                    createImageVersion($targetFile, $targetDir . 'display_' . $image_name, 800, 800);
                    createImageVersion($targetFile, $targetDir . 'thumbs/' . $image_name, 300, 300);
                } else {
                    $error = "Failed to upload image.";
                    $image_name = $bouquet['image']; // Keep old image
                }
            } else {
                $error = "Invalid image type. Allowed: jpg, jpeg, png, webp.";
            }
        }
        
        if (empty($error)) {
            $update_stmt = $conn->prepare("
                UPDATE bouquets SET 
                name = ?, description = ?, category = ?, occasion = ?, 
                color_scheme = ?, size = ?, price = ?, stock = ?, status = ?,
                is_best_seller = ?, has_vase = ?, vase_charge = ?, primary_flowers = ?,
                filler_flowers = ?, foliage = ?, delivery_ready = ?, image = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $update_stmt->bind_param(
                "ssssssdisiiisssssi",
                $name, $description, $category, $occasion, $color_scheme, $size,
                $price, $stock, $status, $is_best_seller, $has_vase, $vase_charge,
                $primary_flowers, $filler_flowers, $foliage, $delivery_ready,
                $image_name, $id
            );
            
            if ($update_stmt->execute()) {
                $success = "🌸 Bouquet updated successfully!";
                // Refresh bouquet data
                $stmt->execute();
                $bouquet = $stmt->get_result()->fetch_assoc();
                logActivity('update_bouquet', "Updated bouquet: " . $name . " (ID: " . $id . ")");
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
} else {
    // Set form values from database
    extract($bouquet);
}

// Helper function for image processing
function createImageVersion($source, $destination, $width, $height) {
    // Similar to function in add_bouquet.php
    $dir = dirname($destination);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    // Image processing code here
    return true;
}
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">Edit Bouquet</h1>
            <div class="page-subtitle">
                <i class="bi bi-pencil me-1"></i> Update flower arrangement details
            </div>
        </div>
        <div>
            <a href="list_bouquets.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to List
            </a>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if ($success): ?>
<div class="alert alert-success alert-floral d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem;"></i>
    <div><?php echo $success; ?></div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-floral d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
    <div><?php echo $error; ?></div>
</div>
<?php endif; ?>

<!-- Bouquet Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(46, 139, 87, 0.1);">
                <i class="bi bi-cart-check" style="color: var(--leaf-green);"></i>
            </div>
            <div class="stats-number">
                <?php 
                $sales_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM order_items WHERE bouquet_id = ?");
                $sales_stmt->bind_param("i", $id);
                $sales_stmt->execute();
                $sales = $sales_stmt->get_result()->fetch_assoc()['total'] ?? 0;
                echo $sales;
                ?>
            </div>
            <div class="stats-label">Total Sold</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 107, 139, 0.1);">
                <i class="bi bi-box-seam" style="color: var(--primary-pink);"></i>
            </div>
            <div class="stats-number <?php echo $bouquet['stock'] < 10 ? 'text-danger' : 'text-success'; ?>">
                <?php echo $bouquet['stock']; ?>
            </div>
            <div class="stats-label">Current Stock</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 215, 0, 0.1);">
                <i class="bi bi-star" style="color: var(--gold);"></i>
            </div>
            <div class="stats-number">
                <?php 
                $review_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE bouquet_id = ?");
                $review_stmt->bind_param("i", $id);
                $review_stmt->execute();
                $rating = $review_stmt->get_result()->fetch_assoc()['avg_rating'] ?? 0;
                echo number_format($rating, 1);
                ?>
            </div>
            <div class="stats-label">Avg Rating</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(230, 230, 250, 0.1);">
                <i class="bi bi-calendar" style="color: var(--lavender);"></i>
            </div>
            <div class="stats-number small">
                <?php echo date('M d, Y', strtotime($bouquet['created_at'])); ?>
            </div>
            <div class="stats-label">Created Date</div>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="editBouquetForm">
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Basic Information Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2 flower-icon"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label required">Bouquet Name</label>
                            <input type="text" class="form-control form-control-floral" name="name" required 
                                   value="<?php echo htmlspecialchars($bouquet['name']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" class="form-control form-control-floral bg-light" value="<?php echo $bouquet['sku']; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Category</label>
                            <select class="form-select form-control-floral" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $bouquet['category'] === $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Occasion</label>
                            <select class="form-select form-control-floral" name="occasion">
                                <option value="">Select Occasion</option>
                                <?php foreach($occasions as $occ): ?>
                                <option value="<?php echo $occ; ?>" <?php echo $bouquet['occasion'] === $occ ? 'selected' : ''; ?>>
                                    <?php echo $occ; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control form-control-floral" name="description" rows="4"><?php echo htmlspecialchars($bouquet['description']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Flower Details Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-flower2 me-2 leaf-icon"></i> Flower Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Primary Flowers</label>
                            <input type="text" class="form-control form-control-floral" name="primary_flowers" required
                                   value="<?php echo htmlspecialchars($bouquet['primary_flowers']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Filler Flowers</label>
                            <input type="text" class="form-control form-control-floral" name="filler_flowers"
                                   value="<?php echo htmlspecialchars($bouquet['filler_flowers']); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Foliage & Greens</label>
                            <input type="text" class="form-control form-control-floral" name="foliage"
                                   value="<?php echo htmlspecialchars($bouquet['foliage']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Color Scheme</label>
                            <select class="form-select form-control-floral" name="color_scheme">
                                <option value="">Select Color Scheme</option>
                                <?php foreach($color_schemes as $scheme): ?>
                                <option value="<?php echo $scheme; ?>" <?php echo $bouquet['color_scheme'] === $scheme ? 'selected' : ''; ?>>
                                    <?php echo $scheme; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Size</label>
                        <select class="form-select form-control-floral" name="size">
                            <option value="">Select Size</option>
                            <?php foreach($sizes as $size_opt): ?>
                            <option value="<?php echo $size_opt; ?>" <?php echo $bouquet['size'] === $size_opt ? 'selected' : ''; ?>>
                                <?php echo $size_opt; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Current Image Preview -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-image me-2 flower-icon"></i> Current Image</h5>
                </div>
                <div class="card-body text-center">
                    <?php if($bouquet['image']): ?>
                    <img src="../../uploads/bouquets/display_<?php echo $bouquet['image']; ?>" 
                         alt="<?php echo $bouquet['name']; ?>" 
                         class="img-fluid rounded mb-3" 
                         style="max-height: 200px;">
                    <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" 
                         style="height: 200px;">
                        <i class="bi bi-flower2 text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Update Image</label>
                        <input type="file" class="form-control form-control-floral" name="image" accept="image/*">
                        <div class="form-text small">Leave empty to keep current image</div>
                    </div>
                </div>
            </div>
            
            <!-- Pricing & Stock Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-tag me-2 leaf-icon"></i> Pricing & Stock</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Price (₱)</label>
                        <input type="number" class="form-control form-control-floral" name="price" step="0.01" min="0.01" required
                               value="<?php echo $bouquet['price']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Stock Quantity</label>
                        <input type="number" class="form-control form-control-floral" name="stock" min="0" required
                               value="<?php echo $bouquet['stock']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-control-floral" name="status">
                            <option value="available" <?php echo $bouquet['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="limited" <?php echo $bouquet['status'] === 'limited' ? 'selected' : ''; ?>>Limited Stock</option>
                            <option value="preorder" <?php echo $bouquet['status'] === 'preorder' ? 'selected' : ''; ?>>Pre-order Only</option>
                            <option value="out_of_stock" <?php echo $bouquet['status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Features Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-stars me-2 flower-icon"></i> Features</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_best_seller" id="bestSeller" value="1" 
                               <?php echo $bouquet['is_best_seller'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="bestSeller">
                            <i class="bi bi-trophy text-warning me-2"></i> Best Seller
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="has_vase" id="hasVase" value="1"
                               <?php echo $bouquet['has_vase'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="hasVase">
                            <i class="bi bi-flower3 text-primary me-2"></i> Includes Vase
                        </label>
                    </div>
                    
                    <div id="vaseChargeField" class="mb-3" style="<?php echo $bouquet['has_vase'] ? '' : 'display: none;'; ?>">
                        <label class="form-label">Vase Charge (₱)</label>
                        <input type="number" class="form-control form-control-floral" name="vase_charge" step="0.01" min="0"
                               value="<?php echo $bouquet['vase_charge']; ?>">
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="delivery_ready" id="deliveryReady" value="1"
                               <?php echo $bouquet['delivery_ready'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="deliveryReady">
                            <i class="bi bi-truck text-success me-2"></i> Delivery Ready
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body text-center">
            <button type="submit" class="btn btn-floral btn-lg px-5">
                <i class="bi bi-save me-2"></i> Update Bouquet
            </button>
            <a href="list_bouquets.php" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-x-circle me-2"></i> Cancel
            </a>
            <a href="?duplicate=<?php echo $id; ?>" class="btn btn-outline-success ms-2">
                <i class="bi bi-files me-2"></i> Duplicate
            </a>
        </div>
    </div>
</form>

<script>
// Vase charge toggle
document.getElementById('hasVase').addEventListener('change', function() {
    document.getElementById('vaseChargeField').style.display = this.checked ? 'block' : 'none';
});

// Form validation
document.getElementById('editBouquetForm').addEventListener('submit', function(e) {
    const stock = parseInt(document.querySelector('input[name="stock"]').value);
    if (stock < 0) {
        e.preventDefault();
        alert('Stock quantity cannot be negative.');
        return false;
    }
    
    const price = parseFloat(document.querySelector('input[name="price"]').value);
    if (price <= 0) {
        e.preventDefault();
        alert('Price must be greater than 0.');
        return false;
    }
    
    return confirm('Are you sure you want to update this bouquet?');
});
</script>

<?php include '../inclusion/footer.php'; ?>