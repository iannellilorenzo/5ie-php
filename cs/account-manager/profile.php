<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: sign_in.php");
    exit();
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $new_phone_number = $_POST['phone_number'];
    $new_first_name = $_POST['first_name'];
    $new_last_name = $_POST['last_name'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        try {
            $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_ARGON2ID);
                $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, password_hash = :password_hash, phone_number = :phone_number, first_name = :first_name, last_name = :last_name WHERE username = :current_username");
                $stmt->bindParam(':password_hash', $password_hash);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, phone_number = :phone_number, first_name = :first_name, last_name = :last_name WHERE username = :current_username");
            }

            $stmt->bindParam(':username', $new_username);
            $stmt->bindParam(':email', $new_email);
            $stmt->bindParam(':phone_number', $new_phone_number);
            $stmt->bindParam(':first_name', $new_first_name);
            $stmt->bindParam(':last_name', $new_last_name);
            $stmt->bindParam(':current_username', $username);
            $stmt->execute();

            $_SESSION['username'] = $new_username;
            $username = $new_username;
            $message = "Profile updated successfully.";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
} else {
    try {
        $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $new_username = $user['username'];
        $new_email = $user['email'];
        $new_phone_number = $user['phone_number'];
        $new_first_name = $user['first_name'];
        $new_last_name = $user['last_name'];
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center align-items-center" style="height:100vh">
            <div class="col-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Profile</h3>
                        <?php if (isset($message)): ?>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        <form action="profile.php" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($new_username); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($new_email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($new_phone_number); ?>" placeholder="+1 123 456 7890" pattern="^\+?(\d{1,3})?[-.\s]?(\(?\d{1,4}\)?)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}[-.\s]?\d{1,9}$" required>
                            </div>
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($new_first_name); ?>" placeholder="Enter your first name here" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($new_last_name); ?>" placeholder="Enter your last name here" required>
                            </div>
                            <button type="button" class="btn btn-secondary btn-block mb-3" id="changePasswordButton">Change Password</button>
                            <div class="form-group" id="passwordGroup" style="display: none;">
                                <label for="password">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="New Password" onpaste="return false;">
                            </div>
                            <div class="form-group" id="confirmPasswordGroup" style="display: none;">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" onpaste="return false;">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Update Profile</button>
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
        document.getElementById('changePasswordButton').addEventListener('click', function () {
            const passwordGroup = document.getElementById('passwordGroup');
            const confirmPasswordGroup = document.getElementById('confirmPasswordGroup');
            if (passwordGroup.style.display === 'none') {
                passwordGroup.style.display = 'block';
                confirmPasswordGroup.style.display = 'block';
            } else {
                passwordGroup.style.display = 'none';
                confirmPasswordGroup.style.display = 'none';
            }
        });
    </script>
</body>
</html>