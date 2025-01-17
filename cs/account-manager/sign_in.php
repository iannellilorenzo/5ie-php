<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        try {
            $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND status_id = 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($user['role_id'] == 1) { // Admin role
                    header("Location: admin_login_supersecure_mega_strong_invisble_to_the_audience.php?message=" . urlencode("You have been redirected because you are an admin."));
                    exit();
                } else if (password_verify($password, $user['password_hash'])) {
                    $token = bin2hex(random_bytes(32));
                    $_SESSION['username'] = $username;
                    $_SESSION['token'] = $token;

                    $stmt = $conn->prepare("UPDATE users SET session_token = :session_token WHERE username = :username");
                    $stmt->bindParam(':session_token', $token);
                    $stmt->bindParam(':username', $username);
                    $stmt->execute();

                    setcookie("auth_token", $token, time() + (86400 * 30), "/"); // 86400 = 1 day

                    header("Location: homepage.php");
                    exit();
                } else {
                    header("Location: sign_in.php?message=" . urlencode("Invalid username or password."));
                    exit();
                }
            } else {
                header("Location: sign_in.php?message=" . urlencode("Invalid username, password, or unverified email."));
                exit();
            }
        } catch (PDOException $e) {
            header("Location: sign_in.php?message=" . urlencode("Error: " . $e->getMessage()));
            exit();
        }
    } else {
        header("Location: sign_in.php?message=" . urlencode("Please provide both username and password."));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - Lockr</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #6a11cb;
            box-shadow: none;
        }
        .btn-primary {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            border: none;
            padding: 12px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106,17,203,0.4);
        }
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #eee;
            color: #666;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .social-btn:hover {
            transform: translateY(-2px);
            color: #6a11cb;
            border-color: #6a11cb;
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/images/logo_favicon.png" height="60" class="mb-3">
                            <h2 class="fw-bold">Welcome Back</h2>
                            <p class="text-muted">Sign in to access your vault</p>
                        </div>

                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($_GET['message']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="social-login mb-4">
                            <a href="#" class="social-btn"><i class="fab fa-google"></i></a>
                            <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-btn"><i class="fab fa-apple"></i></a>
                        </div>

                        <div class="text-center mb-4">
                            <span class="text-muted">or sign in with email</span>
                        </div>

                        <form action="sign_in.php" method="post">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" pattern="^[A-Za-z0-9_.-]{1,30}$" required>
                                <label for="username">Username</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,32}$" required>
                                <label for="password">Password</label>
                                <div class="position-absolute end-0 top-50 translate-middle-y pe-3">
                                    <i class="fas fa-eye text-muted" id="togglePasswordIcon" style="cursor: pointer;" onclick="togglePasswordVisibility()"></i>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Sign In</button>

                            <div class="text-center">
                                <span class="text-muted">Don't have an account?</span>
                                <a href="sign_up.php" class="text-decoration-none ms-1">Sign Up</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            togglePasswordIcon.classList.toggle('fa-eye');
            togglePasswordIcon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html>