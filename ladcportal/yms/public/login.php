<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/User.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$rateLimitError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check rate limit (5 attempts per 5 minutes per IP)
    $rateLimitKey = 'login_' . getUserIP();
    if (!checkRateLimit($rateLimitKey, 5, 300)) {
        $resetTime = getRateLimitResetTime($rateLimitKey);
        $minutes = ceil($resetTime / 60);
        $error = "Too many login attempts. Please try again in $minutes minute(s).";
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $user = User::authenticate($username, $password);

        if ($user) {
            // Login successful - reset rate limit
            resetRateLimit($rateLimitKey);

            // Login user (regenerates session ID)
            loginUser($user);

            // Log audit
            logAudit('USER_LOGIN', 'USER', $user['id'], null, ['username' => $username]);

            // Validate and sanitize redirect parameter (prevent open redirect)
            $redirectTo = $_GET['redirect'] ?? 'index.php';
            // Only allow internal redirects (no external URLs)
            if (preg_match('/^[a-zA-Z0-9_\-]+\.php(\?.*)?$/', $redirectTo)) {
                header('Location: ' . $redirectTo);
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            // Log failed login attempt
            logAudit('USER_LOGIN_FAILED', 'USER', null, null, ['username' => $username, 'ip' => getUserIP()]);
            $error = 'Invalid username or password';
        }
    }
}

$pageTitle = 'Login - Penske Laredo YMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --penske-blue: #003DA5;
            --penske-yellow: #FFCD00;
        }

        body {
            background: linear-gradient(135deg, var(--penske-blue) 0%, #0052cc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
        }

        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            background-color: var(--penske-yellow);
            color: var(--penske-blue);
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .login-body {
            padding: 40px;
        }

        .btn-login {
            background-color: var(--penske-blue);
            color: white;
            padding: 12px;
            font-size: 16px;
        }

        .btn-login:hover {
            background-color: #002d7a;
            color: white;
        }

        .form-control:focus {
            border-color: var(--penske-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 61, 165, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <h2 class="mb-0">
                    <i class="bi bi-truck"></i><br>
                    Penske Logistics
                </h2>
                <p class="mb-0 mt-2">Laredo Distribution Center<br>Yard Management System</p>
            </div>
            <div class="login-body bg-white">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?php echo e($_POST['username'] ?? ''); ?>"
                                   required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>

                <hr class="my-4">

                <div class="text-center text-muted small">
                    <p class="mb-1"><strong>Demo Credentials:</strong></p>
                    <p class="mb-0">
                        admin / password123 (Administrator)<br>
                        guard1 / password123 (Guard)<br>
                        xd_clerk1 / password123 (XD Clerk)
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-3 text-white">
            <small>&copy; <?php echo date('Y'); ?> Penske Logistics - Phase 1 YMS</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
