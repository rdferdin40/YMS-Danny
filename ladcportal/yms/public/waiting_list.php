<?php
/**
 * Waiting List - Clerk Management View
 * Interactive page for managing waiting list priorities and notes
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../models/Trailer.php';

requireLogin();

$user = getCurrentUser();

// Determine which yard to display
$yard = $_GET['yard'] ?? 'WEST';
$yard = strtoupper($yard);

if (!in_array($yard, ['WEST', 'EAST'])) {
    $yard = 'WEST';
}

$yardArea = $yard . '_YARD';
$yardName = $yard === 'WEST' ? 'West Side' : 'East Side';
$yardColor = $yard === 'WEST' ? 'primary' : 'success';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    requireCSRF();
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $trailerId = (int)($_POST['trailer_id'] ?? 0);

    try {
        switch ($action) {
            case 'update_priority':
                $priority = $_POST['priority'] ?? 'NORMAL';
                if (Trailer::updatePriority($trailerId, $priority)) {
                    $trailer = Trailer::getById($trailerId);
                    logAudit('PRIORITY_UPDATED', 'TRAILER', $trailerId, null, ['priority' => $priority]);
                    echo json_encode(['success' => true, 'message' => 'Priority updated']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update priority']);
                }
                break;

            case 'update_notes':
                $notes = trim($_POST['notes'] ?? '');
                if (Trailer::updateWaitingNotes($trailerId, $notes)) {
                    logAudit('WAITING_NOTES_UPDATED', 'TRAILER', $trailerId, null, ['notes' => $notes]);
                    echo json_encode(['success' => true, 'message' => 'Notes updated']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update notes']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("Waiting list update failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    exit;
}

// Get waiting list
$waitingList = Trailer::getWaitingList($yardArea);
$stats = Trailer::getWaitingListStats($yardArea);

$pageTitle = "$yardName Waiting List - Clerk Management";

// Helper function to format wait time
function formatWaitTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($hours > 0) {
        return "{$hours}h {$mins}m";
    }
    return "{$mins}m";
}

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>
                <i class="bi bi-clipboard-check"></i> <?php echo e($yardName); ?> Waiting List
                <span class="badge bg-<?php echo $yardColor; ?>"><?php echo $stats['total_waiting'] ?? 0; ?> Waiting</span>
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="waiting_list_tv.php?yard=<?php echo $yard; ?>" target="_blank" class="btn btn-dark btn-lg">
                <i class="bi bi-tv"></i> TV Display
            </a>
            <?php if ($yard === 'WEST'): ?>
                <a href="waiting_list.php?yard=EAST" class="btn btn-success btn-lg">
                    <i class="bi bi-arrow-right-circle"></i> Switch to East Side
                </a>
            <?php else: ?>
                <a href="waiting_list.php?yard=WEST" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-left-circle"></i> Switch to West Side
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-danger"><?php echo $stats['high_priority'] ?? 0; ?></h3>
                    <small class="text-muted">High Priority</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $stats['normal_priority'] ?? 0; ?></h3>
                    <small class="text-muted">Normal Priority</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-secondary"><?php echo $stats['low_priority'] ?? 0; ?></h3>
                    <small class="text-muted">Low Priority</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning"><?php echo $stats['loaded_count'] ?? 0; ?></h3>
                    <small class="text-muted">Loaded</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?php echo $stats['empty_count'] ?? 0; ?></h3>
                    <small class="text-muted">Empty</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo isset($stats['avg_wait_minutes']) ? formatWaitTime($stats['avg_wait_minutes']) : '0m'; ?></h3>
                    <small class="text-muted">Avg Wait</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Waiting List Table -->
    <div class="card">
        <div class="card-header bg-<?php echo $yardColor; ?> text-white">
            <h5 class="mb-0">
                <i class="bi bi-list-check"></i> Waiting Queue
                <span class="badge bg-light text-dark float-end">Auto-refresh: 30s</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($waitingList)): ?>
                <div class="text-center p-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                    <p class="mt-3">No trailers waiting in <?php echo strtolower($yardName); ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="12%">Trailer Number</th>
                                <th width="15%">Carrier</th>
                                <th width="10%">Load Status</th>
                                <th width="10%">Wait Time</th>
                                <th width="12%">Checked In</th>
                                <th width="12%">Priority</th>
                                <th width="18%">Notes</th>
                                <th width="6%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($waitingList as $index => $trailer):
                                $rowClass = '';
                                if ($trailer['priority'] === 'HIGH') {
                                    $rowClass = 'table-danger';
                                } elseif ($trailer['wait_hours'] >= 3) {
                                    $rowClass = 'table-warning';
                                }
                            ?>
                                <tr class="<?php echo $rowClass; ?>" id="trailer-row-<?php echo $trailer['id']; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo e($trailer['trailer_number']); ?></strong>
                                    </td>
                                    <td><?php echo e($trailer['carrier'] ?: '-'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'secondary';
                                        if ($trailer['load_status'] === 'LOADED') {
                                            $statusClass = 'warning';
                                        } elseif ($trailer['load_status'] === 'EMPTY') {
                                            $statusClass = 'info';
                                        } elseif ($trailer['load_status'] === 'LIVE_LOAD') {
                                            $statusClass = 'danger';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo e($trailer['load_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo formatWaitTime($trailer['wait_minutes']); ?></strong>
                                        <?php if ($trailer['wait_hours'] >= 3): ?>
                                            <i class="bi bi-exclamation-triangle text-danger" title="Long wait!"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo formatDateTime($trailer['time_in'], 'M d, H:i'); ?></small>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm priority-selector"
                                                data-trailer-id="<?php echo $trailer['id']; ?>"
                                                onchange="updatePriority(this)">
                                            <option value="LOW" <?php echo $trailer['priority'] === 'LOW' ? 'selected' : ''; ?>>Low</option>
                                            <option value="NORMAL" <?php echo $trailer['priority'] === 'NORMAL' ? 'selected' : ''; ?>>Normal</option>
                                            <option value="HIGH" <?php echo $trailer['priority'] === 'HIGH' ? 'selected' : ''; ?>>High</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm notes-input"
                                               data-trailer-id="<?php echo $trailer['id']; ?>"
                                               value="<?php echo e($trailer['waiting_notes'] ?? ''); ?>"
                                               placeholder="Add note..."
                                               onblur="updateNotes(this)">
                                    </td>
                                    <td>
                                        <a href="trailer_detail.php?id=<?php echo $trailer['id']; ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?php echo generateCSRFToken(); ?>';

function updatePriority(selectElement) {
    const trailerId = selectElement.dataset.trailerId;
    const priority = selectElement.value;
    const originalValue = selectElement.querySelector('option[selected]')?.value || 'NORMAL';

    // Disable select while updating
    selectElement.disabled = true;

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=update_priority&trailer_id=${trailerId}&priority=${priority}&csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        selectElement.disabled = false;
        if (data.success) {
            // Update selected attribute
            selectElement.querySelectorAll('option').forEach(opt => opt.removeAttribute('selected'));
            selectElement.querySelector(`option[value="${priority}"]`).setAttribute('selected', 'selected');

            // Show success feedback
            showToast('Priority updated successfully', 'success');

            // Refresh page after 1 second to reorder
            setTimeout(() => location.reload(), 1000);
        } else {
            // Revert to original value
            selectElement.value = originalValue;
            showToast('Failed to update priority', 'error');
        }
    })
    .catch(error => {
        selectElement.disabled = false;
        selectElement.value = originalValue;
        showToast('Error updating priority', 'error');
    });
}

function updateNotes(inputElement) {
    const trailerId = inputElement.dataset.trailerId;
    const notes = inputElement.value.trim();

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=update_notes&trailer_id=${trailerId}&notes=${encodeURIComponent(notes)}&csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Notes updated', 'success');
        } else {
            showToast('Failed to update notes', 'error');
        }
    })
    .catch(error => {
        showToast('Error updating notes', 'error');
    });
}

function showToast(message, type) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
