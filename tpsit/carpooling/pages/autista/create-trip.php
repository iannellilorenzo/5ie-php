<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Create Trip";

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
require_once $rootPath . 'api/models/Viaggio.php';
require_once $rootPath . 'api/models/Automobile.php';
require_once $rootPath . 'api/utils/validation.php';

// Initialize
$errors = [];
$success = false;
$vehicles = [];
$selectedVehicle = isset($_GET['vehicle']) ? $_GET['vehicle'] : '';

// Default form data
$formData = [
    'citta_partenza' => '',
    'citta_destinazione' => '',
    'data_partenza' => date('Y-m-d'),
    'ora_partenza' => date('H:i', strtotime('+1 hour')),
    'prezzo_cadauno' => '',
    'ore_stimate' => '',
    'minuti_stimati' => '',
    'soste' => false,
    'bagaglio' => false,
    'animali' => false
];

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get driver's vehicles
    $automobileModel = new Automobile($conn);
    $vehicles = $automobileModel->getAll(['id_autista' => $_SESSION['user_id']]);
    
    // Check if driver has any vehicles
    if (empty($vehicles)) {
        $errors[] = "You need to add a vehicle before creating a trip. <a href='{$rootPath}pages/autista/add-vehicle.php' class='alert-link'>Add vehicle</a>";
    }
    
} catch (Exception $e) {
    $errors[] = "Error loading vehicles: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData['citta_partenza'] = trim(filter_input(INPUT_POST, 'citta_partenza', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['citta_destinazione'] = trim(filter_input(INPUT_POST, 'citta_destinazione', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['data_partenza'] = trim(filter_input(INPUT_POST, 'data_partenza', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['ora_partenza'] = trim(filter_input(INPUT_POST, 'ora_partenza', FILTER_SANITIZE_SPECIAL_CHARS));
    $formData['prezzo_cadauno'] = trim(filter_input(INPUT_POST, 'prezzo_cadauno', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
    $formData['ore_stimate'] = trim(filter_input(INPUT_POST, 'ore_stimate', FILTER_SANITIZE_NUMBER_INT));
    $formData['minuti_stimati'] = trim(filter_input(INPUT_POST, 'minuti_stimati', FILTER_SANITIZE_NUMBER_INT));
    
    $formData['soste'] = isset($_POST['soste']) ? true : false;
    $formData['bagaglio'] = isset($_POST['bagaglio']) ? true : false;
    $formData['animali'] = isset($_POST['animali']) ? true : false;
    
    // Validate required fields
    if (empty($formData['citta_partenza'])) $errors[] = "Departure city is required";
    if (empty($formData['citta_destinazione'])) $errors[] = "Destination city is required";
    
    // Check that departure and destination are different
    if ($formData['citta_partenza'] == $formData['citta_destinazione']) {
        $errors[] = "Departure and destination cities must be different";
    }
    
    if (empty($formData['data_partenza'])) $errors[] = "Departure date is required";
    else {
        $now = new DateTime();
        $departureDate = new DateTime($formData['data_partenza'] . ' ' . $formData['ora_partenza']);
        
        if ($departureDate < $now) {
            $errors[] = "Departure time must be in the future";
        }
    }
    
    if (empty($formData['ora_partenza'])) $errors[] = "Departure time is required";
    if (empty($formData['prezzo_cadauno'])) $errors[] = "Price per passenger is required";
    elseif (!is_numeric($formData['prezzo_cadauno']) || $formData['prezzo_cadauno'] <= 0) {
        $errors[] = "Price must be a positive number";
    }
    
    // Validate travel time
    if (empty($formData['ore_stimate']) && empty($formData['minuti_stimati'])) {
        $errors[] = "Estimated travel time is required (hours or minutes)";
    }
    
    // If no errors, process trip creation
    if (empty($errors)) {
        try {
            // Create timestamp for departure
            $timestamp_partenza = $formData['data_partenza'] . ' ' . $formData['ora_partenza'] . ':00';
            
            // Create time format for estimated time
            $hours = empty($formData['ore_stimate']) ? 0 : intval($formData['ore_stimate']);
            $minutes = empty($formData['minuti_stimati']) ? 0 : intval($formData['minuti_stimati']);
            $tempo_stimato = sprintf('%02d:%02d:00', $hours, $minutes);
            
            // Create trip data array
            $tripData = [
                'id_autista' => $_SESSION['user_id'],
                'citta_partenza' => $formData['citta_partenza'],
                'citta_destinazione' => $formData['citta_destinazione'],
                'timestamp_partenza' => $timestamp_partenza,
                'prezzo_cadauno' => $formData['prezzo_cadauno'],
                'tempo_stimato' => $tempo_stimato,
                'soste' => $formData['soste'],
                'bagaglio' => $formData['bagaglio'],
                'animali' => $formData['animali']
            ];
            
            // Create new trip
            $viaggioModel = new Viaggio($conn);
            $tripId = $viaggioModel->create($tripData);
            
            // Set success flag
            $success = true;
            
            // Reset form data on success
            $formData = [
                'citta_partenza' => '',
                'citta_destinazione' => '',
                'data_partenza' => date('Y-m-d'),
                'ora_partenza' => date('H:i', strtotime('+1 hour')),
                'prezzo_cadauno' => '',
                'ore_stimate' => '',
                'minuti_stimati' => '',
                'soste' => false,
                'bagaglio' => false,
                'animali' => false
            ];
            
        } catch (Exception $e) {
            $errors[] = "Failed to create trip: " . $e->getMessage();
        }
    }
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<div class="container-wrapper">
<!-- Create Trip Section -->
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
                                <h2 class="fw-bold">Trip Created Successfully!</h2>
                                <p class="text-muted mb-4">Your trip has been published and is now visible to passengers.</p>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <a href="<?= $rootPath ?>pages/autista/trips.php" class="btn btn-outline-primary">View All Trips</a>
                                    <a href="<?= $rootPath ?>pages/autista/create-trip.php" class="btn btn-primary">Create Another Trip</a>
                                </div>
                            </div>
                        <?php elseif (!empty($errors) && isset($errors[0]) && strpos($errors[0], 'Add vehicle') !== false): ?>
                            <div class="text-center mb-5">
                                <div class="mb-4">
                                    <i class="bi bi-car-front text-primary" style="font-size: 4rem;"></i>
                                </div>
                                <h2 class="fw-bold">Add a Vehicle First</h2>
                                <p class="text-muted mb-4">You need to register a vehicle before you can create trips.</p>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <a href="<?= $rootPath ?>pages/autista/add-vehicle.php" class="btn btn-primary">Add Vehicle</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <h2 class="fw-bold mb-1">Create a Trip</h2>
                                <p class="text-muted">Publish your journey and find passengers</p>
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
                            
                            <!-- Trip Creation Form -->
                            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="needs-validation" novalidate>
                                <!-- Route Section -->
                                <div class="mb-4">
                                    <h5 class="mb-3">Route Information</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="citta_partenza" class="form-label">From</label>
                                            <input type="text" class="form-control" id="citta_partenza" name="citta_partenza" placeholder="Departure city" value="<?= htmlspecialchars($formData['citta_partenza']) ?>" required>
                                            <div class="invalid-feedback">Please provide the departure city</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="citta_destinazione" class="form-label">To</label>
                                            <input type="text" class="form-control" id="citta_destinazione" name="citta_destinazione" placeholder="Destination city" value="<?= htmlspecialchars($formData['citta_destinazione']) ?>" required>
                                            <div class="invalid-feedback">Please provide the destination city</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Date & Time Section -->
                                <div class="mb-4">
                                    <h5 class="mb-3">Date & Time</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="data_partenza" class="form-label">Departure Date</label>
                                            <input type="date" class="form-control" id="data_partenza" name="data_partenza" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($formData['data_partenza']) ?>" required>
                                            <div class="invalid-feedback">Please select a valid departure date</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="ora_partenza" class="form-label">Departure Time</label>
                                            <input type="time" class="form-control" id="ora_partenza" name="ora_partenza" value="<?= htmlspecialchars($formData['ora_partenza']) ?>" required>
                                            <div class="invalid-feedback">Please select a departure time</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Price & Duration Section -->
                                <div class="mb-4">
                                    <h5 class="mb-3">Price & Duration</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="prezzo_cadauno" class="form-label">Price per Passenger (€)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input type="number" step="0.01" min="0.50" class="form-control" id="prezzo_cadauno" name="prezzo_cadauno" placeholder="25.00" value="<?= htmlspecialchars($formData['prezzo_cadauno']) ?>" required>
                                                <div class="invalid-feedback">Please provide a valid price</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Estimated Travel Time</label>
                                            <div class="input-group">
                                                <input type="number" min="0" max="23" class="form-control" id="ore_stimate" name="ore_stimate" placeholder="Hours" value="<?= htmlspecialchars($formData['ore_stimate']) ?>">
                                                <span class="input-group-text">h</span>
                                                <input type="number" min="0" max="59" class="form-control" id="minuti_stimati" name="minuti_stimati" placeholder="Minutes" value="<?= htmlspecialchars($formData['minuti_stimati']) ?>">
                                                <span class="input-group-text">m</span>
                                            </div>
                                            <div class="form-text">Enter the estimated travel time</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Trip Features Section -->
                                <div class="mb-4">
                                    <h5 class="mb-3">Trip Features</h5>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="soste" name="soste" <?= $formData['soste'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="soste">Stops along the way</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="bagaglio" name="bagaglio" <?= $formData['bagaglio'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="bagaglio">Luggage allowed</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="animali" name="animali" <?= $formData['animali'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="animali">Pets allowed</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">Publish Trip</button>
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
                
                // Check if at least hours or minutes are provided
                const hours = document.getElementById('ore_stimate').value;
                const minutes = document.getElementById('minuti_stimati').value;
                
                if (!hours && !minutes) {
                    event.preventDefault();
                    alert('Please provide estimated travel time (hours or minutes)');
                    return false;
                }
                
                form.classList.add('was-validated');
            }, false);
        }
        
        // Set minimum date for departure date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_partenza').setAttribute('min', today);
    });
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>