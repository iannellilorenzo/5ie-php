<?php
session_start();
require_once 'conf.php';
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if ($conn !== null) {
    echo "Connection established";
    die();
} else {
    echo "Connection failed";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">
    <title>Protected Page</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Protected Page</h3>
                    </div>
                    <div class="card-body">
                        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</p>
                        <p>Your interest is: <?php echo htmlspecialchars($_COOKIE['interest']); ?></p>
                        <p>You have entered this page <?php echo $_COOKIE['accesses']?> times</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</body>
</html>