<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        try {
            $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND role_id = 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $token = bin2hex(random_bytes(32));
                $_SESSION['username'] = $user['username'];
                $_SESSION['token'] = $token;

                $stmt = $conn->prepare("UPDATE users SET session_token = :session_token WHERE username = :username");
                $stmt->bindParam(':session_token', $token);
                $stmt->bindParam(':username', $username);
                $stmt->execute();

                setcookie("auth_token", $token, time() + (86400 * 30), "/"); // 86400 = 1 day

                header("Location: homepage.php");
                exit();
            } else {
                $message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "Please provide both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a, #363636);
            min-height: 100vh;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #2575fc;
            box-shadow: none;
        }
        .btn-primary {
            background: linear-gradient(45deg, #1a1a1a, #363636);
            border: none;
            padding: 12px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .admin-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #1a1a1a, #363636);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
        }
        .admin-icon i {
            font-size: 1.8rem;
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="admin-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h2 class="fw-bold">Admin Login</h2>
                            <p class="text-muted">Secure access for administrators</p>
                        </div>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form action="admin_login_supersecure_mega_strong_invisble_to_the_audience.php" method="post">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" pattern="^[A-Za-z0-9_.-]{1,30}$" required>
                                <label for="username">Username</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,32}$" required>
                                <label for="password">Password</label>
                                <div class="position-absolute end-0 top-50 translate-middle-y pe-3">
                                    <i class="fas fa-eye text-muted" id="togglePasswordIcon" style="cursor: pointer;"></i>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Login to Dashboard</button>

                            <div class="text-center">
                                <small class="text-muted">Restricted access only</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');
        togglePasswordIcon.addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>