<d?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "My Profile";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'autista') {
    // Redirect to login
    header("Location: {$rootPath}login.php");
    exit();
}

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Autista.php';
require_once $rootPath . 'api/models/Automobile.php';
require_once $rootPath . 'api/models/Viaggio.php';

// Initialize variables
$success = false;
$passwordSuccess = false;
$error = null;
$passwordError = null;
$photoSuccess = false;
$photoError = null;

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get driver information
    $autistaModel = new Autista($conn);
    $driver = $autistaModel->getById($_SESSION['user_id']);
    
    // Get driver's vehicles
    $automobileModel = new Automobile($conn);
    $vehicles = $automobileModel->getAll(['id_autista' => $_SESSION['user_id']]);
    
    // Get driver's trips count
    $viaggioModel = new Viaggio($conn);
    $trips = $viaggioModel->getByDriverId($_SESSION['user_id']);
    $tripsCount = count($trips);
    
    // Get completed trips count
    $completedTrips = count(array_filter($trips, function($trip) {
        return $trip['stato'] === 'completato';
    }));
    
    // Process profile update if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check which form was submitted
        if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
            // Sanitize input
            $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
            $cognome = trim(filter_input(INPUT_POST, 'cognome', FILTER_SANITIZE_SPECIAL_CHARS));
            $numero_telefono = trim(filter_input(INPUT_POST, 'numero_telefono', FILTER_SANITIZE_SPECIAL_CHARS));
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            
            // Validate required fields
            if (empty($nome) || empty($cognome) || empty($numero_telefono) || empty($email)) {
                $error = "All fields are required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address";
            } else {
                // Update profile information
                $updateData = [
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'numero_telefono' => $numero_telefono,
                    'email' => $email
                ];
                
                // Update profile in database
                $autistaModel->update($_SESSION['user_id'], $updateData);
                
                // Update session name if changed
                if ($nome !== $driver['nome'] || $cognome !== $driver['cognome']) {
                    $_SESSION['user_name'] = $nome . ' ' . $cognome;
                }
                
                // Refresh driver data
                $driver = $autistaModel->getById($_SESSION['user_id']);
                $success = true;
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
                    // Create directory if it doesn't exist
                    $uploadDir = $rootPath . 'uploads/drivers/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'driver_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                    $targetPath = $uploadDir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
                        // Update driver photo in database
                        $autistaModel->update($_SESSION['user_id'], ['fotografia' => 'uploads/drivers/' . $filename]);
                        
                        // Refresh driver data
                        $driver = $autistaModel->getById($_SESSION['user_id']);
                        $photoSuccess = true;
                    } else {
                        $photoError = "Failed to upload file";
                    }
                }
            } else {
                $photoError = "No file selected";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update_license') {
            // Process license information update
            $numero_patente = trim(filter_input(INPUT_POST, 'numero_patente', FILTER_SANITIZE_SPECIAL_CHARS));
            $scadenza_patente = trim(filter_input(INPUT_POST, 'scadenza_patente', FILTER_SANITIZE_SPECIAL_CHARS));
            
            // Validate required fields
            if (empty($numero_patente) || empty($scadenza_patente)) {
                $error = "License number and expiration date are required";
            } else {
                // Validate expiration date (must be in the future)
                $today = new DateTime();
                $expiration = new DateTime($scadenza_patente);
                
                if ($expiration <= $today) {
                    $error = "License expiration date must be in the future";
                } else {
                    // Update license information
                    $updateData = [
                        'numero_patente' => $numero_patente,
                        'scadenza_patente' => $scadenza_patente
                    ];
                    
                    // Update profile in database
                    $autistaModel->update($_SESSION['user_id'], $updateData);
                    
                    // Refresh driver data
                    $driver = $autistaModel->getById($_SESSION['user_id']);
                    $success = true;
                }
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
                if (password_verify($currentPassword, $driver['password'])) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $autistaModel->update($_SESSION['user_id'], ['password' => $hashedPassword]);
                    $passwordSuccess = true;
                } else {
                    $passwordError = "Current password is incorrect";
                }
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
                        <?php if (!empty($driver['fotografia']) && file_exists($rootPath . $driver['fotografia'])): ?>
                            <img src="<?php echo $rootPath . $driver['fotografia']; ?>" alt="Profile Photo" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
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
                    
                    <h4 class="mb-1"><?php echo htmlspecialchars($driver['nome'] . ' ' . $driver['cognome']); ?></h4>
                    <p class="text-muted">Driver</p>
                    
                    <hr class="my-4">
                    
                    <!-- Account Stats -->
                    <div class="row text-center">
                        <div class="col">
                            <h5 class="mb-0"><?php echo $tripsCount; ?></h5>
                            <p class="text-muted small">Trips</p>
                        </div>
                        <div class="col">
                            <div class="text-warning">
                                <i class="bi bi-star-fill"></i>
                                <span class="ms-1"><?php echo number_format($driver['valutazione'] ?? 0, 1); ?></span>
                            </div>
                            <p class="text-muted small">Rating</p>
                        </div>
                        <div class="col">
                            <h5 class="mb-0"><?php echo $completedTrips; ?></h5>
                            <p class="text-muted small">Completed</p>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Quick Links -->
                    <div class="d-grid gap-2">
                        <a href="<?php echo $rootPath; ?>pages/autista/dashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a href="<?php echo $rootPath; ?>pages/autista/trips.php" class="btn btn-outline-primary">
                            <i class="bi bi-calendar-check me-2"></i> My Trips
                        </a>
                        <a href="<?php echo $rootPath; ?>pages/autista/vehicles.php" class="btn btn-outline-primary">
                            <i class="bi bi-car-front me-2"></i> My Vehicles
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Personal Information</h5>
                    <span class="badge bg-primary">Driver Account</span>
                </div>
                <div class="card-body p-4">
                    <?php if ($success && !isset($_POST['action'])): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i> Your profile has been updated successfully.
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
                    
                    <?php if ($error && $_POST['action'] === 'update_profile'): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($driver['nome'] ?? ''); ?>" required>
                                <div class="invalid-feedback">First name is required</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="cognome" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="cognome" name="cognome" value="<?php echo htmlspecialchars($driver['cognome'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Last name is required</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($driver['email'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Please provide a valid email address</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="numero_telefono" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="numero_telefono" name="numero_telefono" value="<?php echo htmlspecialchars($driver['numero_telefono'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Phone number is required</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="data_nascita" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="data_nascita" value="<?php echo htmlspecialchars($driver['data_nascita'] ?? ''); ?>" disabled>
                                <div class="form-text">Date of birth cannot be changed</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary mt-4">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Driver's License Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Driver's License Information</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($success && $_POST['action'] === 'update_license'): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i> Your license information has been updated successfully.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error && $_POST['action'] === 'update_license'): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_license">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="numero_patente" class="form-label">License Number</label>
                                <input type="text" class="form-control" id="numero_patente" name="numero_patente" value="<?php echo htmlspecialchars($driver['numero_patente'] ?? ''); ?>" required>
                                <div class="invalid-feedback">License number is required</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="scadenza_patente" class="form-label">Expiration Date</label>
                                <input type="date" class="form-control" id="scadenza_patente" name="scadenza_patente" value="<?php echo htmlspecialchars($driver['scadenza_patente'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Valid expiration date is required</div>
                                <div class="form-text">Must be a future date</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary mt-4">
                            <i class="bi bi-save me-2"></i> Update License Information
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Vehicle Information Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Vehicles</h5>
                    <a href="<?php echo $rootPath; ?>pages/autista/vehicles.php" class="btn btn-sm btn-outline-primary">Manage Vehicles</a>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($vehicles)): ?>
                        <div class="text-center py-3">
                            <div class="mb-3">
                                <i class="bi bi-car-front text-muted" style="font-size: 2rem;"></i>
                            </div>
                            <p class="text-muted mb-3">You haven't added any vehicles yet.</p>
                            <a href="<?php echo $rootPath; ?>pages/autista/add-vehicle.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i> Add Your First Vehicle
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>License Plate</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-wrapper me-3 bg-light rounded-circle p-2">
                                                        <i class="bi bi-car-front"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($vehicle['marca'] . ' ' . $vehicle['modello']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($vehicle['targa']); ?></td>
                                            <td>
                                                <a href="<?php echo $rootPath; ?>pages/autista/edit-vehicle.php?id=<?php echo urlencode($vehicle['targa']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="<?php echo $rootPath; ?>pages/autista/add-vehicle.php" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle me-2"></i> Add Another Vehicle
                            </a>
                        </div>
                    <?php endif; ?>
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
        
        // Set minimum date for license expiration
        const today = new Date().toISOString().split('T')[0];
        const expirationInput = document.getElementById('scadenza_patente');
        if (expirationInput) {
            expirationInput.setAttribute('min', today);
        }
    });
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>