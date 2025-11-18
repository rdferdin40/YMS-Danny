<?php
/**
 * Main Dashboard
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';
require_once __DIR__ . '/../models/Move.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Dashboard - Penske Laredo YMS';

// Get dashboard statistics
$activeTrailers = Trailer::getActiveCount();
$eastYardTrailers = count(Trailer::getByYard('EAST_YARD'));
$westYardTrailers = count(Trailer::getByYard('WEST_YARD'));
$dockOccupancy = count(Trailer::getDockDoorStatuses());
$recentMoves = Move::getAll(['limit' => 10]);

// Get trailers with high dwell time
$dwellTrailers = array_filter(Trailer::getByDwellTime(), function($t) {
    return $t['dwell_hours'] > 24;
});

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-speedometer2"></i> Dashboard
                <small class="text-muted">Welcome, <?php echo e($user['full_name']); ?></small>
            </h2>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Active Trailers</h6>
                            <h2 class="mb-0"><?php echo $activeTrailers; ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-truck" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">East Yard</h6>
                            <h2 class="mb-0"><?php echo $eastYardTrailers; ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-grid-3x3" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">West Yard</h6>
                            <h2 class="mb-0"><?php echo $westYardTrailers; ?></h2>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-grid-3x3" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Doors Occupied</h6>
                            <h2 class="mb-0"><?php echo $dockOccupancy; ?> / 52</h2>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-door-open" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Moves -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Moves</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Trailer</th>
                                    <th>Move Type</th>
                                    <th>Time</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentMoves)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No recent moves</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentMoves as $move): ?>
                                        <tr>
                                            <td><strong><?php echo e($move['trailer_number']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo e($move['move_type']); ?></span>
                                            </td>
                                            <td><?php echo formatDateTime($move['created_at'], 'H:i'); ?></td>
                                            <td><?php echo e($move['performed_by_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!empty($recentMoves)): ?>
                    <div class="card-footer text-center">
                        <a href="move_log.php" class="btn btn-sm btn-outline-primary">View All Moves</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- High Dwell Time Trailers -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> High Dwell Time (>24h)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Trailer</th>
                                    <th>Location</th>
                                    <th>Dwell Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dwellTrailers)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No trailers over 24 hours</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($dwellTrailers, 0, 10) as $trailer): ?>
                                        <?php
                                        $dwellHours = $trailer['dwell_hours'];
                                        $location = $trailer['current_location_type'] === 'DOCK'
                                            ? 'Door ' . $trailer['dock_door']
                                            : str_replace('_', ' ', $trailer['yard_area']);
                                        ?>
                                        <tr class="<?php echo getDwellTimeColorClass($dwellHours); ?>">
                                            <td><strong><?php echo e($trailer['trailer_number']); ?></strong></td>
                                            <td><?php echo e($location); ?></td>
                                            <td><?php echo number_format($dwellHours, 1); ?>h</td>
                                            <td>
                                                <a href="trailer_detail.php?id=<?php echo $trailer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions (Role-based) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (canCheckInOut()): ?>
                            <div class="col-md-3 mb-2">
                                <a href="gate_east.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> East Gate Check-In/Out
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="gate_west.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> West Gate Check-In/Out
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (canCreateMoves()): ?>
                            <div class="col-md-3 mb-2">
                                <a href="move_entry.php" class="btn btn-outline-success w-100">
                                    <i class="bi bi-arrow-left-right"></i> Create Move
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="dock_board.php" class="btn btn-outline-info w-100">
                                    <i class="bi bi-door-open"></i> Dock Door Board
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (canViewReports()): ?>
                            <div class="col-md-3 mb-2">
                                <a href="reports.php" class="btn btn-outline-warning w-100">
                                    <i class="bi bi-file-earmark-bar-graph"></i> Reports
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (canAccessAdmin()): ?>
                            <div class="col-md-3 mb-2">
                                <a href="admin_users.php" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-gear"></i> Admin Panel
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
