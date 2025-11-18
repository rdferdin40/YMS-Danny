<?php
/**
 * Security Functions - CSRF Protection, Session Security, Rate Limiting
 */

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token hidden field for forms
 * @return string HTML for hidden input
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get CSRF token value (for AJAX requests)
 * @return string
 */
function getCSRFToken() {
    return generateCSRFToken();
}

/**
 * Require valid CSRF token or die
 */
function requireCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!validateCSRFToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}

/**
 * Check rate limit for an action
 * @param string $identifier Unique identifier (e.g., 'login_' . $username)
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if under limit, false if exceeded
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . $identifier;
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'first_attempt' => $now,
            'reset_at' => $now + $timeWindow
        ];
        return true;
    }

    $data = $_SESSION[$key];

    // Reset if time window has passed
    if ($now >= $data['reset_at']) {
        $_SESSION[$key] = [
            'count' => 1,
            'first_attempt' => $now,
            'reset_at' => $now + $timeWindow
        ];
        return true;
    }

    // Increment counter
    $_SESSION[$key]['count']++;

    // Check if exceeded
    if ($_SESSION[$key]['count'] > $maxAttempts) {
        return false;
    }

    return true;
}

/**
 * Get time until rate limit resets
 * @param string $identifier
 * @return int Seconds until reset
 */
function getRateLimitResetTime($identifier) {
    $key = 'rate_limit_' . $identifier;
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    return max(0, $_SESSION[$key]['reset_at'] - time());
}

/**
 * Reset rate limit for an identifier
 * @param string $identifier
 */
function resetRateLimit($identifier) {
    $key = 'rate_limit_' . $identifier;
    unset($_SESSION[$key]);
}

/**
 * Configure secure session settings
 */
function configureSecureSession() {
    // Only configure if session not started
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        // Enable secure flag if HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', 1);
        }

        // Use strict session ID mode
        ini_set('session.use_strict_mode', 1);

        // Regenerate session ID periodically to prevent fixation
        session_start();

        // Check for session timeout (30 minutes)
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > 1800) { // 30 minutes
                session_unset();
                session_destroy();
                session_start();
            }
        }
        $_SESSION['last_activity'] = time();

    } else {
        // Session already started, just update activity time
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > 1800) {
                session_unset();
                session_destroy();
                session_start();
            }
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Sanitize input string
 * @param mixed $input
 * @return string
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(strip_tags((string)$input));
}

/**
 * Validate ENUM value against allowed values
 * @param string $value
 * @param array $allowedValues
 * @return bool
 */
function validateEnum($value, array $allowedValues) {
    return in_array($value, $allowedValues, true);
}

/**
 * Validate string length
 * @param string $value
 * @param int $min
 * @param int $max
 * @return bool
 */
function validateLength($value, $min = 0, $max = PHP_INT_MAX) {
    $length = mb_strlen($value);
    return $length >= $min && $length <= $max;
}

/**
 * Validate integer range
 * @param int $value
 * @param int $min
 * @param int $max
 * @return bool
 */
function validateRange($value, $min, $max) {
    return is_numeric($value) && $value >= $min && $value <= $max;
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate username format
 * @param string $username
 * @return array ['valid' => bool, 'error' => string]
 */
function validateUsername($username) {
    if (strlen($username) < 3 || strlen($username) > 20) {
        return ['valid' => false, 'error' => 'Username must be 3-20 characters long'];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];
    }

    return ['valid' => true, 'error' => ''];
}

/**
 * Log error to file (not exposing to user)
 * @param string $message
 * @param Exception $e
 */
function logError($message, $e = null) {
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);

    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";

    if ($e) {
        $logMessage .= "\n" . $e->getMessage() . "\n" . $e->getTraceAsString();
    }

    $logMessage .= "\n" . str_repeat('-', 80) . "\n";

    @error_log($logMessage, 3, $logFile);
}
