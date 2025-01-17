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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up - Lockr</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
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
            padding: 12px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106,17,203,0.4);
        }
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #eee;
            color: #666;
            transition: all 0.3s ease;
        }
        .social-btn:hover {
            transform: translateY(-2px);
            color: #6a11cb;
            border-color: #6a11cb;
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/images/logo_favicon.png" height="60" class="mb-3">
                            <h2 class="fw-bold">Create Account</h2>
                            <p class="text-muted">Join the most secure password manager</p>
                        </div>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-info">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="social-login mb-4">
                            <a href="#" class="social-btn text-decoration-none"><i class="fab fa-google"></i></a>
                            <a href="#" class="social-btn text-decoration-none"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-btn text-decoration-none"><i class="fab fa-apple"></i></a>
                        </div>

                        <div class="text-center mb-4">
                            <span class="text-muted">or sign up with email</span>
                        </div>

                        <form action="sign_up.php" method="post">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" pattern="^[A-Za-z' -]{1,35}$">
                                        <label for="first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" pattern="^[A-Za-z' -]{1,35}$">
                                        <label for="last_name">Last Name</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3 mt-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" pattern="^[A-Za-z0-9_.-]{1,30}$" required>
                                <label for="username">Username</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" pattern="^[A-Za-z0-9._%+-]{1,60}@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$" required>
                                <label for="email">Email</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="phone_number" name="phone_number" placeholder="Phone Number" pattern="^\+?(\d{1,3})?[-.\s]?(\(?\d{1,4}\)?)?[-.\s]?\d{1,4}[-.\s]?\d{1,4}[-.\s]?\d{1,9}$" required>
                                <label for="phone_number">Phone Number</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,32}$" required>
                                <label for="password">Password</label>
                                <div class="position-absolute end-0 top-50 translate-middle-y pe-3">
                                    <i class="fas fa-eye text-muted" id="togglePasswordIcon" style="cursor: pointer;" onclick="togglePasswordVisibility()"></i>
                                </div>
                            </div>

                            <div class="mb-4 d-flex justify-content-center">
                                <div class="cf-turnstile" data-sitekey="<?php echo $turnstile_site_key; ?>"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Create Account</button>

                            <div class="text-center">
                                <span class="text-muted">Already have an account?</span>
                                <a href="sign_in.php" class="text-decoration-none ms-1">Sign In</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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