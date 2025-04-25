<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Driver Dashboard";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'autista') {
    // Redirect to login
    header("Location: {$rootPath}login.php");
    exit();
}

// Include database and models
require_once $rootPath . '/api/config/database.php';
require_once $rootPath . '/api/models/Autista.php';
require_once $rootPath . '/api/models/Viaggio.php';
require_once $rootPath . '/api/models/Automobile.php';
require_once $rootPath . '/api/models/Prenotazione.php';

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get driver information
    $autistaModel = new Autista($conn);
    $driver = $autistaModel->getById($_SESSION['user_id']);
    
    // Get driver's vehicles
    $automobileModel = new Automobile($conn);
    $vehicles = $automobileModel->getAll(['autista_id' => $_SESSION['user_id']]);
    
    // Get driver's trips
    $viaggioModel = new Viaggio($conn);
    $allTrips = $viaggioModel->getAll(['autista_id' => $_SESSION['user_id']]);
    
    // Get bookings for all driver's trips
    $prenotazioneModel = new Prenotazione($conn);
    $tripIds = array_column($allTrips, 'id');
    $allBookings = !empty($tripIds) ? $prenotazioneModel->getAll(['viaggio_ids' => $tripIds]) : [];
    
    // Calculate statistics
    $totalEarnings = 0;
    $completedTrips = 0;
    $totalPassengers = 0;
    
    // Split trips by status
    $upcomingTrips = [];
    $pastTrips = [];
    $currentDate = date('Y-m-d');
    
    foreach ($allTrips as $trip) {
        // Extract just the date portion from timestamp_partenza for comparison
        $tripDate = date('Y-m-d', strtotime($trip['timestamp_partenza']));
        if ($tripDate >= $currentDate) {
            $upcomingTrips[] = $trip;
        } else {
            $pastTrips[] = $trip;
            if (isset($trip['stato']) && $trip['stato'] === 'completato') {
                $completedTrips++;
            } else if ($tripDate < $currentDate) {
                // If past trip without explicit status, consider it completed
                $completedTrips++;
                // Calculate earnings
                foreach ($allBookings as $booking) {
                    if ($booking['viaggio_id'] == $trip['id'] && $booking['stato'] === 'confermata') {
                        $totalEarnings += $trip['prezzo_cadauno'] * $booking['n_posti'];
                        $totalPassengers += $booking['n_posti'];
                    }
                }
            }
        }
    }
    
    // Get recent bookings for my trips
    $recentBookings = array_filter($allBookings, function($booking) {
        return $booking['stato'] === 'in attesa';
    });
    
    // Sort by most recent
    usort($recentBookings, function($a, $b) {
        return strtotime($b['data_prenotazione']) - strtotime($a['data_prenotazione']);
    });
    
    // Take just the most recent 5
    $recentBookings = array_slice($recentBookings, 0, 5);
    
} catch (Exception $e) {
    $error = "Error retrieving dashboard data: " . $e->getMessage();
}

// Set default values for statistics (in case something goes wrong)
if (!isset($totalEarnings)) $totalEarnings = 0;
if (!isset($completedTrips)) $completedTrips = 0;
if (!isset($totalPassengers)) $totalPassengers = 0;
if (!isset($upcomingTrips)) $upcomingTrips = [];
if (!isset($pastTrips)) $pastTrips = [];
if (!isset($recentBookings)) $recentBookings = [];
if (!isset($vehicles)) $vehicles = [];
if (!isset($allTrips)) $allTrips = [];
if (!isset($allBookings)) $allBookings = [];

// Set default driver info if not available
if (!isset($driver) || !is_array($driver)) {
    $driver = [
        'nome' => $_SESSION['user_name'] ?? 'Driver',
        'cognome' => '',
        'valutazione' => 0
    ];
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<!-- Dashboard Content -->
<div class="container py-5">
    <!-- Welcome Banner -->
    <div class="card bg-primary text-white mb-4 border-0 shadow">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($driver['nome']); ?>!</h2>
                    <p class="mb-0">Manage your trips and see your stats</p>
                </div>
                <div class="d-none d-md-block">
                    <i class="bi bi-car-front" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Earnings</h6>
                    <h3 class="mb-0">€<?php echo number_format($totalEarnings ?? 0, 2); ?></h3>
                    <div class="small text-success mt-2">
                        <i class="bi bi-graph-up"></i> Your driving income
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Completed Trips</h6>
                    <h3 class="mb-0"><?php echo $completedTrips ?? 0; ?></h3>
                    <div class="small text-primary mt-2">
                        <i class="bi bi-check-circle"></i> Successfully finished rides
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Passengers</h6>
                    <h3 class="mb-0"><?php echo $totalPassengers ?? 0; ?></h3>
                    <div class="small text-info mt-2">
                        <i class="bi bi-people"></i> People you've helped
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Your Rating</h6>
                    <h3 class="mb-0">
                        <span class="text-warning"><?php echo number_format($driver['valutazione'] ?? 0, 1); ?></span>
                        <small class="text-muted">/5</small>
                    </h3>
                    <div class="small text-warning mt-2">
                        <i class="bi bi-star-fill"></i> Based on passenger reviews
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <h5>Create Trip</h5>
                    <p class="text-muted small">Offer a new ride to passengers</p>
                    <div class="mt-3">
                        <a href="<?php echo $rootPath; ?>pages/autista/create-trip.php" class="btn btn-primary">Create Now</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h5>My Trips</h5>
                    <p class="text-muted small">View and manage all your rides</p>
                    <div class="mt-3">
                        <a href="<?php echo $rootPath; ?>pages/autista/trips.php" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-car-front"></i>
                    </div>
                    <h5>My Vehicles</h5>
                    <p class="text-muted small">Manage your registered vehicles</p>
                    <div class="mt-3">
                        <a href="<?php echo $rootPath; ?>pages/autista/vehicles.php" class="btn btn-outline-primary">Manage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Trips -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i> Upcoming Trips</h5>
                <a href="<?php echo $rootPath; ?>pages/autista/trips.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($upcomingTrips)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="text-muted">No upcoming trips</h5>
                    <p class="mb-4">You don't have any upcoming trips scheduled</p>
                    <a href="<?php echo $rootPath; ?>pages/autista/create-trip.php" class="btn btn-primary">Create a Trip</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Route</th>
                                <th>Time</th>
                                <th>Available Seats</th>
                                <th>Price</th>
                                <th>Bookings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Sort upcoming trips by date (closest first)
                            usort($upcomingTrips, function($a, $b) {
                                return strtotime($a['timestamp_partenza']) - strtotime($b['timestamp_partenza']);
                            });
                            
                            // Show first 5
                            $displayTrips = array_slice($upcomingTrips, 0, 5);
                            foreach ($displayTrips as $trip): 
                                // Count bookings
                                $tripBookings = array_filter($allBookings, function($booking) use ($trip) {
                                    return $booking['id_viaggio'] == $trip['id_viaggio'] && $booking['stato'] === 'confermata';
                                });
                                $bookedSeats = array_sum(array_column($tripBookings, 'n_posti'));
                            ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($trip['timestamp_partenza'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($trip['citta_partenza']); ?> → 
                                        <?php echo htmlspecialchars($trip['citta_destinazione']); ?>
                                    </td>
                                    <td><?php echo date('H:i', strtotime($trip['timestamp_partenza'])); ?></td>
                                    <td>
                                        <?php 
                                        // Calculate seats - if you don't have a seats field, you'll need to determine how to do this
                                        $totalSeats = $trip['posti_totali'] ?? 4; // Default to 4 if not set
                                        echo ($totalSeats - $bookedSeats) . '/' . $totalSeats; 
                                        ?>
                                    </td>
                                    <td>€<?php echo number_format($trip['prezzo_cadauno'], 2); ?></td>
                                    <td><?php echo count($tripBookings); ?></td>
                                    <td>
                                        <button type="button"
                                               class="btn btn-sm btn-outline-primary" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#viewTripModal"
                                               data-trip-id="<?php echo $trip['id_viaggio']; ?>"
                                               data-trip-departure="<?php echo htmlspecialchars($trip['citta_partenza']); ?>"
                                               data-trip-destination="<?php echo htmlspecialchars($trip['citta_destinazione']); ?>"
                                               data-trip-date="<?php echo date('Y-m-d', strtotime($trip['timestamp_partenza'])); ?>"
                                               data-trip-time="<?php echo date('H:i', strtotime($trip['timestamp_partenza'])); ?>"
                                               data-trip-price="<?php echo $trip['prezzo_cadauno']; ?>"
                                               data-trip-duration="<?php echo $trip['tempo_stimato'] ?? '00:00'; ?>"
                                               data-trip-stops="<?php echo $trip['soste'] ? '1' : '0'; ?>"
                                               data-trip-luggage="<?php echo $trip['bagaglio'] ? '1' : '0'; ?>"
                                               data-trip-pets="<?php echo $trip['animali'] ? '1' : '0'; ?>">
                                            <i class="bi bi-eye"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Booking Requests -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-bell me-2"></i> Recent Booking Requests</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recentBookings)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">No pending booking requests</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($recentBookings as $booking): 
                        // Get trip details
                        $tripDetails = null;
                        foreach ($allTrips as $trip) {
                            if ($trip['id_viaggio'] == $booking['id_viaggio']) {
                                $tripDetails = $trip;
                                break;
                            }
                        }
                        if (!$tripDetails) continue;
                    ?>
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($booking['nome_passeggero'] . ' ' . $booking['cognome_passeggero']); ?></h6>
                                <p class="mb-1 small text-muted">
                                    <?php echo htmlspecialchars($tripDetails['citta_partenza'] . ' → ' . $tripDetails['citta_destinazione']); ?> | 
                                    <?php echo date('M d', strtotime($tripDetails['timestamp_partenza'])); ?> | 
                                    <?php echo $booking['n_posti']; ?> seat(s)
                                </p>
                                <small class="text-muted">Requested <?php echo date('M d, H:i', strtotime($booking['data_prenotazione'])); ?></small>
                            </div>
                            <div>
                                <a href="<?php echo $rootPath; ?>pages/autista/booking-action.php?id=<?php echo $booking['id']; ?>&action=accept" class="btn btn-sm btn-success">Accept</a>
                                <a href="<?php echo $rootPath; ?>pages/autista/booking-action.php?id=<?php echo $booking['id']; ?>&action=reject" class="btn btn-sm btn-danger">Reject</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- My Vehicles -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-car-front me-2"></i> My Vehicles</h5>
                <a href="<?php echo $rootPath; ?>pages/autista/add-vehicle.php" class="btn btn-sm btn-primary">Add Vehicle</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($vehicles)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-car-front text-muted" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="text-muted">No vehicles added yet</h5>
                    <p class="mb-4">Add your vehicle to start offering rides</p>
                    <a href="<?php echo $rootPath; ?>pages/autista/add-vehicle.php" class="btn btn-primary">Add Vehicle</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($vehicle['marca'] . ' ' . $vehicle['modello']); ?></h5>
                                    <div class="text-muted mb-3">
                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($vehicle['targa']); ?>
                                    </div>
                                    <a href="<?php echo $rootPath; ?>pages/autista/edit-vehicle.php?id=<?php echo urlencode($vehicle['targa']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="<?php echo $rootPath; ?>pages/autista/create-trip.php?vehicle=<?php echo urlencode($vehicle['targa']); ?>" class="btn btn-sm btn-outline-success ms-1">
                                        <i class="bi bi-plus-circle"></i> Create Trip
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Trip Details Modal -->
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
                                <th>Seats</th>
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
                <a href="" id="editTripBtn" class="btn btn-primary">Edit Trip</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view trip details modal
    const viewTripModal = document.getElementById('viewTripModal');
    if (viewTripModal) {
        viewTripModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tripId = button.getAttribute('data-trip-id');
            const tripDeparture = button.getAttribute('data-trip-departure');
            const tripDestination = button.getAttribute('data-trip-destination');
            const tripRoute = tripDeparture + ' → ' + tripDestination;
            const tripDate = button.getAttribute('data-trip-date');
            const tripTime = button.getAttribute('data-trip-time');
            const tripDateTime = tripDate + ' at ' + tripTime;
            const tripPrice = '€' + button.getAttribute('data-trip-price');
            
            // Set trip details
            document.getElementById('viewTripRoute').textContent = tripRoute;
            document.getElementById('viewTripDateTime').textContent = tripDateTime;
            document.getElementById('viewTripPrice').textContent = tripPrice;
            
            // Parse duration
            const duration = button.getAttribute('data-trip-duration');
            if (duration) {
                const [hours, minutes] = duration.split(':');
                const durationText = (hours > 0 ? hours + ' hour' + (hours !== 1 ? 's' : '') : '') + 
                                   (minutes > 0 ? ' ' + minutes + ' minute' + (minutes !== 1 ? 's' : '') : '');
                document.getElementById('viewTripDuration').textContent = durationText || 'Not specified';
            }
            
            // Set features
            document.getElementById('viewTripStops').innerHTML = button.getAttribute('data-trip-stops') === '1' ? 
                '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            document.getElementById('viewTripLuggage').innerHTML = button.getAttribute('data-trip-luggage') === '1' ? 
                '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            document.getElementById('viewTripPets').innerHTML = button.getAttribute('data-trip-pets') === '1' ? 
                '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            
            // Set edit button link
            document.getElementById('editTripBtn').href = '<?php echo $rootPath; ?>pages/autista/edit-trip.php?id=' + encodeURIComponent(tripId);
            
            // Load bookings via AJAX
            fetch('<?php echo $rootPath; ?>pages/autista/get-bookings.php?trip_id=' + encodeURIComponent(tripId))
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
                            const bookingDate = new Date(booking.data_prenotazione || booking.timestamp_prenotazione);
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
                            
                            const passengerName = booking.nome_passeggero ? 
                                booking.nome_passeggero + ' ' + booking.cognome_passeggero : 
                                booking.nome + ' ' + booking.cognome;
                            
                            html += `
                                <tr>
                                    <td>${passengerName}</td>
                                    <td>${formattedDate}</td>
                                    <td>${booking.n_posti}</td>
                                    <td>${statusBadge}</td>
                                    <td>
                                        <a href="<?php echo $rootPath; ?>pages/autista/booking-action.php?id=${booking.id_prenotazione || booking.id}&action=view" 
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
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>