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

    // Load the user's hashed secret key
    $stmt = $conn->prepare("SELECT secret_key, email FROM users WHERE username = :username AND session_token = :session_token AND status_id = 1");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':session_token', $session_token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['secret_key'])) {
        header("Location: homepage.php");
        exit();
    }

    $hashed_secret_key = $user['secret_key'];
    $user_email = $user['email'];
} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['account_username']);
    $email = htmlspecialchars($_POST['account_email']);
    $password = htmlspecialchars($_POST['account_password']);
    $description = htmlspecialchars($_POST['account_description']);
    $secret_key = implode('', array_map('htmlspecialchars', $_POST['secret_key']));

    // Verify the secret key
    if (!password_verify($secret_key, $hashed_secret_key)) {
        $message = "Invalid secret key.";
    } else {
        // Generate a password if not provided
        if (empty($password)) {
            $password = bin2hex(random_bytes(8)); // Generate a random 16-character password
        }

        // Encrypt account details using OpenSSL
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted_password = openssl_encrypt($password, 'aes-256-cbc', $secret_key, 0, $iv);

        // Store the IV along with the encrypted data
        $encrypted_password = base64_encode($iv . $encrypted_password);

        try {
            $stmt = $conn->prepare("INSERT INTO accounts (username, email, password, description, user_reference) VALUES (:username, :email, :password, :description, :user_reference)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $encrypted_password);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':user_reference', $user_email);
            $stmt->execute();

            $message = "Account added successfully.";
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
    <title>Add Account - Lockr</title>
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
        .form-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6a11cb;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .form-icon:hover {
            color: #2575fc;
        }
        .account-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #6a11cb20, #2575fc20);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .account-icon i {
            font-size: 1.8rem;
            color: #6a11cb;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
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

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header .shield-icon {
            font-size: 2rem;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
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

    <div class="container py-5" style="margin-top: 4rem;">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="page-header">
                            <i class="fas fa-shield-alt shield-icon"></i>
                            <h3 class="fw-bold">Add New Account</h3>
                            <p class="text-muted">Securely store your account details</p>
                        </div>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-info alert-dismissible fade show">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="add_account.php" method="post">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" name="account_username" placeholder="Enter account username" required>
                                    <i class="fas fa-user form-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <div class="position-relative">
                                    <input type="email" class="form-control" name="account_email" placeholder="Enter account email" required>
                                    <i class="fas fa-envelope form-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="account_password" name="account_password" placeholder="Enter account password">
                                    <button class="btn btn-outline-gradient" type="button" onclick="togglePasswordVisibility()">
                                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                    </button>
                                    <button class="btn btn-outline-gradient" type="button" onclick="generatePassword()">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Leave blank to generate a random password</small>
                            </div>

                            <div class="password-strength bg-light mt-2"></div>

                            <div class="mb-4">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="account_description" rows="3" placeholder="Enter account description"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-center d-block">Secret Key</label>
                                <div class="d-flex justify-content-center gap-2">
                                    <?php for($i = 0; $i < 6; $i++): ?>
                                        <input type="password" class="otp-input" name="secret_key[]" maxlength="1" required>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Add Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('account_password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function generatePassword() {
            const lowercase = 'abcdefghijklmnopqrstuvwxyz';
            const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const numbers = '0123456789';
            const special = '@$!%*?&';
            
            let password = '';

            // Generate random length between 12-32
            const length = Math.floor(Math.random() * (32 - 12 + 1)) + 12;

            // Ensure at least one of each required character type
            password += lowercase[Math.floor(Math.random() * lowercase.length)];
            password += uppercase[Math.floor(Math.random() * uppercase.length)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += special[Math.floor(Math.random() * special.length)];
            
            // Fill remaining length with random characters
            const allChars = lowercase + uppercase + numbers + special;
            for (let i = password.length; i < 12; i++) {
                password += allChars[Math.floor(Math.random() * allChars.length)];
            }
            
            // Shuffle password
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            document.getElementById('account_password').value = password;
            document.getElementById('account_password').type = 'text';
            document.getElementById('togglePasswordIcon').classList.replace('fa-eye', 'fa-eye-slash');
        }

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