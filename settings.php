<?php
/**
 * Settings Page - Manage user preferences
 */
if (!defined('DB_PATH')) die('Direct access not permitted');

// Get list of common timezones
$timezones = DateTimeZone::listIdentifiers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LibreScale - Personal weight tracking application">
    <meta name="theme-color" content="#5e35b1">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="icon-192.png">
    <title>Settings - LibreScale</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="<?php echo getThemeClasses($user); ?>">
    <div class="app-container">
        <header class="app-header">
            <h1>Settings</h1>
        </header>
        
        <main class="app-content">
            <form id="settingsForm" class="settings-form">
                <div class="settings-section">
                    <h2>Profile</h2>
                    
                    <div class="form-group">
                        <label for="display_name">Display Name</label>
                        <input type="text" id="display_name" name="display_name" 
                               value="<?php echo e($user['display_name']); ?>" required>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h2>Preferences</h2>
                    
                    <div class="form-group">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone">
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?php echo e($tz); ?>" 
                                    <?php echo $user['timezone'] === $tz ? 'selected' : ''; ?>>
                                    <?php echo e($tz); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text" id="detectedTimezone"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight_unit">Weight Unit</label>
                        <select id="weight_unit" name="weight_unit">
                            <option value="kg" <?php echo $user['weight_unit'] === 'kg' ? 'selected' : ''; ?>>
                                Kilograms (kg)
                            </option>
                            <option value="lbs" <?php echo $user['weight_unit'] === 'lbs' ? 'selected' : ''; ?>>
                                Pounds (lbs)
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h2>Appearance</h2>
                    
                    <div class="form-group">
                        <label for="theme_mode">Theme Mode</label>
                        <div class="theme-mode-toggle">
                            <button type="button" class="theme-mode-btn <?php echo $user['theme_mode'] === 'light' ? 'active' : ''; ?>" 
                                    data-mode="light" onclick="selectThemeMode('light')">
                                <span class="material-symbols-outlined">light_mode</span> Light
                            </button>
                            <button type="button" class="theme-mode-btn <?php echo $user['theme_mode'] === 'dark' ? 'active' : ''; ?>" 
                                    data-mode="dark" onclick="selectThemeMode('dark')">
                                <span class="material-symbols-outlined">dark_mode</span> Dark
                            </button>
                        </div>
                        <input type="hidden" id="theme_mode" name="theme_mode" value="<?php echo e($user['theme_mode']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="theme_color">Theme Color</label>
                        <div class="theme-color-grid">
                            <?php 
                            $colors = [
                                'purple' => ['name' => 'Purple', 'hex' => '#5e35b1'],
                                'blue' => ['name' => 'Blue', 'hex' => '#1976d2'],
                                'green' => ['name' => 'Green', 'hex' => '#388e3c'],
                                'red' => ['name' => 'Red', 'hex' => '#d32f2f'],
                                'orange' => ['name' => 'Orange', 'hex' => '#f57c00'],
                                'pink' => ['name' => 'Pink', 'hex' => '#c2185b']
                            ];
                            foreach ($colors as $key => $color): 
                            ?>
                                <button type="button" 
                                        class="theme-color-btn <?php echo $user['theme_color'] === $key ? 'active' : ''; ?>" 
                                        data-color="<?php echo $key; ?>"
                                        style="background: <?php echo $color['hex']; ?>;"
                                        onclick="selectThemeColor('<?php echo $key; ?>')"
                                        title="<?php echo $color['name']; ?>">
                                    <?php if ($user['theme_color'] === $key): ?>✓<?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="theme_color" name="theme_color" value="<?php echo e($user['theme_color']); ?>">
                    </div>
                </div>
                
                <div class="settings-section">
                    <h2>Goals</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="starting_weight">Starting Weight</label>
                            <input type="number" id="starting_weight" name="starting_weight" 
                                   step="0.1" value="<?php echo $settings['starting_weight']; ?>" required>
                            <div class="help-text"><?php echo e($user['weight_unit']); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="target_weight">Target Weight</label>
                            <input type="number" id="target_weight" name="target_weight" 
                                   step="0.1" value="<?php echo $settings['target_weight']; ?>" required>
                            <div class="help-text"><?php echo e($user['weight_unit']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h2>Data</h2>
                    
                    <div class="button-group">
                        <button type="button" class="btn-secondary" onclick="exportData()">
                            <span class="material-symbols-outlined">download</span> Export CSV
                        </button>
                        <a href="index.php?page=logout" class="btn-secondary">
                            <span class="material-symbols-outlined">logout</span> Logout
                        </a>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Settings</button>
                </div>
            </form>
            
            <div id="saveMessage" class="save-message" style="display: none;"></div>
        </main>
        
        <?php include 'nav.php'; ?>
    </div>
    
    <script src="app.js"></script>
    <script>
        // Detect browser timezone
        const detectedTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const tzHelp = document.getElementById('detectedTimezone');
        const tzSelect = document.getElementById('timezone');
        
        if (detectedTz && detectedTz !== tzSelect.value) {
            tzHelp.innerHTML = `💡 Your browser detected: <strong>${detectedTz}</strong> 
                <a href="#" onclick="setTimezone('${detectedTz}'); return false;">Use this</a>`;
        }
        
        function setTimezone(tz) {
            document.getElementById('timezone').value = tz;
            document.getElementById('detectedTimezone').innerHTML = '';
        }
        
        // Theme selection
        function selectThemeMode(mode) {
            document.getElementById('theme_mode').value = mode;
            document.querySelectorAll('.theme-mode-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.mode === mode);
            });
            
            // Live preview
            document.body.className = document.body.className
                .replace(/theme-(light|dark)/, 'theme-' + mode);
        }
        
        function selectThemeColor(color) {
            document.getElementById('theme_color').value = color;
            document.querySelectorAll('.theme-color-btn').forEach(btn => {
                const isActive = btn.dataset.color === color;
                btn.classList.toggle('active', isActive);
                btn.innerHTML = isActive ? '✓' : '';
            });
            
            // Live preview
            document.body.className = document.body.className
                .replace(/theme-color-\w+/, 'theme-color-' + color);
        }
        
        // Handle form submission
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('index.php?action=update_settings', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage('Settings saved successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.error || 'Failed to save settings', 'error');
                }
            })
            .catch(err => {
                showMessage('Failed to save settings', 'error');
            });
        });
        
        function showMessage(message, type) {
            const msgEl = document.getElementById('saveMessage');
            msgEl.textContent = message;
            msgEl.className = 'save-message ' + type;
            msgEl.style.display = 'block';
            
            setTimeout(() => {
                msgEl.style.display = 'none';
            }, 3000);
        }
        
        function exportData() {
            window.location.href = 'index.php?action=export_csv';
        }
    </script>
</body>
</html>
