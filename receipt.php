<?php
// receipt.php
session_start();
include "db.php";

// Get order id (integer)
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    echo "Invalid order ID.";
    exit;
}

// Fetch order details (user, shipping, payment, status, created_at)
$order_sql = "SELECT id, user_id, address, phone, payment, status, created_at, total FROM orders WHERE id = $order_id LIMIT 1";
  $order_res = mysqli_query($conn, $order_sql);
  if (!$order_res || mysqli_num_rows($order_res) === 0) {
      echo "Order not found.";
      exit;
  }
$order = mysqli_fetch_assoc($order_res);
$user_name = '';
            if (!empty($order['user_id'])) {
                $u = mysqli_query($conn, "SELECT name, email FROM users WHERE id = " . (int)$order['user_id'] . " LIMIT 1");
                if ($u && mysqli_num_rows($u)) {
                    $ur = mysqli_fetch_assoc($u);
                    $user_name = $ur['name'] . ($ur['email'] ? " ({$ur['email']})" : '');
                }
            }
// Fetch order items (product name, price, quantity)
$items_sql = "
SELECT p.name, p.price, oi.quantity
FROM order_items oi
JOIN products p ON oi.product_id = p.id
WHERE oi.order_id = $order_id
";
$items_res = mysqli_query($conn, $items_sql);
if (!$items_res) {
    echo "Error loading order items.";
    exit;
}

// Compute totals safely
$total = 0.0;
$items = [];
while ($row = mysqli_fetch_assoc($items_res)) {
    $row['price'] = (float)$row['price'];
    $row['quantity'] = (int)$row['quantity'];
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $items[] = $row;
}

// If orders.total column exists and is set, prefer it; otherwise use computed total
if (!empty($order['total']) && (float)$order['total'] > 0) {
    $display_total = (float)$order['total'];
} else {
    $display_total = $total;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Receipt — Order #<?= htmlspecialchars($order['id']) ?></title>
<style>
    :root { --bg:#0d0a08; --card:#1c120d; --accent:#D4AF37; --muted:#bfbfbf; }
    body{font-family:Arial, Helvetica, sans-serif;background:var(--bg);color:#fff;margin:0;padding:24px;}
    .receipt{max-width:760px;margin:20px auto;background:var(--card);padding:24px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.6);}
    .brand{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
    .brand h1{margin:0;font-size:20px;color:var(--accent);}
    .meta{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px;}
    .meta .block{background:rgba(255,255,255,0.03);padding:10px;border-radius:8px;min-width:180px;}
    .meta .block h4{margin:0 0 6px 0;color:var(--muted);font-size:12px;}
    table{width:100%;border-collapse:collapse;margin-top:12px;}
    th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:left;}
    th{background:rgba(255,255,255,0.02);font-weight:600;color:var(--muted);}
    .right{text-align:right;}
    .total-row td{font-weight:700;color:var(--accent);font-size:18px}
    .buttons{display:flex;gap:12px;margin-top:18px;justify-content:flex-end;}
    .btn{background:var(--accent);color:#000;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;display:inline-block;}
    .btn.secondary{background:transparent;color:var(--accent);border:1px solid rgba(212,175,55,0.2);}
    @media print {
        .buttons { display:none; }
        body{background:#fff;color:#000}
        .receipt{box-shadow:none;background:#fff;color:#000}
    }
    .backBtn{
 position:fixed;
 top:20px;
 left:20px;
 background:#D4AF37;
 color:black;
 padding:8px 14px;
 border-radius:14px;
 cursor:pointer;
 font-weight:600;
 box-shadow:0 0 8px black;
 z-index:9999;
}
.backBtn:hover{
 background:white;
}

</style>
</head>
<body>
<!-- BACK BUTTON -->
<div class="backBtn" onclick="goBack()">← Back</div>

<div class="receipt">
    <div class="brand">
        <div>
            <h1>🌸 Flower 'n GO</h1>
            <div style="color:var(--muted);font-size:13px">Order Receipt</div>
        </div>
        <div style="text-align:right">
            <div style="color:var(--muted);font-size:13px">Order #</div>
            <div style="font-weight:700"><?= htmlspecialchars($order['id']) ?></div>
            <div style="color:var(--muted);font-size:12px;margin-top:6px"><?= date("M d, Y H:i", strtotime($order['created_at'])) ?></div>
        </div>
    </div>

    <div class="meta">
        <div class="block">
            <h4>Shipping Details</h4>
            <div><?= htmlspecialchars($user_name ?: 'Customer') ?></div>
            <div style="color:var(--muted);font-size:13px;margin-top:6px"><?= nl2br(htmlspecialchars($order['address'] ?? '—')) ?></div>
            <div style="color:var(--muted);font-size:13px;margin-top:6px">Phone: <?= htmlspecialchars($order['phone'] ?? '—') ?></div>
        </div>

        <div class="block">
            <h4>Payment</h4>
            <div><?= htmlspecialchars($order['payment'] ?? '—') ?></div>
            <div style="color:var(--muted);font-size:13px;margin-top:6px">Status: <?= htmlspecialchars($order['status'] ?? '—') ?></div>
        </div>

        <div class="block">
            <h4>Customer</h4>
            <div><?= htmlspecialchars($user_name ?: 'Guest') ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Price</th>
                <th class="right">Qty</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td class="right">₱<?= number_format($it['price'], 2) ?></td>
                <td class="right"><?= $it['quantity'] ?></td>
                <td class="right">₱<?= number_format($it['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="3" class="right">TOTAL</td>
                <td class="right">₱<?= number_format($display_total, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="buttons">
        <a class="btn secondary" href="orders.php">Back to Orders</a>
        <a class="btn" href="javascript:window.print()">Print Receipt</a>
    </div>
</div>

</body>
</html>
