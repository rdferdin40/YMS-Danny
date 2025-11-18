<?php
/**
 * East Yard View
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'East Yard - Penske Laredo YMS';

// Get all trailers in East Yard
$trailers = Trailer::getByYard('EAST_YARD');

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>
                <i class="bi bi-grid-3x3"></i> East Yard
                <span class="badge bg-success"><?php echo count($trailers); ?> Trailers</span>
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="yard_west.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-right-circle"></i> Switch to West Yard
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Trailer #</th>
                            <th>Carrier</th>
                            <th>Type</th>
                            <th>Load Status</th>
                            <th>Time In</th>
                            <th>Dwell Time</th>
                            <th>Last Move</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trailers)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No trailers in East Yard</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trailers as $trailer):
                                $dwellHours = calculateDwellTime($trailer['time_in']);
                            ?>
                                <tr class="<?php echo getDwellTimeColorClass($dwellHours); ?>">
                                    <td><strong><?php echo e($trailer['trailer_number']); ?></strong></td>
                                    <td><?php echo e($trailer['carrier'] ?? '-'); ?></td>
                                    <td><?php echo e($trailer['trailer_type'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo getLoadStatusBadgeClass($trailer['load_status']); ?>">
                                            <?php echo e($trailer['load_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($trailer['time_in']); ?></td>
                                    <td><?php echo number_format($dwellHours, 1); ?>h</td>
                                    <td><?php echo e($trailer['last_move_type'] ?? '-'); ?></td>
                                    <td>
                                        <a href="trailer_detail.php?id=<?php echo $trailer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <?php if (canCreateMoves()): ?>
                                            <a href="move_entry.php?trailer_id=<?php echo $trailer['id']; ?>" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-left-right"></i> Move
                                            </a>
                                        <?php endif; ?>
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

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
