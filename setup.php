<?php
/**
 * LibreScale Setup Script
 * Run this once to initialize the database and create your user account
 */

// Use fixed database name (protected by .htaccess)
$db_name = 'librescale.db';
$db_path = __DIR__ . '/' . $db_name;

// Check if already setup
if (file_exists($db_path)) {
    die('Setup has already been completed. Delete librescale.db to run setup again.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $display_name = trim($_POST['display_name'] ?? '');
    $timezone = $_POST['timezone'] ?? 'UTC';
    $weight_unit = $_POST['weight_unit'] ?? 'kg';
    $starting_weight = floatval($_POST['starting_weight'] ?? 0);
    $target_weight = floatval($_POST['target_weight'] ?? 0);
    
    $errors = [];
    
    // Validation
    if (empty($username)) $errors[] = 'Username is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    if (empty($display_name)) $errors[] = 'Display name is required';
    if ($starting_weight <= 0) $errors[] = 'Starting weight must be greater than 0';
    if ($target_weight <= 0) $errors[] = 'Target weight must be greater than 0';
    
    if (empty($errors)) {
        try {
            // Create sessions directory for persistent sessions
            $session_path = __DIR__ . '/sessions';
            if (!is_dir($session_path)) {
                if (!mkdir($session_path, 0700, true)) {
                    $errors[] = 'Failed to create sessions directory';
                }
            }
            
            // Create .htaccess in sessions directory to block web access
            $htaccess_path = $session_path . '/.htaccess';
            if (!file_exists($htaccess_path)) {
                file_put_contents($htaccess_path, "# Deny all web access to session files\nRequire all denied\n");
            }
            
            // Create database
            $db = new PDO('sqlite:' . $db_path);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables
            $db->exec('
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    display_name TEXT NOT NULL,
                    timezone TEXT DEFAULT "UTC",
                    weight_unit TEXT DEFAULT "kg",
                    theme_mode TEXT DEFAULT "light",
                    theme_color TEXT DEFAULT "purple",
                    created_at INTEGER NOT NULL
                )
            ');
            
            $db->exec('
                CREATE TABLE IF NOT EXISTS weights (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    weight REAL NOT NULL,
                    timestamp INTEGER NOT NULL,
                    notes TEXT,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ');
            
            $db->exec('
                CREATE TABLE IF NOT EXISTS settings (
                    user_id INTEGER PRIMARY KEY,
                    starting_weight REAL NOT NULL,
                    target_weight REAL NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ');
            
            // Create indexes
            $db->exec('CREATE INDEX idx_weights_user_timestamp ON weights(user_id, timestamp)');
            $db->exec('CREATE INDEX idx_weights_timestamp ON weights(timestamp)');
            
            // Insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('
                INSERT INTO users (username, password, display_name, timezone, weight_unit, theme_mode, theme_color, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$username, $password_hash, $display_name, $timezone, $weight_unit, 'light', 'purple', time()]);
            $user_id = $db->lastInsertId();
            
            // Insert settings
            $stmt = $db->prepare('
                INSERT INTO settings (user_id, starting_weight, target_weight) 
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$user_id, $starting_weight, $target_weight]);
            
            // Insert starting weight entry
            $stmt = $db->prepare('
                INSERT INTO weights (user_id, weight, timestamp) 
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$user_id, $starting_weight, time()]);
            
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get list of common timezones
$timezones = [
    'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
    'America/Toronto', 'America/Vancouver', 'Europe/London', 'Europe/Paris',
    'Europe/Berlin', 'Europe/Rome', 'Asia/Tokyo', 'Asia/Shanghai', 'Asia/Dubai',
    'Australia/Sydney', 'Pacific/Auckland', 'UTC'
];
sort($timezones);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreScale Setup</title>
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 100 700;
            src: url('MaterialSymbolsOutlined.woff2') format('woff2');
        }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            display: inline-block;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #5e35b1;
            font-size: 32px;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 32px;
            font-size: 14px;
        }
        .success-message {
            background: #4caf50;
            color: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .error-message {
            background: #f44336;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #5e35b1;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #5e35b1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #4527a0;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .success-actions {
            display: flex;
            gap: 12px;
        }
        .success-actions a {
            flex: 1;
            padding: 12px;
            background: white;
            color: #4caf50;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: 2px solid white;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <?php if (isset($success) && $success): ?>
            <h1><span class="material-symbols-outlined" style="font-size: 32px; vertical-align: middle;">celebration</span> Setup Complete!</h1>
            <div class="success-message">
                <p style="margin-bottom: 16px;">LibreScale has been successfully configured!</p>
                <div class="success-actions">
                    <a href="index.php">Go to Login</a>
                </div>
            </div>
            <p class="help-text">
                Your database has been created as: <strong>librescale.db</strong><br>
                Keep this file secure and backed up!
            </p>
        <?php else: ?>
            <h1><span class="material-symbols-outlined" style="font-size: 32px; vertical-align: middle;">fitness_center</span> LibreScale Setup</h1>
            <p class="subtitle">Configure your weight tracking application</p>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <div class="help-text">Minimum 6 characters</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" required 
                           value="<?php echo htmlspecialchars($_POST['display_name'] ?? ''); ?>">
                    <div class="help-text">This is how you'll be greeted in the app</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone">
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?php echo $tz; ?>" 
                                    <?php echo (($_POST['timezone'] ?? 'UTC') === $tz) ? 'selected' : ''; ?>>
                                    <?php echo $tz; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight_unit">Weight Unit</label>
                        <select id="weight_unit" name="weight_unit">
                            <option value="kg" <?php echo (($_POST['weight_unit'] ?? 'kg') === 'kg') ? 'selected' : ''; ?>>Kilograms (kg)</option>
                            <option value="lbs" <?php echo (($_POST['weight_unit'] ?? 'kg') === 'lbs') ? 'selected' : ''; ?>>Pounds (lbs)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="starting_weight">Starting Weight</label>
                        <input type="number" id="starting_weight" name="starting_weight" 
                               step="0.1" required 
                               value="<?php echo htmlspecialchars($_POST['starting_weight'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="target_weight">Target Weight</label>
                        <input type="number" id="target_weight" name="target_weight" 
                               step="0.1" required 
                               value="<?php echo htmlspecialchars($_POST['target_weight'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit">Complete Setup</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
