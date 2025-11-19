<?php
/**
 * Waiting List - TV Display Mode
 * Full-screen, auto-refresh, read-only display for traffic clerks
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/Trailer.php';

requireLogin();

// Determine which yard to display
$yard = $_GET['yard'] ?? 'WEST';
$yard = strtoupper($yard);

if (!in_array($yard, ['WEST', 'EAST'])) {
    $yard = 'WEST';
}

$yardArea = $yard . '_YARD';
$yardName = $yard === 'WEST' ? 'West Side' : 'East Side';
$yardColor = $yard === 'WEST' ? 'primary' : 'success';

// Get waiting list
$waitingList = Trailer::getWaitingList($yardArea);
$stats = Trailer::getWaitingListStats($yardArea);

$pageTitle = "$yardName Waiting List - TV Display";

// Helper function to format wait time
function formatWaitTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($hours > 0) {
        return "{$hours}h {$mins}m";
    }
    return "{$mins}m";
}

// Helper function to get priority badge class
function getPriorityClass($priority) {
    switch ($priority) {
        case 'HIGH':
            return 'danger';
        case 'LOW':
            return 'secondary';
        default:
            return 'primary';
    }
}

// Helper function to get load status badge class
function getLoadStatusClass($status) {
    switch ($status) {
        case 'LOADED':
            return 'warning';
        case 'EMPTY':
            return 'info';
        case 'LIVE_LOAD':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: 'Arial', sans-serif;
            overflow: hidden;
        }

        .tv-header {
            background: linear-gradient(135deg, #<?php echo $yard === 'WEST' ? '0d6efd' : '198754'; ?> 0%, #<?php echo $yard === 'WEST' ? '0a58ca' : '146c43'; ?> 100%);
            padding: 20px 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }

        .tv-title {
            font-size: 3.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin: 0;
        }

        .stats-bar {
            background-color: #1a1a1a;
            padding: 15px 40px;
            border-bottom: 3px solid #<?php echo $yard === 'WEST' ? '0d6efd' : '198754'; ?>;
        }

        .stat-item {
            font-size: 1.4rem;
            padding: 5px 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-right: 8px;
        }

        .waiting-table {
            margin: 20px 40px;
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }

        .waiting-table table {
            width: 100%;
            font-size: 1.8rem;
        }

        .waiting-table thead {
            background-color: #2d2d2d;
        }

        .waiting-table th {
            padding: 20px;
            font-weight: bold;
            border-bottom: 3px solid #<?php echo $yard === 'WEST' ? '0d6efd' : '198754'; ?>;
        }

        .waiting-table td {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
        }

        .waiting-table tbody tr {
            transition: background-color 0.3s;
        }

        .waiting-table tbody tr:hover {
            background-color: #2d2d2d;
        }

        .priority-HIGH {
            border-left: 6px solid #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .priority-NORMAL {
            border-left: 6px solid #0d6efd;
        }

        .priority-LOW {
            border-left: 6px solid #6c757d;
        }

        .badge {
            font-size: 1.2rem;
            padding: 8px 15px;
        }

        .trailer-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 2rem;
        }

        .wait-time {
            font-size: 1.6rem;
            font-weight: bold;
        }

        .wait-time.long-wait {
            color: #ffc107;
        }

        .wait-time.very-long-wait {
            color: #dc3545;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .no-trailers {
            text-align: center;
            padding: 60px;
            font-size: 2.5rem;
            color: #6c757d;
        }

        .refresh-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 1rem;
        }

        .current-time {
            position: fixed;
            top: 20px;
            right: 40px;
            font-size: 2rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="tv-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="tv-title">
                <i class="bi bi-clipboard-check"></i>
                <?php echo e($yardName); ?> Waiting List
            </h1>
            <div class="current-time" id="currentTime"></div>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="row text-center">
            <div class="col stat-item">
                <span class="stat-number text-<?php echo $yardColor; ?>"><?php echo $stats['total_waiting'] ?? 0; ?></span>
                <span>Total Waiting</span>
            </div>
            <div class="col stat-item">
                <span class="stat-number text-danger"><?php echo $stats['high_priority_count'] ?? 0; ?></span>
                <span>High Priority</span>
            </div>
            <div class="col stat-item">
                <span class="stat-number text-warning"><?php echo $stats['loaded_count'] ?? 0; ?></span>
                <span>Loaded</span>
            </div>
            <div class="col stat-item">
                <span class="stat-number text-info"><?php echo $stats['empty_count'] ?? 0; ?></span>
                <span>Empty</span>
            </div>
            <div class="col stat-item">
                <span class="stat-number text-light"><?php echo isset($stats['avg_wait_minutes']) ? formatWaitTime($stats['avg_wait_minutes']) : '0m'; ?></span>
                <span>Avg Wait</span>
            </div>
        </div>
    </div>

    <!-- Waiting List Table -->
    <div class="waiting-table">
        <?php if (empty($waitingList)): ?>
            <div class="no-trailers">
                <i class="bi bi-inbox" style="font-size: 4rem; display: block; margin-bottom: 20px;"></i>
                No trailers waiting
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th width="15%">Priority</th>
                        <th width="20%">Trailer</th>
                        <th width="20%">Carrier</th>
                        <th width="15%">Status</th>
                        <th width="15%">Wait Time</th>
                        <th width="15%">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waitingList as $trailer):
                        $waitClass = '';
                        if ($trailer['wait_hours'] >= 3) {
                            $waitClass = 'very-long-wait';
                        } elseif ($trailer['wait_hours'] >= 1) {
                            $waitClass = 'long-wait';
                        }
                    ?>
                        <tr class="priority-<?php echo e($trailer['priority']); ?>">
                            <td>
                                <span class="badge bg-<?php echo getPriorityClass($trailer['priority']); ?>">
                                    <?php echo e($trailer['priority']); ?>
                                </span>
                            </td>
                            <td class="trailer-number"><?php echo e($trailer['trailer_number']); ?></td>
                            <td><?php echo e($trailer['carrier'] ?: '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getLoadStatusClass($trailer['load_status']); ?>">
                                    <?php echo e($trailer['load_status']); ?>
                                </span>
                            </td>
                            <td class="wait-time <?php echo $waitClass; ?>">
                                <i class="bi bi-clock"></i>
                                <?php echo formatWaitTime($trailer['wait_minutes']); ?>
                            </td>
                            <td>
                                <?php if ($trailer['waiting_notes']): ?>
                                    <small><?php echo e($trailer['waiting_notes']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Refresh Indicator -->
    <div class="refresh-indicator">
        <i class="bi bi-arrow-clockwise"></i>
        Auto-refresh: 30s
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('currentTime').textContent = timeString;
        }

        updateTime();
        setInterval(updateTime, 1000);

        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
