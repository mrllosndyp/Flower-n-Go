<?php
include '../../config.php';

if (!isset($_GET['id'])) {
    die('Order ID required');
}

$order_id = (int)$_GET['id'];

// Fetch order details (similar to view_order.php but simplified for printing)
$order_sql = "
    SELECT o.*, c.full_name, c.email, c.phone,
    a.address_line1, a.address_line2, a.city, a.province, a.zip_code
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ?
";

$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

// Fetch order items
$items_sql = "
    SELECT oi.*, b.name as bouquet_name, b.price as unit_price
    FROM order_items oi
    JOIN bouquets b ON oi.bouquet_id = b.id
    WHERE oi.order_id = ?
";

$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result();

// Calculate subtotal
$subtotal = $order['total_amount'] - $order['shipping_fee'] - $order['tax_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order['order_number']; ?> - Flower N Go</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ff6b8b;
            padding-bottom: 20px;
        }
        .logo {
            font-family: 'Dancing Script', cursive;
            font-size: 28px;
            font-weight: bold;
            color: #2e8b57;
        }
        .logo span {
            color: #ff6b8b;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            margin: 0;
            color: #333;
        }
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .detail-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .detail-box h3 {
            margin-top: 0;
            color: #ff6b8b;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #2e8b57;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #ff6b8b;
            border-top: 2px solid #2e8b57;
            margin-top: 10px;
            padding-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                border: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                Flower<span>N</span>Go
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p>#<?php echo $order['order_number']; ?></p>
                <p>Date: <?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- Details -->
        <div class="details">
            <div class="detail-box">
                <h3>BILL TO</h3>
                <p><strong><?php echo $order['full_name']; ?></strong></p>
                <p><?php echo $order['email']; ?></p>
                <p><?php echo $order['phone']; ?></p>
                <?php if($order['address_line1']): ?>
                <p><?php echo $order['address_line1']; ?></p>
                <?php if($order['address_line2']): ?><p><?php echo $order['address_line2']; ?></p><?php endif; ?>
                <p><?php echo $order['city'] . ', ' . $order['province'] . ' ' . $order['zip_code']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="detail-box">
                <h3>ORDER DETAILS</h3>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                <p><strong>Delivery Type:</strong> <?php echo ucfirst($order['delivery_type']); ?></p>
                <?php if($order['delivery_date']): ?>
                <p><strong>Delivery Date:</strong> <?php echo date('F d, Y', strtotime($order['delivery_date'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['bouquet_name']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>₱<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($subtotal, 2); ?></span>
            </div>
            
            <?php if($order['shipping_fee'] > 0): ?>
            <div class="total-row">
                <span>Shipping Fee:</span>
                <span>₱<?php echo number_format($order['shipping_fee'], 2); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($order['tax_amount'] > 0): ?>
            <div class="total-row">
                <span>Tax:</span>
                <span>₱<?php echo number_format($order['tax_amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($order['discount_amount'] > 0): ?>
            <div class="total-row">
                <span>Discount:</span>
                <span>-₱<?php echo number_format($order['discount_amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your order!</p>
            <p>Flower N Go • 123 Flower Street, Manila • (02) 1234-5678 • flowers@flowerngo.com</p>
            <p>This is a computer-generated invoice. No signature required.</p>
        </div>
        
        <!-- Print Button -->
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="padding: 10px 30px; background: #2e8b57; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Print Invoice
            </button>
        </div>
    </div>
    
    <script>
        // Auto print on page load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>