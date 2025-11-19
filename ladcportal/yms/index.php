<?php
/**
 * Root redirect - Send users to login page
 */

// Redirect to the login page in the public directory
header('Location: public/login.php');
exit;
