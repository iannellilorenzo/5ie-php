<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Register";

// Set root path for includes - FIXED: Go up two directories
$rootPath = "../../";

// Set extra CSS for this page (in case header.php uses this variable)
$extraCSS = '<link rel="stylesheet" href="' . $rootPath . 'assets/css/form-themes.css">';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard
    header("Location: {$rootPath}dashboard.php");
    exit();
}

// Process form submission
$errors = [];
$success = false;
$formData = [
    'nome' => '',
    'cognome' => '',
    'email' => '',
    'telefono' => '',
    'data_nascita' => '',
    'citta' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData['nome'] = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['cognome'] = trim(filter_input(INPUT_POST, 'cognome', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['email'] = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $formData['telefono'] = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['data_nascita'] = trim(filter_input(INPUT_POST, 'data_nascita', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['citta'] = trim(filter_input(INPUT_POST, 'citta', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $userType = filter_input(INPUT_POST, 'user_type', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Validate required fields
    if (empty($formData['nome'])) $errors[] = "First name is required";
    if (empty($formData['cognome'])) $errors[] = "Last name is required";
    if (empty($formData['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    if (empty($formData['telefono'])) $errors[] = "Phone number is required";
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords don't match";
    }
    
    // If no validation errors, process registration
    if (empty($errors)) {
        try {
            // Connect to database
            require_once $rootPath . '/api/config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM passeggeri WHERE email = ?");
            $stmt->execute([$formData['email']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Email already registered. Please use a different email or login.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
                
                // Insert new user
                $stmt = $conn->prepare("
                    INSERT INTO passeggeri (nome, cognome, email, password, telefono, data_nascita, citta) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $formData['nome'],
                    $formData['cognome'],
                    $formData['email'],
                    $hashedPassword,
                    $formData['telefono'],
                    empty($formData['data_nascita']) ? null : $formData['data_nascita'],
                    empty($formData['citta']) ? null : $formData['citta']
                ]);
                
                // Get the new user ID
                $userId = $conn->lastInsertId();
                
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $formData['nome'] . ' ' . $formData['cognome'];
                $_SESSION['user_type'] = 'passeggero';
                
                // Redirect to dashboard or appropriate page
                $success = true;
                
                // Optional: redirect to dashboard after successful registration
                // header("Location: {$rootPath}pages/passeggero/dashboard.php");
                // exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<!-- Registration Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <?php if ($success): ?>
                            <div class="text-center mb-5">
                                <div class="mb-4">
                                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h2 class="fw-bold">Registration Successful!</h2>
                                <p class="text-muted mb-4">Your account has been created and you are now logged in.</p>
                                <div class="d-grid gap-2">
                                    <a href="<?= $rootPath ?>pages/passeggero/dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
                                    <a href="<?= $rootPath ?>pages/passeggero/search.php" class="btn btn-outline-primary">Find a Ride</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <h2 class="fw-bold mb-1">Create your passenger account</h2>
                                <p class="text-muted">Find and book rides easily</p>
                            </div>
                            
                            <!-- Validation errors alert -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <h5 class="alert-heading">Please fix the following errors:</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= $error ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Registration Form -->
                            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="user_type" value="passeggero">
                                
                                <!-- Name Fields -->
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($formData['nome']) ?>" required>
                                        <div class="invalid-feedback">First name is required</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cognome" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="cognome" name="cognome" value="<?= htmlspecialchars($formData['cognome']) ?>" required>
                                        <div class="invalid-feedback">Last name is required</div>
                                    </div>
                                </div>
                                
                                <!-- Contact Info -->
                                <div class="mb-3 mt-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                                    <div class="invalid-feedback">Please provide a valid email address</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($formData['telefono']) ?>" required>
                                    <div class="invalid-feedback">Phone number is required</div>
                                </div>
                                
                                <!-- Password Fields -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">At least 8 characters</div>
                                        <div class="invalid-feedback">Password must be at least 8 characters</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="invalid-feedback">Passwords must match</div>
                                    </div>
                                </div>
                                
                                <!-- Optional Fields -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="data_nascita" class="form-label">Date of Birth <span class="text-muted">(Optional)</span></label>
                                        <input type="date" class="form-control" id="data_nascita" name="data_nascita" value="<?= htmlspecialchars($formData['data_nascita']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="citta" class="form-label">City <span class="text-muted">(Optional)</span></label>
                                        <input type="text" class="form-control" id="citta" name="citta" value="<?= htmlspecialchars($formData['citta']) ?>">
                                    </div>
                                </div>
                                
                                <!-- Terms and Conditions -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                        </label>
                                        <div class="invalid-feedback">
                                            You must agree before submitting
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                                </div>
                                
                                <!-- Login Link -->
                                <div class="text-center mt-4">
                                    Already have an account? <a href="<?= $rootPath ?>login.php">Log In</a>
                                </div>
                                
                                <!-- Drive With Us Link -->
                                <div class="text-center mt-2">
                                    <a href="<?= $rootPath ?>pages/autista/register.php" class="text-decoration-none">
                                        Want to offer rides? Register as a driver
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Introduction</h5>
                <p>Welcome to RideTogether! These Terms and Conditions govern your use of our carpooling platform.</p>
                
                <h5>2. User Accounts</h5>
                <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account.</p>
                
                <h5>3. Passenger Responsibilities</h5>
                <p>As a passenger, you are responsible for:</p>
                <ul>
                    <li>Providing accurate information when booking rides</li>
                    <li>Being on time for scheduled pickups</li>
                    <li>Notifying drivers of any changes or cancellations in advance</li>
                    <li>Treating drivers and their vehicles with respect</li>
                    <li>Paying the agreed amount for rides</li>
                </ul>
                
                <h5>4. Privacy</h5>
                <p>Your privacy is important to us. Please refer to our Privacy Policy for information about how we collect, use, and disclose your personal data.</p>
                
                <h5>5. Limitation of Liability</h5>
                <p>The platform serves as a connection service between drivers and passengers. We are not responsible for the actions of individual users or for any damages that may occur during rides arranged through our platform.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Password visibility toggle script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Check if passwords match
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords don't match");
                } else {
                    confirmPassword.setCustomValidity('');
                }
                
                form.classList.add('was-validated');
            }, false);
        });
        
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle the eye / eye-slash icon
            togglePassword.querySelector('i').classList.toggle('bi-eye');
            togglePassword.querySelector('i').classList.toggle('bi-eye-slash');
        });
    });
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>