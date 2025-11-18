<?php
/**
 * Application Path Configuration
 * Handles dynamic base path detection for flexible deployment
 */

// Determine the base path dynamically
// For development: /yms or /ladcportal/yms
// For production: /yms (at ladcportal.com/yms or 192.168.0.20/yms)

// Method 1: Auto-detect from current script path
// Get the directory path of the current script relative to document root
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);

// Remove '/public' from the path if present (since we're in /public directory)
$basePath = $scriptDir;
if (basename($scriptDir) === 'public') {
    $basePath = dirname($scriptDir);
}

// Define the base path constant
define('BASE_PATH', $basePath);

// Define the full base URL (protocol + host + base path)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . '://' . $host . BASE_PATH);

/**
 * Helper function to generate URLs relative to the application base path
 * @param string $path Path relative to the public directory (e.g., 'index.php' or 'admin_users.php')
 * @return string Full path from web root
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_PATH . '/public/' . $path;
}

/**
 * Helper function to generate asset URLs (for CSS, JS, images)
 * @param string $path Path to asset file
 * @return string Full URL to asset
 */
function asset($path) {
    return BASE_URL . '/public/' . ltrim($path, '/');
}

/**
 * Redirect to a page within the application
 * @param string $path Path relative to public directory
 */
function redirectTo($path) {
    header('Location: ' . url($path));
    exit;
}
