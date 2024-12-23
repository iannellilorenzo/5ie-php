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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign In</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="height:100vh">
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Sign In</h3>
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($_GET['message']); ?>
                            </div>
                        <?php endif; ?>
                        <form action="sign_in.php" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>