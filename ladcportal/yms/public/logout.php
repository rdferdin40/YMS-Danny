<?php
/**
 * Logout Page
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

// Log the logout action
if (isLoggedIn()) {
    $user = getCurrentUser();
    logAudit('USER_LOGOUT', 'USER', $user['id'], null, ['username' => $user['username']]);
}

// Destroy session
logoutUser();

// Redirect to login page
header('Location: login.php');
exit;
