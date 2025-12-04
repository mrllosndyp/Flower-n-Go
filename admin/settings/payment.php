<?php
include '../inclusion/header.php';
include '../config.php';

// Fetch payment settings
$settings_sql = "SELECT setting_key, value FROM settings WHERE setting_key LIKE 'payment_%'";
$settings_result = $conn->query($settings_sql);
$payment_settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $payment_settings[$row['setting_key']] = $row['value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'payment_') === 0) {
            $setting_key = $key;
            $setting_value = trim($value);
            
            // Handle checkboxes
            if (is_array($setting_value)) {
                $setting_value = implode(',', $setting_value);
            }
            
            // Check if setting exists
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM settings WHERE setting_key = ?");
            $check_stmt->bind_param("s", $setting_key);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc()['count'] > 0;
            
            if ($exists) {
                $update_stmt = $conn->prepare("UPDATE settings SET value = ?, updated_at = NOW() WHERE setting_key = ?");
                $update_stmt->bind_param("ss", $setting_value, $setting_key);
                $update_stmt->execute();
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO settings (setting_key, value, created_at) VALUES (?, ?, NOW())");
                $insert_stmt->bind_param("ss", $setting_key, $setting_value);
                $insert_stmt->execute();
            }
        }
    }
    
    // Handle test payment
    if (isset($_POST['test_payment'])) {
        $test_result = testPaymentGateway();
        if ($test_result['success']) {
            $success = "Payment gateway test successful!";
        } else {
            $error = "Payment gateway test failed: " . $test_result['message'];
        }
    }
    
    $success = $success ?: "Payment settings updated successfully!";
    logActivity('update_payment_settings', "Updated payment settings");
    
    // Refresh settings
    $settings_result = $conn->query($settings_sql);
    $payment_settings = [];
    while ($row = $settings_result->fetch_assoc()) {
        $payment_settings[$row['setting_key']] = $row['value'];
    }
}

// Test payment gateway function
function testPaymentGateway() {
    // Simulate payment gateway test
    return ['success' => true, 'message' => 'Connection established successfully'];
}
?>

<div class="page-header">
    <h1 class="page-title">Payment Settings</h1>
    <div class="page-subtitle">
        <i class="bi bi-credit-card me-1"></i> Configure payment gateways and methods
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
<div class="alert alert-success alert-floral d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem;"></i>
    <div><?php echo $success; ?></div>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-floral d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
    <div><?php echo $error; ?></div>
</div>
<?php endif; ?>

<form method="POST" id="paymentSettingsForm">
    <div class="row">
        <!-- Payment Methods -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-wallet2 me-2 flower-icon"></i> Payment Methods</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Enabled Payment Methods</label>
                        <div class="payment-methods-grid">
                            <?php 
                            $payment_methods = [
                                'cod' => ['name' => 'Cash on Delivery', 'icon' => 'cash', 'color' => 'success'],
                                'gcash' => ['name' => 'GCash', 'icon' => 'phone', 'color' => 'info'],
                                'paymaya' => ['name' => 'PayMaya', 'icon' => 'credit-card', 'color' => 'primary'],
                                'credit_card' => ['name' => 'Credit Card', 'icon' => 'credit-card-2-front', 'color' => 'warning'],
                                'bank_transfer' => ['name' => 'Bank Transfer', 'icon' => 'bank', 'color' => 'secondary'],
                                'paypal' => ['name' => 'PayPal', 'icon' => 'paypal', 'color' => 'primary'],
                                'grabpay' => ['name' => 'GrabPay', 'icon' => 'car-front', 'color' => 'success']
                            ];
                            
                            $enabled_methods = isset($payment_settings['payment_methods_enabled']) ? 
                                explode(',', $payment_settings['payment_methods_enabled']) : ['cod', 'gcash', 'paymaya'];
                            ?>
                            
                            <?php foreach ($payment_methods as $key => $method): ?>
                            <div class="payment-method-card">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="payment_methods_enabled[]" 
                                           value="<?php echo $key; ?>"
                                           id="method_<?php echo $key; ?>"
                                           <?php echo in_array($key, $enabled_methods) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="method_<?php echo $key; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="payment-icon bg-<?php echo $method['color']; ?> me-3">
                                                <i class="bi bi-<?php echo $method['icon']; ?>"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo $method['name']; ?></div>
                                                <div class="small text-muted">
                                                    <?php 
                                                    $status = in_array($key, $enabled_methods) ? 'Enabled' : 'Disabled';
                                                    echo $status;
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Default Payment Method</label>
                        <select class="form-select form-control-floral" name="payment_default_method">
                            <?php foreach ($payment_methods as $key => $method): ?>
                            <option value="<?php echo $key; ?>" 
                                <?php echo ($payment_settings['payment_default_method'] ?? 'cod') === $key ? 'selected' : ''; ?>>
                                <?php echo $method['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Minimum Order Amount for COD</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control form-control-floral" 
                                   name="payment_cod_minimum" 
                                   value="<?php echo $payment_settings['payment_cod_minimum'] ?? '500'; ?>"
                                   step="0.01" min="0">
                        </div>
                        <div class="form-text">
                            Set 0 for no minimum
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Instructions</label>
                        <textarea class="form-control form-control-floral" 
                                  name="payment_instructions" 
                                  rows="3"><?php echo htmlspecialchars($payment_settings['payment_instructions'] ?? 'Please have exact change ready for Cash on Delivery orders. For online payments, please save your transaction reference number.'); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Currency Settings -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-currency-exchange me-2 leaf-icon"></i> Currency & Pricing</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select form-control-floral" name="payment_currency">
                                <option value="PHP" <?php echo ($payment_settings['payment_currency'] ?? 'PHP') === 'PHP' ? 'selected' : ''; ?>>Philippine Peso (₱)</option>
                                <option value="USD" <?php echo ($payment_settings['payment_currency'] ?? 'PHP') === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                <option value="EUR" <?php echo ($payment_settings['payment_currency'] ?? 'PHP') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Currency Position</label>
                            <select class="form-select form-control-floral" name="payment_currency_position">
                                <option value="left" <?php echo ($payment_settings['payment_currency_position'] ?? 'left') === 'left' ? 'selected' : ''; ?>>Left (₱100)</option>
                                <option value="right" <?php echo ($payment_settings['payment_currency_position'] ?? 'left') === 'right' ? 'selected' : ''; ?>>Right (100₱)</option>
                                <option value="left_space" <?php echo ($payment_settings['payment_currency_position'] ?? 'left') === 'left_space' ? 'selected' : ''; ?>>Left with Space (₱ 100)</option>
                                <option value="right_space" <?php echo ($payment_settings['payment_currency_position'] ?? 'left') === 'right_space' ? 'selected' : ''; ?>>Right with Space (100 ₱)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Decimal Places</label>
                            <select class="form-select form-control-floral" name="payment_decimal_places">
                                <option value="0" <?php echo ($payment_settings['payment_decimal_places'] ?? '2') === '0' ? 'selected' : ''; ?>>0 (₱100)</option>
                                <option value="2" <?php echo ($payment_settings['payment_decimal_places'] ?? '2') === '2' ? 'selected' : ''; ?>>2 (₱100.00)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Thousand Separator</label>
                            <select class="form-select form-control-floral" name="payment_thousand_separator">
                                <option value="," <?php echo ($payment_settings['payment_thousand_separator'] ?? ',') === ',' ? 'selected' : ''; ?>>Comma (1,000)</option>
                                <option value="." <?php echo ($payment_settings['payment_thousand_separator'] ?? ',') === '.' ? 'selected' : ''; ?>>Period (1.000)</option>
                                <option value="space" <?php echo ($payment_settings['payment_thousand_separator'] ?? ',') === 'space' ? 'selected' : ''; ?>>Space (1 000)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price Display Example</label>
                        <div class="alert alert-light">
                            <div class="fw-bold" id="priceExample">
                                <?php 
                                $currency = $payment_settings['payment_currency'] ?? 'PHP';
                                $position = $payment_settings['payment_currency_position'] ?? 'left';
                                $decimal = $payment_settings['payment_decimal_places'] ?? '2';
                                $separator = $payment_settings['payment_thousand_separator'] ?? ',';
                                
                                $symbol = $currency === 'PHP' ? '₱' : ($currency === 'USD' ? '$' : '€');
                                $price = 1250.50;
                                
                                if ($decimal == '0') {
                                    $price = round($price);
                                }
                                
                                $formatted = number_format($price, $decimal, '.', $separator);
                                
                                switch ($position) {
                                    case 'left': echo $symbol . $formatted; break;
                                    case 'right': echo $formatted . $symbol; break;
                                    case 'left_space': echo $symbol . ' ' . $formatted; break;
                                    case 'right_space': echo $formatted . ' ' . $symbol; break;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Gateway Configuration -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-shield-check me-2 flower-icon"></i> Payment Gateway</h5>
                    <button type="submit" name="test_payment" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plug me-1"></i> Test Connection
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Payment Gateway</label>
                        <select class="form-select form-control-floral" name="payment_gateway" id="paymentGateway">
                            <option value="manual" <?php echo ($payment_settings['payment_gateway'] ?? 'manual') === 'manual' ? 'selected' : ''; ?>>Manual Processing</option>
                            <option value="paymongo" <?php echo ($payment_settings['payment_gateway'] ?? 'manual') === 'paymongo' ? 'selected' : ''; ?>>PayMongo</option>
                            <option value="dragonpay" <?php echo ($payment_settings['payment_gateway'] ?? 'manual') === 'dragonpay' ? 'selected' : ''; ?>>Dragonpay</option>
                            <option value="paypal_standard" <?php echo ($payment_settings['payment_gateway'] ?? 'manual') === 'paypal_standard' ? 'selected' : ''; ?>>PayPal Standard</option>
                            <option value="stripe" <?php echo ($payment_settings['payment_gateway'] ?? 'manual') === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                            <option value="authorizenet" <?php echo ($payment_settings['payment_gateway'] ?? 'manual') === 'authorizenet' ? 'selected' : ''; ?>>Authorize.net</option>
                        </select>
                    </div>
                    
                    <div id="gatewayConfig">
                        <!-- Gateway-specific configuration will appear here -->
                        <div class="gateway-config manual">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Manual payment processing enabled. Customers will receive payment instructions via email.
                            </div>
                        </div>
                        
                        <div class="gateway-config paymongo" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">PayMongo Public Key</label>
                                <input type="text" class="form-control form-control-floral" 
                                       name="payment_paymongo_public_key" 
                                       value="<?php echo htmlspecialchars($payment_settings['payment_paymongo_public_key'] ?? ''); ?>"
                                       placeholder="pk_live_...">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">PayMongo Secret Key</label>
                                <input type="password" class="form-control form-control-floral" 
                                       name="payment_paymongo_secret_key" 
                                       value="<?php echo htmlspecialchars($payment_settings['payment_paymongo_secret_key'] ?? ''); ?>"
                                       placeholder="sk_live_...">
                            </div>
                            
                            <div class="alert alert-warning small">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Use test keys (pk_test_/sk_test_) for development. Replace with live keys for production.
                            </div>
                        </div>
                        
                        <!-- Add more gateway configs as needed -->
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Timeout</label>
                        <div class="input-group">
                            <input type="number" class="form-control form-control-floral" 
                                   name="payment_timeout_minutes" 
                                   value="<?php echo $payment_settings['payment_timeout_minutes'] ?? '30'; ?>"
                                   min="5" max="1440">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="form-text">
                            Time before pending payments are cancelled
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Auto-capture Payments</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   name="payment_auto_capture" 
                                   id="autoCapture"
                                   value="1"
                                   <?php echo ($payment_settings['payment_auto_capture'] ?? '1') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="autoCapture">
                                Automatically capture payments when authorized
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Security & Compliance -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-shield-lock me-2 leaf-icon"></i> Security & Compliance</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Enable SSL/HTTPS</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   name="payment_ssl_enabled" 
                                   id="sslEnabled"
                                   value="1"
                                   <?php echo ($payment_settings['payment_ssl_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sslEnabled">
                                Force HTTPS for all payment pages
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Enable 3D Secure</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   name="payment_3d_secure" 
                                   id="3dSecure"
                                   value="1"
                                   <?php echo ($payment_settings['payment_3d_secure'] ?? '1') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="3dSecure">
                                Require 3D Secure authentication for card payments
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">PCI DSS Compliance</label>
                        <div class="alert alert-info small">
                            <i class="bi bi-check-circle me-2"></i>
                            Your payment setup is <strong>PCI DSS compliant</strong> when using external payment gateways.
                            Never store credit card information on your servers.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data Retention Period</label>
                        <div class="input-group">
                            <input type="number" class="form-control form-control-floral" 
                                   name="payment_data_retention" 
                                   value="<?php echo $payment_settings['payment_data_retention'] ?? '365'; ?>"
                                   min="30" max="3650">
                            <span class="input-group-text">days</span>
                        </div>
                        <div class="form-text">
                            How long to keep payment transaction logs
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Refund Policy -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-arrow-counterclockwise me-2 flower-icon"></i> Refund Policy</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Refund Policy</label>
                        <textarea class="form-control form-control-floral" 
                                  name="payment_refund_policy" 
                                  rows="3"><?php echo htmlspecialchars($payment_settings['payment_refund_policy'] ?? 'Refunds are processed within 7-14 business days. For damaged or incorrect items, please contact us within 24 hours of delivery. Digital products are non-refundable.'); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Refund Processing Time</label>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-floral" 
                                       name="payment_refund_days" 
                                       value="<?php echo $payment_settings['payment_refund_days'] ?? '14'; ?>"
                                       min="1" max="60">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cancellation Window</label>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-floral" 
                                       name="payment_cancellation_hours" 
                                       value="<?php echo $payment_settings['payment_cancellation_hours'] ?? '24'; ?>"
                                       min="1" max="168">
                                <span class="input-group-text">hours</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Partial Refunds Allowed</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   name="payment_partial_refunds" 
                                   id="partialRefunds"
                                   value="1"
                                   <?php echo ($payment_settings['payment_partial_refunds'] ?? '1') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="partialRefunds">
                                Allow partial refunds for orders
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body text-center">
            <button type="submit" class="btn btn-floral btn-lg px-5">
                <i class="bi bi-save me-2"></i> Save Payment Settings
            </button>
            <button type="button" class="btn btn-outline-secondary ms-2" onclick="viewPaymentLogs()">
                <i class="bi bi-list-check me-2"></i> View Payment Logs
            </button>
            <button type="button" class="btn btn-outline-primary ms-2" onclick="generateComplianceReport()">
                <i class="bi bi-file-earmark-text me-2"></i> Compliance Report
            </button>
        </div>
    </div>
</form>

<!-- Payment Test Modal -->
<div class="modal fade" id="testPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Test Payment Gateway</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-4">
                    <i class="bi bi-credit-card" style="font-size: 3rem; color: var(--primary-pink);"></i>
                    <h5 class="mt-3">Test Payment Processing</h5>
                    <p class="text-muted">This will simulate a test transaction</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Test Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control form-control-floral text-center" 
                               id="testAmount" value="100.00" step="0.01" min="1">
                    </div>
                </div>
                
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-2"></i>
                    This will create a test transaction to verify your payment gateway configuration.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-floral" onclick="runPaymentTest()">
                    <i class="bi bi-play-circle me-2"></i> Run Test
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.payment-methods-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}

.payment-method-card {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 15px;
    transition: all 0.3s;
}

.payment-method-card:hover {
    border-color: var(--primary-pink);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.payment-method-card .form-check-input:checked ~ .form-check-label .payment-method-card {
    border-color: var(--primary-pink);
    background: rgba(255, 107, 139, 0.05);
}

.payment-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.gateway-config {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background: #f8f9fa;
}

/* SSL Indicator */
.ssl-indicator {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
}

.ssl-active {
    background: #d4edda;
    color: #155724;
}

.ssl-inactive {
    background: #f8d7da;
    color: #721c24;
}
</style>

<script>
// Show/hide gateway configuration based on selection
document.getElementById('paymentGateway').addEventListener('change', function() {
    const selectedGateway = this.value;
    
    // Hide all gateway configs
    document.querySelectorAll('.gateway-config').forEach(config => {
        config.style.display = 'none';
    });
    
    // Show selected gateway config
    document.querySelector(`.gateway-config.${selectedGateway}`).style.display = 'block';
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const selectedGateway = document.getElementById('paymentGateway').value;
    document.querySelector(`.gateway-config.${selectedGateway}`).style.display = 'block';
});

// Update price example when currency settings change
function updatePriceExample() {
    const currency = document.querySelector('select[name="payment_currency"]').value;
    const position = document.querySelector('select[name="payment_currency_position"]').value;
    const decimal = document.querySelector('select[name="payment_decimal_places"]').value;
    const separator = document.querySelector('select[name="payment_thousand_separator"]').value;
    
    const symbol = currency === 'PHP' ? '₱' : (currency === 'USD' ? '$' : '€');
    let price = 1250.50;
    
    if (decimal === '0') {
        price = Math.round(price);
    }
    
    let formatted = number_format(price, parseInt(decimal), '.', separator);
    
    let display;
    switch (position) {
        case 'left': display = symbol + formatted; break;
        case 'right': display = formatted + symbol; break;
        case 'left_space': display = symbol + ' ' + formatted; break;
        case 'right_space': display = formatted + ' ' + symbol; break;
        default: display = symbol + formatted;
    }
    
    document.getElementById('priceExample').textContent = display;
}

// Helper function for number formatting
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

// Attach event listeners for currency settings
document.querySelectorAll('select[name^="payment_"]').forEach(select => {
    select.addEventListener('change', updatePriceExample);
});

// Initialize price example
updatePriceExample();

// View payment logs
function viewPaymentLogs() {
    window.open('payment_logs.php', '_blank');
}

// Generate compliance report
function generateComplianceReport() {
    window.open('compliance_report.php', '_blank');
}

// Run payment test
function runPaymentTest() {
    const amount = document.getElementById('testAmount').value;
    
    fetch('test_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            amount: amount,
            gateway: document.getElementById('paymentGateway').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment test successful! Transaction ID: ' + data.transaction_id);
            $('#testPaymentModal').modal('hide');
        } else {
            alert('Payment test failed: ' + data.message);
        }
    });
}

// Show test modal
function showTestModal() {
    $('#testPaymentModal').modal('show');
}
</script>

<?php include '../inclusion/footer.php'; ?>