<?php
/**
 * Move Log - History of all trailer movements
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Move.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Move Log - Penske Laredo YMS';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Get total count
$totalSql = "SELECT COUNT(*) as count FROM moves";
$totalResult = fetchOne($totalSql);
$totalMoves = $totalResult['count'] ?? 0;
$totalPages = ceil($totalMoves / $perPage);

// Get moves with pagination
$sql = "SELECT
            m.*,
            t.trailer_number,
            t.carrier,
            u.full_name as created_by_name
        FROM moves m
        LEFT JOIN trailers t ON m.trailer_id = t.id
        LEFT JOIN users u ON m.created_by_user_id = u.id
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?";

$moves = fetchAll($sql, [$perPage, $offset]);

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>
                <i class="bi bi-clock-history"></i> Move Log
                <span class="badge bg-secondary"><?php echo number_format($totalMoves); ?> Total Moves</span>
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?php echo url('index.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-list-ul"></i> Recent Moves
                <?php if ($totalPages > 1): ?>
                    <span class="text-muted small">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($moves)): ?>
                <div class="text-center p-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                    <p class="mt-3">No moves recorded yet</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Move ID</th>
                                <th>Date/Time</th>
                                <th>Trailer</th>
                                <th>Carrier</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Move Type</th>
                                <th>Spotter</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moves as $move):
                                // Format from location
                                $fromLocation = '-';
                                if ($move['from_location_type'] === 'YARD' && $move['from_yard_area']) {
                                    $fromLocation = str_replace('_', ' ', $move['from_yard_area']);
                                } elseif ($move['from_location_type'] === 'DOCK' && $move['from_dock_door']) {
                                    $fromLocation = 'Dock ' . $move['from_dock_door'];
                                } else {
                                    $fromLocation = $move['from_location_type'] ?: 'CHECK-IN';
                                }

                                // Format to location
                                $toLocation = '-';
                                if ($move['to_location_type'] === 'YARD' && $move['to_yard_area']) {
                                    $toLocation = str_replace('_', ' ', $move['to_yard_area']);
                                } elseif ($move['to_location_type'] === 'DOCK' && $move['to_dock_door']) {
                                    $toLocation = 'Dock ' . $move['to_dock_door'];
                                } elseif ($move['to_location_type'] === 'DEPARTED') {
                                    $toLocation = 'DEPARTED';
                                } else {
                                    $toLocation = $move['to_location_type'] ?: '-';
                                }

                                // Move type badge color
                                $moveTypeBadge = 'secondary';
                                if (strpos($move['move_type'], 'CHECK') !== false) {
                                    $moveTypeBadge = 'success';
                                } elseif ($move['move_type'] === 'YARD_TO_DOCK') {
                                    $moveTypeBadge = 'primary';
                                } elseif ($move['move_type'] === 'DOCK_TO_YARD') {
                                    $moveTypeBadge = 'warning';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <small class="font-monospace"><?php echo e($move['move_id']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo formatDateTime($move['created_at'], 'M d, Y H:i'); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo e($move['trailer_number']); ?></strong>
                                    </td>
                                    <td><?php echo e($move['carrier'] ?: '-'); ?></td>
                                    <td><?php echo e($fromLocation); ?></td>
                                    <td><?php echo e($toLocation); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $moveTypeBadge; ?>">
                                            <?php echo e(str_replace('_', ' ', $move['move_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($move['spotter_name'] ?: '-'); ?></td>
                                    <td>
                                        <small><?php echo e($move['created_by_name'] ?: 'System'); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <!-- Previous -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>

                                <!-- Page numbers -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }

                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $active = $i === $page ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '">';
                                    echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                                    echo '</li>';
                                }

                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                                }
                                ?>

                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
