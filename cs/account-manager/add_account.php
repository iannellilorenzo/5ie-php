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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Account</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <style>
        .otp-input {
            width: 2rem;
            height: 2rem;
            text-align: center;
            font-size: 1.5rem;
            margin: 0.2rem;
        }
        .container {
            padding-bottom: 2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Lockr</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="homepage.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center align-items-center" style="height:80vh">
            <div class="col-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Add Account</h3>
                        <?php if (isset($message)): ?>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        <form action="add_account.php" method="post">
                            <div class="form-group">
                                <label for="account_username">Account Username</label>
                                <input type="text" class="form-control" id="account_username" name="account_username" placeholder="Enter account username" patter="^[A-Za-z0-9_.-]{1,30}$" required>
                            </div>
                            <div class="form-group">
                                <label for="account_email">Account Email</label>
                                <input type="email" class="form-control" id="account_email" name="account_email" placeholder="Enter account email" pattern="^[A-Za-z0-9._%+-]{1,60}@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$" required>
                            </div>
                            <div class="form-group">
                                <label for="account_password">Account Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="account_password" name="account_password" placeholder="Enter account password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,32}$">
                                    <div class="input-group-append">
                                        <span class="input-group-text" onclick="togglePasswordVisibility()">
                                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                        </span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Leave blank to generate a random password.</small>
                            </div>
                            <div class="form-group">
                                <label for="account_description">Account Description</label>
                                <textarea class="form-control" id="account_description" name="account_description" placeholder="Enter account description" rows="3"></textarea>
                            </div>
                            <hr>
                            <div class="form-group text-center">
                                <label for="secret_key">Secret Key</label>
                                <div class="form-group text-center">
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Add Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('account_password');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            togglePasswordIcon.classList.toggle('fa-eye');
            togglePasswordIcon.classList.toggle('fa-eye-slash');
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