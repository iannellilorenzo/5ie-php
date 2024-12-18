<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: sign_in.php");
    exit();
}

$username = $_SESSION['username'];

try {
    $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT created_at, role_id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $account_creation_date = $user['created_at'];
        $role_id = $user['role_id'];
    } else {
        header("Location: sign_in.php?message=" . urlencode("User not found."));
        exit();
    }
} catch (PDOException $e) {
    header("Location: sign_in.php?message=" . urlencode("Error: " . $e->getMessage()));
    exit();
}

// Calculate the account age
$account_age = (new DateTime())->diff(new DateTime($account_creation_date))->days;

// Determine the welcome message
if ($account_age > 30) {
    $welcome_message = "Welcome back, $username!";
} else {
    $welcome_message = "We're happy you chose us, $username!";
}

// Determine the navbar brand class
$navbar_brand_class = $role_id == 1 ? 'navbar-brand-admin' : 'navbar-brand';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Homepage</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .welcome-message {
            font-size: 2rem;
            font-family: 'Arial', sans-serif;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        .navbar-brand-admin {
            color: gold !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="<?php echo $navbar_brand_class; ?>" href="#">
            <img src="assets/images/logo_favicon.png" width="30" height="30" class="d-inline-block align-top" alt="">
            <strong>Lo</strong>ckr
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center welcome-message">
                    <?php echo $welcome_message; ?>
                </div>
            </div>
        </div>
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
                        <p class="card-text">Create a new user account.</p>
                        <a href="sign_up.php" class="btn btn-primary">Add Account</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Other Service</h5>
                        <p class="card-text">Description of another service.</p>
                        <a href="#" class="btn btn-primary">Go to Service</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© 2023 Lockr. <a href="policy.php">Privacy Policy</a> | <a href="terms.php">Terms of Service</a></span>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>