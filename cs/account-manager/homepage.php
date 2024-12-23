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

    if ($user && empty($user['secret_key'])) {
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
            $stmt = $conn->prepare("UPDATE users SET secret_key = :secret_key WHERE username = :username");
            $stmt->bindParam(':secret_key', $secret_key);
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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Homepage</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
        .greeting {
            padding-bottom: 1rem;
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
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php if (!$showSecretKeyForm): ?>
            <h2 class="text-center mt-4 greeting">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($showSecretKeyForm): ?>
            <div class="row justify-content-center align-items-center" style="height:80vh">
                <div class="col-6">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title text-center">Complete your Secret Key Setup</h3>
                            <p class="text-center">This secret key will be used to encrypt your passwords, don't forget it. <br> Please enter a 6-digit PIN.</p>
                            <form action="homepage.php" method="post" id="secretKeyForm">
                                <div class="form-group text-center">
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                    <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Set Secret Key</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">View All Accounts</h5>
                            <p class="card-text">See a list of all user accounts.</p>
                            <a href="view_accounts.php" class="btn btn-primary">View Accounts</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Add New Account</h5>
                            <p class="card-text">Let us store your account.</p>
                            <a href="sign_up.php" class="btn btn-primary">Add Account</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Other Service</h5>
                            <p class="card-text">Lorem ipsum dolor sit amet.</p>
                            <a href="#" class="btn btn-primary">Go to Service</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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