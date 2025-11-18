<?php
/**
 * Admin - Lookup Tables Management
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';

requireLogin();
requireRole('ADMIN');

$user = getCurrentUser();
$pageTitle = 'Manage Lookups - Penske Laredo YMS';

$table = $_GET['table'] ?? 'carriers';
$allowedTables = ['carriers', 'spotters', 'load_reasons', 'purposes'];

if (!in_array($table, $allowedTables)) {
    $table = 'carriers';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = $_POST['name'] ?? '';

    if ($action === 'create' && !empty($name)) {
        $sql = "INSERT INTO $table (name, active, created_at) VALUES (?, 1, NOW())";
        query($sql, [$name]);
        logAudit('LOOKUP_CREATED', strtoupper($table), lastInsertId(), null, ['name' => $name]);
        redirect("admin_lookups.php?table=$table", 'Item created successfully', 'success');
    } elseif ($action === 'toggle' && !empty($_POST['id'])) {
        $id = $_POST['id'];
        $sql = "UPDATE $table SET active = NOT active WHERE id = ?";
        query($sql, [$id]);
        logAudit('LOOKUP_UPDATED', strtoupper($table), $id, null, null);
        redirect("admin_lookups.php?table=$table", 'Item updated successfully', 'success');
    }
}

// Get items
$items = fetchAll("SELECT * FROM $table ORDER BY name ASC");

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-list-check"></i> Manage Lookup Tables
            </h2>
        </div>
    </div>

    <!-- Table Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?table=carriers" class="btn btn-outline-primary <?php echo $table === 'carriers' ? 'active' : ''; ?>">Carriers</a>
                <a href="?table=spotters" class="btn btn-outline-primary <?php echo $table === 'spotters' ? 'active' : ''; ?>">Spotters</a>
                <a href="?table=load_reasons" class="btn btn-outline-primary <?php echo $table === 'load_reasons' ? 'active' : ''; ?>">Load Reasons</a>
                <a href="?table=purposes" class="btn btn-outline-primary <?php echo $table === 'purposes' ? 'active' : ''; ?>">Purposes</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Manage <?php echo ucfirst(str_replace('_', ' ', $table)); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td><strong><?php echo e($item['name']); ?></strong></td>
                                        <td>
                                            <?php if ($item['active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <?php echo $item['active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Add New Item</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
