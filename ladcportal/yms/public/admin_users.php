<?php
/**
 * Admin - User Management
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/User.php';

requireLogin();
requireRole('ADMIN');

$user = getCurrentUser();
$pageTitle = 'User Management - Penske Laredo YMS';

$action = $_GET['action'] ?? 'list';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? '';

        if (empty($username) || empty($password) || empty($fullName) || empty($role)) {
            $error = 'All fields are required';
        } elseif (User::usernameExists($username)) {
            $error = 'Username already exists';
        } else {
            $userId = User::create([
                'username' => $username,
                'password' => $password,
                'full_name' => $fullName,
                'role' => $role,
                'active' => 1
            ]);
            logAudit('USER_CREATED', 'USER', $userId, null, ['username' => $username, 'role' => $role]);
            redirect('admin_users.php', 'User created successfully', 'success');
        }
    } elseif ($formAction === 'update') {
        $userId = $_POST['user_id'] ?? 0;
        $fullName = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';

        $updateData = [
            'full_name' => $fullName,
            'role' => $role
        ];
        if (!empty($password)) {
            $updateData['password'] = $password;
        }

        User::update($userId, $updateData);
        logAudit('USER_UPDATED', 'USER', $userId, null, $updateData);
        redirect('admin_users.php', 'User updated successfully', 'success');
    } elseif ($formAction === 'delete') {
        $userId = $_POST['user_id'] ?? 0;
        if ($userId != $user['id']) {
            User::delete($userId);
            logAudit('USER_DELETED', 'USER', $userId, null, null);
            redirect('admin_users.php', 'User deactivated successfully', 'success');
        } else {
            $error = 'Cannot delete your own account';
        }
    }
}

// Get users
$users = User::getAll(false);

// Get single user for edit
$editUser = null;
if ($action === 'edit' && !empty($_GET['id'])) {
    $editUser = User::findById($_GET['id']);
}

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>
                <i class="bi bi-people"></i> User Management
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-plus-circle"></i> Create New User
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><strong><?php echo e($u['username']); ?></strong></td>
                                <td><?php echo e($u['full_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo e(getRoleDisplayName($u['role'])); ?></span></td>
                                <td>
                                    <?php if ($u['active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDateTime($u['created_at'], 'Y-m-d'); ?></td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php if ($u['id'] != $user['id']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Deactivate this user?');">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Deactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Create New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="form_action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Select Role --</option>
                            <option value="GUARD">Guard</option>
                            <option value="XD_TRAFFIC_CLERK">XD Traffic Clerk</option>
                            <option value="FG_TRAFFIC_CLERK">FG Traffic Clerk</option>
                            <option value="SHIPPING_CLERK">Shipping Clerk</option>
                            <option value="SUPERVISOR">Supervisor</option>
                            <option value="ADMIN">Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editUser): ?>
<!-- Edit User Modal -->
<div class="modal fade show" id="editUserModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit User</h5>
                <a href="admin_users.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST">
                <input type="hidden" name="form_action" value="update">
                <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo e($editUser['username']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo e($editUser['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="GUARD" <?php echo $editUser['role'] === 'GUARD' ? 'selected' : ''; ?>>Guard</option>
                            <option value="XD_TRAFFIC_CLERK" <?php echo $editUser['role'] === 'XD_TRAFFIC_CLERK' ? 'selected' : ''; ?>>XD Traffic Clerk</option>
                            <option value="FG_TRAFFIC_CLERK" <?php echo $editUser['role'] === 'FG_TRAFFIC_CLERK' ? 'selected' : ''; ?>>FG Traffic Clerk</option>
                            <option value="SHIPPING_CLERK" <?php echo $editUser['role'] === 'SHIPPING_CLERK' ? 'selected' : ''; ?>>Shipping Clerk</option>
                            <option value="SUPERVISOR" <?php echo $editUser['role'] === 'SUPERVISOR' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="ADMIN" <?php echo $editUser['role'] === 'ADMIN' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
