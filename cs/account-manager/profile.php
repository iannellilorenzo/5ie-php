<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['token'])) {
    header("Location: sign_in.php");
    exit();
}

$username = $_SESSION['username'];
$session_token = $_SESSION['token'];

try {
    $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username AND session_token = :session_token AND status_id = 1");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':session_token', $session_token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $email = htmlspecialchars($user['email']);
        $phone_number = htmlspecialchars($user['phone_number']);
        $first_name = htmlspecialchars($user['first_name']);
        $last_name = htmlspecialchars($user['last_name']);
    } else {
        $message = "User not found or unverified.";
    }
} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        try {
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_ARGON2ID);
                $stmt = $conn->prepare("UPDATE users SET email = :email, password_hash = :password_hash, phone_number = :phone_number, first_name = :first_name, last_name = :last_name WHERE username = :username");
                $stmt->bindParam(':password_hash', $password_hash);
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = :email, phone_number = :phone_number, first_name = :first_name, last_name = :last_name WHERE username = :username");
            }

            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $message = "Profile updated successfully.";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - Lockr</title>
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
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            background: rgba(255, 255, 255, 0.95);
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
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
            padding: 12px 25px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106,17,203,0.4);
        }
        .btn-secondary {
            background: linear-gradient(45deg, #3a3a3a, #2c2c2c);
            border: none;
            color: white;
        }
        .btn-secondary:hover {
            background: linear-gradient(45deg, #2c2c2c, #1f1f1f);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .input-group-text {
            background: transparent;
            border: 2px solid #eee;
            border-left: none;
        }
        .form-control:focus + .input-group-text {
            border-color: #6a11cb;
        }
        .btn-outline-gradient {
            border: 2px solid #eee;
            color: #6a11cb;
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(45deg, #6a11cb20, #2575fc20);
            border-color: #6a11cb;
            color: #6a11cb;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="homepage.php">
                <img src="assets/images/logo_favicon.png" height="30" class="me-2">
                <span class="fw-bold">Lockr</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_accounts.php">
                            <i class="fas fa-key me-1"></i> View Accounts
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
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-gradient p-3 d-inline-block mb-3" style="background: linear-gradient(45deg, #6a11cb20, #2575fc20);">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                            <h3 class="fw-bold">Profile Settings</h3>
                            <p class="text-muted">Manage your account information</p>
                        </div>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-info alert-dismissible fade show">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="profile.php" method="post">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($username); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" pattern="^[A-Za-z0-9._%+-]{1,60}@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" placeholder="+1 123 456 7890" pattern="^\+?(\d{1,3})?[-.\s]?(\(?\d{1,4}\)?)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}[-.\s]?\d{1,9}$" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" pattern="^[A-Za-z' -]{1,35}$" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" pattern="^[A-Za-z' -]{1,35}$" required>
                                </div>
                            </div>

                            <button type="button" class="btn btn-secondary w-100 mb-3" id="changePasswordButton">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>

                            <div id="passwordGroup" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="password" placeholder="New Password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,32}$">
                                        <button class="btn btn-outline-gradient" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-requirements mt-2">
                                        <small class="text-muted d-block mb-1">Password must contain:</small>
                                        <small class="text-muted d-block"><i class="fas fa-check-circle me-1"></i> 8-32 characters</small>
                                        <small class="text-muted d-block"><i class="fas fa-check-circle me-1"></i> At least one uppercase letter (A-Z)</small>
                                        <small class="text-muted d-block"><i class="fas fa-check-circle me-1"></i> At least one lowercase letter (a-z)</small>
                                        <small class="text-muted d-block"><i class="fas fa-check-circle me-1"></i> At least one number (0-9)</small>
                                        <small class="text-muted d-block"><i class="fas fa-check-circle me-1"></i> At least one special character (@$!%*?&)</small>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,32}$">
                                        <button class="btn btn-outline-gradient" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('changePasswordButton').addEventListener('click', function() {
            const passwordGroup = document.getElementById('passwordGroup');
            passwordGroup.style.display = passwordGroup.style.display === 'none' ? 'block' : 'none';
        });

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>