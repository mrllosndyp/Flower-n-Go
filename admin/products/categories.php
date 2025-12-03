<?php
include '../inclusion/header.php';
include '../config.php';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name']);
        $description = trim($_POST['category_description']);
        $image = null;
        
        if (!empty($name)) {
            // Handle image upload
            if (!empty($_FILES['category_image']['name'])) {
                $targetDir = "../../uploads/categories/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $original = basename($_FILES['category_image']['name']);
                $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
                
                if (in_array($ext, $allowed)) {
                    $image_name = uniqid('category_', true) . '.' . $ext;
                    $targetFile = $targetDir . $image_name;
                    
                    if (move_uploaded_file($_FILES['category_image']['tmp_name'], $targetFile)) {
                        $image = $image_name;
                    }
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO categories (name, description, image, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $name, $description, $image);
            if ($stmt->execute()) {
                $success = "Category added successfully!";
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];
        // Check if category has bouquets
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bouquets WHERE category_id = ?");
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $has_bouquets = $check_stmt->get_result()->fetch_assoc()['count'] > 0;
        
        if ($has_bouquets) {
            $error = "Cannot delete category that has bouquets. Reassign bouquets first.";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $delete_stmt->bind_param("i", $category_id);
            if ($delete_stmt->execute()) {
                $success = "Category deleted successfully!";
            }
        }
    }
}

// Get all categories
$categories = $conn->query("
    SELECT c.*, 
    (SELECT COUNT(*) FROM bouquets b WHERE b.category = c.name) as bouquet_count
    FROM categories c 
    ORDER BY c.name
");
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">Categories Management</h1>
            <div class="page-subtitle">
                <i class="bi bi-tags me-1"></i> Organize bouquets into categories
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Add Category Form -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2 flower-icon"></i> Add New Category</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label required">Category Name</label>
                        <input type="text" class="form-control form-control-floral" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control form-control-floral" name="category_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category Image</label>
                        <input type="file" class="form-control form-control-floral" name="category_image" accept="image/*">
                        <div class="form-text small">Optional: Image representing this category</div>
                    </div>
                    
                    <button type="submit" name="add_category" class="btn btn-floral w-100">
                        <i class="bi bi-plus-lg me-2"></i> Add Category
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Category Stats -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2 leaf-icon"></i> Category Stats</h5>
            </div>
            <div class="card-body">
                <?php
                $stats_result = $conn->query("
                    SELECT c.name, COUNT(b.id) as count
                    FROM categories c
                    LEFT JOIN bouquets b ON b.category = c.name
                    GROUP BY c.name
                    ORDER BY count DESC
                    LIMIT 5
                ");
                
                while($stat = $stats_result->fetch_assoc()):
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-truncate" style="max-width: 150px;"><?php echo $stat['name']; ?></span>
                    <span class="badge bg-primary"><?php echo $stat['count']; ?> bouquets</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <?php 
                    $percentage = ($stat['count'] / max(1, $categories->num_rows)) * 100;
                    ?>
                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Categories List -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-grid me-2 leaf-icon"></i> All Categories</h5>
            </div>
            <div class="card-body">
                <?php if ($categories->num_rows > 0): ?>
                <div class="row">
                    <?php while($category = $categories->fetch_assoc()): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card category-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <?php if($category['image']): ?>
                                    <div class="flex-shrink-0 me-3">
                                        <img src="../../uploads/categories/<?php echo $category['image']; ?>" 
                                             alt="<?php echo $category['name']; ?>" 
                                             class="rounded" 
                                             width="60" 
                                             height="60"
                                             style="object-fit: cover;">
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo $category['name']; ?></h6>
                                        <p class="text-muted small mb-2"><?php echo $category['description']; ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-flower2 me-1"></i> <?php echo $category['bouquet_count']; ?> bouquets
                                            </span>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editCategoryModal"
                                                        data-id="<?php echo $category['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                        data-description="<?php echo htmlspecialchars($category['description']); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" name="delete_category" 
                                                            class="btn btn-outline-danger"
                                                            onclick="return confirm('Delete this category?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-tags" style="font-size: 3rem; color: #ddd;"></i>
                    <h5 class="mt-3">No Categories Yet</h5>
                    <p class="text-muted">Start by adding your first category</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCategoryForm">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="editCategoryId">
                    
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control form-control-floral" name="category_name" id="editCategoryName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control form-control-floral" name="category_description" id="editCategoryDescription" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_category" class="btn btn-floral">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.category-card {
    border: 2px solid transparent;
    transition: all 0.3s;
    border-radius: 12px;
}

.category-card:hover {
    border-color: var(--primary-pink);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.progress-bar {
    background: linear-gradient(135deg, var(--primary-pink) 0%, #ff8e8e 100%);
}
</style>

<script>
// Edit Category Modal
var editCategoryModal = document.getElementById('editCategoryModal');
editCategoryModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var name = button.getAttribute('data-name');
    var description = button.getAttribute('data-description');
    
    document.getElementById('editCategoryId').value = id;
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editCategoryDescription').value = description;
});

// Add CSS for category cards
document.addEventListener('DOMContentLoaded', function() {
    var style = document.createElement('style');
    style.textContent = `
        .category-card {
            border-left: 5px solid var(--leaf-green);
        }
        .category-card:hover {
            border-left-color: var(--primary-pink);
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php include '../inclusion/footer.php'; ?>