<?php
/**
 * Trailer Detail View
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';
require_once __DIR__ . '/../models/Move.php';

requireLogin();

$user = getCurrentUser();
$trailerId = $_GET['id'] ?? 0;

if (!$trailerId) {
    redirect('trailers.php', 'Invalid trailer ID', 'error');
}

$trailer = Trailer::findById($trailerId);
if (!$trailer) {
    redirect('trailers.php', 'Trailer not found', 'error');
}

$moves = Move::getByTrailer($trailerId);
$dwellHours = calculateDwellTime($trailer['time_in'], $trailer['time_out']);

$pageTitle = 'Trailer ' . $trailer['trailer_number'] . ' - Penske Laredo YMS';

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>
                <i class="bi bi-truck"></i> Trailer: <?php echo e($trailer['trailer_number']); ?>
                <span class="badge <?php echo getLoadStatusBadgeClass($trailer['load_status']); ?>">
                    <?php echo e($trailer['load_status']); ?>
                </span>
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="trailers.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <?php if (canCreateMoves()): ?>
                <a href="move_entry.php?trailer_id=<?php echo $trailer['id']; ?>" class="btn btn-success">
                    <i class="bi bi-arrow-left-right"></i> Create Move
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Trailer Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Trailer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Trailer Number:</strong><br>
                            <?php echo e($trailer['trailer_number']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Carrier:</strong><br>
                            <?php echo e($trailer['carrier'] ?? '-'); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Trailer Type:</strong><br>
                            <?php echo e($trailer['trailer_type'] ?? '-'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Seal Number:</strong><br>
                            <?php echo e($trailer['seal_number'] ?? '-'); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Reference Number:</strong><br>
                            <?php echo e($trailer['reference_number'] ?? '-'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Live or Drop:</strong><br>
                            <?php echo e($trailer['live_or_drop'] ?? '-'); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tractor Number:</strong><br>
                            <?php echo e($trailer['tractor_number'] ?? '-'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Route:</strong><br>
                            <?php echo e($trailer['route'] ?? '-'); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Plant:</strong><br>
                            <?php echo e($trailer['plant'] ?? '-'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Gate:</strong><br>
                            <span class="badge <?php echo $trailer['gate'] === 'East' ? 'bg-success' : 'bg-primary'; ?>">
                                <?php echo e($trailer['gate'] ?? '-'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Comments:</strong><br>
                            <?php echo e($trailer['comments'] ?? '-'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Move History -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Move History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Move ID</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Type</th>
                                    <th>Spotter</th>
                                    <th>By</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($moves)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No move history</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($moves as $move):
                                        $fromLoc = $move['from_location_type'];
                                        if ($move['from_yard_area']) $fromLoc .= ' - ' . str_replace('_', ' ', $move['from_yard_area']);
                                        if ($move['from_dock_door']) $fromLoc .= ' - Door ' . $move['from_dock_door'];

                                        $toLoc = $move['to_location_type'];
                                        if ($move['to_yard_area']) $toLoc .= ' - ' . str_replace('_', ' ', $move['to_yard_area']);
                                        if ($move['to_dock_door']) $toLoc .= ' - Door ' . $move['to_dock_door'];
                                    ?>
                                        <tr>
                                            <td><?php echo e($move['move_id']); ?></td>
                                            <td><?php echo e($fromLoc); ?></td>
                                            <td><?php echo e($toLoc); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo e($move['move_type']); ?></span></td>
                                            <td><?php echo e($move['spotter_name'] ?? '-'); ?></td>
                                            <td><?php echo e($move['performed_by_name']); ?></td>
                                            <td><?php echo formatDateTime($move['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Sidebar -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-speedometer"></i> Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Load Status:</strong><br>
                        <span class="badge <?php echo getLoadStatusBadgeClass($trailer['load_status']); ?> fs-6">
                            <?php echo e($trailer['load_status']); ?>
                        </span>
                    </div>

                    <div class="mb-3">
                        <strong>Current Location:</strong><br>
                        <?php
                        $location = $trailer['current_location_type'];
                        if ($trailer['dock_door']) {
                            $location .= ' - Door ' . $trailer['dock_door'];
                        } elseif ($trailer['yard_area']) {
                            $location .= ' - ' . str_replace('_', ' ', $trailer['yard_area']);
                        }
                        ?>
                        <span class="badge bg-info fs-6"><?php echo e($location); ?></span>
                    </div>

                    <div class="mb-3 <?php echo getDwellTimeColorClass($dwellHours); ?>" style="padding: 10px; border-radius: 5px;">
                        <strong>Dwell Time:</strong><br>
                        <span class="fs-4"><?php echo number_format($dwellHours, 1); ?> hours</span>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <strong>Time In:</strong><br>
                        <?php echo formatDateTime($trailer['time_in']); ?>
                    </div>

                    <div class="mb-2">
                        <strong>Time Out:</strong><br>
                        <?php echo formatDateTime($trailer['time_out']); ?>
                    </div>

                    <div class="mb-2">
                        <strong>Last Moved:</strong><br>
                        <?php echo formatDateTime($trailer['last_moved_at']); ?>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <strong>Created By:</strong><br>
                        <?php echo e($trailer['created_by_name'] ?? '-'); ?>
                    </div>

                    <div class="mb-2">
                        <strong>Last Spotter:</strong><br>
                        <?php echo e($trailer['last_spotter_name'] ?? '-'); ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <?php if (canCreateMoves() && $trailer['current_location_type'] !== 'DEPARTED'): ?>
                <div class="card">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="move_entry.php?trailer_id=<?php echo $trailer['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-left-right"></i> Create Move
                            </a>
                            <?php if ($trailer['current_location_type'] !== 'DEPARTED'): ?>
                                <form method="POST" action="checkout_trailer.php" onsubmit="return confirm('Check out this trailer?');">
                                    <input type="hidden" name="trailer_id" value="<?php echo $trailer['id']; ?>">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="bi bi-box-arrow-right"></i> Check Out
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
