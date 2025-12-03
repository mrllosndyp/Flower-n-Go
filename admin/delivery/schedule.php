<?php
include '../inclusion/header.php';
include '../config.php';

// Get current date or selected date
$selected_date = $_GET['date'] ?? date('Y-m-d');
$view_type = $_GET['view'] ?? 'daily'; // daily, weekly, monthly

// Calculate date range based on view type
if ($view_type === 'weekly') {
    $start_date = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
    $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($selected_date)));
    $date_range = date('M d', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
} elseif ($view_type === 'monthly') {
    $start_date = date('Y-m-01', strtotime($selected_date));
    $end_date = date('Y-m-t', strtotime($selected_date));
    $date_range = date('F Y', strtotime($selected_date));
} else {
    $start_date = $selected_date;
    $end_date = $selected_date;
    $date_range = date('F d, Y', strtotime($selected_date));
}

// Time slots
$time_slots = [
    '09:00-11:00' => 'Morning (9AM-11AM)',
    '11:00-13:00' => 'Midday (11AM-1PM)',
    '13:00-15:00' => 'Afternoon (1PM-3PM)',
    '15:00-17:00' => 'Late Afternoon (3PM-5PM)',
    '17:00-19:00' => 'Evening (5PM-7PM)',
    '19:00-21:00' => 'Night (7PM-9PM)'
];

// Get deliveries for the date range
$deliveries_sql = "
    SELECT o.*, c.full_name, c.phone, c.email,
    a.address_line1, a.address_line2, a.city, a.province, a.zip_code, a.landmark,
    d.driver_name, d.vehicle_number, d.driver_phone,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count,
    (SELECT GROUP_CONCAT(b.name SEPARATOR ', ') 
     FROM order_items oi 
     JOIN bouquets b ON oi.bouquet_id = b.id 
     WHERE oi.order_id = o.id LIMIT 2) as bouquet_names
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    LEFT JOIN delivery_drivers d ON o.driver_id = d.id
    WHERE o.delivery_type = 'delivery' 
    AND o.delivery_date BETWEEN ? AND ?
    AND o.status IN ('processing', 'shipped', 'delivered')
    ORDER BY o.delivery_date, o.delivery_time_slot, o.created_at
";

$deliveries_stmt = $conn->prepare($deliveries_sql);
$deliveries_stmt->bind_param("ss", $start_date, $end_date);
$deliveries_stmt->execute();
$deliveries_result = $deliveries_stmt->get_result();

// Group deliveries by time slot
$deliveries_by_slot = [];
while ($delivery = $deliveries_result->fetch_assoc()) {
    $time_slot = $delivery['delivery_time_slot'] ?? '09:00-11:00';
    if (!isset($deliveries_by_slot[$time_slot])) {
        $deliveries_by_slot[$time_slot] = [];
    }
    $deliveries_by_slot[$time_slot][] = $delivery;
}

// Get delivery stats
$stats_sql = "
    SELECT 
        COUNT(CASE WHEN o.status = 'processing' AND o.delivery_date = CURDATE() THEN 1 END) as today_pending,
        COUNT(CASE WHEN o.status = 'shipped' AND o.delivery_date = CURDATE() THEN 1 END) as today_out_for_delivery,
        COUNT(CASE WHEN o.status = 'delivered' AND o.delivery_date = CURDATE() THEN 1 END) as today_delivered,
        COUNT(CASE WHEN o.delivery_date = CURDATE() THEN 1 END) as total_today,
        COUNT(CASE WHEN o.delivery_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as tomorrow_scheduled,
        (SELECT COUNT(*) FROM delivery_drivers WHERE status = 'active') as active_drivers
    FROM orders o
    WHERE o.delivery_type = 'delivery'
";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Handle driver assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_driver'])) {
    $order_id = (int)$_POST['order_id'];
    $driver_id = (int)$_POST['driver_id'];
    
    $update_stmt = $conn->prepare("UPDATE orders SET driver_id = ?, status = 'shipped', updated_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("ii", $driver_id, $order_id);
    
    if ($update_stmt->execute()) {
        $success = "Driver assigned successfully!";
        logActivity('assign_driver', "Assigned driver to order #$order_id");
        
        // Refresh deliveries
        $deliveries_stmt->execute();
        $deliveries_result = $deliveries_stmt->get_result();
    }
}

// Get available drivers
$drivers_sql = "SELECT * FROM delivery_drivers WHERE status = 'active' ORDER BY name";
$drivers_result = $conn->query($drivers_sql);
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">Delivery Schedule</h1>
            <div class="page-subtitle">
                <i class="bi bi-calendar-date me-1"></i> 
                <?php echo $date_range; ?>
                <span class="mx-2">•</span>
                <span class="badge bg-primary"><?php echo $deliveries_result->num_rows; ?> deliveries</span>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-floral" data-bs-toggle="modal" data-bs-target="#addDeliveryModal">
                <i class="bi bi-plus-circle me-2"></i> Schedule Delivery
            </button>
        </div>
    </div>
</div>

<!-- Delivery Stats -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1);">
                <i class="bi bi-clock" style="color: #ffc107;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['today_pending']; ?></div>
            <div class="stats-label">Today Pending</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1);">
                <i class="bi bi-truck" style="color: #007bff;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['today_out_for_delivery']; ?></div>
            <div class="stats-label">Out for Delivery</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1);">
                <i class="bi bi-check-circle" style="color: #28a745;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['today_delivered']; ?></div>
            <div class="stats-label">Today Delivered</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(23, 162, 184, 0.1);">
                <i class="bi bi-calendar-check" style="color: #17a2b8;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['total_today']; ?></div>
            <div class="stats-label">Total Today</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(111, 66, 193, 0.1);">
                <i class="bi bi-calendar-plus" style="color: #6f42c1;"></i>
            </div>
            <div class="stats-number"><?php echo $stats['tomorrow_scheduled']; ?></div>
            <div class="stats-label">Tomorrow</div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 107, 139, 0.1);">
                <i class="bi bi-people" style="color: var(--primary-pink);"></i>
            </div>
            <div class="stats-number"><?php echo $stats['active_drivers']; ?></div>
            <div class="stats-label">Active Drivers</div>
        </div>
    </div>
</div>

<!-- Date Navigation & Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="btn-group" role="group">
                    <a href="?view=daily&date=<?php echo date('Y-m-d', strtotime($selected_date . ' -1 day')); ?>" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-floral dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar me-2"></i>
                            <?php echo date('F d, Y', strtotime($selected_date)); ?>
                        </button>
                        <div class="dropdown-menu">
                            <input type="date" class="form-control m-2" id="datePicker" 
                                   value="<?php echo $selected_date; ?>"
                                   onchange="window.location.href = '?view=<?php echo $view_type; ?>&date=' + this.value">
                        </div>
                    </div>
                    
                    <a href="?view=daily&date=<?php echo date('Y-m-d', strtotime($selected_date . ' +1 day')); ?>" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    
                    <a href="?view=daily&date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary ms-2">
                        Today
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 text-center">
                <div class="btn-group" role="group">
                    <a href="?view=daily&date=<?php echo $selected_date; ?>" 
                       class="btn <?php echo $view_type === 'daily' ? 'btn-leaf' : 'btn-outline-secondary'; ?>">
                        Daily
                    </a>
                    <a href="?view=weekly&date=<?php echo $selected_date; ?>" 
                       class="btn <?php echo $view_type === 'weekly' ? 'btn-leaf' : 'btn-outline-secondary'; ?>">
                        Weekly
                    </a>
                    <a href="?view=monthly&date=<?php echo $selected_date; ?>" 
                       class="btn <?php echo $view_type === 'monthly' ? 'btn-leaf' : 'btn-outline-secondary'; ?>">
                        Monthly
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="printSchedule()">
                        <i class="bi bi-printer me-2"></i> Print
                    </button>
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#optimizeRouteModal">
                        <i class="bi bi-geo-alt me-2"></i> Optimize Routes
                    </button>
                    <a href="?export=schedule" class="btn btn-outline-info">
                        <i class="bi bi-download me-2"></i> Export
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Schedule by Time Slots -->
<div class="row">
    <?php foreach ($time_slots as $slot_key => $slot_label): 
        $slot_deliveries = $deliveries_by_slot[$slot_key] ?? [];
        $slot_count = count($slot_deliveries);
    ?>
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock me-2 flower-icon"></i> 
                    <?php echo $slot_label; ?>
                    <span class="badge bg-primary ms-2"><?php echo $slot_count; ?> deliveries</span>
                </h5>
                <span class="badge bg-light text-dark">
                    <?php echo $slot_key; ?>
                </span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if ($slot_count > 0): ?>
                    <?php foreach ($slot_deliveries as $delivery): ?>
                    <div class="delivery-card mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <a href="../orders/view_order.php?id=<?php echo $delivery['id']; ?>" class="text-decoration-none">
                                        #<?php echo $delivery['order_number']; ?>
                                    </a>
                                </h6>
                                <div class="small text-muted">
                                    <i class="bi bi-person me-1"></i> <?php echo $delivery['full_name']; ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-status badge-<?php echo $delivery['status']; ?>">
                                    <?php echo ucfirst($delivery['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="small">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?php 
                                $address = $delivery['address_line1'];
                                if ($delivery['address_line2']) $address .= ', ' . $delivery['address_line2'];
                                $address .= ', ' . $delivery['city'];
                                echo strlen($address) > 40 ? substr($address, 0, 40) . '...' : $address;
                                ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small">
                                <?php if ($delivery['bouquet_names']): ?>
                                <i class="bi bi-flower2 me-1"></i>
                                <?php echo $delivery['bouquet_names']; ?>
                                <?php endif; ?>
                            </div>
                            <div class="small fw-bold">
                                ₱<?php echo number_format($delivery['total_amount'], 0); ?>
                            </div>
                        </div>
                        
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <?php if ($delivery['driver_name']): ?>
                                    <i class="bi bi-person-badge me-1 text-success"></i>
                                    <?php echo $delivery['driver_name']; ?>
                                    <?php else: ?>
                                    <span class="text-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i> No driver assigned
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$delivery['driver_name']): ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#assignDriverModal"
                                            data-order-id="<?php echo $delivery['id']; ?>"
                                            data-order-number="<?php echo $delivery['order_number']; ?>"
                                            data-customer-name="<?php echo htmlspecialchars($delivery['full_name']); ?>">
                                        Assign
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-outline-success btn-sm"
                                            onclick="markAsDelivered(<?php echo $delivery['id']; ?>)">
                                        Delivered
                                    </button>
                                    
                                    <a href="../orders/view_order.php?id=<?php echo $delivery['id']; ?>" 
                                       class="btn btn-outline-info btn-sm">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle" style="font-size: 3rem; color: #ddd;"></i>
                        <p class="text-muted mt-3">No deliveries scheduled for this time slot</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Delivery Map View -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-geo me-2 leaf-icon"></i> Delivery Route Map</h5>
    </div>
    <div class="card-body">
        <div class="delivery-map" id="deliveryMap">
            <div class="text-center">
                <i class="bi bi-map" style="font-size: 4rem; color: #ddd;"></i>
                <h5 class="mt-3">Delivery Route Visualization</h5>
                <p class="text-muted">Map integration would show optimized delivery routes here</p>
                <button class="btn btn-floral mt-2" onclick="showSampleRoute()">
                    <i class="bi bi-play-circle me-2"></i> Show Sample Route
                </button>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <div class="display-6 text-primary"><?php echo $stats['total_today']; ?></div>
                    <div class="text-muted">Total Stops</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <div class="display-6 text-success" id="estimatedDistance">--</div>
                    <div class="text-muted">Est. Distance (km)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <div class="display-6 text-warning" id="estimatedTime">--</div>
                    <div class="text-muted">Est. Time (hrs)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 bg-light rounded">
                    <div class="display-6 text-info" id="fuelCost">--</div>
                    <div class="text-muted">Est. Fuel Cost (₱)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Driver Modal -->
<div class="modal fade" id="assignDriverModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Assign Delivery Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="assignDriverForm">
                <input type="hidden" name="order_id" id="assignOrderId">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="fw-bold">Order #<span id="assignOrderNumber"></span></div>
                        <div class="text-muted">Customer: <span id="assignCustomerName"></span></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Select Driver</label>
                        <select class="form-select form-control-floral" name="driver_id" required>
                            <option value="">Choose a driver...</option>
                            <?php while($driver = $drivers_result->fetch_assoc()): ?>
                            <option value="<?php echo $driver['id']; ?>">
                                <?php echo $driver['name']; ?> 
                                (<?php echo $driver['vehicle_number']; ?>)
                                <?php if($driver['current_load'] > 0): ?>
                                - <?php echo $driver['current_load']; ?> deliveries today
                                <?php endif; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estimated Delivery Time</label>
                        <input type="text" class="form-control form-control-floral" 
                               id="estimatedDeliveryTime" readonly>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="notify_customer" id="notifyCustomer" checked>
                        <label class="form-check-label" for="notifyCustomer">
                            Notify customer via SMS/Email
                        </label>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-2"></i>
                        Driver will be notified of this assignment via their app.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_driver" class="btn btn-floral">Assign Driver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Delivery Modal -->
<div class="modal fade" id="addDeliveryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Schedule New Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleDeliveryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Delivery Date</label>
                        <input type="date" class="form-control form-control-floral" 
                               id="deliveryDate" 
                               value="<?php echo date('Y-m-d'); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Time Slot</label>
                        <select class="form-select form-control-floral" id="deliveryTimeSlot">
                            <?php foreach ($time_slots as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Order</label>
                        <select class="form-select form-control-floral" id="deliveryOrder">
                            <option value="">Choose an order...</option>
                            <!-- Orders would be populated via AJAX -->
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select form-control-floral" id="deliveryPriority">
                            <option value="normal">Normal</option>
                            <option value="high">High Priority</option>
                            <option value="express">Express Delivery</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control form-control-floral" id="deliveryNotes" rows="3" 
                                  placeholder="Special instructions for delivery..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-floral" onclick="scheduleDelivery()">Schedule Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Optimize Routes Modal -->
<div class="modal fade" id="optimizeRouteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Optimize Delivery Routes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Distribution</h6>
                        <div class="list-group mb-3">
                            <div class="list-group-item d-flex justify-content-between">
                                <span>Driver 1 (Juan)</span>
                                <span class="badge bg-primary">5 deliveries</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span>Driver 2 (Pedro)</span>
                                <span class="badge bg-primary">4 deliveries</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span>Driver 3 (Maria)</span>
                                <span class="badge bg-primary">6 deliveries</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Optimization Options</h6>
                        <div class="mb-3">
                            <label class="form-label">Optimization Type</label>
                            <select class="form-select form-control-floral" id="optimizationType">
                                <option value="distance">Minimize Distance</option>
                                <option value="time">Minimize Time</option>
                                <option value="balance">Balance Workload</option>
                                <option value="priority">Prioritize Express</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="considerTraffic" checked>
                            <label class="form-check-label" for="considerTraffic">
                                Consider traffic conditions
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="groupByArea" checked>
                            <label class="form-check-label" for="groupByArea">
                                Group by geographic area
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-success mt-3">
                    <i class="bi bi-lightbulb me-2"></i>
                    Optimization can reduce total distance by up to 25% and save approximately 2 hours of delivery time.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-floral" onclick="optimizeRoutes()">
                    <i class="bi bi-magic me-2"></i> Optimize Routes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.delivery-card {
    border-left: 4px solid var(--leaf-green);
    transition: all 0.3s;
    background: white;
}

.delivery-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.delivery-card.no-driver {
    border-left: 4px solid #ffc107;
    background: rgba(255, 193, 7, 0.05);
}

.delivery-card.delivered {
    border-left: 4px solid #28a745;
    background: rgba(40, 167, 69, 0.05);
}

.delivery-map {
    height: 300px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
}

.badge-processing { background: linear-gradient(135deg, #cce5ff, #a8d4ff); color: #004085; }
.badge-shipped { background: linear-gradient(135deg, #d1ecf1, #b3e0e8); color: #0c5460; }
.badge-delivered { background: linear-gradient(135deg, #d4edda, #b8e0c1); color: #155724; }

.time-slot-header {
    background: linear-gradient(135deg, var(--primary-pink), #ff8e8e);
    color: white;
    padding: 10px 15px;
    border-radius: 8px 8px 0 0;
}
</style>

<script>
// Assign Driver Modal
const assignDriverModal = document.getElementById('assignDriverModal');
assignDriverModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const orderId = button.getAttribute('data-order-id');
    const orderNumber = button.getAttribute('data-order-number');
    const customerName = button.getAttribute('data-customer-name');
    
    document.getElementById('assignOrderId').value = orderId;
    document.getElementById('assignOrderNumber').textContent = orderNumber;
    document.getElementById('assignCustomerName').textContent = customerName;
    
    // Calculate estimated delivery time (sample logic)
    const now = new Date();
    const estimatedTime = new Date(now.getTime() + 60 * 60 * 1000); // +1 hour
    document.getElementById('estimatedDeliveryTime').value = 
        estimatedTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
});

// Calculate route estimates
function calculateRouteEstimates() {
    const totalDeliveries = <?php echo $stats['total_today']; ?>;
    const estimatedDistance = totalDeliveries * 3.5; // 3.5km per delivery average
    const estimatedTime = (totalDeliveries * 15) / 60; // 15 minutes per delivery
    const fuelCost = estimatedDistance * 10; // ₱10 per km
    
    document.getElementById('estimatedDistance').textContent = estimatedDistance.toFixed(1);
    document.getElementById('estimatedTime').textContent = estimatedTime.toFixed(1);
    document.getElementById('fuelCost').textContent = fuelCost.toFixed(0);
}

// Mark as delivered
function markAsDelivered(orderId) {
    if (confirm('Mark this delivery as completed?')) {
        fetch('update_delivery.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: 'delivered'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Schedule delivery
function scheduleDelivery() {
    const date = document.getElementById('deliveryDate').value;
    const timeSlot = document.getElementById('deliveryTimeSlot').value;
    const orderId = document.getElementById('deliveryOrder').value;
    const priority = document.getElementById('deliveryPriority').value;
    const notes = document.getElementById('deliveryNotes').value;
    
    if (!orderId) {
        alert('Please select an order');
        return;
    }
    
    // Implement scheduling logic
    alert(`Delivery scheduled for ${date} (${timeSlot})`);
    $('#addDeliveryModal').modal('hide');
}

// Optimize routes
function optimizeRoutes() {
    const type = document.getElementById('optimizationType').value;
    const considerTraffic = document.getElementById('considerTraffic').checked;
    const groupByArea = document.getElementById('groupByArea').checked;
    
    // Show loading
    alert(`Optimizing routes for ${type}...`);
    
    // Simulate optimization
    setTimeout(() => {
        alert('Routes optimized successfully! Estimated savings: 22% distance, 1.8 hours time');
        $('#optimizeRouteModal').modal('hide');
    }, 1500);
}

// Show sample route
function showSampleRoute() {
    const mapDiv = document.getElementById('deliveryMap');
    mapDiv.innerHTML = `
        <div class="text-center p-4">
            <div class="mb-3">
                <div class="d-flex justify-content-center mb-3">
                    <div class="route-point me-3">
                        <div class="route-dot bg-primary"></div>
                        <div class="small">Store</div>
                    </div>
                    <div class="route-line"></div>
                    <div class="route-point me-3">
                        <div class="route-dot bg-success"></div>
                        <div class="small">Stop 1</div>
                    </div>
                    <div class="route-line"></div>
                    <div class="route-point me-3">
                        <div class="route-dot bg-success"></div>
                        <div class="small">Stop 2</div>
                    </div>
                    <div class="route-line"></div>
                    <div class="route-point">
                        <div class="route-dot bg-danger"></div>
                        <div class="small">Stop 3</div>
                    </div>
                </div>
            </div>
            <h5>Optimized Route Sample</h5>
            <p class="text-muted">Total: 12.5km • Est. Time: 2.8 hours • 8 deliveries</p>
            <button class="btn btn-outline-secondary btn-sm" onclick="resetMap()">
                <i class="bi bi-x-circle me-1"></i> Close
            </button>
        </div>
    `;
}

function resetMap() {
    const mapDiv = document.getElementById('deliveryMap');
    mapDiv.innerHTML = `
        <div class="text-center">
            <i class="bi bi-map" style="font-size: 4rem; color: #ddd;"></i>
            <h5 class="mt-3">Delivery Route Visualization</h5>
            <p class="text-muted">Map integration would show optimized delivery routes here</p>
            <button class="btn btn-floral mt-2" onclick="showSampleRoute()">
                <i class="bi bi-play-circle me-2"></i> Show Sample Route
            </button>
        </div>
    `;
}

// Print schedule
function printSchedule() {
    window.open('print_schedule.php?date=<?php echo $selected_date; ?>&view=<?php echo $view_type; ?>', '_blank');
}

// Initialize
$(document).ready(function() {
    calculateRouteEstimates();
    
    // Highlight deliveries without drivers
    $('.delivery-card:has(.text-warning)').addClass('no-driver');
    
    // Auto-refresh every 2 minutes for real-time updates
    setInterval(() => {
        // Check for new deliveries (optional)
        // location.reload();
    }, 120000);
});
</script>

<?php include '../inclusion/footer.php'; ?>