<?php
/**
 * Quick Checkout Handler
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';
require_once __DIR__ . '/../models/Move.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php', 'Invalid request', 'error');
}

// Validate CSRF token
requireCSRF();

$trailerId = $_POST['trailer_id'] ?? 0;

if (!$trailerId) {
    redirect('index.php', 'Trailer ID required', 'error');
}

$trailer = Trailer::findById($trailerId);
if (!$trailer) {
    redirect('index.php', 'Trailer not found', 'error');
}

$user = getCurrentUser();

try {
    // Check out trailer
    Trailer::checkOut($trailerId);

    // Create move record
    $moveId = generateMoveId();
    Move::create([
        'move_id' => $moveId,
        'trailer_id' => $trailerId,
        'from_location_type' => $trailer['current_location_type'],
        'from_yard_area' => $trailer['yard_area'],
        'from_dock_door' => $trailer['dock_door'],
        'to_location_type' => 'DEPARTED',
        'move_type' => 'CHECK_OUT',
        'performed_by_user_id' => $user['id'],
        'notes' => 'Quick checkout'
    ]);

    // Log audit
    logAudit('TRAILER_CHECKOUT', 'TRAILER', $trailerId, $trailer, [
        'trailer_number' => $trailer['trailer_number'],
        'time_out' => date('Y-m-d H:i:s')
    ]);

    redirect('trailers.php', 'Trailer ' . $trailer['trailer_number'] . ' checked out successfully', 'success');
} catch (Exception $e) {
    redirect('trailer_detail.php?id=' . $trailerId, 'Failed to check out: ' . $e->getMessage(), 'error');
}
