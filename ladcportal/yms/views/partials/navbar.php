<?php
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo url('index.php'); ?>">
            <i class="bi bi-truck"></i> Penske Laredo YMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" href="<?php echo url('index.php'); ?>">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>

                <?php if (hasRole('GUARD')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="gateDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shield-check"></i> Guard Station
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo url('gate_east.php'); ?>">East Gate</a></li>
                            <li><a class="dropdown-item" href="<?php echo url('gate_west.php'); ?>">West Gate</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (canCreateMoves()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="yardDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-grid-3x3"></i> Yard
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo url('yard_east.php'); ?>">East Yard</a></li>
                            <li><a class="dropdown-item" href="<?php echo url('yard_west.php'); ?>">West Yard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo url('dock_board.php'); ?>">Dock Door Board</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $currentPage === 'waiting_list' || $currentPage === 'waiting_list_tv' ? 'active' : ''; ?>" href="#" id="waitingListDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-clipboard-check"></i> Waiting List
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Clerk Management</h6></li>
                            <li><a class="dropdown-item" href="<?php echo url('waiting_list.php?yard=WEST'); ?>">
                                <i class="bi bi-arrow-left-circle text-primary"></i> West Side
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo url('waiting_list.php?yard=EAST'); ?>">
                                <i class="bi bi-arrow-right-circle text-success"></i> East Side
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">TV Display</h6></li>
                            <li><a class="dropdown-item" href="<?php echo url('waiting_list_tv.php?yard=WEST'); ?>" target="_blank">
                                <i class="bi bi-tv"></i> West TV Display
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo url('waiting_list_tv.php?yard=EAST'); ?>" target="_blank">
                                <i class="bi bi-tv"></i> East TV Display
                            </a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'trailers' ? 'active' : ''; ?>" href="<?php echo url('trailers.php'); ?>">
                            <i class="bi bi-list-ul"></i> Trailers
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'move_entry' ? 'active' : ''; ?>" href="<?php echo url('move_entry.php'); ?>">
                            <i class="bi bi-arrow-left-right"></i> Create Move
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (canViewReports()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'reports' ? 'active' : ''; ?>" href="<?php echo url('reports.php'); ?>">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reports
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (canAccessAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo url('admin_users.php'); ?>">Manage Users</a></li>
                            <li><a class="dropdown-item" href="<?php echo url('admin_lookups.php'); ?>">Manage Lookups</a></li>
                            <li><a class="dropdown-item" href="<?php echo url('admin_audit.php'); ?>">Audit Log</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo e($currentUser['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?php echo e(getRoleDisplayName($currentUser['role'])); ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo url('logout.php'); ?>">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
