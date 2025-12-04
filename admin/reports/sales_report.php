<?php
include '../inclusion/header.php';
include '../config.php';

// Date range filters
$date_range = $_GET['date_range'] ?? 'today';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'summary';

// Adjust date range based on selection
switch ($date_range) {
    case 'today':
        $start_date = $end_date = date('Y-m-d');
        break;
    case 'yesterday':
        $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'last_week':
        $start_date = date('Y-m-d', strtotime('monday last week'));
        $end_date = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'this_month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('-1 month'));
        $end_date = date('Y-m-t', strtotime('-1 month'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        // Use custom dates from inputs
        break;
}

// Sales Summary
$summary_sql = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        AVG(CASE WHEN status = 'delivered' THEN total_amount ELSE NULL END) as avg_order_value,
        (SELECT COUNT(DISTINCT customer_id) FROM orders 
         WHERE created_at BETWEEN ? AND ? AND status = 'delivered') as unique_customers
    FROM orders 
    WHERE created_at BETWEEN ? AND ?
";

$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Sales by Payment Method
$payment_methods_sql = "
    SELECT 
        payment_method,
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount,
        AVG(total_amount) as avg_amount
    FROM orders 
    WHERE created_at BETWEEN ? AND ? AND status = 'delivered'
    GROUP BY payment_method
    ORDER BY total_amount DESC
";

$payment_stmt = $conn->prepare($payment_methods_sql);
$payment_stmt->bind_param("ss", $start_date, $end_date);
$payment_stmt->execute();
$payment_methods = $payment_stmt->get_result();

// Top Selling Bouquets
$top_bouquets_sql = "
    SELECT 
        b.name,
        b.category,
        b.image,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.unit_price) as total_revenue,
        COUNT(DISTINCT oi.order_id) as order_count
    FROM order_items oi
    JOIN bouquets b ON oi.bouquet_id = b.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ? AND o.status = 'delivered'
    GROUP BY b.id
    ORDER BY total_quantity DESC
    LIMIT 10
";

$top_stmt = $conn->prepare($top_bouquets_sql);
$top_stmt->bind_param("ss", $start_date, $end_date);
$top_stmt->execute();
$top_bouquets = $top_stmt->get_result();

// Daily Sales Trend (for chart)
$daily_trend_sql = "
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as order_count,
        SUM(total_amount) as daily_revenue,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE created_at BETWEEN ? AND ? AND status = 'delivered'
    GROUP BY DATE(created_at)
    ORDER BY sale_date
";

$daily_stmt = $conn->prepare($daily_trend_sql);
$daily_stmt->bind_param("ss", $start_date, $end_date);
$daily_stmt->execute();
$daily_trend = $daily_stmt->get_result();

// Prepare data for charts
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];

while ($day = $daily_trend->fetch_assoc()) {
    $chart_labels[] = date('M d', strtotime($day['sale_date']));
    $chart_revenue[] = $day['daily_revenue'];
    $chart_orders[] = $day['order_count'];
}

// Customer acquisition
$customer_sql = "
    SELECT 
        DATE(c.created_at) as join_date,
        COUNT(*) as new_customers,
        (SELECT COUNT(DISTINCT o.customer_id) 
         FROM orders o 
         WHERE DATE(o.created_at) = DATE(c.created_at) 
         AND o.status = 'delivered') as converting_customers
    FROM customers c
    WHERE c.created_at BETWEEN ? AND ?
    GROUP BY DATE(c.created_at)
    ORDER BY join_date
";

$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("ss", $start_date, $end_date);
$customer_stmt->execute();
$customer_acquisition = $customer_stmt->get_result();
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">Sales Analytics</h1>
            <div class="page-subtitle">
                <i class="bi bi-graph-up me-1"></i> 
                <?php 
                if ($date_range === 'today') {
                    echo "Today's Report - " . date('F d, Y');
                } elseif ($date_range === 'custom') {
                    echo date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date));
                } else {
                    echo ucfirst(str_replace('_', ' ', $date_range)) . ' Report';
                }
                ?>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-floral" onclick="printReport()">
                <i class="bi bi-printer me-2"></i> Print Report
            </button>
        </div>
    </div>
</div>

<!-- Date Range Selector -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Report Period</label>
                <select class="form-select form-control-floral" name="date_range" id="dateRange" onchange="toggleCustomDates()">
                    <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="yesterday" <?php echo $date_range === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                    <option value="this_week" <?php echo $date_range === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="last_week" <?php echo $date_range === 'last_week' ? 'selected' : ''; ?>>Last Week</option>
                    <option value="this_month" <?php echo $date_range === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="last_month" <?php echo $date_range === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                    <option value="this_year" <?php echo $date_range === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                    <option value="custom" <?php echo $date_range === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                </select>
            </div>
            
            <div class="col-md-2" id="customStartDate" style="<?php echo $date_range === 'custom' ? '' : 'display: none;'; ?>">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control form-control-floral" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            
            <div class="col-md-2" id="customEndDate" style="<?php echo $date_range === 'custom' ? '' : 'display: none;'; ?>">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control form-control-floral" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select class="form-select form-control-floral" name="report_type">
                    <option value="summary" <?php echo $report_type === 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                    <option value="detailed" <?php echo $report_type === 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                    <option value="comparative" <?php echo $report_type === 'comparative' ? 'selected' : ''; ?>>Comparative Analysis</option>
                    <option value="export" <?php echo $report_type === 'export' ? 'selected' : ''; ?>>Export Data</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-floral w-100">
                    <i class="bi bi-filter me-2"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Key Metrics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 107, 139, 0.1);">
                <i class="bi bi-currency-dollar" style="color: var(--primary-pink);"></i>
            </div>
            <div class="stats-number">₱<?php echo number_format($summary['total_revenue'] ?? 0, 0); ?></div>
            <div class="stats-label">Total Revenue</div>
            <div class="small text-muted mt-1">
                <?php echo $summary['completed_orders'] ?? 0; ?> completed orders
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(40, 167, 69, 0.1);">
                <i class="bi bi-cart-check" style="color: #28a745;"></i>
            </div>
            <div class="stats-number"><?php echo $summary['total_orders'] ?? 0; ?></div>
            <div class="stats-label">Total Orders</div>
            <div class="small text-muted mt-1">
                <?php echo $summary['cancelled_orders'] ?? 0; ?> cancelled
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(0, 123, 255, 0.1);">
                <i class="bi bi-people" style="color: #007bff;"></i>
            </div>
            <div class="stats-number"><?php echo $summary['unique_customers'] ?? 0; ?></div>
            <div class="stats-label">Unique Customers</div>
            <div class="small text-muted mt-1">
                New customer acquisition
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1);">
                <i class="bi bi-calculator" style="color: #ffc107;"></i>
            </div>
            <div class="stats-number">₱<?php echo number_format($summary['avg_order_value'] ?? 0, 0); ?></div>
            <div class="stats-label">Average Order Value</div>
            <div class="small text-muted mt-1">
                Per completed order
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2 flower-icon"></i> Sales Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2 leaf-icon"></i> Payment Methods</h5>
            </div>
            <div class="card-body">
                <canvas id="paymentChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Selling Bouquets -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-trophy me-2 flower-icon"></i> Top Selling Bouquets</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-floral">
                <thead>
                    <tr>
                        <th>Bouquet</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Quantity Sold</th>
                        <th class="text-center">Orders</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-center">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($bouquet = $top_bouquets->fetch_assoc()): 
                        $performance = ($bouquet['total_quantity'] / max(1, $bouquet['order_count'])) * 100;
                        $performance_class = $performance > 200 ? 'success' : ($performance > 100 ? 'warning' : 'info');
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if($bouquet['image']): ?>
                                <div class="flex-shrink-0 me-3">
                                    <img src="../../uploads/bouquets/thumbs/<?php echo $bouquet['image']; ?>" 
                                         alt="<?php echo $bouquet['name']; ?>" 
                                         class="rounded" 
                                         width="50" 
                                         height="50">
                                </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo $bouquet['name']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark"><?php echo $bouquet['category']; ?></span>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold text-primary"><?php echo $bouquet['total_quantity']; ?></div>
                        </td>
                        <td class="text-center">
                            <?php echo $bouquet['order_count']; ?>
                        </td>
                        <td class="text-end fw-bold text-success">
                            ₱<?php echo number_format($bouquet['total_revenue'], 0); ?>
                        </td>
                        <td class="text-center">
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-<?php echo $performance_class; ?>" 
                                     style="width: <?php echo min(100, $performance / 2); ?>%">
                                </div>
                            </div>
                            <div class="small text-muted">
                                <?php echo round($performance); ?>% per order
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detailed Breakdown -->
<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-wallet2 me-2 leaf-icon"></i> Payment Method Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php while($method = $payment_methods->fetch_assoc()): 
                        $percentage = ($method['total_amount'] / max(1, $summary['total_revenue'])) * 100;
                    ?>
                    <div class="list-group-item border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?php echo ucfirst($method['payment_method']); ?></div>
                                <div class="small text-muted">
                                    <?php echo $method['order_count']; ?> orders • 
                                    Avg: ₱<?php echo number_format($method['avg_amount'], 0); ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">₱<?php echo number_format($method['total_amount'], 0); ?></div>
                                <div class="small text-muted">
                                    <?php echo round($percentage, 1); ?>% of total
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0"><i class="bi bi-people me-2 flower-icon"></i> Customer Acquisition</h5>
            </div>
            <div class="card-body">
                <canvas id="customerChart" height="200"></canvas>
                <div class="row mt-3 text-center">
                    <div class="col-6">
                        <div class="fw-bold text-primary">
                            <?php 
                            $total_customers = 0;
                            $converting_customers = 0;
                            $customer_acquisition->data_seek(0); // Reset pointer
                            while($cust = $customer_acquisition->fetch_assoc()) {
                                $total_customers += $cust['new_customers'];
                                $converting_customers += $cust['converting_customers'];
                            }
                            echo $total_customers;
                            ?>
                        </div>
                        <div class="small text-muted">New Customers</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-success">
                            <?php 
                            $conversion_rate = $total_customers > 0 ? ($converting_customers / $total_customers) * 100 : 0;
                            echo round($conversion_rate, 1); ?>%
                        </div>
                        <div class="small text-muted">Conversion Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="bi bi-download me-2 leaf-icon"></i> Export Report</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <a href="?export=pdf&date_range=<?php echo $date_range; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-outline-danger w-100 mb-2">
                    <i class="bi bi-file-pdf me-2"></i> PDF Report
                </a>
                <small class="text-muted">Printable format</small>
            </div>
            <div class="col-md-3">
                <a href="?export=excel&date_range=<?php echo $date_range; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-outline-success w-100 mb-2">
                    <i class="bi bi-file-excel me-2"></i> Excel Data
                </a>
                <small class="text-muted">Spreadsheet format</small>
            </div>
            <div class="col-md-3">
                <a href="?export=csv&date_range=<?php echo $date_range; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV Export
                </a>
                <small class="text-muted">Raw data</small>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-floral w-100 mb-2" onclick="emailReport()">
                    <i class="bi bi-envelope me-2"></i> Email Report
                </button>
                <small class="text-muted">Send to stakeholders</small>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle custom date inputs
function toggleCustomDates() {
    const dateRange = document.getElementById('dateRange').value;
    const startDate = document.getElementById('customStartDate');
    const endDate = document.getElementById('customEndDate');
    
    if (dateRange === 'custom') {
        startDate.style.display = 'block';
        endDate.style.display = 'block';
    } else {
        startDate.style.display = 'none';
        endDate.style.display = 'none';
    }
}

// Initialize on page load
toggleCustomDates();

// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [
            {
                label: 'Revenue (₱)',
                data: <?php echo json_encode($chart_revenue); ?>,
                borderColor: '#ff6b8b',
                backgroundColor: 'rgba(255, 107, 139, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: <?php echo json_encode($chart_orders); ?>,
                borderColor: '#2e8b57',
                backgroundColor: 'rgba(46, 139, 87, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (₱)'
                },
                grid: {
                    drawOnChartArea: true,
                },
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Orders'
                },
                grid: {
                    drawOnChartArea: false,
                },
            },
        }
    }
});

// Payment Methods Chart
const paymentCtx = document.getElementById('paymentChart').getContext('2d');
const paymentChart = new Chart(paymentCtx, {
    type: 'doughnut',
    data: {
        labels: [],
        datasets: [{
            data: [],
            backgroundColor: [
                '#ff6b8b',
                '#2e8b57',
                '#007bff',
                '#ffc107',
                '#6c757d'
            ],
            borderWidth: 2,
            borderColor: 'white'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ₱';
                        }
                        label += context.parsed.toLocaleString();
                        return label;
                    }
                }
            }
        }
    }
});

// Customer Acquisition Chart
const customerCtx = document.getElementById('customerChart').getContext('2d');
const customerChart = new Chart(customerCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [
            {
                label: 'New Customers',
                data: [],
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: '#007bff',
                borderWidth: 1
            },
            {
                label: 'Converting Customers',
                data: [],
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: '#28a745',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Customers'
                }
            }
        }
    }
});

// Load payment method data via AJAX
function loadPaymentData() {
    fetch(`ajax_payment_data.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>`)
        .then(response => response.json())
        .then(data => {
            paymentChart.data.labels = data.labels;
            paymentChart.data.datasets[0].data = data.data;
            paymentChart.update();
        });
}

// Load customer data via AJAX
function loadCustomerData() {
    fetch(`ajax_customer_data.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>`)
        .then(response => response.json())
        .then(data => {
            customerChart.data.datasets[0].data = data.new_customers;
            customerChart.data.datasets[1].data = data.converting_customers;
            customerChart.update();
        });
}

// Print report
function printReport() {
    window.open('print_report.php?date_range=<?php echo $date_range; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>', '_blank');
}

// Email report
function emailReport() {
    const email = prompt('Enter email address to send report to:');
    if (email) {
        alert(`Report will be sent to ${email}`);
        // Implement email sending logic
    }
}

// Initialize charts
$(document).ready(function() {
    loadPaymentData();
    loadCustomerData();
    
    // Auto-refresh data every 5 minutes
    setInterval(() => {
        loadPaymentData();
        loadCustomerData();
    }, 300000);
});
</script>

<style>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.financial-card {
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.income-card {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border-left: 5px solid #28a745;
}

.expense-card {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border-left: 5px solid #dc3545;
}

.profit-card {
    background: linear-gradient(135deg, #cce5ff, #b8daff);
    border-left: 5px solid #007bff;
}

.metric-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    border: 1px solid #e0e0e0;
    transition: transform 0.3s;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Report table styles */
.report-table th {
    background: linear-gradient(135deg, var(--dark-green), var(--leaf-green));
    color: white;
    border: none;
}

.report-table td {
    vertical-align: middle;
}

/* Chart legends */
.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
}

.legend-color {
    width: 15px;
    height: 15px;
    border-radius: 3px;
}

/* Print styles for reports */
@media print {
    .no-print,
    .admin-nav,
    .admin-header,
    .page-header .btn,
    .card-header .btn {
        display: none !important;
    }
    
    body {
        background: white !important;
        padding: 20px !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    
    .stats-card {
        break-inside: avoid;
    }
    
    .chart-container {
        height: 250px !important;
    }
}

/* Metric highlight */
.highlight-metric {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--primary-pink);
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Performance indicators */
.perf-excellent { color: #28a745; }
.perf-good { color: #ffc107; }
.perf-poor { color: #dc3545; }

/* Data grid for detailed reports */
.data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.grid-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid var(--primary-pink);
}

.grid-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--dark-green);
}

.grid-label {
    color: #666;
    font-size: 0.9rem;
}