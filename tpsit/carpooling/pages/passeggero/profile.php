<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "My Profile";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passeggero') {
    // Redirect to login
    header("Location: {$rootPath}login.php");
    exit();
}

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Passeggero.php';

// Initialize variables
$success = false;
$passwordSuccess = false;
$photoSuccess = false;
$error = null;
$passwordError = null;
$photoError = null;

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get passenger information
    $passeggeroModel = new Passeggero($conn);
    $passenger = $passeggeroModel->getById($_SESSION['user_id']);
    
    // Process profile update if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check which form was submitted
        if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
            // Sanitize input
            $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
            $cognome = trim(filter_input(INPUT_POST, 'cognome', FILTER_SANITIZE_SPECIAL_CHARS));
            $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS));
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            
            // Validate required fields
            if (empty($nome) || empty($cognome) || empty($telefono) || empty($email)) {
                $error = "All fields are required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address";
            } else {
                // Update profile information
                $updateData = [
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'telefono' => $telefono,
                    'email' => $email
                ];
                
                // Update profile in database
                $passeggeroModel->update($_SESSION['user_id'], $updateData);
                
                // Update session name if changed
                if ($nome !== $passenger['nome'] || $cognome !== $passenger['cognome']) {
                    $_SESSION['user_name'] = $nome . ' ' . $cognome;
                }
                
                // Refresh passenger data
                $passenger = $passeggeroModel->getById($_SESSION['user_id']);
                $success = true;
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
            // Password change form
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate password fields
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $passwordError = "All password fields are required";
            } elseif (strlen($newPassword) < 8) {
                $passwordError = "New password must be at least 8 characters long";
            } elseif ($newPassword !== $confirmPassword) {
                $passwordError = "New passwords don't match";
            } else {
                // Verify current password
                if (password_verify($currentPassword, $passenger['password'])) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $passeggeroModel->update($_SESSION['user_id'], ['password' => $hashedPassword]);
                    $passwordSuccess = true;
                } else {
                    $passwordError = "Current password is incorrect";
                }
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
            // Handle profile photo upload
            if (!empty($_FILES['profile_photo']['name'])) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($_FILES['profile_photo']['type'], $allowedTypes)) {
                    $photoError = "Only JPG, PNG, and GIF files are allowed";
                } elseif ($_FILES['profile_photo']['size'] > $maxSize) {
                    $photoError = "File size must be less than 2MB";
                } elseif ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                    $photoError = "Error uploading file";
                } else {
                    // Create directory if it doesn't exist (using identical approach to driver profile)
                    $uploadDir = $rootPath . 'uploads/passengers/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'passenger_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                    $targetPath = $uploadDir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
                        // Update passenger photo in database
                        $passeggeroModel->update($_SESSION['user_id'], ['fotografia' => 'uploads/passengers/' . $filename]);
                        
                        // Refresh passenger data
                        $passenger = $passeggeroModel->getById($_SESSION['user_id']);
                        $photoSuccess = true;
                    } else {
                        $photoError = "Failed to upload file";
                    }
                }
            } else {
                $photoError = "No file selected";
            }
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<div class="container-wrapper">
<div class="container py-5">
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <!-- Profile Photo -->
                    <div class="profile-photo-wrapper mb-4">
                        <?php if (!empty($passenger['fotografia']) && file_exists($rootPath . $passenger['fotografia'])): ?>
                            <img src="<?php echo $rootPath . $passenger['fotografia']; ?>" alt="Profile Photo" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="profile-photo-placeholder rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;">
                                <i class="bi bi-person" style="font-size: 4rem; color: #6c757d;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Photo Upload Button -->
                        <div class="mt-3">
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" class="d-inline">
                                <input type="hidden" name="action" value="upload_photo">
                                <input type="file" name="profile_photo" id="profile_photo_input" class="d-none" accept="image/*" onchange="this.form.submit()">
                                <label for="profile_photo_input" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-camera me-1"></i> Update Photo
                                </label>
                            </form>
                        </div>
                    </div>
                    
                    <h4 class="mb-1"><?php echo htmlspecialchars($passenger['nome'] . ' ' . $passenger['cognome']); ?></h4>
                    <p class="text-muted">Passenger</p>
                    
                    <hr class="my-4">
                    
                    <!-- Account Stats -->
                    <div class="row text-center">
                        <div class="col">
                            <h5 class="mb-0" id="tripCount">0</h5>
                            <p class="text-muted small">Trips</p>
                        </div>
                        <div class="col">
                            <div class="text-warning">
                                <i class="bi bi-star-fill"></i>
                                <span class="ms-1"><?php echo isset($passenger['valutazione']) ? number_format($passenger['valutazione'], 1) : '0.0'; ?></span>
                            </div>
                            <p class="text-muted small">Rating</p>
                        </div>
                        <div class="col">
                            <h5 class="mb-0" id="memberSince"><?php echo date('Y', strtotime($passenger['data_registrazione'] ?? 'now')); ?></h5>
                            <p class="text-muted small">Since</p>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Quick Links -->
                    <div class="d-grid gap-2">
                        <a href="<?php echo $rootPath; ?>pages/passeggero/dashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a href="<?php echo $rootPath; ?>pages/passeggero/bookings.php" class="btn btn-outline-primary">
                            <i class="bi bi-journal-check me-2"></i> My Bookings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="col-lg-8">
            <!-- Profile Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i> Your profile has been updated successfully.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($photoSuccess): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i> Your profile photo has been updated successfully.
                        </div>
                    <?php endif; ?>

                    <?php if ($photoError): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $photoError; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($passenger['nome'] ?? ''); ?>" required>
                                <div class="invalid-feedback">First name is required</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="cognome" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="cognome" name="cognome" value="<?php echo htmlspecialchars($passenger['cognome'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Last name is required</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($passenger['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a valid email address</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($passenger['telefono'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Phone number is required</div>
                            </div>
                            
                            <div class="col-12">
                                <label for="documento_identita" class="form-label">ID Document Number</label>
                                <input type="text" class="form-control" id="documento_identita" value="<?php echo htmlspecialchars($passenger['documento_identita'] ?? ''); ?>" disabled>
                                <div class="form-text">Identity documents cannot be changed. Contact support if needed.</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary mt-4">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($passwordSuccess): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i> Your password has been changed successfully.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($passwordError): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $passwordError; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <div class="invalid-feedback">Please enter your current password</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                            <div class="form-text">Minimum 8 characters</div>
                            <div class="invalid-feedback">New password must be at least 8 characters long</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Please confirm your new password</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key me-2"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

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
                
                // Check if passwords match for password form
                if (form.querySelector('#confirm_password')) {
                    const newPassword = form.querySelector('#new_password');
                    const confirmPassword = form.querySelector('#confirm_password');
                    
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                form.classList.add('was-validated');
            }, false);
        });
        
        // Load trip count from API
        fetch('<?php echo $rootPath; ?>api/passengers/<?php echo $_SESSION['user_id']; ?>/stats')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    document.getElementById('tripCount').textContent = data.data.trip_count || 0;
                }
            })
            .catch(error => console.error('Error loading stats:', error));
    });
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>