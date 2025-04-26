<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Add Vehicle";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'autista') {
    // Redirect to login
    header("Location: {$rootPath}login.php");
    exit();
}

// Process form submission
$errors = [];
$success = false;
$formData = [
    'marca' => '',
    'modello' => '',
    'targa' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData['marca'] = trim(filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['modello'] = trim(filter_input(INPUT_POST, 'modello', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['targa'] = trim(filter_input(INPUT_POST, 'targa', FILTER_SANITIZE_SPECIAL_CHARS));
    
    // Validate required fields
    if (empty($formData['marca'])) $errors[] = "Car make/brand is required";
    if (empty($formData['modello'])) $errors[] = "Car model is required";
    if (empty($formData['targa'])) $errors[] = "License plate is required";
    
    // If no validation errors, process vehicle addition
    if (empty($errors)) {
        try {
            // Connect to database
            require_once $rootPath . '/api/config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Use the Automobile model
            require_once $rootPath . '/api/models/Automobile.php';
            require_once $rootPath . '/api/utils/validation.php';
            $automobileModel = new Automobile($conn);
            
            // Create vehicle data array - only fields that exist in database
            $vehicleData = [
                'id_autista' => $_SESSION['user_id'],
                'marca' => $formData['marca'],
                'modello' => $formData['modello'],
                'targa' => strtoupper($formData['targa'])
            ];
            
            // Let the model handle insertion
            $vehicleId = $automobileModel->create($vehicleData);
            
            // Set success flag
            $success = true;
            
            // Reset form data on success
            $formData = [
                'marca' => '',
                'modello' => '',
                'targa' => ''
            ];
            
        } catch (Exception $e) {
            // Extract specific error messages for better user experience
            $errorMessage = $e->getMessage();
            
            if (strpos($errorMessage, "License plate already exists") !== false) {
                $errors[] = "This license plate is already registered. Please check and try again.";
            } else {
                $errors[] = "Failed to add vehicle: " . $errorMessage;
            }
        }
    }
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';

?>

<div class="container-wrapper">
<!-- Add Vehicle Section -->
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
                                <h2 class="fw-bold">Vehicle Added Successfully!</h2>
                                <p class="text-muted mb-4">Your vehicle has been registered and is now available for your trips.</p>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <a href="<?= $rootPath ?>pages/autista/vehicles.php" class="btn btn-outline-primary">View My Vehicles</a>
                                    <a href="<?= $rootPath ?>pages/autista/create-trip.php" class="btn btn-primary">Create a Trip</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <h2 class="fw-bold mb-1">Add Your Vehicle</h2>
                                <p class="text-muted">Register your car to start offering rides</p>
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
                            
                            <!-- Vehicle Registration Form -->
                            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="marca" class="form-label">Make / Brand</label>
                                        <input type="text" class="form-control" id="marca" name="marca" value="<?= htmlspecialchars($formData['marca']) ?>" required>
                                        <div class="invalid-feedback">Please provide the car brand</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modello" class="form-label">Model</label>
                                        <input type="text" class="form-control" id="modello" name="modello" value="<?= htmlspecialchars($formData['modello']) ?>" required>
                                        <div class="invalid-feedback">Please provide the car model</div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label for="targa" class="form-label">License Plate</label>
                                    <input type="text" class="form-control text-uppercase" id="targa" name="targa" value="<?= htmlspecialchars($formData['targa']) ?>" required>
                                    <div class="invalid-feedback">Please provide the license plate number</div>
                                    <div class="form-text">This will be the unique identifier for your vehicle</div>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">Add Vehicle</button>
                                    <a href="<?= $rootPath ?>pages/autista/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.querySelector('.needs-validation');
        
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        }
        
        // Auto-uppercase license plate input
        const targaInput = document.getElementById('targa');
        if (targaInput) {
            targaInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>