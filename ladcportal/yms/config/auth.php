<?php
/**
 * Authentication and Authorization Functions
 */

// Include path configuration
require_once __DIR__ . '/paths.php';

// Include security functions
require_once __DIR__ . '/security.php';

// Configure secure session
configureSecureSession();

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

/**
 * Get current user data from session
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Check if current user has a specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Check if current user has any of the specified roles
 * @param array $roles
 * @return bool
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    return in_array($_SESSION['role'], $roles);
}

/**
 * Require specific role - die with error if not authorized
 * @param array|string $allowedRoles
 */
function requireRole($allowedRoles) {
    requireLogin();

    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    if (!hasAnyRole($allowedRoles)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this page.');
    }
}

/**
 * Check if user can check in/out trailers
 * @return bool
 */
function canCheckInOut() {
    return hasAnyRole(['GUARD', 'XD_TRAFFIC_CLERK', 'FG_TRAFFIC_CLERK', 'SHIPPING_CLERK', 'SUPERVISOR', 'ADMIN']);
}

/**
 * Check if user can assign yards and docks
 * @return bool
 */
function canAssignLocation() {
    return hasAnyRole(['XD_TRAFFIC_CLERK', 'FG_TRAFFIC_CLERK', 'SHIPPING_CLERK', 'SUPERVISOR', 'ADMIN']);
}

/**
 * Check if user can create moves
 * @return bool
 */
function canCreateMoves() {
    return hasAnyRole(['XD_TRAFFIC_CLERK', 'FG_TRAFFIC_CLERK', 'SHIPPING_CLERK', 'SUPERVISOR', 'ADMIN']);
}

/**
 * Check if user can edit trailer info
 * @return bool
 */
function canEditTrailer() {
    return hasAnyRole(['XD_TRAFFIC_CLERK', 'FG_TRAFFIC_CLERK', 'SHIPPING_CLERK', 'SUPERVISOR', 'ADMIN']);
}

/**
 * Check if user can view reports
 * @return bool
 */
function canViewReports() {
    return isLoggedIn(); // All logged-in users can view reports
}

/**
 * Check if user can access admin panel
 * @return bool
 */
function canAccessAdmin() {
    return hasRole('ADMIN');
}

/**
 * Login user
 * @param array $user User data from database
 */
function loginUser($user) {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Get user's IP address
 * Note: Only uses REMOTE_ADDR to prevent IP spoofing via HTTP headers
 * @return string
 */
function getUserIP() {
    // Only use REMOTE_ADDR - HTTP headers can be spoofed
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get role display name
 * @param string $role
 * @return string
 */
function getRoleDisplayName($role) {
    $roleNames = [
        'GUARD' => 'Guard',
        'XD_TRAFFIC_CLERK' => 'XD Traffic Clerk',
        'FG_TRAFFIC_CLERK' => 'FG Traffic Clerk',
        'SHIPPING_CLERK' => 'Shipping Clerk',
        'SUPERVISOR' => 'Supervisor',
        'ADMIN' => 'Administrator'
    ];
    return $roleNames[$role] ?? $role;
}
