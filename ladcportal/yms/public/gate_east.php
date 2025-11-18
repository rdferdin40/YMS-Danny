<?php
/**
 * East Gate Check-In/Out
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';
require_once __DIR__ . '/../models/Move.php';

requireLogin();
requireRole(['GUARD', 'XD_TRAFFIC_CLERK', 'FG_TRAFFIC_CLERK', 'SHIPPING_CLERK', 'SUPERVISOR', 'ADMIN']);

$user = getCurrentUser();
$pageTitle = 'East Gate - Penske Laredo YMS';
$gate = 'East'; // Enforce East gate

$error = '';
$success = '';
$duplicateWarning = null;
$action = $_GET['action'] ?? 'checkin'; // checkin or checkout

// Get lookup data
$carriers = getActiveLookupItems('carriers');
$loadReasons = getActiveLookupItems('load_reasons');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    requireCSRF();

    $formAction = $_POST['action'] ?? 'checkin';

    if ($formAction === 'checkin') {
        // Check-in process
        $trailerNumber = normalizeTrailerNumber($_POST['trailer_number'] ?? '');
        $validation = validateTrailerNumber($trailerNumber);

        if (!$validation['valid']) {
            $error = $validation['error'];
        } else {
            // Check for duplicate
            $existingTrailer = checkDuplicateTrailer($trailerNumber);

            if ($existingTrailer && !isset($_POST['confirm_duplicate'])) {
                // Show warning
                $duplicateWarning = $existingTrailer;
            } else {
                // Proceed with check-in
                try {
                    $trailerId = Trailer::create([
                        'trailer_number' => $trailerNumber,
                        'carrier' => $_POST['carrier'] ?? null,
                        'trailer_type' => $_POST['trailer_type'] ?? null,
                        'seal_number' => $_POST['seal_number'] ?? null,
                        'reference_number' => $_POST['reference_number'] ?? null,
                        'live_or_drop' => $_POST['live_or_drop'] ?? null,
                        'tractor_number' => $_POST['tractor_number'] ?? null,
                        'route' => $_POST['route'] ?? null,
                        'plant' => $_POST['plant'] ?? null,
                        'load_status' => $_POST['load_status'],
                        'reason_load_status' => $_POST['reason_load_status'] ?? null,
                        'comments' => $_POST['comments'] ?? null,
                        'gate' => $gate,
                        'yard_area' => 'EAST_YARD', // Default to East Yard for East gate
                        'created_by_user_id' => $user['id'],
                        'date_in' => date('Y-m-d'),
                        'time_in' => date('Y-m-d H:i:s'),
                        'current_location_type' => 'YARD'
                    ]);

                    // Create move record
                    $moveId = generateMoveId();
                    Move::create([
                        'move_id' => $moveId,
                        'trailer_id' => $trailerId,
                        'from_location_type' => 'GATE',
                        'to_location_type' => 'YARD',
                        'to_yard_area' => 'EAST_YARD',
                        'move_type' => 'CHECK_IN',
                        'performed_by_user_id' => $user['id'],
                        'notes' => 'Check-in at East Gate'
                    ]);

                    // Log audit
                    logAudit('TRAILER_CREATED', 'TRAILER', $trailerId, null, [
                        'trailer_number' => $trailerNumber,
                        'gate' => $gate
                    ]);

                    redirect('gate_east.php?action=checkin', 'Trailer ' . $trailerNumber . ' checked in successfully at East Gate', 'success');
                } catch (Exception $e) {
                    $error = 'Failed to check in trailer: ' . $e->getMessage();
                }
            }
        }
    } elseif ($formAction === 'checkout') {
        // Check-out process
        $trailerNumber = normalizeTrailerNumber($_POST['trailer_number'] ?? '');

        if (empty($trailerNumber)) {
            $error = 'Please enter a trailer number';
        } else {
            $trailer = checkDuplicateTrailer($trailerNumber);

            if (!$trailer) {
                $error = 'Trailer ' . $trailerNumber . ' not found or already departed';
            } else {
                try {
                    // Check out trailer
                    Trailer::checkOut($trailer['id']);

                    // Create move record
                    $moveId = generateMoveId();
                    Move::create([
                        'move_id' => $moveId,
                        'trailer_id' => $trailer['id'],
                        'from_location_type' => $trailer['current_location_type'],
                        'from_yard_area' => $trailer['yard_area'],
                        'from_dock_door' => $trailer['dock_door'],
                        'to_location_type' => 'DEPARTED',
                        'move_type' => 'CHECK_OUT',
                        'performed_by_user_id' => $user['id'],
                        'notes' => 'Check-out at East Gate'
                    ]);

                    // Log audit
                    logAudit('TRAILER_CHECKOUT', 'TRAILER', $trailer['id'], $trailer, [
                        'trailer_number' => $trailerNumber,
                        'gate' => $gate,
                        'time_out' => date('Y-m-d H:i:s')
                    ]);

                    redirect('gate_east.php?action=checkout', 'Trailer ' . $trailerNumber . ' checked out successfully from East Gate', 'success');
                } catch (Exception $e) {
                    $error = 'Failed to check out trailer: ' . $e->getMessage();
                }
            }
        }
    }
}

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-shield-check"></i> East Gate Check-In/Out
                <span class="badge bg-success">EAST</span>
            </h2>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($duplicateWarning): ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Duplicate Trailer Warning</h5>
            <p>Trailer <strong><?php echo e($duplicateWarning['trailer_number']); ?></strong> is already active in the system:</p>
            <ul>
                <li>Location: <?php echo e($duplicateWarning['current_location_type']); ?></li>
                <li>Check-in time: <?php echo formatDateTime($duplicateWarning['time_in']); ?></li>
            </ul>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="checkin">
                <input type="hidden" name="confirm_duplicate" value="1">
                <input type="hidden" name="trailer_number" value="<?php echo e($_POST['trailer_number']); ?>">
                <input type="hidden" name="carrier" value="<?php echo e($_POST['carrier'] ?? ''); ?>">
                <input type="hidden" name="trailer_type" value="<?php echo e($_POST['trailer_type'] ?? ''); ?>">
                <input type="hidden" name="seal_number" value="<?php echo e($_POST['seal_number'] ?? ''); ?>">
                <input type="hidden" name="reference_number" value="<?php echo e($_POST['reference_number'] ?? ''); ?>">
                <input type="hidden" name="live_or_drop" value="<?php echo e($_POST['live_or_drop'] ?? ''); ?>">
                <input type="hidden" name="tractor_number" value="<?php echo e($_POST['tractor_number'] ?? ''); ?>">
                <input type="hidden" name="route" value="<?php echo e($_POST['route'] ?? ''); ?>">
                <input type="hidden" name="plant" value="<?php echo e($_POST['plant'] ?? ''); ?>">
                <input type="hidden" name="load_status" value="<?php echo e($_POST['load_status']); ?>">
                <input type="hidden" name="reason_load_status" value="<?php echo e($_POST['reason_load_status'] ?? ''); ?>">
                <input type="hidden" name="comments" value="<?php echo e($_POST['comments'] ?? ''); ?>">
                <button type="submit" class="btn btn-warning me-2">Proceed with Check-In Anyway</button>
                <a href="gate_east.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $action === 'checkin' ? 'active' : ''; ?>"
                    onclick="window.location.href='gate_east.php?action=checkin'">
                <i class="bi bi-box-arrow-in-right"></i> Check-In
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $action === 'checkout' ? 'active' : ''; ?>"
                    onclick="window.location.href='gate_east.php?action=checkout'">
                <i class="bi bi-box-arrow-right"></i> Check-Out
            </button>
        </li>
    </ul>

    <div class="row">
        <div class="col-md-8">
            <?php if ($action === 'checkin'): ?>
                <!-- Check-In Form -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Trailer Check-In</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="checkin">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Trailer Number <span class="text-danger">*</span></label>
                                    <input type="text" name="trailer_number" class="form-control"
                                           pattern="[A-Z0-9]{1,20}" maxlength="20" required
                                           value="<?php echo e($_POST['trailer_number'] ?? ''); ?>">
                                    <small class="text-muted">A-Z, 0-9 only, max 20 characters</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Carrier</label>
                                    <select name="carrier" class="form-select">
                                        <option value="">-- Select Carrier --</option>
                                        <?php foreach ($carriers as $carrier): ?>
                                            <option value="<?php echo e($carrier['name']); ?>"><?php echo e($carrier['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Trailer Type</label>
                                    <select name="trailer_type" class="form-select">
                                        <option value="">-- Select Type --</option>
                                        <option value="Dry Van">Dry Van</option>
                                        <option value="Reefer">Reefer</option>
                                        <option value="Flatbed">Flatbed</option>
                                        <option value="Container">Container</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Seal Number</label>
                                    <input type="text" name="seal_number" class="form-control">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Reference Number (BOL/ASN)</label>
                                    <input type="text" name="reference_number" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Live or Drop</label>
                                    <select name="live_or_drop" class="form-select">
                                        <option value="">-- Select --</option>
                                        <option value="LIVE">Live</option>
                                        <option value="DROP">Drop</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Load Status <span class="text-danger">*</span></label>
                                    <select name="load_status" class="form-select" required>
                                        <option value="">-- Select Status --</option>
                                        <option value="EMPTY">Empty</option>
                                        <option value="LOADED">Loaded</option>
                                        <option value="BOBTAIL">Bobtail</option>
                                        <option value="LIVE_LOAD">Live Load</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reason/Purpose</label>
                                    <select name="reason_load_status" class="form-select">
                                        <option value="">-- Select Reason --</option>
                                        <?php foreach ($loadReasons as $reason): ?>
                                            <option value="<?php echo e($reason['name']); ?>"><?php echo e($reason['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Tractor Number</label>
                                    <input type="text" name="tractor_number" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Route</label>
                                    <select name="route" class="form-select">
                                        <option value="">-- Select --</option>
                                        <option value="NORTHBOUND">Northbound</option>
                                        <option value="SOUTHBOUND">Southbound</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Plant</label>
                                    <input type="text" name="plant" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Comments/Notes</label>
                                <textarea name="comments" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Check In Trailer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Check-Out Form -->
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-box-arrow-right"></i> Trailer Check-Out</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="checkout">

                            <div class="mb-3">
                                <label class="form-label">Trailer Number <span class="text-danger">*</span></label>
                                <input type="text" name="trailer_number" class="form-control"
                                       pattern="[A-Z0-9]{1,20}" maxlength="20" required autofocus>
                                <small class="text-muted">Enter trailer number to check out</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="bi bi-box-arrow-right"></i> Check Out Trailer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent East Gate Activity</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                        <?php
                        $recentMoves = Move::getAll([
                            'move_type' => 'CHECK_IN',
                            'limit' => 10
                        ]);
                        foreach ($recentMoves as $move):
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo e($move['trailer_number']); ?></strong>
                                    <small class="text-muted"><?php echo formatDateTime($move['created_at'], 'H:i'); ?></small>
                                </div>
                                <small class="text-muted">
                                    <?php echo e($move['move_type']); ?> by <?php echo e($move['performed_by_name']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
