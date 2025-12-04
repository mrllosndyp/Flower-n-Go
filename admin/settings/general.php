<?php
include '../inclusion/header.php';
include '../config.php';

// Fetch current settings
$settings_sql = "SELECT setting_key, value FROM settings";
$settings_result = $conn->query($settings_sql);
$current_settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $current_settings[$row['setting_key']] = $row['value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = str_replace('setting_', '', $key);
            $setting_value = trim($value);
            
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
    
    // Handle logo upload
    if (!empty($_FILES['site_logo']['name'])) {
        $targetDir = "../../uploads/settings/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $original = basename($_FILES['site_logo']['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','svg','webp'];
        
        if (in_array($ext, $allowed)) {
            $logo_name = 'logo.' . $ext;
            $targetFile = $targetDir . $logo_name;
            
            // Delete old logo if exists
            $old_logos = glob($targetDir . 'logo.*');
            foreach ($old_logos as $old_logo) {
                unlink($old_logo);
            }
            
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $targetFile)) {
                $update_stmt = $conn->prepare("UPDATE settings SET value = ? WHERE setting_key = 'site_logo'");
                $update_stmt->bind_param("s", $logo_name);
                $update_stmt->execute();
            }
        }
    }
    
    $success = "Settings updated successfully!";
    logActivity('update_settings', "Updated general settings");
    
    // Refresh settings
    $settings_result = $conn->query($settings_sql);
    $current_settings = [];
    while ($row = $settings_result->fetch_assoc()) {
        $current_settings[$row['setting_key']] = $row['value'];
    }
}
?>

<div class="page-header">
    <h1 class="page-title">General Settings</h1>
    <div class="page-subtitle">
        <i class="bi bi-gear me-1"></i> Configure your flower shop's basic settings
    </div>
</div>

<!-- Success Message -->
<?php if (isset($success)): ?>
<div class="alert alert-success alert-floral d-flex align-items-center" role="alert">
    <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem;"></i>
    <div><?php echo $success; ?></div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="settingsForm">
    <div class="row">
        <!-- Store Information -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-shop me-2 flower-icon"></i> Store Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Shop Name</label>
                        <input type="text" class="form-control form-control-floral" 
                               name="setting_shop_name" 
                               value="<?php echo htmlspecialchars($current_settings['shop_name'] ?? 'Flower N Go'); ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tagline</label>
                        <input type="text" class="form-control form-control-floral" 
                               name="setting_shop_tagline" 
                               value="<?php echo htmlspecialchars($current_settings['shop_tagline'] ?? 'Beautiful Flowers for Every Occasion'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" class="form-control form-control-floral" 
                               name="setting_contact_email" 
                               value="<?php echo htmlspecialchars($current_settings['contact_email'] ?? 'contact@flowerngo.com'); ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control form-control-floral" 
                               name="setting_contact_phone" 
                               value="<?php echo htmlspecialchars($current_settings['contact_phone'] ?? '+63 123 456 7890'); ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control form-control-floral" 
                                  name="setting_shop_address" 
                                  rows="3"><?php echo htmlspecialchars($current_settings['shop_address'] ?? '123 Flower Street, Manila, Philippines'); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Business Hours -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-clock me-2 leaf-icon"></i> Business Hours</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opening Time</label>
                            <input type="time" class="form-control form-control-floral" 
                                   name="setting_open_time" 
                                   value="<?php echo $current_settings['open_time'] ?? '09:00'; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Closing Time</label>
                            <input type="time" class="form-control form-control-floral" 
                                   name="setting_close_time" 
                                   value="<?php echo $current_settings['close_time'] ?? '21:00'; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Days Open</label>
                        <div class="row">
                            <?php 
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $open_days = isset($current_settings['open_days']) ? explode(',', $current_settings['open_days']) : $days;
                            ?>
                            <?php foreach ($days as $day): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="open_days[]" 
                                           value="<?php echo $day; ?>"
                                           id="day_<?php echo strtolower($day); ?>"
                                           <?php echo in_array($day, $open_days) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="day_<?php echo strtolower($day); ?>">
                                        <?php echo $day; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Holiday Message</label>
                        <textarea class="form-control form-control-floral" 
                                  name="setting_holiday_message" 
                                  rows="2"><?php echo htmlspecialchars($current_settings['holiday_message'] ?? 'We are closed on holidays. Orders will be processed on the next business day.'); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Branding & Appearance -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-palette me-2 flower-icon"></i> Branding & Appearance</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php 
                        $logo_path = "../../uploads/settings/";
                        $logo_files = glob($logo_path . 'logo.*');
                        $current_logo = !empty($logo_files) ? basename($logo_files[0]) : ($current_settings['site_logo'] ?? '');
                        ?>
                        
                        <?php if ($current_logo && file_exists($logo_path . $current_logo)): ?>
                        <div class="mb-3">
                            <img src="<?php echo $logo_path . $current_logo; ?>" 
                                 alt="Current Logo" 
                                 class="img-fluid rounded" 
                                 style="max-height: 100px;">
                            <div class="small text-muted mt-2">Current Logo</div>
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-center bg-light rounded" 
                                 style="width: 150px; height: 100px; margin: 0 auto;">
                                <i class="bi bi-flower2 text-muted" style="font-size: 2rem;"></i>
                            </div>
                            <div class="small text-muted mt-2">No logo uploaded</div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload New Logo</label>
                            <input type="file" class="form-control form-control-floral" 
                                   name="site_logo" 
                                   accept="image/*">
                            <div class="form-text small">
                                Recommended: 300x100px PNG with transparent background
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Primary Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" 
                                   name="setting_primary_color" 
                                   value="<?php echo $current_settings['primary_color'] ?? '#ff6b8b'; ?>"
                                   title="Choose primary color">
                            <input type="text" class="form-control form-control-floral" 
                                   value="<?php echo $current_settings['primary_color'] ?? '#ff6b8b'; ?>"
                                   readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Secondary Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" 
                                   name="setting_secondary_color" 
                                   value="<?php echo $current_settings['secondary_color'] ?? '#2e8b57'; ?>"
                                   title="Choose secondary color">
                            <input type="text" class="form-control form-control-floral" 
                                   value="<?php echo $current_settings['secondary_color'] ?? '#2e8b57'; ?>"
                                   readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Theme</label>
                        <select class="form-select form-control-floral" name="setting_theme">
                            <option value="light" <?php echo ($current_settings['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light Theme</option>
                            <option value="dark" <?php echo ($current_settings['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Dark Theme</option>
                            <option value="auto" <?php echo ($current_settings['theme'] ?? 'light') === 'auto' ? 'selected' : ''; ?>>Auto (System Preference)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Font Family</label>
                        <select class="form-select form-control-floral" name="setting_font_family">
                            <option value="Poppins" <?php echo ($current_settings['font_family'] ?? 'Poppins') === 'Poppins' ? 'selected' : ''; ?>>Poppins (Modern)</option>
                            <option value="Roboto" <?php echo ($current_settings['font_family'] ?? 'Poppins') === 'Roboto' ? 'selected' : ''; ?>>Roboto (Clean)</option>
                            <option value="Open Sans" <?php echo ($current_settings['font_family'] ?? 'Poppins') === 'Open Sans' ? 'selected' : ''; ?>>Open Sans (Readable)</option>
                            <option value="Playfair Display" <?php echo ($current_settings['font_family'] ?? 'Poppins') === 'Playfair Display' ? 'selected' : ''; ?>>Playfair Display (Elegant)</option>
                            <option value="Dancing Script" <?php echo ($current_settings['font_family'] ?? 'Poppins') === 'Dancing Script' ? 'selected' : ''; ?>>Dancing Script (Floral)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Social Media -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-globe me-2 leaf-icon"></i> Social Media & Links</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Facebook URL</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-facebook text-primary"></i></span>
                            <input type="url" class="form-control form-control-floral" 
                                   name="setting_facebook_url" 
                                   value="<?php echo htmlspecialchars($current_settings['facebook_url'] ?? 'https://facebook.com/flowerngo'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Instagram URL</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-instagram text-danger"></i></span>
                            <input type="url" class="form-control form-control-floral" 
                                   name="setting_instagram_url" 
                                   value="<?php echo htmlspecialchars($current_settings['instagram_url'] ?? 'https://instagram.com/flowerngo'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Twitter URL</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-twitter text-info"></i></span>
                            <input type="url" class="form-control form-control-floral" 
                                   name="setting_twitter_url" 
                                   value="<?php echo htmlspecialchars($current_settings['twitter_url'] ?? 'https://twitter.com/flowerngo'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Website URL</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                            <input type="url" class="form-control form-control-floral" 
                                   name="setting_website_url" 
                                   value="<?php echo htmlspecialchars($current_settings['website_url'] ?? 'https://flowerngo.com'); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Google Maps Embed URL</label>
                        <textarea class="form-control form-control-floral" 
                                  name="setting_google_maps_url" 
                                  rows="2"
                                  placeholder="https://www.google.com/maps/embed?pb=..."><?php echo htmlspecialchars($current_settings['google_maps_url'] ?? ''); ?></textarea>
                        <div class="form-text small">
                            Paste the embed URL from Google Maps
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
                <i class="bi bi-save me-2"></i> Save Settings
            </button>
            <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetToDefaults()">
                <i class="bi bi-arrow-clockwise me-2"></i> Reset to Defaults
            </button>
            <button type="button" class="btn btn-outline-primary ms-2" onclick="previewChanges()">
                <i class="bi bi-eye me-2"></i> Preview
            </button>
        </div>
    </div>
</form>

<!-- Live Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Settings Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <?php if ($current_logo && file_exists($logo_path . $current_logo)): ?>
                    <img src="<?php echo $logo_path . $current_logo; ?>" 
                         alt="Logo Preview" 
                         class="img-fluid rounded" 
                         style="max-height: 60px;">
                    <?php else: ?>
                    <div class="d-inline-block bg-light rounded p-3">
                        <i class="bi bi-flower2" style="font-size: 2rem;"></i>
                    </div>
                    <?php endif; ?>
                    
                    <h4 class="mt-3" id="previewShopName"><?php echo htmlspecialchars($current_settings['shop_name'] ?? 'Flower N Go'); ?></h4>
                    <p class="text-muted" id="previewTagline"><?php echo htmlspecialchars($current_settings['shop_tagline'] ?? 'Beautiful Flowers for Every Occasion'); ?></p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Contact Information</h6>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-envelope me-2"></i> <span id="previewEmail"><?php echo htmlspecialchars($current_settings['contact_email'] ?? 'contact@flowerngo.com'); ?></span></li>
                            <li><i class="bi bi-telephone me-2"></i> <span id="previewPhone"><?php echo htmlspecialchars($current_settings['contact_phone'] ?? '+63 123 456 7890'); ?></span></li>
                            <li><i class="bi bi-geo-alt me-2"></i> <span id="previewAddress"><?php echo htmlspecialchars($current_settings['shop_address'] ?? '123 Flower Street, Manila, Philippines'); ?></span></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Business Hours</h6>
                        <div class="small">
                            <div><i class="bi bi-clock me-2"></i> 
                                <span id="previewOpenTime"><?php echo $current_settings['open_time'] ?? '09:00'; ?></span> - 
                                <span id="previewCloseTime"><?php echo $current_settings['close_time'] ?? '21:00'; ?></span>
                            </div>
                            <div class="mt-2" id="previewOpenDays">
                                <?php echo isset($current_settings['open_days']) ? str_replace(',', ', ', $current_settings['open_days']) : 'Monday - Sunday'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>Color Preview</h6>
                    <div class="d-flex gap-2">
                        <div class="color-preview" style="background-color: <?php echo $current_settings['primary_color'] ?? '#ff6b8b'; ?>; width: 50px; height: 30px; border-radius: 5px;"></div>
                        <div class="color-preview" style="background-color: <?php echo $current_settings['secondary_color'] ?? '#2e8b57'; ?>; width: 50px; height: 30px; border-radius: 5px;"></div>
                        <div class="small ms-2">
                            Primary & Secondary Colors
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-floral" onclick="saveSettings()">
                    <i class="bi bi-check-circle me-2"></i> Apply Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Update color text inputs when color picker changes
document.querySelectorAll('input[type="color"]').forEach(picker => {
    picker.addEventListener('input', function() {
        const textInput = this.parentElement.querySelector('input[type="text"]');
        textInput.value = this.value;
    });
});

// Preview changes
function previewChanges() {
    // Update preview modal with current form values
    document.getElementById('previewShopName').textContent = 
        document.querySelector('input[name="setting_shop_name"]').value;
    
    document.getElementById('previewTagline').textContent = 
        document.querySelector('input[name="setting_shop_tagline"]').value;
    
    document.getElementById('previewEmail').textContent = 
        document.querySelector('input[name="setting_contact_email"]').value;
    
    document.getElementById('previewPhone').textContent = 
        document.querySelector('input[name="setting_contact_phone"]').value;
    
    document.getElementById('previewAddress').textContent = 
        document.querySelector('textarea[name="setting_shop_address"]').value;
    
    document.getElementById('previewOpenTime').textContent = 
        document.querySelector('input[name="setting_open_time"]').value;
    
    document.getElementById('previewCloseTime').textContent = 
        document.querySelector('input[name="setting_close_time"]').value;
    
    // Update open days
    const checkedDays = Array.from(document.querySelectorAll('input[name="open_days[]"]:checked'))
        .map(cb => cb.value);
    document.getElementById('previewOpenDays').textContent = 
        checkedDays.length === 7 ? 'Monday - Sunday' : checkedDays.join(', ');
    
    // Update color previews
    document.querySelectorAll('.color-preview')[0].style.backgroundColor = 
        document.querySelector('input[name="setting_primary_color"]').value;
    document.querySelectorAll('.color-preview')[1].style.backgroundColor = 
        document.querySelector('input[name="setting_secondary_color"]').value;
    
    // Show modal
    $('#previewModal').modal('show');
}

// Reset to defaults
function resetToDefaults() {
    if (confirm('Reset all settings to default values? This cannot be undone.')) {
        fetch('reset_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'reset_defaults' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Save settings from preview
function saveSettings() {
    document.getElementById('settingsForm').submit();
}

// Auto-save open days as comma-separated string
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    const openDaysCheckboxes = document.querySelectorAll('input[name="open_days[]"]:checked');
    const openDaysValues = Array.from(openDaysCheckboxes).map(cb => cb.value);
    
    // Create hidden input for open days
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'setting_open_days';
    hiddenInput.value = openDaysValues.join(',');
    
    this.appendChild(hiddenInput);
});

// Live preview of color changes
document.querySelectorAll('input[type="color"]').forEach(picker => {
    picker.addEventListener('change', function() {
        const color = this.value;
        const colorName = this.name.replace('setting_', '').replace('_color', '');
        
        // Update CSS variable for live preview
        document.documentElement.style.setProperty(`--${colorName}-preview`, color);
    });
});

// Initialize color previews
$(document).ready(function() {
    // Set initial preview colors
    document.documentElement.style.setProperty('--primary-preview', 
        document.querySelector('input[name="setting_primary_color"]').value);
    document.documentElement.style.setProperty('--secondary-preview', 
        document.querySelector('input[name="setting_secondary_color"]').value);
});
</script>

<style>
:root {
    --primary-preview: <?php echo $current_settings['primary_color'] ?? '#ff6b8b'; ?>;
    --secondary-preview: <?php echo $current_settings['secondary_color'] ?? '#2e8b57'; ?>;
}

.form-control-color {
    height: 38px;
    width: 60px;
    padding: 2px;
}

.color-preview {
    transition: background-color 0.3s;
}

.settings-section {
    border-left: 4px solid var(--primary-pink);
    padding-left: 15px;
    margin-bottom: 25px;
}

.settings-section h5 {
    color: var(--dark-green);
    margin-bottom: 15px;
}

/* Live preview styles */
.preview-box {
    border: 2px dashed var(--secondary-pink);
    border-radius: 10px;
    padding: 20px;
    background: white;
    margin-bottom: 20px;
}
</style>

<?php include '../inclusion/footer.php'; ?>