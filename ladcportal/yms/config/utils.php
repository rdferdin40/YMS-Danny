<?php
/**
 * Utility Functions
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Generate next move ID in format MV-YYYY-MM-DD-00001
 * Uses database locking to prevent race conditions
 * @return string
 */
function generateMoveId() {
    $today = date('Y-m-d');
    $prefix = 'MV-' . $today . '-';

    // Use FOR UPDATE to lock the row and prevent race conditions
    $sql = "SELECT move_id FROM moves WHERE move_id LIKE ? ORDER BY move_id DESC LIMIT 1 FOR UPDATE";

    // Execute within a transaction if not already in one
    $wasInTransaction = inTransaction();

    if (!$wasInTransaction) {
        beginTransaction();
    }

    try {
        $lastMove = fetchOne($sql, [$prefix . '%']);

        if ($lastMove) {
            // Extract the counter part and increment
            $lastCounter = (int)substr($lastMove['move_id'], -5);
            $newCounter = $lastCounter + 1;
        } else {
            // First move of the day
            $newCounter = 1;
        }

        $moveId = $prefix . str_pad($newCounter, 5, '0', STR_PAD_LEFT);

        // Commit if we started the transaction
        if (!$wasInTransaction) {
            commit();
        }

        return $moveId;

    } catch (Exception $e) {
        if (!$wasInTransaction) {
            rollback();
        }
        throw $e;
    }
}

/**
 * Validate trailer number
 * Must be A-Z, 0-9, max 20 chars
 * @param string $trailerNumber
 * @return array ['valid' => bool, 'error' => string]
 */
function validateTrailerNumber($trailerNumber) {
    if (empty($trailerNumber)) {
        return ['valid' => false, 'error' => 'Trailer number is required'];
    }

    if (strlen($trailerNumber) > 20) {
        return ['valid' => false, 'error' => 'Trailer number cannot exceed 20 characters'];
    }

    if (!preg_match('/^[A-Z0-9]+$/', $trailerNumber)) {
        return ['valid' => false, 'error' => 'Trailer number can only contain letters A-Z and digits 0-9'];
    }

    return ['valid' => true, 'error' => null];
}

/**
 * Normalize trailer number to uppercase
 * @param string $trailerNumber
 * @return string
 */
function normalizeTrailerNumber($trailerNumber) {
    return strtoupper(trim($trailerNumber));
}

/**
 * Check if dock door is available
 * @param int $dockDoor
 * @param int|null $excludeTrailerId
 * @return array ['available' => bool, 'trailer_number' => string|null]
 */
function isDockDoorAvailable($dockDoor, $excludeTrailerId = null) {
    $sql = "SELECT id, trailer_number FROM trailers
            WHERE dock_door = ?
            AND current_location_type = 'DOCK'
            AND id != ?
            LIMIT 1";

    $occupyingTrailer = fetchOne($sql, [$dockDoor, $excludeTrailerId ?? 0]);

    if ($occupyingTrailer) {
        return [
            'available' => false,
            'trailer_number' => $occupyingTrailer['trailer_number']
        ];
    }

    return ['available' => true, 'trailer_number' => null];
}

/**
 * Check if trailer is already active in yard
 * @param string $trailerNumber
 * @return array|null Returns trailer data if active, null if not
 */
function checkDuplicateTrailer($trailerNumber) {
    $sql = "SELECT * FROM trailers
            WHERE trailer_number = ?
            AND current_location_type != 'DEPARTED'
            ORDER BY id DESC
            LIMIT 1";

    return fetchOne($sql, [$trailerNumber]);
}

/**
 * Log to audit trail
 * @param string $action
 * @param string $entityType
 * @param int $entityId
 * @param array|null $beforeData
 * @param array|null $afterData
 */
function logAudit($action, $entityType, $entityId, $beforeData = null, $afterData = null) {
    $user = getCurrentUser();
    $userId = $user ? $user['id'] : null;

    $sql = "INSERT INTO audit_log (user_id, action, entity_type, entity_id, before_data, after_data, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

    query($sql, [
        $userId,
        $action,
        $entityType,
        $entityId,
        $beforeData ? json_encode($beforeData) : null,
        $afterData ? json_encode($afterData) : null,
        getUserIP()
    ]);
}

/**
 * Format datetime for display
 * @param string|null $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Calculate dwell time in hours
 * @param string $timeIn
 * @param string|null $timeOut
 * @return float
 */
function calculateDwellTime($timeIn, $timeOut = null) {
    $start = strtotime($timeIn);
    $end = $timeOut ? strtotime($timeOut) : time();
    return round(($end - $start) / 3600, 2);
}

/**
 * Get dwell time color class
 * @param float $hours
 * @return string
 */
function getDwellTimeColorClass($hours) {
    if ($hours > 72) {
        return 'bg-danger text-white';
    } elseif ($hours > 48) {
        return 'bg-warning';
    } elseif ($hours > 24) {
        return 'bg-info';
    }
    return '';
}

/**
 * Get load status badge class
 * @param string $loadStatus
 * @return string
 */
function getLoadStatusBadgeClass($loadStatus) {
    switch ($loadStatus) {
        case 'LOADED':
            return 'bg-primary';
        case 'EMPTY':
            return 'bg-secondary';
        case 'LIVE_LOAD':
            return 'bg-danger';
        case 'BOBTAIL':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
}

/**
 * Sanitize output for HTML
 * @param string $str
 * @return string
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Export array to CSV
 * @param array $data
 * @param string $filename
 */
function exportToCSV($data, $filename) {
    if (empty($data)) {
        die('No data to export');
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, array_keys($data[0]));

    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Redirect with message
 * @param string $url
 * @param string $message
 * @param string $type (success, error, warning, info)
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Get all active items from a lookup table
 * @param string $table
 * @return array
 */
function getActiveLookupItems($table) {
    $allowedTables = ['carriers', 'spotters', 'load_reasons', 'purposes'];
    if (!in_array($table, $allowedTables)) {
        return [];
    }

    $sql = "SELECT * FROM $table WHERE active = 1 ORDER BY name ASC";
    return fetchAll($sql);
}
