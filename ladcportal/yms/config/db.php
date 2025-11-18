<?php
/**
 * Database Configuration
 * Returns a PDO instance for MySQL connection
 */

// Include path configuration
require_once __DIR__ . '/paths.php';

// Database credentials - EDIT THESE FOR YOUR ENVIRONMENT
define('DB_HOST', 'localhost');
define('DB_NAME', 'ladc_yms');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty

/**
 * Get database connection
 * @return PDO
 */
function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error securely without exposing details to users
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please contact your system administrator.");
        }
    }

    return $pdo;
}

/**
 * Execute a prepared query
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function query($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch single row
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

/**
 * Fetch all rows
 * @param string $sql
 * @param array $params
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get last insert ID
 * @return string
 */
function lastInsertId() {
    return getDbConnection()->lastInsertId();
}

/**
 * Begin database transaction
 * @return bool
 */
function beginTransaction() {
    return getDbConnection()->beginTransaction();
}

/**
 * Commit database transaction
 * @return bool
 */
function commit() {
    return getDbConnection()->commit();
}

/**
 * Rollback database transaction
 * @return bool
 */
function rollback() {
    return getDbConnection()->rollBack();
}

/**
 * Check if currently in a transaction
 * @return bool
 */
function inTransaction() {
    return getDbConnection()->inTransaction();
}

/**
 * Execute a callback within a database transaction
 * Automatically commits on success, rolls back on exception
 * @param callable $callback
 * @return mixed Returns the callback result
 * @throws Exception
 */
function transaction($callback) {
    $pdo = getDbConnection();

    try {
        $pdo->beginTransaction();
        $result = $callback();
        $pdo->commit();
        return $result;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
