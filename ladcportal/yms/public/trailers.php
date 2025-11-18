<?php
/**
 * Trailer Master List
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Trailers - Penske Laredo YMS';

// Get filter parameters
$filters = [];
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (!empty($_GET['location_type'])) {
    $filters['location_type'] = $_GET['location_type'];
}
if (!empty($_GET['yard_area'])) {
    $filters['yard_area'] = $_GET['yard_area'];
}

// Get trailers
$trailers = Trailer::getAll($filters);

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>
                <i class="bi bi-list-ul"></i> Trailer Master List
                <span class="badge bg-primary"><?php echo count($trailers); ?> Trailers</span>
            </h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Trailer #, Carrier, Reference..."
                           value="<?php echo e($_GET['search'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Location Type</label>
                    <select name="location_type" class="form-select">
                        <option value="">All Locations</option>
                        <option value="YARD" <?php echo ($_GET['location_type'] ?? '') === 'YARD' ? 'selected' : ''; ?>>Yard</option>
                        <option value="DOCK" <?php echo ($_GET['location_type'] ?? '') === 'DOCK' ? 'selected' : ''; ?>>Dock</option>
                        <option value="DEPARTED" <?php echo ($_GET['location_type'] ?? '') === 'DEPARTED' ? 'selected' : ''; ?>>Departed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Yard Area</label>
                    <select name="yard_area" class="form-select">
                        <option value="">All Yards</option>
                        <option value="EAST_YARD" <?php echo ($_GET['yard_area'] ?? '') === 'EAST_YARD' ? 'selected' : ''; ?>>East Yard</option>
                        <option value="WEST_YARD" <?php echo ($_GET['yard_area'] ?? '') === 'WEST_YARD' ? 'selected' : ''; ?>>West Yard</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Trailers Table -->
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
                            <th>Location</th>
                            <th>Gate</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Dwell</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trailers)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">No trailers found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trailers as $trailer):
                                $dwellHours = calculateDwellTime($trailer['time_in'], $trailer['time_out']);
                                $location = '';
                                if ($trailer['current_location_type'] === 'DOCK') {
                                    $location = 'Door ' . $trailer['dock_door'];
                                } elseif ($trailer['current_location_type'] === 'YARD') {
                                    $location = str_replace('_', ' ', $trailer['yard_area']);
                                } else {
                                    $location = 'Departed';
                                }
                            ?>
                                <tr class="<?php echo $trailer['current_location_type'] !== 'DEPARTED' ? getDwellTimeColorClass($dwellHours) : ''; ?>">
                                    <td><strong><?php echo e($trailer['trailer_number']); ?></strong></td>
                                    <td><?php echo e($trailer['carrier'] ?? '-'); ?></td>
                                    <td><?php echo e($trailer['trailer_type'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo getLoadStatusBadgeClass($trailer['load_status']); ?>">
                                            <?php echo e($trailer['load_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($location); ?></td>
                                    <td><?php echo e($trailer['gate'] ?? '-'); ?></td>
                                    <td><?php echo formatDateTime($trailer['time_in']); ?></td>
                                    <td><?php echo formatDateTime($trailer['time_out']); ?></td>
                                    <td><?php echo number_format($dwellHours, 1); ?>h</td>
                                    <td>
                                        <a href="trailer_detail.php?id=<?php echo $trailer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
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

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
