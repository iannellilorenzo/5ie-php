<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "My Trips";

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
require_once $rootPath . 'api/models/Prenotazione.php';
require_once $rootPath . 'api/models/Automobile.php';
require_once $rootPath . 'api/models/Autista.php';
require_once $rootPath . 'api/models/Passeggero.php';

// Initialize
$error = null;
$success = null;
$trips = [];
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Handle trip deletion if requested
if (isset($_GET['action']) && isset($_GET['id']) && !empty($_GET['id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $viaggioModel = new Viaggio($conn);
        
        $tripId = $_GET['id'];
        $action = $_GET['action'];
        
        if ($action === 'delete') {
            try {
                $viaggioModel->delete($tripId);
                $success = "Trip deleted successfully";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else if ($action === 'cancel') {
            try {
                $viaggioModel->update($tripId, ['stato' => 'annullato']);
                $success = "Trip canceled successfully";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else if ($action === 'complete') {
            try {
                // Update trip status
                $viaggioModel->update($tripId, ['stato' => 'completato']);
                
                // Get trip details and bookings
                $trip = $viaggioModel->getById($tripId);
                $prenotazioneModel = new Prenotazione($conn);
                $bookings = $prenotazioneModel->getAll(['id_viaggio' => $tripId, 'stato' => 'confermata']);
                
                // Get driver info
                $autistaModel = new Autista($conn);
                $driver = $autistaModel->getById($trip['id_autista']);
                $driverName = $driver['nome'] . ' ' . $driver['cognome'];
                
                // Send completion emails to all passengers and the driver
                require_once $rootPath . 'api/utils/EmailService.php';
                $emailService = new EmailService();
                
                foreach ($bookings as $booking) {
                    // Update booking status to completed
                    $prenotazioneModel->update($booking['id_prenotazione'], ['stato' => 'completata']);
                    
                    // Get passenger info
                    $passeggeroModel = new Passeggero($conn);
                    $passenger = $passeggeroModel->getById($booking['id_passeggero']);
                    $passengerName = $passenger['nome'] . ' ' . $passenger['cognome'];
                    
                    // Send email to passenger
                    $emailService->sendTripCompletionNotification(
                        $passenger['email'],
                        $passengerName,
                        $trip,
                        $driverName,
                        false // passenger is not a driver
                    );
                    
                    // Send email to driver for each passenger
                    $emailService->sendTripCompletionNotification(
                        $driver['email'],
                        $driverName,
                        $trip,
                        $passengerName,
                        true // driver is not a passenger
                    );
                }
                
                $success = "Trip marked as completed successfully";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all trips for this driver
    $viaggioModel = new Viaggio($conn);
    $allTrips = $viaggioModel->getByDriverId($_SESSION['user_id']);
    
    // Get all bookings for all trips
    $prenotazioneModel = new Prenotazione($conn);
    $tripIds = array_column($allTrips, 'id_viaggio');
    $allBookings = !empty($tripIds) ? $prenotazioneModel->getAll(['viaggio_ids' => $tripIds]) : [];
    
    // Get automobiles for reference
    $automobileModel = new Automobile($conn);
    $vehicles = $automobileModel->getAll(['id_autista' => $_SESSION['user_id']]);
    
    // Apply filter
    $currentDate = date('Y-m-d H:i:s');
    
    if ($filter === 'upcoming') {
        $trips = array_filter($allTrips, function($trip) use ($currentDate) {
            return $trip['timestamp_partenza'] > $currentDate;
        });
    } elseif ($filter === 'past') {
        $trips = array_filter($allTrips, function($trip) use ($currentDate) {
            return $trip['timestamp_partenza'] <= $currentDate;
        });
    } elseif ($filter === 'active') {
        // Active now means future trips only
        $trips = array_filter($allTrips, function($trip) use ($currentDate) {
            return $trip['timestamp_partenza'] > $currentDate;
        });
    } else {
        $trips = $allTrips;
    }
    
    // Apply search
    if (!empty($search)) {
        $trips = array_filter($trips, function($trip) use ($search) {
            $searchLower = strtolower($search);
            return (
                stripos($trip['citta_partenza'], $searchLower) !== false ||
                stripos($trip['citta_destinazione'], $searchLower) !== false
            );
        });
    }
    
    // Sort trips by departure date (newest first)
    usort($trips, function($a, $b) {
        return strtotime($b['timestamp_partenza']) - strtotime($a['timestamp_partenza']);
    });
    
} catch (Exception $e) {
    $error = "Error retrieving trips: " . $e->getMessage();
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<div class="container-wrapper">
<!-- My Trips Content -->
<div class="container py-5">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">My Trips</h1>
        <a href="<?php echo $rootPath; ?>pages/autista/create-trip.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i> Create New Trip
        </a>
    </div>

    <!-- Flash messages -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Bar -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3 align-items-end">
                <!-- Filter -->
                <div class="col-md-4">
                    <label for="filter" class="form-label small text-muted">Filter trips</label>
                    <select name="filter" id="filter" class="form-select">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All trips</option>
                        <option value="upcoming" <?php echo $filter === 'upcoming' ? 'selected' : ''; ?>>Upcoming trips</option>
                        <option value="past" <?php echo $filter === 'past' ? 'selected' : ''; ?>>Past trips</option>
                    </select>
                </div>
                
                <!-- Search -->
                <div class="col-md-6">
                    <label for="search" class="form-label small text-muted">Search by city</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search cities..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Reset button -->
                <div class="col-md-2">
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Trips List -->
    <?php if (empty($trips)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3.5rem;"></i>
                </div>
                <h4 class="text-muted">No trips found</h4>
                <?php if (!empty($search)): ?>
                    <p class="text-muted mb-4">Try changing your search criteria</p>
                <?php elseif ($filter !== 'all'): ?>
                    <p class="text-muted mb-4">Try changing the filter</p>
                <?php else: ?>
                    <p class="text-muted mb-4">You haven't created any trips yet</p>
                <?php endif; ?>
                <a href="<?php echo $rootPath; ?>pages/autista/create-trip.php" class="btn btn-primary">
                    Create Your First Trip
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Route</th>
                            <th>Time</th>
                            <th>Price</th>
                            <th>Features</th>
                            <th>Bookings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trips as $trip): 
                            // Get bookings for this trip
                            $tripBookings = array_filter($allBookings, function($booking) use ($trip) {
                                return $booking['id_viaggio'] == $trip['id_viaggio'];
                            });
                            
                            $confirmedBookings = array_filter($tripBookings, function($booking) {
                                return isset($booking['stato']) && $booking['stato'] === 'confermata';
                            });
                            
                            // Calculate departure and arrival time
                            $departureDateTime = new DateTime($trip['timestamp_partenza']);
                            $departureDate = $departureDateTime->format('j M Y');
                            $departureTime = $departureDateTime->format('H:i');
                            
                            // Calculate features
                            $features = [];
                            if ($trip['soste']) $features[] = 'Stops';
                            if ($trip['bagaglio']) $features[] = 'Luggage';
                            if ($trip['animali']) $features[] = 'Pets';
                        ?>
                        <tr>
                            <td><?php echo $departureDate; ?></td>
                            <td>
                                <?php echo htmlspecialchars($trip['citta_partenza']); ?> → 
                                <?php echo htmlspecialchars($trip['citta_destinazione']); ?>
                            </td>
                            <td><?php echo $departureTime; ?></td>
                            <td>€<?php echo number_format($trip['prezzo_cadauno'], 2); ?></td>
                            <td>
                                <?php if (!empty($features)): ?>
                                    <?php foreach ($features as $feature): ?>
                                        <span class="badge bg-light text-dark"><?php echo $feature; ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo count($confirmedBookings); ?> confirmed</span>
                            </td>
                            <td>
                                <?php if ($trip['stato'] === 'completato'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($trip['stato'] === 'annullato'): ?>
                                    <span class="badge bg-danger">Canceled</span>
                                <?php else: ?>
                                    <?php if ($departureDateTime < new DateTime()): ?>
                                        <span class="badge bg-warning text-dark">Past</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Active</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <!-- View Details Button (always visible) -->
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewTripModal"
                                            data-trip-id="<?php echo $trip['id_viaggio']; ?>"
                                            data-trip-route="<?php echo htmlspecialchars($trip['citta_partenza'] . ' → ' . $trip['citta_destinazione']); ?>"
                                            data-trip-date="<?php echo $departureDate; ?>"
                                            data-trip-time="<?php echo $departureTime; ?>"
                                            data-trip-price="<?php echo number_format($trip['prezzo_cadauno'], 2); ?>"
                                            data-trip-duration="<?php echo $trip['tempo_stimato'] ?? '00:00'; ?>"
                                            data-trip-stops="<?php echo $trip['soste'] ? '1' : '0'; ?>"
                                            data-trip-luggage="<?php echo $trip['bagaglio'] ? '1' : '0'; ?>"
                                            data-trip-pets="<?php echo $trip['animali'] ? '1' : '0'; ?>"
                                            title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    
                                    <?php if ($trip['stato'] !== 'completato' && $trip['stato'] !== 'annullato'): ?>
                                        <?php if ($departureDateTime > new DateTime()): ?>
                                            <!-- Edit Trip Button (only for future trips) -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editTripModal"
                                                    data-trip-id="<?php echo $trip['id_viaggio']; ?>"
                                                    data-trip-departure="<?php echo htmlspecialchars($trip['citta_partenza']); ?>"
                                                    data-trip-destination="<?php echo htmlspecialchars($trip['citta_destinazione']); ?>"
                                                    data-trip-date="<?php echo date('Y-m-d', strtotime($trip['timestamp_partenza'])); ?>"
                                                    data-trip-time="<?php echo date('H:i', strtotime($trip['timestamp_partenza'])); ?>"
                                                    data-trip-price="<?php echo $trip['prezzo_cadauno']; ?>"
                                                    data-trip-duration="<?php echo $trip['tempo_stimato'] ?? '00:00'; ?>"
                                                    data-trip-stops="<?php echo $trip['soste'] ? '1' : '0'; ?>"
                                                    data-trip-luggage="<?php echo $trip['bagaglio'] ? '1' : '0'; ?>"
                                                    data-trip-pets="<?php echo $trip['animali'] ? '1' : '0'; ?>"
                                                    title="Edit Trip">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <!-- Cancel Trip Button -->
                                            <button class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#cancelTripModal" 
                                                    data-trip-id="<?php echo $trip['id_viaggio']; ?>"
                                                    data-trip-route="<?php echo htmlspecialchars($trip['citta_partenza'] . ' to ' . $trip['citta_destinazione']); ?>"
                                                    title="Cancel Trip">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php elseif ($departureDateTime < new DateTime()): ?>
                                            <!-- Mark as Completed Button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#completeTripModal"
                                                    data-trip-id="<?php echo $trip['id_viaggio']; ?>"
                                                    data-trip-route="<?php echo htmlspecialchars($trip['citta_partenza'] . ' → ' . $trip['citta_destinazione']); ?>"
                                                    data-trip-date="<?php echo $departureDate; ?>"
                                                    title="Mark as Completed">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Trip Modal -->
<div class="modal fade" id="cancelTripModal" tabindex="-1" aria-labelledby="cancelTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelTripModalLabel">Cancel Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this trip: <strong id="tripRouteToCancel"></strong>?</p>
                <p class="text-danger mb-0"><strong>Warning:</strong> All passengers will be notified and their bookings will be canceled.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Trip</button>
                <a href="#" id="confirmCancelBtn" class="btn btn-danger">Yes, Cancel Trip</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Trip Modal -->
<div class="modal fade" id="deleteTripModal" tabindex="-1" aria-labelledby="deleteTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTripModalLabel">Delete Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to permanently delete this trip: <strong id="tripRouteToDelete"></strong>?</p>
                <p class="text-danger mb-0"><strong>Warning:</strong> This action cannot be undone and will remove all associated bookings.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete Trip</a>
            </div>
        </div>
    </div>
</div>

<!-- View Trip Details Modal -->
<div class="modal fade" id="viewTripModal" tabindex="-1" aria-labelledby="viewTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTripModalLabel">Trip Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Trip Information -->
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Trip Information</h6>
                        <div class="mb-3">
                            <label class="text-muted small">Route</label>
                            <p class="mb-1" id="viewTripRoute"></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Date & Time</label>
                            <p class="mb-1" id="viewTripDateTime"></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Price per person</label>
                            <p class="mb-1" id="viewTripPrice"></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Estimated Time</label>
                            <p class="mb-1" id="viewTripDuration"></p>
                        </div>
                    </div>
                    
                    <!-- Features -->
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Features</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Stops allowed
                                <span id="viewTripStops"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Luggage allowed
                                <span id="viewTripLuggage"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Pets allowed
                                <span id="viewTripPets"></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- Booking Information -->
                <h6 class="fw-bold mb-3">Passenger Bookings</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Passenger</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="viewTripBookings">
                            <!-- Bookings will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div id="noBookingsMessage" class="text-center text-muted py-3" style="display: none;">
                    <p>No bookings for this trip yet</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="viewTripEditBtn" class="btn btn-primary">Edit Trip</a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Trip Modal -->
<div class="modal fade" id="editTripModal" tabindex="-1" aria-labelledby="editTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTripModalLabel">Edit Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTripForm" method="POST" action="update-trip.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="editTripId" name="id_viaggio">
                    
                    <!-- Route Section -->
                    <div class="mb-4">
                        <h6 class="mb-3">Route Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editCittaPartenza" class="form-label">From</label>
                                <input type="text" class="form-control" id="editCittaPartenza" name="citta_partenza" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editCittaDestinazione" class="form-label">To</label>
                                <input type="text" class="form-control" id="editCittaDestinazione" name="citta_destinazione" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date & Time Section -->
                    <div class="mb-4">
                        <h6 class="mb-3">Date & Time</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editData" class="form-label">Departure Date</label>
                                <input type="date" class="form-control" id="editData" name="data" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editOra" class="form-label">Departure Time</label>
                                <input type="time" class="form-control" id="editOra" name="ora" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Price & Duration Section -->
                    <div class="mb-4">
                        <h6 class="mb-3">Price & Duration</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editPrezzo" class="form-label">Price per Passenger (€)</label>
                                <div class="input-group">
                                    <span class="input-group-text">€</span>
                                    <input type="number" step="0.01" min="0.50" class="form-control" id="editPrezzo" name="prezzo_cadauno" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estimated Travel Time</label>
                                <div class="input-group">
                                    <input type="number" min="0" max="23" class="form-control" id="editOre" name="ore" placeholder="Hours">
                                    <span class="input-group-text">h</span>
                                    <input type="number" min="0" max="59" class="form-control" id="editMinuti" name="minuti" placeholder="Minutes">
                                    <span class="input-group-text">m</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Trip Features Section -->
                    <div class="mb-4">
                        <h6 class="mb-3">Trip Features</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="editSoste" name="soste" value="1">
                                    <label class="form-check-label" for="editSoste">Stops along the way</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="editBagaglio" name="bagaglio" value="1">
                                    <label class="form-check-label" for="editBagaglio">Luggage allowed</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="editAnimali" name="animali" value="1">
                                    <label class="form-check-label" for="editAnimali">Pets allowed</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Mark Trip as Completed Modal -->
<div class="modal fade" id="completeTripModal" tabindex="-1" aria-labelledby="completeTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeTripModalLabel">Mark Trip as Completed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark this trip as completed?</p>
                <p><strong>Route:</strong> <span id="completeTripRoute"></span></p>
                <p><strong>Date:</strong> <span id="completeTripDate"></span></p>
                <p class="text-info"><i class="bi bi-info-circle"></i> This will update the trip status to completed and allow passengers to leave ratings.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmCompleteBtn" class="btn btn-success">Yes, Mark as Completed</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle cancel modal (existing)
    const cancelModal = document.getElementById('cancelTripModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tripId = button.getAttribute('data-trip-id');
            const tripRoute = button.getAttribute('data-trip-route');
            
            document.getElementById('tripRouteToCancel').textContent = tripRoute;
            document.getElementById('confirmCancelBtn').href = '?action=cancel&id=' + encodeURIComponent(tripId);
        });
    }
    
    // Handle view trip details modal (new)
    const viewTripModal = document.getElementById('viewTripModal');
    if (viewTripModal) {
        viewTripModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tripId = button.getAttribute('data-trip-id');
            const tripRoute = button.getAttribute('data-trip-route');
            const tripDateTime = button.getAttribute('data-trip-date') + ' at ' + button.getAttribute('data-trip-time');
            const tripPrice = '€' + button.getAttribute('data-trip-price');
            
            // Set trip details
            document.getElementById('viewTripRoute').textContent = tripRoute;
            document.getElementById('viewTripDateTime').textContent = tripDateTime;
            document.getElementById('viewTripPrice').textContent = tripPrice;
            
            // Parse duration
            const durationParts = button.getAttribute('data-trip-duration').split(':');
            const hours = parseInt(durationParts[0]);
            const minutes = parseInt(durationParts[1]);
            const durationText = (hours > 0 ? hours + ' hour' + (hours !== 1 ? 's' : '') : '') + 
                               (minutes > 0 ? ' ' + minutes + ' minute' + (minutes !== 1 ? 's' : '') : '');
            document.getElementById('viewTripDuration').textContent = durationText || 'Not specified';
            
            // Set features
            document.getElementById('viewTripStops').innerHTML = button.getAttribute('data-trip-stops') === '1' ? 
                '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            document.getElementById('viewTripLuggage').innerHTML = button.getAttribute('data-trip-luggage') === '1' ? 
                '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            document.getElementById('viewTripPets').innerHTML = button.getAttribute('data-trip-pets') === '1' ? 
                '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            
            // Set edit button link
            document.getElementById('viewTripEditBtn').onclick = function() {
                // Close this modal and open edit modal
                const editModal = new bootstrap.Modal(document.getElementById('editTripModal'));
                bootstrap.Modal.getInstance(viewTripModal).hide();
                
                // Trigger the edit modal with correct data
                const editBtn = document.querySelector(`button[data-bs-target="#editTripModal"][data-trip-id="${tripId}"]`);
                if (editBtn) {
                    // Use a small timeout to ensure the first modal is closed
                    setTimeout(() => {
                        editBtn.click();
                    }, 500);
                } else {
                    // If button not found, manually set up and show modal
                    const editTripModal = document.getElementById('editTripModal');
                    document.getElementById('editTripId').value = tripId;
                    editModal.show();
                }
            };
            
            // Load bookings via AJAX
            fetch('<?php echo $rootPath; ?>api/prenotazione?trip_id=' + encodeURIComponent(tripId))
                .then(response => response.json())
                .then(data => {
                    const bookingsTable = document.getElementById('viewTripBookings');
                    const noBookingsMsg = document.getElementById('noBookingsMessage');
                    
                    if (data.length === 0) {
                        bookingsTable.innerHTML = '';
                        noBookingsMsg.style.display = 'block';
                    } else {
                        noBookingsMsg.style.display = 'none';
                        let html = '';
                        
                        data.forEach(booking => {
                            const bookingDate = new Date(booking.timestamp_prenotazione);
                            const formattedDate = bookingDate.toLocaleDateString() + ' ' + 
                                                 bookingDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            
                            let statusBadge = '';
                            if (booking.stato === 'confermata') {
                                statusBadge = '<span class="badge bg-success">Confirmed</span>';
                            } else if (booking.stato === 'in attesa') {
                                statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                            } else {
                                statusBadge = '<span class="badge bg-secondary">' + booking.stato + '</span>';
                            }
                            
                            html += `
                                <tr>
                                    <td>${booking.nome} ${booking.cognome}</td>
                                    <td>${formattedDate}</td>
                                    <td>${statusBadge}</td>
                                    <td>
                                        <a href="booking-action.php?id=${booking.id_prenotazione}&action=view" 
                                           class="btn btn-sm btn-outline-primary">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        bookingsTable.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error fetching bookings:', error);
                    document.getElementById('noBookingsMessage').style.display = 'block';
                    document.getElementById('noBookingsMessage').innerHTML = '<p class="text-danger">Error loading bookings</p>';
                });
        });
    }
    
    // Handle edit trip modal (new)
    const editTripModal = document.getElementById('editTripModal');
    if (editTripModal) {
        editTripModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tripId = button.getAttribute('data-trip-id');
            const departure = button.getAttribute('data-trip-departure');
            const destination = button.getAttribute('data-trip-destination');
            const date = button.getAttribute('data-trip-date');
            const time = button.getAttribute('data-trip-time');
            const price = button.getAttribute('data-trip-price');
            const stops = button.getAttribute('data-trip-stops') === '1';
            const luggage = button.getAttribute('data-trip-luggage') === '1';
            const pets = button.getAttribute('data-trip-pets') === '1';
            
            // Set form values
            document.getElementById('editTripId').value = tripId;
            document.getElementById('editCittaPartenza').value = departure;
            document.getElementById('editCittaDestinazione').value = destination;
            document.getElementById('editData').value = date;
            document.getElementById('editOra').value = time;
            document.getElementById('editPrezzo').value = price;
            
            // Parse duration
            const duration = button.getAttribute('data-trip-duration');
            if (duration) {
                const [hours, minutes] = duration.split(':');
                document.getElementById('editOre').value = parseInt(hours);
                document.getElementById('editMinuti').value = parseInt(minutes);
            }
            
            // Set checkboxes
            document.getElementById('editSoste').checked = stops;
            document.getElementById('editBagaglio').checked = luggage;
            document.getElementById('editAnimali').checked = pets;
            
            // Set form action
            document.getElementById('editTripForm').action = 'update-trip.php?id=' + encodeURIComponent(tripId);
        });
        
        // Form validation
        const form = editTripModal.querySelector('form');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Check that at least hours or minutes are provided
            const hours = document.getElementById('editOre').value;
            const minutes = document.getElementById('editMinuti').value;
            
            if (!hours && !minutes) {
                event.preventDefault();
                alert('Please provide estimated travel time (hours or minutes)');
                return false;
            }
            
            form.classList.add('was-validated');
        });
    }
    
    // Handle complete trip modal (new)
    const completeTripModal = document.getElementById('completeTripModal');
    if (completeTripModal) {
        completeTripModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tripId = button.getAttribute('data-trip-id');
            const tripRoute = button.getAttribute('data-trip-route');
            const tripDate = button.getAttribute('data-trip-date');
            
            document.getElementById('completeTripRoute').textContent = tripRoute;
            document.getElementById('completeTripDate').textContent = tripDate;
            document.getElementById('confirmCompleteBtn').href = '?action=complete&id=' + encodeURIComponent(tripId);
        });
    }
    
    // Handle delete modal
    const deleteModal = document.getElementById('deleteTripModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tripId = button.getAttribute('data-trip-id');
            const tripRoute = button.getAttribute('data-trip-route');
            
            document.getElementById('tripRouteToDelete').textContent = tripRoute;
            document.getElementById('confirmDeleteBtn').href = '?action=delete&id=' + encodeURIComponent(tripId);
        });
    }
    
    // Auto-dismiss alerts after 4 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 4000);
    
    // Auto-submit form when filter changes
    document.getElementById('filter').addEventListener('change', function() {
        this.form.submit();
    });

    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>