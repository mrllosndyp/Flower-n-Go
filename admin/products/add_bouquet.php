<?php
include '../inclusion/header.php';
include '../config.php';

$name = $description = $status = $size = $occasion = $color_scheme = $category = "";
$price = $stock = 0;
$is_best_seller = $has_vase = 0;
$error = "";
$success = "";

// Bouquet options
$sizes = ['Small (10-15 stems)', 'Medium (20-25 stems)', 'Large (30-35 stems)', 'XL (40-50 stems)'];
$occasions = ['Birthday', 'Anniversary', 'Wedding', 'Valentine\'s', 'Get Well', 'Sympathy', 'Congratulations', 'Just Because'];
$color_schemes = ['Red & White', 'Pastel', 'Vibrant', 'Monochrome', 'Romantic', 'Spring Mix', 'Autumn Tones'];
$categories = ['Anniversary', 'Birthday', 'Wedding', 'Romantic', 'Sympathy', 'Graduation', 'Corporate', 'Seasonal'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (same validation and processing logic from earlier, but integrated with new design)
    // Let me create the form with the new design system
}
?>

<div class="page-header">
    <h1 class="page-title">Create New Bouquet</h1>
    <div class="page-subtitle">
        <i class="bi bi-plus-circle me-1"></i> Add a beautiful flower arrangement to your collection
    </div>
</div>

<!-- Progress Steps -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="steps">
            <div class="step active">
                <div class="step-icon">1</div>
                <div class="step-label">Basic Info</div>
            </div>
            <div class="step">
                <div class="step-icon">2</div>
                <div class="step-label">Flower Details</div>
            </div>
            <div class="step">
                <div class="step-icon">3</div>
                <div class="step-label">Pricing & Stock</div>
            </div>
            <div class="step">
                <div class="step-icon">4</div>
                <div class="step-label">Images</div>
            </div>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="bouquetForm" class="needs-validation" novalidate>
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Basic Information Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2 flower-icon"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Bouquet Name</label>
                        <input type="text" class="form-control form-control-floral" name="name" required 
                               value="<?php echo htmlspecialchars($name); ?>"
                               placeholder="e.g., Eternal Love Red Roses">
                        <div class="form-text">Give it a descriptive and appealing name</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Category</label>
                            <select class="form-select form-control-floral" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $occ; ?>" <?php echo $occasion === $occ ? 'selected' : ''; ?>>
                                    <?php echo $occ; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control form-control-floral" name="description" rows="4" 
                                  placeholder="Describe the beauty, fragrance, and emotional appeal of this bouquet..."><?php echo htmlspecialchars($description); ?></textarea>
                        <div class="form-text">This description will be shown to customers</div>
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
                                   value="<?php echo htmlspecialchars($primary_flowers ?? ''); ?>"
                                   placeholder="e.g., Red Roses, White Lilies">
                            <div class="form-text">Main flowers used in the bouquet</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Filler Flowers</label>
                            <input type="text" class="form-control form-control-floral" name="filler_flowers"
                                   value="<?php echo htmlspecialchars($filler_flowers ?? ''); ?>"
                                   placeholder="e.g., Baby's Breath, Gypsophila">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Foliage & Greens</label>
                            <input type="text" class="form-control form-control-floral" name="foliage"
                                   value="<?php echo htmlspecialchars($foliage ?? ''); ?>"
                                   placeholder="e.g., Eucalyptus, Ferns">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Color Scheme</label>
                            <select class="form-select form-control-floral" name="color_scheme">
                                <option value="">Select Color Scheme</option>
                                <?php foreach($color_schemes as $scheme): ?>
                                <option value="<?php echo $scheme; ?>" <?php echo ($color_scheme ?? '') === $scheme ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $size_opt; ?>" <?php echo ($size ?? '') === $size_opt ? 'selected' : ''; ?>>
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
            <!-- Pricing Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-tag me-2 flower-icon"></i> Pricing & Stock</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Price (₱)</label>
                        <input type="number" class="form-control form-control-floral" name="price" step="0.01" min="0.01" required
                               value="<?php echo $price; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Stock Quantity</label>
                        <input type="number" class="form-control form-control-floral" name="stock" min="0" required
                               value="<?php echo $stock; ?>">
                        <div class="form-text">Set to 0 for pre-order only</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-control-floral" name="status">
                            <option value="available" selected>Available</option>
                            <option value="limited">Limited Stock</option>
                            <option value="preorder">Pre-order Only</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Features Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-stars me-2 leaf-icon"></i> Features</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_best_seller" id="bestSeller" value="1" <?php echo $is_best_seller ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="bestSeller">
                            <i class="bi bi-trophy text-warning me-2"></i> Mark as Best Seller
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="has_vase" id="hasVase" value="1" <?php echo $has_vase ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="hasVase">
                            <i class="bi bi-flower3 text-primary me-2"></i> Includes Vase
                        </label>
                    </div>
                    
                    <div id="vaseChargeField" class="mb-3" style="display: none;">
                        <label class="form-label">Vase Additional Charge (₱)</label>
                        <input type="number" class="form-control form-control-floral" name="vase_charge" step="0.01" min="0"
                               value="<?php echo $vase_charge ?? 0; ?>">
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="delivery_ready" id="deliveryReady" value="1" <?php echo ($delivery_ready ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="deliveryReady">
                            <i class="bi bi-truck text-success me-2"></i> Ready for Delivery
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Image Upload Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-image me-2 flower-icon"></i> Bouquet Image</h5>
                </div>
                <div class="card-body text-center">
                    <div class="image-upload-area mb-3" onclick="document.getElementById('imageInput').click()">
                        <i class="bi bi-cloud-arrow-up" style="font-size: 3rem; color: var(--primary-pink);"></i>
                        <h6 class="mt-2">Upload Photo</h6>
                        <p class="text-muted small">Click to upload bouquet image</p>
                        <p class="text-muted small">JPG, PNG, WEBP • Max 5MB</p>
                    </div>
                    
                    <div class="preview-container" id="previewContainer" style="display: none;">
                        <img id="imagePreview" src="#" alt="Preview" class="img-fluid rounded mb-2" style="max-height: 150px;">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearImage()">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                    
                    <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" onchange="previewImage(event)">
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Recommended: Square image, 800x800px minimum
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body text-center">
            <button type="submit" class="btn btn-floral btn-lg px-5">
                <i class="bi bi-plus-circle me-2"></i> Create Bouquet
            </button>
            <a href="list_bouquets.php" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-x-circle me-2"></i> Cancel
            </a>
        </div>
    </div>
</form>

<!-- Image Preview Script -->
<script>
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('imagePreview');
    const previewContainer = document.getElementById('previewContainer');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('previewContainer').style.display = 'none';
}

// Vase charge toggle
document.getElementById('hasVase').addEventListener('change', function() {
    document.getElementById('vaseChargeField').style.display = this.checked ? 'block' : 'none';
});

// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../inclusion/footer.php'; ?>