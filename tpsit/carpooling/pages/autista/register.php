<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Register as Driver";

// Set root path for includes
$rootPath = "../../";

// Set extra CSS for this page
$extraCSS = '<link rel="stylesheet" href="' . $rootPath . 'assets/css/form-themes.css">';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard
    header("Location: {$rootPath}pages/autista/dashboard.php");
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
    'citta' => '',
    'bio' => '',
    'patente_numero' => '',
    'patente_tipo' => '',
    'patente_scadenza' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData['nome'] = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['cognome'] = trim(filter_input(INPUT_POST, 'cognome', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['email'] = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $formData['telefono'] = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['data_nascita'] = trim(filter_input(INPUT_POST, 'data_nascita', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['citta'] = trim(filter_input(INPUT_POST, 'citta', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['bio'] = trim(filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['patente_numero'] = trim(filter_input(INPUT_POST, 'patente_numero', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['patente_tipo'] = trim(filter_input(INPUT_POST, 'patente_tipo', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['patente_scadenza'] = trim(filter_input(INPUT_POST, 'patente_scadenza', FILTER_SANITIZE_SPECIAL_CHARS));
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($formData['nome'])) $errors[] = "First name is required";
    if (empty($formData['cognome'])) $errors[] = "Last name is required";
    if (empty($formData['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    if (empty($formData['telefono'])) $errors[] = "Phone number is required";
    if (empty($formData['data_nascita'])) $errors[] = "Date of birth is required";
    if (empty($formData['citta'])) $errors[] = "City is required";
    if (empty($formData['patente_numero'])) $errors[] = "Driver's license number is required";
    if (empty($formData['patente_tipo'])) $errors[] = "Driver's license type is required";
    if (empty($formData['patente_scadenza'])) $errors[] = "Driver's license expiration date is required";
    
    // Validate password
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
            require_once $rootPath . 'config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM autisti WHERE email = ?");
            $stmt->execute([$formData['email']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Email already registered. Please use a different email or login.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
                
                // Insert new driver
                $stmt = $conn->prepare("
                    INSERT INTO autisti (
                        nome, cognome, email, password, telefono, data_nascita, citta, 
                        bio, patente_numero, patente_tipo, patente_scadenza
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $formData['nome'],
                    $formData['cognome'],
                    $formData['email'],
                    $hashedPassword,
                    $formData['telefono'],
                    $formData['data_nascita'],
                    $formData['citta'],
                    $formData['bio'],
                    $formData['patente_numero'],
                    $formData['patente_tipo'],
                    $formData['patente_scadenza']
                ]);
                
                // Get the new user ID
                $userId = $conn->lastInsertId();
                
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $formData['nome'] . ' ' . $formData['cognome'];
                $_SESSION['user_type'] = 'autista';
                
                // Redirect to dashboard or appropriate page
                $success = true;
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
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <?php if ($success): ?>
                            <div class="text-center mb-5">
                                <div class="mb-4">
                                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h2 class="fw-bold">Registration Successful!</h2>
                                <p class="text-muted mb-4">Your driver account has been created and you are now logged in.</p>
                                <div class="d-grid gap-2">
                                    <a href="<?= $rootPath ?>pages/autista/add-vehicle.php" class="btn btn-primary btn-lg">Add Your Vehicle</a>
                                    <a href="<?= $rootPath ?>pages/autista/dashboard.php" class="btn btn-outline-primary">Go to Dashboard</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <h2 class="fw-bold mb-1">Register as a Driver</h2>
                                <p class="text-muted">Offer rides and earn money by sharing your journeys</p>
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
                                <!-- Tabs for form sections -->
                                <ul class="nav nav-tabs mb-4" id="registerTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">Personal Info</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="license-tab" data-bs-toggle="tab" data-bs-target="#license" type="button" role="tab">Driver's License</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab">Account</button>
                                    </li>
                                </ul>
                                
                                <!-- Tab content -->
                                <div class="tab-content" id="registerTabsContent">
                                    <!-- Personal Info Tab -->
                                    <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
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
                                        
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                                                <div class="invalid-feedback">Please provide a valid email address</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="telefono" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($formData['telefono']) ?>" required>
                                                <div class="invalid-feedback">Phone number is required</div>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-6">
                                                <label for="data_nascita" class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control" id="data_nascita" name="data_nascita" value="<?= htmlspecialchars($formData['data_nascita']) ?>" required>
                                                <div class="invalid-feedback">Date of birth is required</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="citta" class="form-label">City</label>
                                                <input type="text" class="form-control" id="citta" name="citta" value="<?= htmlspecialchars($formData['citta']) ?>" required>
                                                <div class="invalid-feedback">City is required</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <label for="bio" class="form-label">Bio <span class="text-muted">(Tell passengers about yourself)</span></label>
                                            <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($formData['bio']) ?></textarea>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="button" class="btn btn-primary next-tab" data-next="license-tab">Next: Driver's License</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Driver's License Tab -->
                                    <div class="tab-pane fade" id="license" role="tabpanel" aria-labelledby="license-tab">
                                        <div class="alert alert-info mb-4">
                                            <i class="bi bi-info-circle me-2"></i>
                                            We need your driver's license information to verify your identity and ensure passenger safety.
                                        </div>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="patente_numero" class="form-label">License Number</label>
                                                <input type="text" class="form-control" id="patente_numero" name="patente_numero" value="<?= htmlspecialchars($formData['patente_numero']) ?>" required>
                                                <div class="invalid-feedback">License number is required</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="patente_tipo" class="form-label">License Type</label>
                                                <select class="form-select" id="patente_tipo" name="patente_tipo" required>
                                                    <option value="" disabled <?= empty($formData['patente_tipo']) ? 'selected' : '' ?>>Select license type</option>
                                                    <option value="B" <?= $formData['patente_tipo'] === 'B' ? 'selected' : '' ?>>B (Standard car)</option>
                                                    <option value="A" <?= $formData['patente_tipo'] === 'A' ? 'selected' : '' ?>>A (Motorcycle)</option>
                                                    <option value="C" <?= $formData['patente_tipo'] === 'C' ? 'selected' : '' ?>>C (Commercial)</option>
                                                    <option value="D" <?= $formData['patente_tipo'] === 'D' ? 'selected' : '' ?>>D (Bus)</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a license type</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <label for="patente_scadenza" class="form-label">License Expiration Date</label>
                                            <input type="date" class="form-control" id="patente_scadenza" name="patente_scadenza" value="<?= htmlspecialchars($formData['patente_scadenza']) ?>" required>
                                            <div class="invalid-feedback">Expiration date is required</div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-4">
                                            <button type="button" class="btn btn-outline-secondary next-tab" data-next="personal-tab">Previous: Personal Info</button>
                                            <button type="button" class="btn btn-primary next-tab" data-next="account-tab">Next: Account</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Account Tab -->
                                    <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
                                        <div class="row g-3">
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
                                        
                                        <!-- Terms and Privacy -->
                                        <div class="mt-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                                <label class="form-check-label" for="terms">
                                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                                </label>
                                                <div class="invalid-feedback">
                                                    You must agree before submitting
                                                </div>
                                            </div>
                                            
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="marketing" name="marketing">
                                                <label class="form-check-label" for="marketing">
                                                    I'd like to receive updates about new features and promotions
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-4">
                                            <button type="button" class="btn btn-outline-secondary next-tab" data-next="license-tab">Previous: Driver's License</button>
                                            <button type="submit" class="btn btn-success btn-lg px-5">Create Driver Account</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Login Link -->
                            <div class="text-center mt-4">
                                Already have an account? <a href="<?= $rootPath ?>login.php">Log In</a>
                            </div>
                            
                            <!-- Register as Passenger Link -->
                            <div class="text-center mt-2">
                                <a href="<?= $rootPath ?>pages/passeggero/register.php" class="text-decoration-none">
                                    Just looking for a ride? Register as a passenger
                                </a>
                            </div>
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
                
                <h5>2. Driver Accounts</h5>
                <p>When you register as a driver, you must provide information that is accurate, complete, and current at all times, including valid driver's license information.</p>
                
                <h5>3. Driver Responsibilities</h5>
                <p>As a driver, you are responsible for:</p>
                <ul>
                    <li>Maintaining a valid driver's license and appropriate insurance</li>
                    <li>Ensuring your vehicle is in good working condition</li>
                    <li>Following all traffic laws and regulations</li>
                    <li>Picking up passengers at agreed locations and times</li>
                    <li>Providing a safe and respectful environment for passengers</li>
                </ul>
                
                <h5>4. Fees and Payments</h5>
                <p>You may charge reasonable fees for rides to cover your costs. Our platform may take a service fee for each completed ride.</p>
                
                <h5>5. Limitation of Liability</h5>
                <p>The platform serves as a connection service between drivers and passengers. We are not responsible for any damages that may occur during rides.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>We collect personal information including your name, contact details, and driver's license information to verify your identity and provide our services.</p>
                <p>Your information is securely stored and not shared with third parties except as necessary to provide our services or as required by law.</p>
                <p>Passengers will see your name, photo (if provided), vehicle details, and rating when browsing available rides.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation Script -->
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
        
        // Tab navigation
        const nextButtons = document.querySelectorAll('.next-tab');
        nextButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = document.getElementById(button.dataset.next);
                const tab = new bootstrap.Tab(targetTab);
                tab.show();
            });
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