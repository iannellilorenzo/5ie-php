<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Passenger Dashboard";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passeggero') {
    // Redirect to login
    header("Location: {$rootPath}login.php");
    exit();
}

// Include database and models
require_once $rootPath . '/api/config/database.php';
require_once $rootPath . '/api/models/Prenotazione.php';
require_once $rootPath . '/api/models/Passeggero.php';

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get passenger information
    $passeggeroModel = new Passeggero($conn);
    $passenger = $passeggeroModel->getById($_SESSION['user_id']);
    
    // Get passenger's bookings
    $prenotazioneModel = new Prenotazione($conn);
    $allBookings = $prenotazioneModel->getBookingsByPassengerId($_SESSION['user_id']);
    
    // Split bookings by status
    $upcomingBookings = [];
    $pastBookings = [];
    $currentDate = date('Y-m-d');
    
    foreach ($allBookings as $booking) {
        // Use null coalescing operator to provide default value when key doesn't exist
        $bookingStatus = $booking['stato'] ?? 'in attesa';
        
        if ($bookingStatus === 'annullata') {
            // Skip cancelled bookings
            continue;
        }
        
        // Use timestamp_partenza instead of data_partenza (matching your database schema)
        $departureDate = date('Y-m-d', strtotime($booking['timestamp_partenza'] ?? $currentDate));
        
        if ($departureDate >= $currentDate) {
            $upcomingBookings[] = $booking;
        } else {
            $pastBookings[] = $booking;
        }
    }
    
} catch (Exception $e) {
    $error = "Error retrieving dashboard data: " . $e->getMessage();
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<div class="container-wrapper">
<!-- Dashboard Content -->
<div class="container py-5">
    <!-- Welcome Banner -->
    <div class="card bg-primary text-white mb-4 border-0 shadow">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($passenger['nome']); ?>!</h2>
                    <p class="mb-0">Find your next ride or manage your bookings</p>
                </div>
                <div class="d-none d-md-block">
                    <i class="bi bi-car-front" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5>Find a Ride</h5>
                    <p class="text-muted small">Search for rides to your destination</p>
                    <div class="mt-3">
                        <a href="<?php echo $rootPath; ?>pages/passeggero/search.php" class="btn btn-primary">Search Now</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <h5>My Bookings</h5>
                    <p class="text-muted small">View and manage your ride bookings</p>
                    <div class="mt-3">
                        <a href="<?php echo $rootPath; ?>pages/passeggero/bookings.php" class="btn btn-outline-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h5>My Profile</h5>
                    <p class="text-muted small">Update your personal information</p>
                    <div class="mt-3">
                        <a href="<?php echo $rootPath; ?>pages/passeggero/profile.php" class="btn btn-outline-primary">Edit Profile</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-question-circle"></i>
                    </div>
                    <h5>Help Center</h5>
                    <p class="text-muted small">Get support for your questions</p>
                    <div class="mt-3">
                        <a href="<?php echo $rootPath; ?>help.php" class="btn btn-outline-primary">Get Help</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Rides -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i> Upcoming Rides</h5>
                <a href="<?php echo $rootPath; ?>pages/passeggero/bookings.php?filter=upcoming" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($upcomingBookings)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="text-muted">No upcoming rides</h5>
                    <p class="mb-4">You don't have any upcoming ride bookings</p>
                    <a href="<?php echo $rootPath; ?>pages/passeggero/search.php" class="btn btn-primary">Find a Ride</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Departure</th>
                                <th>Driver</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($booking['timestamp_partenza'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($booking['citta_partenza'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($booking['citta_destinazione'] ?? ''); ?></td>
                                    <td><?php echo date('H:i', strtotime($booking['timestamp_partenza'] ?? 'now')); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars(($booking['nome_autista'] ?? '') . ' ' . ($booking['cognome_autista'] ?? '')); ?>
                                        <div class="text-warning small">
                                            <i class="bi bi-star-fill"></i>
                                            <span><?php echo isset($booking['voto_autista']) ? number_format($booking['voto_autista'], 1) : '-'; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $booking['stato'] ?? 'in attesa';
                                        if ($status == 'confermata'): ?>
                                            <span class="badge bg-success">Confirmed</span>
                                        <?php elseif ($status == 'in attesa'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($status); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary view-booking-details" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#bookingDetailsModal"
                                               data-booking-id="<?php echo $booking['id_prenotazione'] ?? 0; ?>">
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

    <!-- Past Trips -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Recent Trip History</h5>
                <a href="<?php echo $rootPath; ?>pages/passeggero/bookings.php?filter=past" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($pastBookings)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-hourglass text-muted" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="text-muted">No trip history</h5>
                    <p>You haven't completed any trips yet</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Route</th>
                                <th>Driver</th>
                                <th>Price</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Show just the most recent 5 trips
                            $recentPastBookings = array_slice($pastBookings, 0, 5); 
                            foreach ($recentPastBookings as $booking): 
                            ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($booking['timestamp_partenza'] ?? '')); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['citta_partenza'] ?? ''); ?> → 
                                        <?php echo htmlspecialchars($booking['citta_destinazione'] ?? ''); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(($booking['nome_autista'] ?? '') . ' ' . ($booking['cognome_autista'] ?? '')); ?></td>
                                    <td>€<?php echo number_format(($booking['prezzo_cadauno'] ?? 0) * ($booking['n_posti'] ?? 1), 2); ?></td>
                                    <td>
                                        <?php if (isset($booking['voto_autista'])): ?>
                                            <div class="text-warning">
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                    <?php if ($i < $booking['voto_autista']): ?>
                                                        <i class="bi bi-star-fill"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        <?php else: ?>
                                            <a href="<?php echo $rootPath; ?>pages/passeggero/rate.php?booking_id=<?php echo $booking['id_prenotazione']; ?>" class="btn btn-sm btn-outline-secondary">
                                                Rate Trip
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (isset($booking['stato']) && $booking['stato'] === 'completata' && empty($booking['voto_autista'])): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="alert alert-warning mb-0">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Please <a href="<?php echo $rootPath; ?>pages/passeggero/rate.php?booking_id=<?php echo $booking['id_prenotazione']; ?>" class="alert-link">rate your driver</a> for this trip.
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Details Modal - Fresh Implementation -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingDetailsModalLabel">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4" id="loadingIndicator">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading booking details...</p>
                </div>
                
                <div id="bookingContent" style="display: none;">
                    <!-- Trip Route Section -->
                    <div class="trip-route mb-4">
                        <div class="d-flex">
                            <div class="timeline me-3">
                                <div class="timeline-start"></div>
                                <div class="timeline-line"></div>
                                <div class="timeline-end"></div>
                            </div>
                            
                            <div class="flex-grow-1">
                                <div class="mb-3">
                                    <p class="small text-muted mb-0" id="departureTime">--:--</p>
                                    <h5 class="mb-0" id="departureCity">Loading...</h5>
                                </div>
                                
                                <div>
                                    <p class="small text-muted mb-0" id="arrivalTime">--:-- (estimated)</p>
                                    <h5 class="mb-0" id="destinationCity">Loading...</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Date</h6>
                                    <p class="card-text fw-bold" id="tripDate">Loading...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Booking Status</h6>
                                    <p class="card-text" id="bookingStatus">
                                        <span class="badge bg-secondary">Loading...</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Booked On</h6>
                                    <p class="card-text" id="bookingDate">Loading...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Seats</h6>
                                    <p class="card-text" id="seatCount">Loading...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Price</h6>
                                    <p class="card-text fw-bold" id="tripPrice">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Trip Features -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">Trip Features</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark" id="feature-stops" style="display: none;">
                                    <i class="bi bi-sign-stop me-1"></i> Stops allowed
                                </span>
                                <span class="badge bg-light text-dark" id="feature-luggage" style="display: none;">
                                    <i class="bi bi-briefcase me-1"></i> Luggage allowed
                                </span>
                                <span class="badge bg-light text-dark" id="feature-pets" style="display: none;">
                                    <i class="bi bi-heart me-1"></i> Pets allowed
                                </span>
                                <span id="no-features" class="text-muted">No special features</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Driver Information -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">Driver Information</h6>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" id="driverPhoto" 
                                         alt="Driver Photo" class="rounded-circle" width="60" height="60">
                                </div>
                                <div>
                                    <h6 class="mb-1" id="driverName">Loading...</h6>
                                    <div class="text-warning">
                                        <i class="bi bi-star-fill me-1"></i>
                                        <span id="driverRating">0.0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vehicle Information -->
                    <div class="card mb-4" id="vehicleInfoCard" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">Vehicle Information</h6>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="bi bi-car-front" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1" id="vehicleName">Loading...</h6>
                                    <p class="text-muted small mb-0" id="licensePlate">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Booking Actions -->
                    <div id="bookingActions" class="mt-4" style="display: none;">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-danger" id="cancelBookingBtn" style="display: none;">
                                <i class="bi bi-x-circle me-2"></i> Cancel Booking
                            </button>
                            
                            <a href="#" class="btn btn-outline-primary" id="rateDriverBtn" style="display: none;">
                                <i class="bi bi-star me-2"></i> Rate Driver
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Error Message -->
                <div id="errorMessage" class="alert alert-danger" style="display: none;">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="errorText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>
<!-- JavaScript for the Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingDetailsModal = document.getElementById('bookingDetailsModal');
    
    if (bookingDetailsModal) {
        bookingDetailsModal.addEventListener('show.bs.modal', function(event) {
            // Get the button that triggered the modal
            const button = event.relatedTarget;
            const bookingId = button.getAttribute('data-booking-id');
            
            // Show loading indicator, hide content and error
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('bookingContent').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            
            // Reset features display
            document.getElementById('feature-stops').style.display = 'none';
            document.getElementById('feature-luggage').style.display = 'none';
            document.getElementById('feature-pets').style.display = 'none';
            document.getElementById('no-features').style.display = 'inline';
            
            // Fetch booking details
            fetch('<?php echo $rootPath; ?>pages/passeggero/get-booking-details.php?id=' + bookingId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Hide loading indicator, show content
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('bookingContent').style.display = 'block';
                    
                    // Debugging - log the data
                    console.log('Booking details:', data);
                    
                    // Populate modal with data
                    document.getElementById('departureCity').textContent = data.citta_partenza || 'Not specified';
                    document.getElementById('destinationCity').textContent = data.citta_destinazione || 'Not specified';
                    document.getElementById('departureTime').textContent = data.departure_time || '--:--';
                    document.getElementById('arrivalTime').textContent = data.arrival_time || '--:-- (estimated)';
                    document.getElementById('tripDate').textContent = data.departure_date || 'Not specified';
                    document.getElementById('bookingDate').textContent = data.booking_date || 'Not specified';
                    document.getElementById('seatCount').textContent = data.n_posti || '1';
                    document.getElementById('tripPrice').textContent = '€' + (data.total_price || '0.00');
                    document.getElementById('driverName').textContent = data.driver_name || 'Not specified';
                    
                    // Update driver rating with stars
                    const driverRating = parseFloat(data.driver_rating) || 0;
                    const fullStars = Math.floor(driverRating);
                    const halfStar = driverRating % 1 >= 0.5;
                    let starsHtml = '';
                    
                    // Create full stars
                    for (let i = 0; i < fullStars; i++) {
                        starsHtml += '<i class="bi bi-star-fill text-warning"></i> ';
                    }
                    
                    // Add half star if needed
                    if (halfStar) {
                        starsHtml += '<i class="bi bi-star-half text-warning"></i> ';
                    }
                    
                    // Add empty stars
                    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
                    for (let i = 0; i < emptyStars; i++) {
                        starsHtml += '<i class="bi bi-star text-warning"></i> ';
                    }
                    
                    // Update the driver rating display
                    document.getElementById('driverRating').innerHTML = starsHtml + 
                        `<span class="ms-1 text-muted small">(${data.driver_rating || '0.0'})</span>`;
                    
                    // Show vehicle information if available
                    const vehicleInfoCard = document.getElementById('vehicleInfoCard');
                    if (data.marca && data.modello) {
                        vehicleInfoCard.style.display = 'block';
                        
                        let vehicleName = data.marca + ' ' + data.modello;
                        if (data.colore) {
                            vehicleName += ' (' + data.colore + ')';
                        }
                        document.getElementById('vehicleName').textContent = vehicleName;
                        
                        if (data.targa) {
                            document.getElementById('licensePlate').textContent = 'License plate: ' + data.targa;
                        } else {
                            document.getElementById('licensePlate').textContent = '';
                        }
                    } else {
                        vehicleInfoCard.style.display = 'none';
                    }
                    
                    // Update trip features
                    let hasFeatures = false;
                    
                    if (data.soste == 1) {
                        document.getElementById('feature-stops').style.display = 'inline-flex';
                        hasFeatures = true;
                    }
                    
                    if (data.bagaglio == 1) {
                        document.getElementById('feature-luggage').style.display = 'inline-flex';
                        hasFeatures = true;
                    }
                    
                    if (data.animali == 1) {
                        document.getElementById('feature-pets').style.display = 'inline-flex';
                        hasFeatures = true;
                    }
                    
                    document.getElementById('no-features').style.display = hasFeatures ? 'none' : 'inline';
                    
                    // Update driver photo if available
                    if (data.driver_photo && data.driver_photo !== '') {
                        document.getElementById('driverPhoto').src = '<?php echo $rootPath; ?>' + data.driver_photo;
                    } else {
                        document.getElementById('driverPhoto').src = '<?php echo $rootPath; ?>assets/img/default-pfp.png';
                    }
                    
                    // Set booking status
                    const status = data.stato || 'in attesa';
                    let statusBadge = '';
                    
                    if (status === 'confermata') {
                        statusBadge = '<span class="badge bg-success">Confirmed</span>';
                    } else if (status === 'in attesa') {
                        statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                    } else if (status === 'rifiutata') {
                        statusBadge = '<span class="badge bg-danger">Rejected</span>';
                    } else if (status === 'completata') {
                        statusBadge = '<span class="badge bg-info">Completed</span>';
                    } else {
                        statusBadge = `<span class="badge bg-secondary">${status}</span>`;
                    }
                    
                    document.getElementById('bookingStatus').innerHTML = statusBadge;
                    
                    // Show/hide actions based on booking status
                    const bookingActions = document.getElementById('bookingActions');
                    const cancelBtn = document.getElementById('cancelBookingBtn');
                    const rateDriverBtn = document.getElementById('rateDriverBtn');
                    
                    bookingActions.style.display = 'none';
                    cancelBtn.style.display = 'none';
                    rateDriverBtn.style.display = 'none';
                    
                    // Only show cancel button for pending or confirmed bookings
                    if (status === 'in attesa' || status === 'confermata') {
                        bookingActions.style.display = 'block';
                        cancelBtn.style.display = 'block';
                        cancelBtn.onclick = function() {
                            if (confirm('Are you sure you want to cancel this booking?')) {
                                window.location.href = '<?php echo $rootPath; ?>pages/passeggero/cancel-booking.php?id=' + bookingId;
                            }
                        };
                    }
                    
                    // Show rating button for completed trips
                    if (status === 'completata' && !data.has_rating) {
                        bookingActions.style.display = 'block';
                        rateDriverBtn.style.display = 'inline-block';
                        rateDriverBtn.href = '<?php echo $rootPath; ?>pages/passeggero/rate.php?booking_id=' + bookingId;
                    }
                })
                .catch(error => {
                    // Show error message
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('bookingContent').style.display = 'none';
                    document.getElementById('errorMessage').style.display = 'block';
                    document.getElementById('errorText').textContent = 'Failed to load booking details: ' + error.message;
                    console.error('Error fetching booking details:', error);
                });
        });
    }
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>