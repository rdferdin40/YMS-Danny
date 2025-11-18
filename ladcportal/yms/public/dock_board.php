<?php
/**
 * Dock Door Board - Tile View
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Dock Door Board - Penske Laredo YMS';

// Get dock door statuses
$dockDoors = Trailer::getDockDoorStatuses();

require __DIR__ . '/../views/partials/header.php';
require __DIR__ . '/../views/partials/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-door-open"></i> Dock Door Board
                <span class="badge bg-info"><?php echo count($dockDoors); ?> / 52 Occupied</span>
            </h2>
        </div>
    </div>

    <!-- West Dock Doors (1-30) -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-building"></i> West Dock Doors (1-30)</h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <?php for ($door = 1; $door <= 30; $door++):
                    $trailer = $dockDoors[$door] ?? null;
                    $tileClass = 'dock-tile ';
                    if ($trailer) {
                        switch ($trailer['load_status']) {
                            case 'LOADED':
                                $tileClass .= 'loaded';
                                break;
                            case 'EMPTY':
                                $tileClass .= 'empty-trailer';
                                break;
                            case 'LIVE_LOAD':
                                $tileClass .= 'live-load';
                                break;
                            default:
                                $tileClass .= 'empty';
                        }
                    } else {
                        $tileClass .= 'empty';
                    }
                ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <div class="<?php echo $tileClass; ?>"
                             onclick="<?php echo $trailer ? "window.location.href='trailer_detail.php?id={$trailer['id']}'" : ""; ?>">
                            <div class="dock-number">Door <?php echo $door; ?></div>
                            <?php if ($trailer): ?>
                                <div class="trailer-number"><?php echo e($trailer['trailer_number']); ?></div>
                                <small>
                                    <?php echo e($trailer['load_status']); ?><br>
                                    <?php echo formatDateTime($trailer['time_in'], 'M d H:i'); ?>
                                </small>
                            <?php else: ?>
                                <div class="text-muted">EMPTY</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- East Dock Doors (31-52) -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-building"></i> East Dock Doors (31-52)</h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <?php for ($door = 31; $door <= 52; $door++):
                    $trailer = $dockDoors[$door] ?? null;
                    $tileClass = 'dock-tile ';
                    if ($trailer) {
                        switch ($trailer['load_status']) {
                            case 'LOADED':
                                $tileClass .= 'loaded';
                                break;
                            case 'EMPTY':
                                $tileClass .= 'empty-trailer';
                                break;
                            case 'LIVE_LOAD':
                                $tileClass .= 'live-load';
                                break;
                            default:
                                $tileClass .= 'empty';
                        }
                    } else {
                        $tileClass .= 'empty';
                    }
                ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <div class="<?php echo $tileClass; ?>"
                             onclick="<?php echo $trailer ? "window.location.href='trailer_detail.php?id={$trailer['id']}'" : ""; ?>">
                            <div class="dock-number">Door <?php echo $door; ?></div>
                            <?php if ($trailer): ?>
                                <div class="trailer-number"><?php echo e($trailer['trailer_number']); ?></div>
                                <small>
                                    <?php echo e($trailer['load_status']); ?><br>
                                    <?php echo formatDateTime($trailer['time_in'], 'M d H:i'); ?>
                                </small>
                            <?php else: ?>
                                <div class="text-muted">EMPTY</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Legend</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="dock-tile empty" style="display: inline-block; width: 100px; text-align: center;">
                        EMPTY
                    </div>
                    <span class="ms-2">Empty Door</span>
                </div>
                <div class="col-md-3">
                    <div class="dock-tile loaded" style="display: inline-block; width: 100px; text-align: center;">
                        LOADED
                    </div>
                    <span class="ms-2">Loaded Trailer</span>
                </div>
                <div class="col-md-3">
                    <div class="dock-tile empty-trailer" style="display: inline-block; width: 100px; text-align: center;">
                        EMPTY
                    </div>
                    <span class="ms-2">Empty Trailer</span>
                </div>
                <div class="col-md-3">
                    <div class="dock-tile live-load" style="display: inline-block; width: 100px; text-align: center;">
                        LIVE
                    </div>
                    <span class="ms-2">Live Load</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
