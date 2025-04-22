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
        if ($booking['stato'] === 'annullata') {
            // Skip cancelled bookings
            continue;
        }
        
        if ($booking['data_partenza'] >= $currentDate) {
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
                                    <td><?php echo date('M d, Y', strtotime($booking['data_partenza'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['partenza']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['destinazione']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['ora_partenza']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['nome_autista'] . ' ' . $booking['cognome_autista']); ?>
                                        <div class="text-warning small">
                                            <i class="bi bi-star-fill"></i>
                                            <span><?php echo isset($booking['valutazione_autista']) ? number_format($booking['valutazione_autista'], 1) : '-'; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($booking['stato'] == 'confermata'): ?>
                                            <span class="badge bg-success">Confirmed</span>
                                        <?php elseif ($booking['stato'] == 'in attesa'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($booking['stato']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo $rootPath; ?>pages/passeggero/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Details
                                        </a>
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
                                    <td><?php echo date('M d, Y', strtotime($booking['data_partenza'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['partenza']); ?> → 
                                        <?php echo htmlspecialchars($booking['destinazione']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['nome_autista'] . ' ' . $booking['cognome_autista']); ?></td>
                                    <td>€<?php echo number_format($booking['prezzo'] * $booking['n_posti'], 2); ?></td>
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
                                            <a href="<?php echo $rootPath; ?>pages/passeggero/rate.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                Rate Trip
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>