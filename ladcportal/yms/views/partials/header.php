<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Penske Laredo YMS'; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --penske-blue: #003DA5;
            --penske-yellow: #FFCD00;
        }

        body {
            padding-top: 56px;
        }

        .navbar {
            background-color: var(--penske-blue) !important;
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--penske-yellow) !important;
        }

        .navbar-nav .nav-link {
            color: white !important;
        }

        .navbar-nav .nav-link:hover {
            color: var(--penske-yellow) !important;
        }

        .btn-penske {
            background-color: var(--penske-yellow);
            color: var(--penske-blue);
            border: none;
        }

        .btn-penske:hover {
            background-color: #e6b800;
            color: var(--penske-blue);
        }

        .dock-tile {
            border: 2px solid #ddd;
            padding: 15px;
            margin: 5px;
            min-height: 120px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .dock-tile:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }

        .dock-tile.empty {
            background-color: #e9ecef;
        }

        .dock-tile.loaded {
            background-color: #0d6efd;
            color: white;
        }

        .dock-tile.empty-trailer {
            background-color: #ffc107;
        }

        .dock-tile.live-load {
            background-color: #dc3545;
            color: white;
        }

        .dock-number {
            font-size: 24px;
            font-weight: bold;
        }

        .trailer-number {
            font-size: 18px;
            font-weight: 600;
        }

        .card-stat {
            border-left: 4px solid var(--penske-blue);
        }

        .table-responsive {
            max-height: 600px;
        }

        .alert-flash {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <?php
    // Display flash messages
    $flash = getFlashMessage();
    if ($flash):
        $alertType = match($flash['type']) {
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'success'
        };
    ?>
    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show alert-flash" role="alert">
        <?php echo e($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
