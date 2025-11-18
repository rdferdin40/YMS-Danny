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

$tableParam = $_GET['table'] ?? 'carriers';

// Strict whitelist with safe table name mapping (prevents SQL injection)
$safeTable = null;
switch ($tableParam) {
    case 'carriers':
        $safeTable = 'carriers';
        break;
    case 'spotters':
        $safeTable = 'spotters';
        break;
    case 'load_reasons':
        $safeTable = 'load_reasons';
        break;
    case 'purposes':
        $safeTable = 'purposes';
        break;
    default:
        $safeTable = 'carriers';
        $tableParam = 'carriers';
}

$table = $safeTable; // Use the verified safe table name

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    requireCSRF();

    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if ($action === 'create' && !empty($name)) {
        // Validate name length
        if (strlen($name) > 100) {
            redirect("admin_lookups.php?table=$tableParam", 'Name is too long (max 100 characters)', 'danger');
        }

        // Use safe table name in query
        switch ($table) {
            case 'carriers':
                $sql = "INSERT INTO carriers (name, active, created_at) VALUES (?, 1, NOW())";
                break;
            case 'spotters':
                $sql = "INSERT INTO spotters (name, active, created_at) VALUES (?, 1, NOW())";
                break;
            case 'load_reasons':
                $sql = "INSERT INTO load_reasons (name, active, created_at) VALUES (?, 1, NOW())";
                break;
            case 'purposes':
                $sql = "INSERT INTO purposes (name, active, created_at) VALUES (?, 1, NOW())";
                break;
        }

        query($sql, [$name]);
        logAudit('LOOKUP_CREATED', strtoupper($table), lastInsertId(), null, ['name' => $name]);
        redirect("admin_lookups.php?table=$tableParam", 'Item created successfully', 'success');

    } elseif ($action === 'toggle' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];

        // Use safe table name in query
        switch ($table) {
            case 'carriers':
                $sql = "UPDATE carriers SET active = NOT active WHERE id = ?";
                break;
            case 'spotters':
                $sql = "UPDATE spotters SET active = NOT active WHERE id = ?";
                break;
            case 'load_reasons':
                $sql = "UPDATE load_reasons SET active = NOT active WHERE id = ?";
                break;
            case 'purposes':
                $sql = "UPDATE purposes SET active = NOT active WHERE id = ?";
                break;
        }

        query($sql, [$id]);
        logAudit('LOOKUP_UPDATED', strtoupper($table), $id, null, null);
        redirect("admin_lookups.php?table=$tableParam", 'Item updated successfully', 'success');
    }
}

// Get items - use safe table name
switch ($table) {
    case 'carriers':
        $items = fetchAll("SELECT * FROM carriers ORDER BY name ASC");
        break;
    case 'spotters':
        $items = fetchAll("SELECT * FROM spotters ORDER BY name ASC");
        break;
    case 'load_reasons':
        $items = fetchAll("SELECT * FROM load_reasons ORDER BY name ASC");
        break;
    case 'purposes':
        $items = fetchAll("SELECT * FROM purposes ORDER BY name ASC");
        break;
}

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
                                                <?php echo csrfField(); ?>
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
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" maxlength="100" required>
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
