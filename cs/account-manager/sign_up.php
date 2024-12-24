<?php
require_once 'config.php';
session_start();

// Read the keys from the file
$cloudflare_keys = file_get_contents(__DIR__ . '/cloudflare_keys.key');
list($turnstile_site_key, $turnstile_secret_key) = explode('~', trim($cloudflare_keys));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turnstile_response = $_POST['cf-turnstile-response'];

    // Use cURL to make the HTTP request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $turnstile_secret_key,
        'response' => $turnstile_response
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $response_keys = json_decode($response, true);

    if (intval($response_keys["success"]) !== 1) {
        $message = "Please complete the CAPTCHA.";
    } else {
        if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email']) && isset($_POST['phone_number'])) {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_ARGON2ID);
            $email = $_POST['email'];
            $phone_number = $_POST['phone_number'];
            $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : null;
            $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : null;
            $status_id = 2; // Inactive status until email is verified
            $role_id = 2; // User role
            $session_token = bin2hex(random_bytes(32)); // Session token
            $verification_token = bin2hex(random_bytes(32)); // Email verification token
            $hashed_verification_token = password_hash($verification_token, PASSWORD_ARGON2ID); // Hash the verification token

            try {
                $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $conn->prepare("INSERT INTO users (email, username, first_name, last_name, password_hash, phone_number, status_id, role_id, session_token, verification_token) VALUES (:email, :username, :first_name, :last_name, :password_hash, :phone_number, :status_id, :role_id, :session_token, :verification_token)");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':password_hash', $password);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':status_id', $status_id);
                $stmt->bindParam(':role_id', $role_id);
                $stmt->bindParam(':session_token', $session_token);
                $stmt->bindParam(':verification_token', $hashed_verification_token);
                $stmt->execute();

                $_SESSION['username'] = $username;
                $_SESSION['token'] = $session_token;

                setcookie("auth_token", $session_token, time() + (86400 * 30), "/"); // 86400 = 1 day

                // Send verification email
                $verification_link = "http://localhost/5ie-php/cs/account-manager/verify_email.php?token=$verification_token";
                $subject = "Email Verification - Lockr Account Activation";
                $message = "Please click the following link to verify your email: $verification_link";
                $headers = "From: iannelli.lorenzo.studente@itispaleocapa.it";

                mail($email, $subject, $message, $headers);

                header("Location: homepage.php");
                exit();
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
            }
        } else {
            $message = "Please provide all required fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign Up</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="height:100vh">
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center">Sign Up</h3>
                        <?php if (isset($message)): ?>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        <form action="sign_up.php" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text" onclick="togglePasswordVisibility()">
                                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" placeholder="+1 123 456 7890" pattern="^\+?(\d{1,3})?[-.\s]?(\(?\d{1,4}\)?)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}[-.\s]?\d{1,9}$" required>
                            </div>
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter first name">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter last name">
                            </div>
                            <div class="cf-turnstile" data-sitekey="<?php echo $turnstile_site_key; ?>"></div>
                            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="sign_in.php">Already have an account? Sign In</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            togglePasswordIcon.classList.toggle('fa-eye');
            togglePasswordIcon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html>