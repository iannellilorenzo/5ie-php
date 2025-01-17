<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) && !isset($_COOKIE['auth_token'])) {
    header("Location: sign_in.php");
    exit();
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

try {
    $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$username && isset($_COOKIE['auth_token'])) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE session_token = :session_token AND status_id = 1");
        $stmt->bindParam(':session_token', $_COOKIE['auth_token']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $username = $user['username'];
            $_SESSION['username'] = $username;
        } else {
            header("Location: sign_in.php");
            exit();
        }
    }

    $stmt = $conn->prepare("SELECT secret_key FROM users WHERE username = :username AND status_id = 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['secret_key'] === null) {
        $showSecretKeyForm = true;
    } else {
        $showSecretKeyForm = false;
    }
} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['secret_key'])) {
    $secret_key = implode('', array_map('htmlspecialchars', $_POST['secret_key']));

    if (preg_match('/^\d{6}$/', $secret_key)) {
        try {
            $hashed_secret_key = password_hash($secret_key, PASSWORD_ARGON2ID);
            $stmt = $conn->prepare("UPDATE users SET secret_key = :secret_key WHERE username = :username");
            $stmt->bindParam(':secret_key', $hashed_secret_key);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $showSecretKeyForm = false;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "Secret key must be a 6-digit PIN.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Homepage - Lockr</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(135deg, #f6f9fc, #edf1f9, #e9ecf5);
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(to right, rgba(106, 17, 203, 0.9), rgba(37, 117, 252, 0.9)) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .navbar-light .navbar-brand,
        .navbar-light .nav-link {
            color: white !important;
        }
        .navbar-light .navbar-toggler {
            border-color: rgba(255,255,255,0.5);
        }
        .navbar-light .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.7%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #6a11cb20, #2575fc20);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .feature-icon i {
            font-size: 1.5rem;
            color: #6a11cb;
        }
        .otp-input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.5rem;
            margin: 0.3rem;
            border: 2px solid #eee;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .otp-input:focus {
            border-color: #6a11cb;
            box-shadow: none;
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106,17,203,0.4);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/images/logo_favicon.png" height="30" class="me-2">
                <span class="fw-bold">Lockr</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 6rem;">
        <?php if (!$showSecretKeyForm): ?>
            <h2 class="text-center mb-5">Welcome back, <span class="text-primary"><?php echo htmlspecialchars($username); ?></span>!</h2>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($showSecretKeyForm): ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card p-4">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-key"></i>
                                </div>
                                <h3>Complete Your Security Setup</h3>
                                <p class="text-muted">Enter a 6-digit PIN that will be used to encrypt your passwords.</p>
                            </div>
                            <form action="homepage.php" method="post" id="secretKeyForm">
                                <div class="d-flex justify-content-center mb-4">
                                    <?php for($i = 0; $i < 6; $i++): ?>
                                        <input type="password" class="otp-input form-control" maxlength="1" name="secret_key[]" required>
                                    <?php endfor; ?>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Set Secret Key</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <h5 class="card-title">View All Accounts</h5>
                            <p class="card-text text-muted mb-4">View and manage your stored accounts.</p>
                            <a href="view_accounts.php" class="btn btn-primary w-100">View Accounts</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h5 class="card-title">Add New Account</h5>
                            <p class="card-text text-muted mb-4">Securely store a new account in your vault.</p>
                            <a href="add_account.php" class="btn btn-primary w-100">Add Account</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="card-title">Security Check</h5>
                            <p class="card-text text-muted mb-4">Analyze and improve your password security.</p>
                            <a href="#" class="btn btn-primary w-100">Run Check</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.otp-input').forEach((input, index, inputs) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>