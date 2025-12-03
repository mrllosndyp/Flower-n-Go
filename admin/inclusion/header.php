<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flower N Go Admin</title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <style>
            <style>
        :root {
            --primary-pink: #ff6b8b;
            --secondary-pink: #ffb6c1;
            --leaf-green: #2e8b57;
            --light-green: #90ee90;
            --cream: #fffaf0;
            --dark-green: #1a472a;
            --gold: #ffd700;
            --lavender: #e6e6fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f9f3f3 0%, #fff5f5 100%);
            min-height: 100vh;
        }
        
        /* ... (existing styles from earlier) ... */
        
        /* ========== PRODUCTS MODULE STYLES ========== */
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .step.active .step-icon {
            background: var(--primary-pink);
            color: white;
        }
        
        .step-label {
            font-weight: 500;
        }
        
        .image-upload-area {
            border: 3px dashed var(--secondary-pink);
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .image-upload-area:hover {
            border-color: var(--primary-pink);
            background: #fffaf5;
        }
        
        /* Stock level colors */
        .stock-low { color: #dc3545 !important; }
        .stock-medium { color: #ffc107 !important; }
        .stock-high { color: #28a745 !important; }
        
        /* Quick actions */
        .quick-action-btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Category cards */
        .category-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* ========== ORDERS MODULE STYLES ========== */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-pink), var(--leaf-green));
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary-pink);
            box-shadow: 0 0 0 3px white;
        }
        
        .timeline-content {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        /* Status badges - ORDER SPECIFIC */
        .badge-pending { background: linear-gradient(135deg, #fff3cd, #ffeaa7); color: #856404; }
        .badge-processing { background: linear-gradient(135deg, #cce5ff, #a8d4ff); color: #004085; }
        .badge-shipped { background: linear-gradient(135deg, #d1ecf1, #b3e0e8); color: #0c5460; }
        .badge-delivered { background: linear-gradient(135deg, #d4edda, #b8e0c1); color: #155724; }
        .badge-cancelled { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; }
        .badge-info { background: linear-gradient(135deg, #d1ecf1, #b3e0e8); color: #0c5460; }
        
        /* Order status rows */
        .order-row {
            transition: all 0.3s;
        }
        
        .order-row:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .order-row[data-status="pending"] { border-left: 4px solid #ffc107; }
        .order-row[data-status="processing"] { border-left: 4px solid #007bff; }
        .order-row[data-status="shipped"] { border-left: 4px solid #17a2b8; }
        .order-row[data-status="delivered"] { border-left: 4px solid #28a745; }
        .order-row[data-status="cancelled"] { border-left: 4px solid #dc3545; }
        
        /* Payment status */
        .payment-paid { color: #28a745; font-weight: bold; }
        .payment-pending { color: #ffc107; font-weight: bold; }
        .payment-failed { color: #dc3545; font-weight: bold; }
        
        /* Delivery info cards */
        .delivery-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 4px solid var(--leaf-green);
        }
        
        /* Quick status update */
        .status-update-btn {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .status-update-btn:hover {
            transform: scale(1.05);
        }
        
        /* ========== CUSTOMERS MODULE STYLES (Placeholder) ========== */
        .customer-avatar-lg {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-pink) 0%, #ff8e8e 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .loyalty-badge {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #856404;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        /* ========== INVENTORY MODULE STYLES (Placeholder) ========== */
        .stock-alert {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        
        /* ========== DELIVERY MODULE STYLES (Placeholder) ========== */
        .delivery-map {
            height: 300px;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        /* ========== REPORTS MODULE STYLES (Placeholder) ========== */
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        /* ========== RESPONSIVE ADJUSTMENTS ========== */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .step-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .timeline {
                padding-left: 20px;
            }
            
            .timeline-marker {
                left: -20px;
                width: 15px;
                height: 15px;
            }
        }
        
        @media print {
            .admin-nav, .admin-header, .page-header .btn, .no-print {
                display: none !important;
            }
            
            body {
                background: white !important;
                padding: 0 !important;
            }
            
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>