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
        if ($trip['data_partenza'] >= $currentDate) {
            $upcomingTrips[] = $trip;
        } else {
            $pastTrips[] = $trip;
            if ($trip['stato'] === 'completato') {
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
                    <h3 class="mb-0">€<?php echo number_format($totalEarnings, 2); ?></h3>
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
                    <h3 class="mb-0"><?php echo $completedTrips; ?></h3>
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
                    <h3 class="mb-0"><?php echo $totalPassengers; ?></h3>
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
                                return strtotime($a['data_partenza']) - strtotime($b['data_partenza']);
                            });
                            
                            // Show first 5
                            $displayTrips = array_slice($upcomingTrips, 0, 5);
                            foreach ($displayTrips as $trip): 
                                // Count bookings
                                $tripBookings = array_filter($allBookings, function($booking) use ($trip) {
                                    return $booking['viaggio_id'] == $trip['id'] && $booking['stato'] === 'confermata';
                                });
                                $bookedSeats = array_sum(array_column($tripBookings, 'n_posti'));
                            ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($trip['data_partenza'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($trip['partenza']); ?> → 
                                        <?php echo htmlspecialchars($trip['destinazione']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($trip['ora_partenza']); ?></td>
                                    <td><?php echo ($trip['posti_disponibili'] - $bookedSeats) . '/' . $trip['posti_disponibili']; ?></td>
                                    <td>€<?php echo number_format($trip['prezzo_cadauno'], 2); ?></td>
                                    <td><?php echo count($tripBookings); ?></td>
                                    <td>
                                        <a href="<?php echo $rootPath; ?>pages/autista/trip-details.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                            if ($trip['id'] == $booking['viaggio_id']) {
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
                                    <?php echo htmlspecialchars($tripDetails['partenza'] . ' → ' . $tripDetails['destinazione']); ?> | 
                                    <?php echo date('M d', strtotime($tripDetails['data_partenza'])); ?> | 
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
                                    <div class="text-muted small mb-3">
                                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($vehicle['targa']); ?> | 
                                        <i class="bi bi-palette"></i> <?php echo htmlspecialchars($vehicle['colore']); ?> | 
                                        <i class="bi bi-calendar"></i> <?php echo htmlspecialchars($vehicle['anno']); ?>
                                    </div>
                                    <p class="card-text">
                                        <i class="bi bi-people"></i> <?php echo $vehicle['n_posti']; ?> seats available
                                    </p>
                                    <a href="<?php echo $rootPath; ?>pages/autista/edit-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
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

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>