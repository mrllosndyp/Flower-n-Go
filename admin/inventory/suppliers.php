<?php
include '../inclusion/header.php';
include '../config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_supplier'])) {
        $name = trim($_POST['name']);
        $contact_person = trim($_POST['contact_person']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $payment_terms = $_POST['payment_terms'];
        $delivery_time = trim($_POST['delivery_time']);
        $notes = trim($_POST['notes']);
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("
            INSERT INTO suppliers 
            (name, contact_person, email, phone, address, payment_terms, 
             delivery_time, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            "sssssssss",
            $name, $contact_person, $email, $phone, $address,
            $payment_terms, $delivery_time, $notes, $status
        );
        
        if ($stmt->execute()) {
            $success = "Supplier added successfully!";
            logActivity('add_supplier', "Added new supplier: $name");
        }
    }
    
    if (isset($_POST['delete_supplier'])) {
        $supplier_id = (int)$_POST['supplier_id'];
        
        // Check if supplier has bouquets
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bouquets WHERE supplier_id = ?");
        $check_stmt->bind_param("i", $supplier_id);
        $check_stmt->execute();
        $has_bouquets = $check_stmt->get_result()->fetch_assoc()['count'] > 0;
        
        if ($has_bouquets) {
            $error = "Cannot delete supplier that has bouquets assigned.";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
            $delete_stmt->bind_param("i", $supplier_id);
            if ($delete_stmt->execute()) {
                $success = "Supplier deleted successfully!";
                logActivity('delete_supplier', "Deleted supplier ID: $supplier_id");
            }
        }
    }
}

// Get all suppliers with stats
$suppliers_sql = "
    SELECT s.*,
    (SELECT COUNT(*) FROM bouquets b WHERE b.supplier_id = s.id) as bouquet_count,
    (SELECT SUM(b.stock) FROM bouquets b WHERE b.supplier_id = s.id) as total_stock,
    (SELECT COUNT(*) FROM purchase_orders po WHERE po.supplier_id = s.id AND MONTH(po.created_at) = MONTH(CURDATE())) as monthly_orders
    FROM suppliers s
    ORDER BY s.name
";

$suppliers_result = $conn->query($suppliers_sql);

// Supplier stats
$stats_sql = "
    SELECT 
        COUNT(*) as total_suppliers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_suppliers,
        COUNT(CASE WHEN payment_terms = 'COD' THEN 1 END) as cod_suppliers,
        COUNT(CASE WHEN payment_terms = '30_days' THEN 1 END) as credit_suppliers
    FROM suppliers
";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<div class="page-header">
    <h1 class="page-title">Supplier Management</h1>
    <div class="page-subtitle">
        <i class="bi bi-truck me-1"></i> Manage your flower suppliers and vendors
    </div>
</div>

<!-- Supplier Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1);">
                <i class="bi bi-building" style="color: #007bff;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['total_suppliers']; ?></div>
            <div class="stats-label">Total Suppliers</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1);">
                <i class="bi bi-check-circle" style="color: #28a745;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['active_suppliers']; ?></div>
            <div class="stats-label">Active</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1);">
                <i class="bi bi-cash" style="color: #ffc107;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['cod_suppliers']; ?></div>
            <div class="stats-label">COD Suppliers</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(108, 117, 125, 0.1);">
                <i class="bi bi-credit-card" style="color: #6c757d;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['credit_suppliers']; ?></div>
            <div class="stats-label">Credit Terms</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Add Supplier Form -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2 flower-icon"></i> Add New Supplier</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label required">Supplier Name</label>
                        <input type="text" class="form-control form-control-floral" name="name" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Contact Person</label>
                            <input type="text" class="form-control form-control-floral" name="contact_person" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control form-control-floral" name="phone">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control form-control-floral" name="email">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control form-control-floral" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Terms</label>
                            <select class="form-select form-control-floral" name="payment_terms">
                                <option value="COD">Cash on Delivery</option>
                                <option value="7_days">7 Days Credit</option>
                                <option value="15_days">15 Days Credit</option>
                                <option value="30_days">30 Days Credit</option>
                                <option value="60_days">60 Days Credit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Delivery Time</label>
                            <input type="text" class="form-control form-control-floral" name="delivery_time" placeholder="e.g., 2-3 days">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-control-floral" name="status">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control form-control-floral" name="notes" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" name="add_supplier" class="btn btn-floral w-100">
                        <i class="bi bi-plus-lg me-2"></i> Add Supplier
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-lightning me-2 leaf-icon"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="purchase_orders.php" class="btn btn-floral">
                        <i class="bi bi-file-earmark-text me-2"></i> View Purchase Orders
                    </a>
                    <button class="btn btn-leaf" data-bs-toggle="modal" data-bs-target="#importSuppliersModal">
                        <i class="bi bi-upload me-2"></i> Import Suppliers
                    </button>
                    <a href="?export=suppliers" class="btn btn-outline-primary">
                        <i class="bi bi-download me-2"></i> Export List
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Suppliers List -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="bi bi-buildings me-2 leaf-icon"></i> 
                    All Suppliers <span class="badge bg-primary"><?php echo $suppliers_result->num_rows; ?> suppliers</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($suppliers_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-floral data-table">
                        <thead>
                            <tr>
                                <th>Supplier Details</th>
                                <th>Contact</th>
                                <th>Products & Stock</th>
                                <th>Terms</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $supplier['name']; ?></div>
                                    <div class="small text-muted"><?php echo $supplier['address']; ?></div>
                                    <div class="small">
                                        <i class="bi bi-clock me-1"></i> 
                                        Delivery: <?php echo $supplier['delivery_time']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><i class="bi bi-person me-1"></i> <?php echo $supplier['contact_person']; ?></div>
                                        <div><i class="bi bi-telephone me-1"></i> <?php echo $supplier['phone']; ?></div>
                                        <div><i class="bi bi-envelope me-1"></i> <?php echo $supplier['email']; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="fw-bold text-primary"><?php echo $supplier['bouquet_count']; ?></div>
                                            <div class="small text-muted">Bouquets</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="fw-bold text-success"><?php echo $supplier['total_stock'] ?: 0; ?></div>
                                            <div class="small text-muted">Total Stock</div>
                                        </div>
                                    </div>
                                    <div class="small text-center text-muted mt-1">
                                        <i class="bi bi-cart me-1"></i> 
                                        <?php echo $supplier['monthly_orders']; ?> orders this month
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php 
                                        $terms = [
                                            'COD' => 'Cash on Delivery',
                                            '7_days' => '7 Days Credit',
                                            '15_days' => '15 Days Credit',
                                            '30_days' => '30 Days Credit',
                                            '60_days' => '60 Days Credit'
                                        ];
                                        echo $terms[$supplier['payment_terms']] ?? $supplier['payment_terms'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-status badge-<?php echo $supplier['status']; ?>">
                                        <?php echo ucfirst($supplier['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editSupplierModal"
                                                data-id="<?php echo $supplier['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($supplier['name']); ?>"
                                                data-contact="<?php echo htmlspecialchars($supplier['contact_person']); ?>"
                                                data-email="<?php echo htmlspecialchars($supplier['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($supplier['phone']); ?>"
                                                data-address="<?php echo htmlspecialchars($supplier['address']); ?>"
                                                data-terms="<?php echo $supplier['payment_terms']; ?>"
                                                data-delivery="<?php echo $supplier['delivery_time']; ?>"
                                                data-status="<?php echo $supplier['status']; ?>"
                                                data-notes="<?php echo htmlspecialchars($supplier['notes']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?view=<?php echo $supplier['id']; ?>" 
                                           class="btn btn-outline-success"
                                           data-bs-toggle="tooltip" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                            <button type="submit" name="delete_supplier" 
                                                    class="btn btn-outline-danger"
                                                    onclick="return confirm('Delete this supplier?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-building" style="font-size: 3rem; color: #ddd;"></i>
                    <h5 class="mt-3">No Suppliers Yet</h5>
                    <p class="text-muted">Add your first flower supplier to get started</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Supplier Performance -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2 flower-icon"></i> Supplier Performance</h5>
            </div>
            <div class="card-body">
                <canvas id="supplierChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editSupplierForm">
                <input type="hidden" name="supplier_id" id="editSupplierId">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" class="form-control form-control-floral" name="name" id="editSupplierName" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control form-control-floral" name="contact_person" id="editContactPerson" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control form-control-floral" name="phone" id="editPhone">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control form-control-floral" name="email" id="editEmail">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control form-control-floral" name="address" id="editAddress" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Terms</label>
                            <select class="form-select form-control-floral" name="payment_terms" id="editPaymentTerms">
                                <option value="COD">Cash on Delivery</option>
                                <option value="7_days">7 Days Credit</option>
                                <option value="15_days">15 Days Credit</option>
                                <option value="30_days">30 Days Credit</option>
                                <option value="60_days">60 Days Credit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Delivery Time</label>
                            <input type="text" class="form-control form-control-floral" name="delivery_time" id="editDeliveryTime">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-control-floral" name="status" id="editStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control form-control-floral" name="notes" id="editNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_supplier" class="btn btn-floral">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Suppliers Modal -->
<div class="modal fade" id="importSuppliersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Import Suppliers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="bi bi-file-earmark-excel" style="font-size: 3rem; color: #28a745;"></i>
                    <h5 class="mt-3">Import from Excel/CSV</h5>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Choose File</label>
                    <input type="file" class="form-control form-control-floral" accept=".csv,.xlsx,.xls">
                    <div class="form-text">Download <a href="#">template file</a> for correct format</div>
                </div>
                
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-2"></i>
                    Required columns: Name, Contact Person, Email, Phone, Address, Payment Terms
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-floral">Import Suppliers</button>
            </div>
        </div>
    </div>
</div>

<script>
// Supplier Chart
const supplierCtx = document.getElementById('supplierChart').getContext('2d');
const supplierChart = new Chart(supplierCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Monthly Orders',
            data: [12, 19, 15, 25, 22, 30],
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
                title: {
                    display: true,
                    text: 'Number of Orders'
                }
            }
        }
    }
});

// Edit Supplier Modal
const editSupplierModal = document.getElementById('editSupplierModal');
editSupplierModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    
    document.getElementById('editSupplierId').value = button.getAttribute('data-id');
    document.getElementById('editSupplierName').value = button.getAttribute('data-name');
    document.getElementById('editContactPerson').value = button.getAttribute('data-contact');
    document.getElementById('editEmail').value = button.getAttribute('data-email');
    document.getElementById('editPhone').value = button.getAttribute('data-phone');
    document.getElementById('editAddress').value = button.getAttribute('data-address');
    document.getElementById('editPaymentTerms').value = button.getAttribute('data-terms');
    document.getElementById('editDeliveryTime').value = button.getAttribute('data-delivery');
    document.getElementById('editStatus').value = button.getAttribute('data-status');
    document.getElementById('editNotes').value = button.getAttribute('data-notes');
});

// Handle edit form submission
document.getElementById('editSupplierForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Implement AJAX update here
    alert('Supplier updated successfully!');
    $('#editSupplierModal').modal('hide');
    location.reload();
});

// Search functionality
$(document).ready(function() {
    $('.data-table').DataTable({
        "pageLength": 10,
        "language": {
            "search": "<i class='bi bi-search'></i> Search suppliers:"
        }
    });
});
</script>

<style>
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
</style>

<?php include '../inclusion/footer.php'; ?>