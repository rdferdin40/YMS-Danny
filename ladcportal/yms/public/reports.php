<?php
/**
 * Reports Page - All Phase 1 Reports
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';
require_once __DIR__ . '/../models/Move.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Reports - Penske Laredo YMS';

$reportType = $_GET['report'] ?? 'daily_snapshot';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Export handling
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $data = [];
    $filename = '';

    switch ($reportType) {
        case 'daily_snapshot':
            $data = Trailer::getAll(['location_type' => 'YARD']) + Trailer::getAll(['location_type' => 'DOCK']);
            $filename = 'daily_snapshot_' . date('Y-m-d') . '.csv';
            break;
        case 'daily_moves':
            $data = Move::getDaily();
            $filename = 'daily_moves_' . date('Y-m-d') . '.csv';
            break;
        case 'dwell_time':
            $data = Trailer::getByDwellTime();
            $filename = 'dwell_time_' . date('Y-m-d') . '.csv';
            break;
        case 'at_dock':
            $data = Trailer::getAll(['location_type' => 'DOCK']);
            $filename = 'trailers_at_dock_' . date('Y-m-d') . '.csv';
            break;
        case 'departed':
            $data = Trailer::getDeparted(7);
            $filename = 'departed_trailers_' . date('Y-m-d') . '.csv';
            break;
        case 'clerk_productivity':
            $data = Move::getStatsByUser($dateFrom, $dateTo);
            $filename = 'clerk_productivity_' . date('Y-m-d') . '.csv';
            break;
        case 'guard_activity':
            $data = Move::getGuardActivity($dateFrom, $dateTo);
            $filename = 'guard_activity_' . date('Y-m-d') . '.csv';
            break;
    }

    if (!empty($data)) {
        exportToCSV($data, $filename);
    }
}

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </h2>
        </div>
    </div>

    <!-- Report Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label"><strong>Select Report:</strong></label>
                    <select class="form-select" id="reportSelector" onchange="window.location.href='reports.php?report=' + this.value">
                        <option value="daily_snapshot" <?php echo $reportType === 'daily_snapshot' ? 'selected' : ''; ?>>1. Daily Yard Snapshot</option>
                        <option value="daily_moves" <?php echo $reportType === 'daily_moves' ? 'selected' : ''; ?>>2. Daily Move Log</option>
                        <option value="dwell_time" <?php echo $reportType === 'dwell_time' ? 'selected' : ''; ?>>3. Trailer Dwell Time</option>
                        <option value="at_dock" <?php echo $reportType === 'at_dock' ? 'selected' : ''; ?>>4. Trailers at Dock</option>
                        <option value="trailer_history" <?php echo $reportType === 'trailer_history' ? 'selected' : ''; ?>>5. Trailer History</option>
                        <option value="departed" <?php echo $reportType === 'departed' ? 'selected' : ''; ?>>6. Departed Trailers</option>
                        <option value="clerk_productivity" <?php echo $reportType === 'clerk_productivity' ? 'selected' : ''; ?>>7. Clerk Productivity</option>
                        <option value="guard_activity" <?php echo $reportType === 'guard_activity' ? 'selected' : ''; ?>>8. Guard Shack Activity</option>
                    </select>
                </div>
                <div class="col-md-6 text-end d-flex align-items-end">
                    <a href="?report=<?php echo $reportType; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&export=csv"
                       class="btn btn-success w-100">
                        <i class="bi bi-download"></i> Export to CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <?php
                $reportTitles = [
                    'daily_snapshot' => '1. Daily Yard Snapshot',
                    'daily_moves' => '2. Daily Move Log (Last 24 Hours)',
                    'dwell_time' => '3. Trailer Dwell Time Report',
                    'at_dock' => '4. Trailers Currently at Dock',
                    'trailer_history' => '5. Trailer History',
                    'departed' => '6. Departed Trailers (Last 7 Days)',
                    'clerk_productivity' => '7. Clerk Productivity Report',
                    'guard_activity' => '8. Guard Shack Activity Report'
                ];
                echo $reportTitles[$reportType] ?? 'Report';
                ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($reportType === 'daily_snapshot'): ?>
                <!-- Daily Yard Snapshot -->
                <?php
                $activeTrailers = array_merge(
                    Trailer::getAll(['location_type' => 'YARD']),
                    Trailer::getAll(['location_type' => 'DOCK'])
                );
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Trailer #</th>
                                <th>Carrier</th>
                                <th>Load Status</th>
                                <th>Location</th>
                                <th>Gate</th>
                                <th>Time In</th>
                                <th>Dwell (hrs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeTrailers as $t):
                                $dwellHours = calculateDwellTime($t['time_in']);
                                $location = $t['dock_door'] ? 'Door ' . $t['dock_door'] : str_replace('_', ' ', $t['yard_area']);
                            ?>
                                <tr class="<?php echo getDwellTimeColorClass($dwellHours); ?>">
                                    <td><strong><?php echo e($t['trailer_number']); ?></strong></td>
                                    <td><?php echo e($t['carrier'] ?? '-'); ?></td>
                                    <td><span class="badge <?php echo getLoadStatusBadgeClass($t['load_status']); ?>"><?php echo e($t['load_status']); ?></span></td>
                                    <td><?php echo e($location); ?></td>
                                    <td><?php echo e($t['gate'] ?? '-'); ?></td>
                                    <td><?php echo formatDateTime($t['time_in']); ?></td>
                                    <td><?php echo number_format($dwellHours, 1); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reportType === 'daily_moves'): ?>
                <!-- Daily Move Log -->
                <?php $moves = Move::getDaily(); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Move ID</th>
                                <th>Trailer #</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Move Type</th>
                                <th>Spotter</th>
                                <th>Performed By</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moves as $m):
                                $fromLoc = $m['from_location_type'];
                                if ($m['from_yard_area']) $fromLoc .= ' - ' . str_replace('_', ' ', $m['from_yard_area']);
                                if ($m['from_dock_door']) $fromLoc .= ' - Door ' . $m['from_dock_door'];

                                $toLoc = $m['to_location_type'];
                                if ($m['to_yard_area']) $toLoc .= ' - ' . str_replace('_', ' ', $m['to_yard_area']);
                                if ($m['to_dock_door']) $toLoc .= ' - Door ' . $m['to_dock_door'];
                            ?>
                                <tr>
                                    <td><?php echo e($m['move_id']); ?></td>
                                    <td><strong><?php echo e($m['trailer_number']); ?></strong></td>
                                    <td><?php echo e($fromLoc); ?></td>
                                    <td><?php echo e($toLoc); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo e($m['move_type']); ?></span></td>
                                    <td><?php echo e($m['spotter_name'] ?? '-'); ?></td>
                                    <td><?php echo e($m['performed_by_name']); ?></td>
                                    <td><?php echo formatDateTime($m['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reportType === 'dwell_time'): ?>
                <!-- Dwell Time Report -->
                <?php $trailers = Trailer::getByDwellTime(); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Trailer #</th>
                                <th>Location</th>
                                <th>Time In</th>
                                <th>Dwell Time (hrs)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trailers as $t):
                                $dwellHours = $t['dwell_hours'];
                                $location = $t['dock_door'] ? 'Door ' . $t['dock_door'] : str_replace('_', ' ', $t['yard_area']);
                            ?>
                                <tr class="<?php echo getDwellTimeColorClass($dwellHours); ?>">
                                    <td><strong><?php echo e($t['trailer_number']); ?></strong></td>
                                    <td><?php echo e($location); ?></td>
                                    <td><?php echo formatDateTime($t['time_in']); ?></td>
                                    <td><strong><?php echo number_format($dwellHours, 1); ?></strong></td>
                                    <td>
                                        <?php if ($dwellHours > 72): ?>
                                            <span class="badge bg-danger">Critical (>72h)</span>
                                        <?php elseif ($dwellHours > 48): ?>
                                            <span class="badge bg-warning">High (>48h)</span>
                                        <?php elseif ($dwellHours > 24): ?>
                                            <span class="badge bg-info">Moderate (>24h)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reportType === 'at_dock'): ?>
                <!-- Trailers at Dock -->
                <?php $dockTrailers = Trailer::getAll(['location_type' => 'DOCK']); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Door #</th>
                                <th>Trailer #</th>
                                <th>Carrier</th>
                                <th>Load Status</th>
                                <th>Time In Door</th>
                                <th>Duration (hrs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dockTrailers as $t):
                                $dwellHours = calculateDwellTime($t['time_in']);
                            ?>
                                <tr>
                                    <td><strong><?php echo $t['dock_door']; ?></strong></td>
                                    <td><?php echo e($t['trailer_number']); ?></td>
                                    <td><?php echo e($t['carrier'] ?? '-'); ?></td>
                                    <td><span class="badge <?php echo getLoadStatusBadgeClass($t['load_status']); ?>"><?php echo e($t['load_status']); ?></span></td>
                                    <td><?php echo formatDateTime($t['time_in']); ?></td>
                                    <td><?php echo number_format($dwellHours, 1); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reportType === 'trailer_history'): ?>
                <!-- Trailer History -->
                <form method="GET" class="mb-3">
                    <input type="hidden" name="report" value="trailer_history">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="trailer_search" class="form-control"
                                   placeholder="Enter Trailer Number" value="<?php echo e($_GET['trailer_search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>

                <?php if (!empty($_GET['trailer_search'])): ?>
                    <?php
                    $trailerNumber = normalizeTrailerNumber($_GET['trailer_search']);
                    $trailer = Trailer::findByNumber($trailerNumber);
                    if ($trailer):
                        $moves = Move::getByTrailer($trailer['id']);
                    ?>
                        <h6>History for Trailer: <?php echo e($trailer['trailer_number']); ?></h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Move ID</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Type</th>
                                        <th>Performed By</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($moves as $m):
                                        $fromLoc = $m['from_location_type'];
                                        if ($m['from_yard_area']) $fromLoc .= ' - ' . str_replace('_', ' ', $m['from_yard_area']);
                                        if ($m['from_dock_door']) $fromLoc .= ' - Door ' . $m['from_dock_door'];

                                        $toLoc = $m['to_location_type'];
                                        if ($m['to_yard_area']) $toLoc .= ' - ' . str_replace('_', ' ', $m['to_yard_area']);
                                        if ($m['to_dock_door']) $toLoc .= ' - Door ' . $m['to_dock_door'];
                                    ?>
                                        <tr>
                                            <td><?php echo e($m['move_id']); ?></td>
                                            <td><?php echo e($fromLoc); ?></td>
                                            <td><?php echo e($toLoc); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo e($m['move_type']); ?></span></td>
                                            <td><?php echo e($m['performed_by_name']); ?></td>
                                            <td><?php echo formatDateTime($m['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Trailer not found</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">Enter a trailer number to view its history</div>
                <?php endif; ?>

            <?php elseif ($reportType === 'departed'): ?>
                <!-- Departed Trailers -->
                <?php $departedTrailers = Trailer::getDeparted(7); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Trailer #</th>
                                <th>Carrier</th>
                                <th>Gate</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Duration (hrs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departedTrailers as $t):
                                $duration = calculateDwellTime($t['time_in'], $t['time_out']);
                            ?>
                                <tr>
                                    <td><strong><?php echo e($t['trailer_number']); ?></strong></td>
                                    <td><?php echo e($t['carrier'] ?? '-'); ?></td>
                                    <td><?php echo e($t['gate'] ?? '-'); ?></td>
                                    <td><?php echo formatDateTime($t['time_in']); ?></td>
                                    <td><?php echo formatDateTime($t['time_out']); ?></td>
                                    <td><?php echo number_format($duration, 1); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reportType === 'clerk_productivity'): ?>
                <!-- Clerk Productivity -->
                <form method="GET" class="mb-3">
                    <input type="hidden" name="report" value="clerk_productivity">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Date From:</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
                        </div>
                        <div class="col-md-4">
                            <label>Date To:</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>

                <?php $stats = Move::getStatsByUser($dateFrom, $dateTo); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Total Moves</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats as $s): ?>
                                <tr>
                                    <td><?php echo e($s['full_name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo e($s['role']); ?></span></td>
                                    <td><strong><?php echo $s['move_count']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reportType === 'guard_activity'): ?>
                <!-- Guard Activity -->
                <form method="GET" class="mb-3">
                    <input type="hidden" name="report" value="guard_activity">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Date From:</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
                        </div>
                        <div class="col-md-4">
                            <label>Date To:</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>

                <?php $guardStats = Move::getGuardActivity($dateFrom, $dateTo); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Guard</th>
                                <th>Date</th>
                                <th>Check-Ins</th>
                                <th>Check-Outs</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guardStats as $g): ?>
                                <tr>
                                    <td><?php echo e($g['full_name']); ?></td>
                                    <td><?php echo e($g['activity_date']); ?></td>
                                    <td><?php echo $g['checkins']; ?></td>
                                    <td><?php echo $g['checkouts']; ?></td>
                                    <td><strong><?php echo $g['total_activities']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
