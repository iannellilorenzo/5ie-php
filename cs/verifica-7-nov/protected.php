<?php
session_start();
require_once 'conf.php';

if (!isset($_SESSION['user']) && (!isset($_COOKIE['remember']) || $_COOKIE['remember'] != 'yes')) {
    header('Location: index.php');
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $conn = null;
}

if ($conn == null) {
    die("An error occurred while connecting to the database");
}

if (isset($_COOKIE["remember"]) && $_COOKIE["remember"] == "yes") {
    $rem = true;
} else {
    $rem = false;
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
                        <p>You have entered this page <?php echo $_COOKIE['accesses']?> times</p>
                        <p>Remember me - You selected: <?php echo $rem ? 'Yes' : 'No'; ?></p>
                        <p>Connection status to the database: OK</p>
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