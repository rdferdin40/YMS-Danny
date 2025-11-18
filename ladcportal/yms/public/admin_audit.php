<?php
/**
 * Admin - Audit Log Viewer
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

requireLogin();
requireRole('ADMIN');

$user = getCurrentUser();
$pageTitle = 'Audit Log - Penske Laredo YMS';

$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$entityType = $_GET['entity_type'] ?? '';

// Build query
$sql = "SELECT a.*, u.username, u.full_name
        FROM audit_log a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE DATE(a.created_at) >= ? AND DATE(a.created_at) <= ?";

$params = [$dateFrom, $dateTo];

if ($entityType) {
    $sql .= " AND a.entity_type = ?";
    $params[] = $entityType;
}

$sql .= " ORDER BY a.created_at DESC LIMIT 500";

$logs = fetchAll($sql, $params);

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-journal-text"></i> Audit Log
            </h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Entity Type</label>
                    <select name="entity_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="USER" <?php echo $entityType === 'USER' ? 'selected' : ''; ?>>USER</option>
                        <option value="TRAILER" <?php echo $entityType === 'TRAILER' ? 'selected' : ''; ?>>TRAILER</option>
                        <option value="MOVE" <?php echo $entityType === 'MOVE' ? 'selected' : ''; ?>>MOVE</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No audit logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo formatDateTime($log['created_at']); ?></td>
                                    <td><?php echo e($log['full_name'] ?? 'System'); ?></td>
                                    <td><span class="badge bg-info"><?php echo e($log['action']); ?></span></td>
                                    <td><?php echo e($log['entity_type']); ?> #<?php echo $log['entity_id']; ?></td>
                                    <td><?php echo e($log['ip_address']); ?></td>
                                    <td>
                                        <?php if ($log['after_data']): ?>
                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#details<?php echo $log['id']; ?>">
                                                View
                                            </button>
                                            <div class="collapse mt-2" id="details<?php echo $log['id']; ?>">
                                                <pre class="small"><?php echo e($log['after_data']); ?></pre>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted small">
                Showing up to 500 most recent entries
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
