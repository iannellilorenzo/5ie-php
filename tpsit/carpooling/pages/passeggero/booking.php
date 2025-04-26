<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Book a Ride";

// Root path for includes
$rootPath = "../../";

// Extra CSS for this page
$extraCSS = '<link rel="stylesheet" href="' . $rootPath . 'assets/css/booking.css">';

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passeggero') {
    // Redirect to login with return URL
    header("Location: {$rootPath}login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Viaggio.php';
require_once $rootPath . 'api/models/Autista.php';
require_once $rootPath . 'api/models/Prenotazione.php';
require_once $rootPath . 'api/models/Automobile.php';

// Initialize variables
$tripId = $_GET['ride_id'] ?? 0;
$error = null;
$success = false;
$trip = null;
$driver = null;
$car = null;
$availableSeats = 0;
$formData = [
    'n_posti' => 1,
    'note' => ''
];

// Validate trip ID is provided
if (empty($tripId)) {
    $error = "Trip ID is missing. Please select a trip to book.";
} else {
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get trip details
        $viaggioModel = new Viaggio($conn);
        $trip = $viaggioModel->getById($tripId);
        
        if (!$trip) {
            $error = "Trip not found. It may have been canceled or removed.";
        } else {
            // Check if trip is in the future
            $tripDateTime = new DateTime($trip['timestamp_partenza']);
            $now = new DateTime();
            
            if ($tripDateTime < $now) {
                $error = "This trip has already departed and cannot be booked.";
            } else if ($trip['stato'] === 'annullato') {
                $error = "This trip has been canceled by the driver.";
            } else if ($trip['stato'] === 'completato') {
                $error = "This trip has already been completed.";
            } else {
                // Get driver information
                $autistaModel = new Autista($conn);
                $driver = $autistaModel->getById($trip['id_autista']);
                
                // Get car information
                $automobileModel = new Automobile($conn);
                $car = $automobileModel->getById($trip['id_autista']);
                
                // Calculate available seats - assume default 4 seats per vehicle
                $prenotazioneModel = new Prenotazione($conn);
                $bookings = $prenotazioneModel->getAll(['id_viaggio' => $tripId]);
                $totalSeats = 4; // Default 4 seats
                $bookedSeats = count($bookings); // Each booking is for 1 seat since we don't have n_posti
                $availableSeats = $totalSeats - $bookedSeats;
                
                // Check if there are available seats
                if ($availableSeats <= 0) {
                    $error = "This trip is fully booked. Please try another one.";
                }
            }
        }
    } catch (Exception $e) {
        $error = "Error retrieving trip details: " . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Since we don't have n_posti in the database, each booking represents one seat
    // We'll need to create multiple bookings for multiple seats
    try {
        $seats = intval($_POST['n_posti'] ?? 1);
        
        if ($seats < 1) {
            $error = "Please select at least 1 seat.";
        } else if ($seats > $availableSeats) {
            $error = "Only {$availableSeats} seats available. Please reduce the number of seats.";
        } else {
            $prenotazioneModel = new Prenotazione($conn);
            
            // Create a booking for each seat
            for ($i = 0; $i < $seats; $i++) {
                $bookingData = [
                    'id_viaggio' => $tripId,
                    'id_passeggero' => $_SESSION['user_id']
                    // Note: We don't include n_posti, note, stato, or timestamp_prenotazione since they don't exist in the schema
                ];
                
                $prenotazioneModel->create($bookingData);
            }
            
            // Set success flag
            $success = true;
        }
    } catch (Exception $e) {
        $error = "Failed to create booking: " . $e->getMessage();
    }
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>


<div class="container-wrapper">
<div class="container py-5">
    <?php if ($success): ?>
        <!-- Booking Success -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-5 text-center">
                <div class="mb-4">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h2 class="fw-bold mb-3">Booking Request Sent!</h2>
                <p class="text-muted mb-4">Your booking request has been sent to the driver and is waiting for confirmation.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="<?php echo $rootPath; ?>pages/passeggero/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <a href="<?php echo $rootPath; ?>pages/passeggero/search.php" class="btn btn-outline-primary">Find More Rides</a>
                </div>
            </div>
        </div>
    <?php elseif (!empty($error)): ?>
        <!-- Error Message -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-5 text-center">
                <div class="mb-4">
                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                </div>
                <h2 class="fw-bold mb-3">Unable to Book Trip</h2>
                <p class="text-muted mb-4"><?php echo $error; ?></p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="<?php echo $rootPath; ?>pages/passeggero/search.php" class="btn btn-primary">Back to Search</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Trip Details Column -->
            <div class="col-lg-8">
                <!-- Trip Details Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h4 class="mb-0">Trip Details</h4>
                    </div>
                    <div class="card-body p-4">
                        <!-- Route and Time -->
                        <div class="trip-route mb-4">
                            <div class="d-flex align-items-center">
                                <div class="timeline">
                                    <div class="timeline-start"></div>
                                    <div class="timeline-line"></div>
                                    <div class="timeline-end"></div>
                                </div>
                                
                                <div class="ms-3 flex-grow-1">
                                    <div class="departure mb-4">
                                        <p class="small text-muted mb-0"><?php echo date('H:i', strtotime($trip['timestamp_partenza'])); ?></p>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($trip['citta_partenza']); ?></h5>
                                    </div>
                                    
                                    <div class="arrival">
                                        <?php
                                        $departureDateTime = new DateTime($trip['timestamp_partenza']);
                                        $durationParts = explode(':', $trip['tempo_stimato'] ?? '00:00');
                                        $durationInterval = new DateInterval('PT' . $durationParts[0] . 'H' . $durationParts[1] . 'M');
                                        $arrivalDateTime = clone $departureDateTime;
                                        $arrivalDateTime->add($durationInterval);
                                        ?>
                                        <p class="small text-muted mb-0"><?php echo $arrivalDateTime->format('H:i'); ?> (estimated)</p>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($trip['citta_destinazione']); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trip Additional Info -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="fw-bold mb-2">Date & Time</h6>
                                    <p class="mb-0"><?php echo date('l, F j, Y', strtotime($trip['timestamp_partenza'])); ?></p>
                                    <p class="mb-0"><?php echo date('H:i', strtotime($trip['timestamp_partenza'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h6 class="fw-bold mb-2">Trip Duration</h6>
                                    <?php
                                    $hours = intval($durationParts[0]);
                                    $minutes = intval($durationParts[1]);
                                    $durationText = '';
                                    if ($hours > 0) {
                                        $durationText .= $hours . ' hour' . ($hours > 1 ? 's' : '');
                                    }
                                    if ($minutes > 0) {
                                        if (!empty($durationText)) {
                                            $durationText .= ' ';
                                        }
                                        $durationText .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                    }
                                    ?>
                                    <p class="mb-0"><?php echo $durationText; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trip Features -->
                        <h6 class="fw-bold mb-3">Trip Features</h6>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php if ($trip['soste']): ?>
                                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-sign-stop me-1"></i> Stops allowed</span>
                            <?php endif; ?>
                            
                            <?php if ($trip['bagaglio']): ?>
                                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-briefcase me-1"></i> Luggage allowed</span>
                            <?php endif; ?>
                            
                            <?php if ($trip['animali']): ?>
                                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-heart me-1"></i> Pets allowed</span>
                            <?php endif; ?>
                            
                            <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-person me-1"></i> <?php echo $availableSeats; ?> seat(s) available</span>
                        </div>
                        
                        <!-- Driver Info -->
                        <h6 class="fw-bold mb-3">About the Driver</h6>
                        <div class="d-flex align-items-center mb-3">
                            <div class="driver-avatar me-3">
                                <?php if (!empty($driver['fotografia']) && file_exists($rootPath . $driver['fotografia'])): ?>
                                    <img src="<?php echo $rootPath . $driver['fotografia']; ?>" alt="Driver profile" class="rounded-circle" width="60" height="60">
                                <?php else: ?>
                                    <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" alt="Driver profile" class="rounded-circle" width="60" height="60">
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($driver['nome'] . ' ' . $driver['cognome']); ?></h6>
                                <div class="text-warning">
                                    <i class="bi bi-star-fill"></i>
                                    <span class="ms-1"><?php echo number_format($driver['valutazione'] ?? 0, 1); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vehicle Info -->
                        <?php if ($car): ?>
                        <div class="border rounded p-3">
                            <h6 class="fw-bold mb-2">Vehicle</h6>
                            <p class="mb-2"><?php echo htmlspecialchars($car['marca'] . ' ' . $car['modello'] . ' (' . $car['colore'] . ')'); ?></p>
                            <small class="text-muted">License plate: <?php echo htmlspecialchars($car['targa']); ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Booking Form Column -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h4 class="mb-0">Booking Details</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="needs-validation" novalidate>
                            <!-- Price Info -->
                            <div class="price-info text-center mb-4">
                                <div class="price-tag mb-2">
                                    <span class="h2 fw-bold">€<?php echo number_format($trip['prezzo_cadauno'], 2); ?></span>
                                    <span class="text-muted">per seat</span>
                                </div>
                                <p class="text-muted small">Price is per passenger</p>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Number of Passengers -->
                            <div class="mb-3">
                                <label for="n_posti" class="form-label fw-semibold">Number of Seats</label>
                                <select class="form-select form-select-lg" id="n_posti" name="n_posti" required>
                                    <?php for ($i = 1; $i <= min(4, $availableSeats); $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($formData['n_posti'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?> seat<?php echo ($i > 1) ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="form-text">
                                    <?php if ($availableSeats > 1): ?>
                                        Up to <?php echo min(4, $availableSeats); ?> seats available
                                    <?php else: ?>
                                        Last seat available!
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Total Price -->
                            <div class="price-summary mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Base price:</span>
                                    <span>€<?php echo number_format($trip['prezzo_cadauno'], 2); ?> × <span id="seatCount">1</span></span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total:</span>
                                    <span id="totalPrice">€<?php echo number_format($trip['prezzo_cadauno'], 2); ?></span>
                                </div>
                            </div>
                            
                            <!-- Booking Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Confirm Booking</button>
                            </div>
                            
                            <!-- Booking Info -->
                            <div class="text-center mt-3">
                                <small class="text-muted">Your booking will be confirmed by the driver</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update total price when number of seats changes
    const seatsSelect = document.getElementById('n_posti');
    const seatCount = document.getElementById('seatCount');
    const totalPrice = document.getElementById('totalPrice');
    const pricePerSeat = <?php echo $trip['prezzo_cadauno'] ?? 0; ?>;
    
    if (seatsSelect && seatCount && totalPrice) {
        seatsSelect.addEventListener('change', function() {
            const seats = parseInt(this.value);
            seatCount.textContent = seats;
            totalPrice.textContent = '€' + (seats * pricePerSeat).toFixed(2);
        });
    }
    
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
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>