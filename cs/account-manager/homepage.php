<?php
require_once 'config.php';

$username = "JohnDoe"; // Replace with actual username from session or database
$account_creation_date = "2023-01-01"; // Replace with actual account creation date from database

// Calculate the account age
$account_age = (new DateTime())->diff(new DateTime($account_creation_date))->days;

// Determine the welcome message
if ($account_age > 30) {
    $welcome_message = "Welcome back, $username!";
} else {
    $welcome_message = "We're happy you chose us, $username!";
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
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Account Manager</a>
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
                <div class="alert alert-info text-center">
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
            <span class="text-muted">© 2023 Account Manager. <a href="policy.php">Privacy Policy</a> | <a href="terms.php">Terms of Service</a></span>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>