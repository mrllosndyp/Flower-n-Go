<?php
session_start(); 
include "db.php";

$order_id = (int)($_GET['id'] ?? 0); 
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id || $order_id <= 0) { 
    header("Location: signin.php"); 
    exit(); 
}

// Get order details
$stmt = mysqli_prepare($conn, "SELECT o.*, u.name, u.email, u.phone FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=? AND o.user_id=?");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    die("Order not found");
}

$order = mysqli_fetch_assoc($order_result);

// Get order items - FIXED: Initialize as empty array first
$order_items = [];
$subtotal = 0;

$stmt2 = mysqli_prepare($conn, "SELECT p.name, p.price, oi.quantity FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
mysqli_stmt_bind_param($stmt2, "i", $order_id);
mysqli_stmt_execute($stmt2);
$items = mysqli_stmt_get_result($stmt2);

if ($items && mysqli_num_rows($items) > 0) {
    while ($item = mysqli_fetch_assoc($items)) {
        $order_items[] = $item;
        $subtotal += $item['price'] * $item['quantity'];
    }
}

// Check if order has a total, if not calculate it
if (isset($order['total']) && $order['total'] > 0) {
    $total = $order['total'];
} else {
    $total = $subtotal + 200; // Add delivery and service fees
}

$order_date = date('M d, Y', strtotime($order['created_at']));
$order_time = date('g:i A', strtotime($order['created_at']));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt | Flower 'n GO</title>
<style>
body {
    background: #0d0a08;
    font-family: 'Courier New', monospace;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
}

.receipt-container {
    background: white;
    width: 100%;
    max-width: 400px;
    padding: 30px;
    border-radius: 5px;
    box-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
    position: relative;
}

.receipt-header {
    text-align: center;
    margin-bottom: 30px;
    border-bottom: 2px dashed #D4AF37;
    padding-bottom: 20px;
}

.receipt-header h1 {
    color: #D4AF37;
    font-size: 24px;
    margin: 10px 0;
    font-weight: bold;
}

.receipt-header .subtitle {
    color: #666;
    font-size: 14px;
    margin: 5px 0;
}

.order-info {
    margin-bottom: 25px;
    text-align: center;
}

.order-number {
    font-size: 20px;
    font-weight: bold;
    color: #000;
    margin-bottom: 5px;
}

.order-date {
    color: #666;
    font-size: 14px;
    margin-bottom: 20px;
}

.receipt-divider {
    border-bottom: 1px dashed #ccc;
    margin: 15px 0;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.items-table th {
    text-align: left;
    color: #666;
    font-size: 14px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.items-table td {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.item-name {
    color: #000;
}

.item-qty {
    text-align: center;
    color: #666;
}

.item-price {
    text-align: right;
    color: #000;
    font-weight: bold;
}

.totals-section {
    margin: 20px 0;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.total-row.total {
    font-size: 18px;
    font-weight: bold;
    color: #D4AF37;
    border-top: 2px solid #D4AF37;
    padding-top: 15px;
    margin-top: 15px;
}

.customer-info {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
    font-size: 14px;
}

.info-label {
    color: #666;
    font-size: 12px;
    margin-bottom: 3px;
}

.info-value {
    color: #000;
    margin-bottom: 10px;
}

.receipt-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px dashed #D4AF37;
    color: #666;
    font-size: 14px;
}

.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 25px;
}

.btn {
    padding: 12px;
    border-radius: 5px;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.btn-primary {
    background: #D4AF37;
    color: #000;
}

.btn-secondary {
    background: transparent;
    color: #D4AF37;
    border: 2px solid #D4AF37;
}

@media print {
    body {
        background: white;
        padding: 0;
    }
    
    .action-buttons {
        display: none;
    }
    
    .receipt-container {
        box-shadow: none;
        max-width: 100%;
        padding: 20px;
    }
}

.error-message {
    color: #e74c3c;
    text-align: center;
    padding: 20px;
    background: #ffe6e6;
    border-radius: 5px;
    margin: 20px 0;
}
</style>
</head>
<body>
<div class="receipt-container">
    <div class="receipt-header">
        <h1>Flower 'n GO</h1>
        <div class="subtitle">Fresh Hand-Picked Blooms</div>
        <div class="subtitle">📍 Polangui, Albay</div>
        <div class="subtitle">📞 0966-395-6793</div>
    </div>
    
    <div class="order-info">
        <div class="order-number">ORDER #<?=str_pad($order_id,6,'0',STR_PAD_LEFT)?></div>
        <div class="order-date"><?=$order_date?> • <?=$order_time?></div>
    </div>
    
    <div class="receipt-divider"></div>
    
    <?php if (empty($order_items)): ?>
    <div class="error-message">
        No items found for this order. The order may be empty or there was an error.
    </div>
    <?php else: ?>
    <table class="items-table">
        <thead>
            <tr>
                <th class="item-name">ITEM</th>
                <th class="item-qty">QTY</th>
                <th class="item-price">PRICE</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($order_items as $item): ?>
            <tr>
                <td class="item-name"><?=htmlspecialchars($item['name'])?></td>
                <td class="item-qty"><?=$item['quantity']?></td>
                <td class="item-price">₱<?=number_format($item['price']*$item['quantity'],2)?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="receipt-divider"></div>
    
    <div class="totals-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>₱<?=number_format($subtotal,2)?></span>
        </div>
        <div class="total-row">
            <span>Delivery Fee:</span>
            <span>₱150.00</span>
        </div>
        <div class="total-row">
            <span>Service Fee:</span>
            <span>₱50.00</span>
        </div>
        <div class="total-row total">
            <span>TOTAL:</span>
            <span>₱<?=number_format($total,2)?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="customer-info">
        <div class="info-label">CUSTOMER</div>
        <div class="info-value"><?=htmlspecialchars($order['name'])?></div>
        
        <div class="info-label">PHONE</div>
        <div class="info-value"><?=htmlspecialchars($order['phone'])?></div>
        
        <div class="info-label">ADDRESS</div>
        <div class="info-value"><?=htmlspecialchars($order['address'])?></div>
        
        <div class="info-label">PAYMENT</div>
        <div class="info-value"><?=htmlspecialchars(strtoupper($order['payment']))?></div>
    </div>
    
    <div class="receipt-footer">
        Thank you for your order!<br>
        <?php if (isset($order['delivery_date'])): ?>
        Your flowers will be delivered on <?=htmlspecialchars($order['delivery_date'])?><br>
        <?php endif; ?>
        <?php if (isset($order['delivery_time'])): ?>
        between <?=htmlspecialchars($order['delivery_time'])?><br>
        <?php endif; ?>
        <br>
        <em>This is your official receipt</em>
    </div>
    
    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-primary">🖨️ PRINT RECEIPT</button>
        <a href="dashboard.php" class="btn btn-secondary">BACK TO SHOP</a>
    </div>
</div>

<script>
// Auto-set focus for printing
window.addEventListener('load', function() {
    // Add a slight delay before focusing for better UX
    setTimeout(() => {
        document.querySelector('.receipt-container').focus();
    }, 100);
});

// Add keyboard shortcut for printing (Ctrl+P or Cmd+P)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
});
</script>
</body>
</html>