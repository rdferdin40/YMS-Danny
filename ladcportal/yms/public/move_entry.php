<?php
/**
 * Move Entry Screen
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';
require_once __DIR__ . '/../models/Move.php';

requireLogin();
requireRole(['XD_TRAFFIC_CLERK', 'FG_TRAFFIC_CLERK', 'SHIPPING_CLERK', 'SUPERVISOR', 'ADMIN']);

$user = getCurrentUser();
$pageTitle = 'Create Move - Penske Laredo YMS';

$error = '';
$spotters = getActiveLookupItems('spotters');

// Pre-select trailer if provided
$preselectedTrailer = null;
if (!empty($_GET['trailer_id'])) {
    $preselectedTrailer = Trailer::findById($_GET['trailer_id']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    requireCSRF();

    // Sanitize and validate input
    $trailerId = (int)($_POST['trailer_id'] ?? 0);
    $toLocationType = trim($_POST['to_location_type'] ?? '');

    // Convert empty strings to NULL for optional fields
    $toYardArea = !empty($_POST['to_yard_area']) ? trim($_POST['to_yard_area']) : null;
    $toDockDoor = !empty($_POST['to_dock_door']) ? (int)$_POST['to_dock_door'] : null;
    $spotterName = !empty($_POST['spotter_name']) ? trim($_POST['spotter_name']) : null;
    $notes = trim($_POST['notes'] ?? '');

    // Validate trailer selection
    if (!$trailerId) {
        $error = 'Please select a trailer';
    }
    // Validate destination type
    elseif (!$toLocationType) {
        $error = 'Please select a destination';
    }
    // Validate ENUM value for location type
    elseif (!validateEnum($toLocationType, ['YARD', 'DOCK', 'DEPARTED'])) {
        $error = 'Invalid destination type';
    }
    // Validate yard area if YARD destination
    elseif ($toLocationType === 'YARD' && !$toYardArea) {
        $error = 'Yard area is required when moving to yard';
    }
    // Validate yard area ENUM
    elseif ($toLocationType === 'YARD' && !validateEnum($toYardArea, ['EAST_YARD', 'WEST_YARD'])) {
        $error = 'Invalid yard area';
    }
    // Validate dock door if DOCK destination
    elseif ($toLocationType === 'DOCK' && !$toDockDoor) {
        $error = 'Dock door is required when moving to dock';
    }
    // Validate dock door range
    elseif ($toLocationType === 'DOCK' && !validateRange($toDockDoor, 1, 52)) {
        $error = 'Dock door must be between 1 and 52';
    }
    // Validate spotter name length
    elseif ($spotterName && !validateLength($spotterName, 1, 100)) {
        $error = 'Spotter name is too long (max 100 characters)';
    }
    // Validate notes length
    elseif (!validateLength($notes, 0, 255)) {
        $error = 'Notes are too long (max 255 characters)';
    }
    else {
        $trailer = Trailer::findById($trailerId);
        if (!$trailer) {
            $error = 'Trailer not found';
        } else {
            // Validate dock door availability
            if ($toDockDoor) {
                $doorCheck = isDockDoorAvailable($toDockDoor, $trailerId);
                if (!$doorCheck['available']) {
                    $error = 'Dock door ' . $toDockDoor . ' is already occupied by trailer ' . $doorCheck['trailer_number'];
                }
            }

            if (!$error) {
                try {
                    // Wrap in transaction for data integrity
                    transaction(function() use ($trailerId, $toLocationType, $toYardArea, $toDockDoor, $spotterName, $notes, $trailer, $user) {
                        // Determine move type
                        $fromType = $trailer['current_location_type'];
                        $moveType = '';

                        if ($fromType === 'YARD' && $toLocationType === 'DOCK') {
                            $moveType = 'YARD_TO_DOCK';
                        } elseif ($fromType === 'DOCK' && $toLocationType === 'YARD') {
                            $moveType = 'DOCK_TO_YARD';
                        } elseif ($fromType === 'YARD' && $toLocationType === 'YARD') {
                            $moveType = 'YARD_TO_YARD';
                        } elseif ($toLocationType === 'DEPARTED') {
                            $moveType = 'CHECK_OUT';
                        } else {
                            $moveType = 'MOVE';
                        }

                        // Create move record
                        $moveId = generateMoveId();
                        Move::create([
                            'move_id' => $moveId,
                            'trailer_id' => $trailerId,
                            'from_location_type' => $trailer['current_location_type'],
                            'from_yard_area' => $trailer['yard_area'],
                            'from_dock_door' => $trailer['dock_door'],
                            'to_location_type' => $toLocationType,
                            'to_yard_area' => $toYardArea,
                            'to_dock_door' => $toDockDoor,
                            'move_type' => $moveType,
                            'spotter_name' => $spotterName,
                            'performed_by_user_id' => $user['id'],
                            'notes' => $notes
                        ]);

                        // Update trailer location
                        if ($toLocationType === 'DEPARTED') {
                            Trailer::checkOut($trailerId);
                        } elseif ($toLocationType === 'DOCK') {
                            Trailer::assignToDock($trailerId, $toDockDoor);
                        } elseif ($toLocationType === 'YARD') {
                            Trailer::assignToYard($trailerId, $toYardArea);
                        }

                        // Update last move info
                        Trailer::update($trailerId, [
                            'last_move_type' => $moveType,
                            'last_spotter_name' => $spotterName
                        ]);

                        // Log audit
                        logAudit('MOVE_CREATED', 'MOVE', $moveId, null, [
                            'trailer_number' => $trailer['trailer_number'],
                            'move_type' => $moveType,
                            'from' => $fromType,
                            'to' => $toLocationType
                        ]);

                        return $moveId;
                    });

                    redirect('trailer_detail.php?id=' . $trailerId, 'Move created successfully', 'success');
                } catch (Exception $e) {
                    logError('Failed to create move', $e);
                    $error = 'Failed to create move. Please try again.';
                }
            }
        }
    }
}

// Get active trailers for dropdown (yard and dock)
$activeTrailers = array_merge(
    Trailer::getAll(['location_type' => 'YARD']),
    Trailer::getAll(['location_type' => 'DOCK'])
);

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-arrow-left-right"></i> Create Move
            </h2>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> New Move</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="moveForm">
                        <?php echo csrfField(); ?>
                        <div class="mb-3">
                            <label class="form-label">Select Trailer <span class="text-danger">*</span></label>
                            <select name="trailer_id" id="trailerSelect" class="form-select" required>
                                <option value="">-- Select Trailer --</option>
                                <?php
                                $allTrailers = array_merge(
                                    Trailer::getAll(['location_type' => 'YARD']),
                                    Trailer::getAll(['location_type' => 'DOCK'])
                                );
                                foreach ($allTrailers as $t):
                                    $selected = ($preselectedTrailer && $t['id'] == $preselectedTrailer['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $t['id']; ?>"
                                            data-location="<?php echo $t['current_location_type']; ?>"
                                            data-yard="<?php echo $t['yard_area'] ?? ''; ?>"
                                            data-door="<?php echo $t['dock_door'] ?? ''; ?>"
                                            <?php echo $selected; ?>>
                                        <?php echo e($t['trailer_number']); ?> -
                                        <?php
                                        if ($t['dock_door']) {
                                            echo 'Door ' . $t['dock_door'];
                                        } else {
                                            echo str_replace('_', ' ', $t['yard_area']);
                                        }
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3" id="currentLocationDiv" style="display: none;">
                            <div class="alert alert-info">
                                <strong>Current Location:</strong> <span id="currentLocation"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Move To <span class="text-danger">*</span></label>
                            <select name="to_location_type" id="toLocationType" class="form-select" required>
                                <option value="">-- Select Destination --</option>
                                <option value="YARD">Yard</option>
                                <option value="DOCK">Dock Door</option>
                                <option value="DEPARTED">Departed</option>
                            </select>
                        </div>

                        <div class="mb-3" id="yardAreaDiv" style="display: none;">
                            <label class="form-label">Select Yard Area</label>
                            <select name="to_yard_area" class="form-select">
                                <option value="">-- Select Yard --</option>
                                <option value="EAST_YARD">East Yard</option>
                                <option value="WEST_YARD">West Yard</option>
                            </select>
                        </div>

                        <div class="mb-3" id="dockDoorDiv" style="display: none;">
                            <label class="form-label">Select Dock Door</label>
                            <select name="to_dock_door" class="form-select">
                                <option value="">-- Select Door --</option>
                                <?php for ($i = 1; $i <= 52; $i++): ?>
                                    <option value="<?php echo $i; ?>">Door <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Spotter</label>
                            <select name="spotter_name" class="form-select">
                                <option value="">-- Select Spotter --</option>
                                <?php foreach ($spotters as $spotter): ?>
                                    <option value="<?php echo e($spotter['name']); ?>"><?php echo e($spotter['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i> Create Move
                            </button>
                            <a href="trailers.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Move Instructions</h6>
                </div>
                <div class="card-body">
                    <ol class="small">
                        <li>Select the trailer to move</li>
                        <li>Choose the destination (Yard, Dock, or Departed)</li>
                        <li>If moving to yard, select East or West</li>
                        <li>If moving to dock, select door number (1-52)</li>
                        <li>Optionally select the spotter performing the move</li>
                        <li>Add any notes about the move</li>
                        <li>Submit to create the move</li>
                    </ol>

                    <hr>

                    <h6>Dock Door Ranges:</h6>
                    <ul class="small">
                        <li><strong>West Dock:</strong> Doors 1-30</li>
                        <li><strong>East Dock:</strong> Doors 31-52</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const trailerSelect = document.getElementById('trailerSelect');
    const toLocationType = document.getElementById('toLocationType');
    const currentLocationDiv = document.getElementById('currentLocationDiv');
    const currentLocation = document.getElementById('currentLocation');
    const yardAreaDiv = document.getElementById('yardAreaDiv');
    const dockDoorDiv = document.getElementById('dockDoorDiv');

    // Show current location when trailer is selected
    trailerSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            const locType = option.dataset.location;
            const yard = option.dataset.yard;
            const door = option.dataset.door;

            let locText = locType;
            if (door) {
                locText += ' - Door ' + door;
            } else if (yard) {
                locText += ' - ' + yard.replace('_', ' ');
            }

            currentLocation.textContent = locText;
            currentLocationDiv.style.display = 'block';
        } else {
            currentLocationDiv.style.display = 'none';
        }
    });

    // Show/hide destination fields based on selection
    toLocationType.addEventListener('change', function() {
        yardAreaDiv.style.display = 'none';
        dockDoorDiv.style.display = 'none';

        if (this.value === 'YARD') {
            yardAreaDiv.style.display = 'block';
            yardAreaDiv.querySelector('select').required = true;
            dockDoorDiv.querySelector('select').required = false;
        } else if (this.value === 'DOCK') {
            dockDoorDiv.style.display = 'block';
            dockDoorDiv.querySelector('select').required = true;
            yardAreaDiv.querySelector('select').required = false;
        } else {
            yardAreaDiv.querySelector('select').required = false;
            dockDoorDiv.querySelector('select').required = false;
        }
    });

    // Trigger change if preselected
    if (trailerSelect.value) {
        trailerSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
