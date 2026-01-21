<?php
/**
 * LibreScale - Main Application Entry Point
 */
require_once __DIR__ . '/config.php';

// Prevent caching of dynamic content
setNoCacheHeaders();

// Handle AJAX requests
if (isset($_GET['action'])) {
    handleAjaxRequest();
    exit;
}

// Handle login/logout
$page = $_GET['page'] ?? 'home';

if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

if ($page === 'login') {
    handleLogin();
    exit;
}

// Require login for all other pages
requireLogin();

$user = getCurrentUser();
$settings = getUserSettings($user['id']);

// Route to appropriate page
switch ($page) {
    case 'entries':
        include 'entries.php';
        break;
    case 'settings':
        include 'settings.php';
        break;
    case 'home':
    default:
        include 'home.php';
        break;
}

/**
 * Handle login page and authentication
 */
function handleLogin() {
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!empty($username) && !empty($password)) {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['last_activity'] = time();
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Please enter both username and password';
        }
    }
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
        <title>Login - LibreScale</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body class="login-page">
        <div class="login-container">
            <div class="login-card">
                <h1><span class="material-symbols-outlined" style="font-size: 32px; vertical-align: middle;">balance</span> LibreScale</h1>
                <p class="subtitle">Your personal weight tracker</p>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo e($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Sign In</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Handle AJAX requests
 */
function handleAjaxRequest() {
    requireLogin();
    
    $action = $_GET['action'] ?? '';
    $user = getCurrentUser();
    $settings = getUserSettings($user['id']);
    
    switch ($action) {
        case 'add_weight':
            addWeight($user, $settings);
            break;
        case 'edit_weight':
            editWeight($user);
            break;
        case 'delete_weight':
            deleteWeight($user);
            break;
        case 'get_entry':
            getEntry($user);
            break;
        case 'get_chart_data':
            getChartData($user, $settings);
            break;
        case 'update_settings':
            updateSettings($user);
            break;
        case 'export_csv':
            exportCSV($user, $settings);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Add weight entry
 */
function addWeight($user, $settings) {
    $weight = floatval($_POST['weight'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $time = $_POST['time'] ?? date('H:i');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($weight <= 0) {
        jsonResponse(['error' => 'Invalid weight'], 400);
    }
    
    // Create timestamp from date and time in user's timezone
    $datetime_string = $date . ' ' . $time;
    $dt = new DateTime($datetime_string, new DateTimeZone($user['timezone']));
    $timestamp = $dt->getTimestamp();
    
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO weights (user_id, weight, timestamp, notes) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user['id'], $weight, $timestamp, $notes]);
    
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

/**
 * Edit weight entry
 */
function editWeight($user) {
    $id = intval($_POST['id'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if ($weight <= 0 || $id <= 0) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    // Create timestamp from date and time in user's timezone
    $datetime_string = $date . ' ' . $time;
    $dt = new DateTime($datetime_string, new DateTimeZone($user['timezone']));
    $timestamp = $dt->getTimestamp();
    
    $db = getDB();
    $stmt = $db->prepare('UPDATE weights SET weight = ?, timestamp = ?, notes = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$weight, $timestamp, $notes, $id, $user['id']]);
    
    jsonResponse(['success' => true]);
}

/**
 * Get single entry
 */
function getEntry($user) {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid ID'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM weights WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    $entry = $stmt->fetch();
    
    if (!$entry) {
        jsonResponse(['error' => 'Entry not found'], 404);
    }
    
    jsonResponse($entry);
}

/**
 * Delete weight entry
 */
function deleteWeight($user) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid ID'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM weights WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    
    jsonResponse(['success' => true]);
}

/**
 * Get chart data for different time periods
 */
function getChartData($user, $settings) {
    $period = $_GET['period'] ?? 'week';
    $timezone = $user['timezone'];
    
    // Calculate time range
    $now = new DateTime('now', new DateTimeZone($timezone));
    $end_timestamp = $now->getTimestamp();
    
    switch ($period) {
        case 'week':
            $start = clone $now;
            $start->modify('-7 days');
            break;
        case 'month':
            $start = clone $now;
            $start->modify('-30 days');
            break;
        case 'year':
            $start = clone $now;
            $start->modify('-365 days');
            break;
        case 'all':
            $start = null;
            break;
        default:
            $start = clone $now;
            $start->modify('-7 days');
    }
    
    $start_timestamp = $start ? $start->getTimestamp() : null;
    
    // Get daily averages
    $data = getDailyAverages($user['id'], $timezone, $start_timestamp, $end_timestamp);
    
    jsonResponse([
        'data' => $data,
        'target_weight' => floatval($settings['target_weight']),
        'unit' => $user['weight_unit']
    ]);
}

/**
 * Update user settings
 */
function updateSettings($user) {
    $display_name = trim($_POST['display_name'] ?? '');
    $timezone = $_POST['timezone'] ?? 'UTC';
    $weight_unit = $_POST['weight_unit'] ?? 'kg';
    $theme_mode = $_POST['theme_mode'] ?? 'light';
    $theme_color = $_POST['theme_color'] ?? 'purple';
    $starting_weight = floatval($_POST['starting_weight'] ?? 0);
    $target_weight = floatval($_POST['target_weight'] ?? 0);
    
    if (empty($display_name) || $starting_weight <= 0 || $target_weight <= 0) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    // Validate theme values
    $valid_modes = ['light', 'dark'];
    $valid_colors = ['purple', 'blue', 'green', 'red', 'orange', 'pink'];
    
    if (!in_array($theme_mode, $valid_modes)) {
        $theme_mode = 'light';
    }
    if (!in_array($theme_color, $valid_colors)) {
        $theme_color = 'purple';
    }
    
    $db = getDB();
    
    // Update user
    $stmt = $db->prepare('UPDATE users SET display_name = ?, timezone = ?, weight_unit = ?, theme_mode = ?, theme_color = ? WHERE id = ?');
    $stmt->execute([$display_name, $timezone, $weight_unit, $theme_mode, $theme_color, $user['id']]);
    
    // Update settings
    $stmt = $db->prepare('UPDATE settings SET starting_weight = ?, target_weight = ? WHERE user_id = ?');
    $stmt->execute([$starting_weight, $target_weight, $user['id']]);
    
    jsonResponse(['success' => true]);
}

/**
 * Export data as CSV
 */
function exportCSV($user, $settings) {
    $entries = getWeightEntries($user['id']);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="librescale_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['Date', 'Time', 'Weight (' . $user['weight_unit'] . ')', 'Notes']);
    
    // Data
    foreach ($entries as $entry) {
        $date = formatDateTime($entry['timestamp'], $user['timezone'], 'Y-m-d');
        $time = formatDateTime($entry['timestamp'], $user['timezone'], 'H:i:s');
        fputcsv($output, [$date, $time, $entry['weight'], $entry['notes'] ?? '']);
    }
    
    fclose($output);
    exit;
}
