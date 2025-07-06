<?php

/**
 * Main Configuration File for Pet Clinic Application
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'Ruang Fauna');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/pet_clinic_app/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);

// Date and time settings
date_default_timezone_set('UTC');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Include database configuration
require_once __DIR__ . '/database.php';

// Authentication functions
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserRole()
{
    return $_SESSION['user_role'] ?? 'guest';
}

function hasPermission($required_role)
{
    $user_role = getUserRole();
    $roles = ['guest' => 0, 'staff' => 1, 'doctor' => 2, 'admin' => 3];

    return ($roles[$user_role] ?? 0) >= ($roles[$required_role] ?? 0);
}

function requirePermission($required_role)
{
    if (!hasPermission($required_role)) {
        header('Location: unauthorized.php');
        exit();
    }
}

// Utility functions
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date, $format = DATE_FORMAT)
{
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatCurrency($amount)
{
    return 'Rp' . number_format($amount, 2);
}

function generateCode($prefix, $length = 3)
{
    $number = str_pad(rand(1, 999), $length, '0', STR_PAD_LEFT);
    return $prefix . $number;
}

// Error handling
function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/../logs/error.log');
}

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Auto-initialize database on first run
if (!file_exists(__DIR__ . '/../.initialized')) {
    if (initializeDatabase()) {
        file_put_contents(__DIR__ . '/../.initialized', date('Y-m-d H:i:s'));
    }
}
