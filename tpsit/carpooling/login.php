<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Login";

// Set root path for includes
$rootPath = "";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard based on user type
    $dashboardUrl = ($rootPath . ($_SESSION['user_type'] === 'autista' ? 'pages/autista/dashboard.php' : 'pages/passeggero/dashboard.php'));
    header("Location: $dashboardUrl");
    exit();
}

// Process login form if submitted
$errors = [];
$email = '';
$userType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? '';
    
    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    if (empty($userType) || !in_array($userType, ['autista', 'passeggero'])) {
        $errors[] = "Please select a valid user type";
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        try {
            // Connect to database
            require_once $rootPath . 'api/config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Determine table to query based on user type
            $table = ($userType === 'autista') ? 'autisti' : 'passeggeri';
            $idField = ($userType === 'autista') ? 'id_autista' : 'id_passeggero';
            
            // Query for user
            $stmt = $conn->prepare("SELECT * FROM {$table} WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user[$idField];
                    $_SESSION['user_name'] = $user['nome'] . ' ' . $user['cognome'];
                    $_SESSION['user_type'] = $userType;
                    
                    // Redirect to dashboard
                    $dashboardUrl = $rootPath . "pages/{$userType}/dashboard.php";
                    header("Location: $dashboardUrl");
                    exit();
                } else {
                    $errors[] = "Invalid email or password";
                }
            } else {
                $errors[] = "Invalid email or password";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Login failed: " . $e->getMessage();
        }
    }
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';

if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    echo '<div id="logoutAlert" class="alert alert-success">You have been successfully logged out.</div>';
}
?>

<!-- Login Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6 col-xl-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Welcome Back</h2>
                            <p class="text-muted">Log in to your account</p>
                        </div>
                        
                        <!-- Validation errors alert -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="needs-validation" novalidate>
                            <!-- User Type Selection -->
                            <div class="mb-4">
                                <label class="form-label">I am logging in as a:</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check user-type-option border rounded p-3 <?= $userType === 'passeggero' ? 'border-primary active' : '' ?>">
                                            <input class="form-check-input" type="radio" name="user_type" id="user_type_passenger" value="passeggero" 
                                                <?= $userType === 'passeggero' ? 'checked' : '' ?> required>
                                            <label class="form-check-label w-100" for="user_type_passenger">
                                                <div class="text-center">
                                                    <i class="bi bi-person-fill d-block mb-2" style="font-size: 1.5rem;"></i>
                                                    Passenger
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check user-type-option border rounded p-3 <?= $userType === 'autista' ? 'border-primary active' : '' ?>">
                                            <input class="form-check-input" type="radio" name="user_type" id="user_type_driver" value="autista" 
                                                <?= $userType === 'autista' ? 'checked' : '' ?> required>
                                            <label class="form-check-label w-100" for="user_type_driver">
                                                <div class="text-center">
                                                    <i class="bi bi-car-front-fill d-block mb-2" style="font-size: 1.5rem;"></i>
                                                    Driver
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-text mt-2">Make sure to select the correct account type</div>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                <div class="invalid-feedback">Please provide your email</div>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="password" class="form-label">Password</label>
                                    <a href="<?= $rootPath ?>forgot-password.php" class="small text-decoration-none">Forgot password?</a>
                                </div>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Please provide your password</div>
                            </div>
                            
                            <!-- Remember Me -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">Log In</button>
                            </div>
                            
                            <!-- Register Link -->
                            <div class="text-center">
                                <p class="mb-0">Don't have an account? <a href="<?= $rootPath ?>register.php">Register here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom JS for login page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
    
    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Toggle the eye icon
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    
    // Style active user type selection
    const userTypeOptions = document.querySelectorAll('.user-type-option');
    const userTypeInputs = document.querySelectorAll('input[name="user_type"]');
    
    userTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Remove active class from all options
            userTypeOptions.forEach(option => {
                option.classList.remove('border-primary', 'active');
            });
            
            // Add active class to selected option
            if (this.checked) {
                this.closest('.user-type-option').classList.add('border-primary', 'active');
            }
        });
    });

    // Auto-hide logout alert after 2 seconds
    const logoutAlert = document.getElementById('logoutAlert');
    if (logoutAlert) {
        setTimeout(() => {
            // Use Bootstrap's alert dismiss functionality
            const bsAlert = new bootstrap.Alert(logoutAlert);
            bsAlert.close();
        }, 2000); // 2000 milliseconds = 2 seconds
    }
});
</script>

<!-- Custom CSS for Login -->
<style>
.user-type-option {
    cursor: pointer;
    transition: all 0.2s;
}

.user-type-option:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.user-type-option.active {
    background-color: rgba(13, 110, 253, 0.1);
    border-width: 2px !important;
}

.user-type-option .form-check-input {
    margin-right: 10px;
}

.user-type-option i {
    color: #0d6efd;
}
</style>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>