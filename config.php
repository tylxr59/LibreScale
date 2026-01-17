<?php
/**
 * LibreScale Configuration and Database Helper
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define database path
define('DB_PATH', __DIR__ . '/librescale.db');

// Check if database exists (setup completed)
if (!file_exists(DB_PATH)) {
    header('Location: setup.php');
    exit;
}

/**
 * Get database connection
 */
function getDB() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $db;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

/**
 * Check if user is logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get user settings
 */
function getUserSettings($user_id) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM settings WHERE user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Format weight based on user preference
 */
function formatWeight($weight, $unit = 'kg', $decimals = 1) {
    return number_format($weight, $decimals) . ' ' . $unit;
}

/**
 * Convert timestamp to user's timezone
 */
function formatDateTime($timestamp, $timezone = 'UTC', $format = 'Y-m-d H:i:s') {
    $dt = new DateTime('@' . $timestamp);
    $dt->setTimezone(new DateTimeZone($timezone));
    return $dt->format($format);
}

/**
 * Get start of day timestamp in user's timezone
 */
function getStartOfDay($timestamp, $timezone) {
    $dt = new DateTime('@' . $timestamp);
    $dt->setTimezone(new DateTimeZone($timezone));
    $dt->setTime(0, 0, 0);
    return $dt->getTimestamp();
}

/**
 * Get weight entries for a date range
 */
function getWeightEntries($user_id, $start_timestamp = null, $end_timestamp = null) {
    $db = getDB();
    
    if ($start_timestamp && $end_timestamp) {
        $stmt = $db->prepare('
            SELECT * FROM weights 
            WHERE user_id = ? AND timestamp >= ? AND timestamp <= ?
            ORDER BY timestamp DESC
        ');
        $stmt->execute([$user_id, $start_timestamp, $end_timestamp]);
    } else {
        $stmt = $db->prepare('
            SELECT * FROM weights 
            WHERE user_id = ?
            ORDER BY timestamp DESC
        ');
        $stmt->execute([$user_id]);
    }
    
    return $stmt->fetchAll();
}

/**
 * Get average weight for a specific day (handles multiple entries per day)
 */
function getDailyAverages($user_id, $timezone, $start_timestamp = null, $end_timestamp = null) {
    $entries = getWeightEntries($user_id, $start_timestamp, $end_timestamp);
    $daily_weights = [];
    
    foreach ($entries as $entry) {
        $date = formatDateTime($entry['timestamp'], $timezone, 'Y-m-d');
        if (!isset($daily_weights[$date])) {
            $daily_weights[$date] = [];
        }
        $daily_weights[$date][] = $entry['weight'];
    }
    
    $averages = [];
    foreach ($daily_weights as $date => $weights) {
        $averages[$date] = [
            'date' => $date,
            'weight' => array_sum($weights) / count($weights),
            'count' => count($weights)
        ];
    }
    
    // Sort by date ascending
    ksort($averages);
    
    return array_values($averages);
}

/**
 * Check if user has entered weight today
 */
function hasEnteredWeightToday($user_id, $timezone) {
    $db = getDB();
    
    // Get start and end of today in user's timezone
    $now = new DateTime('now', new DateTimeZone($timezone));
    $start_of_day = clone $now;
    $start_of_day->setTime(0, 0, 0);
    $end_of_day = clone $now;
    $end_of_day->setTime(23, 59, 59);
    
    $stmt = $db->prepare('
        SELECT COUNT(*) as count FROM weights 
        WHERE user_id = ? AND timestamp >= ? AND timestamp <= ?
    ');
    $stmt->execute([
        $user_id, 
        $start_of_day->getTimestamp(), 
        $end_of_day->getTimestamp()
    ]);
    
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

/**
 * Get theme classes for body tag
 */
function getThemeClasses($user) {
    $mode = $user['theme_mode'] ?? 'light';
    $color = $user['theme_color'] ?? 'purple';
    return 'theme-' . $mode . ' theme-color-' . $color;
}

/**
 * Sanitize output
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * JSON response helper
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
