<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Find a Ride";

// Root path for includes
$rootPath = "../../";

// Extra CSS for this page
$extraCSS = '<link rel="stylesheet" href="' . $rootPath . 'assets/css/search.css">';

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';

// Mock data for ride results (in production, this would come from the database)
$mockRides = [
    [
        'id' => 1,
        'driver_name' => 'Marco Rossi',
        'driver_rating' => 4.8,
        'driver_trips' => 42,
        'from' => 'Milan',
        'to' => 'Rome',
        'departure_date' => '2025-04-25',
        'departure_time' => '08:30',
        'arrival_time' => '13:30',
        'price' => 25,
        'seats_available' => 3,
        'car' => 'Toyota Prius',
        'features' => ['AC', 'WiFi', 'No smoking']
    ],
    [
        'id' => 2,
        'driver_name' => 'Giulia Bianchi',
        'driver_rating' => 4.9,
        'driver_trips' => 78,
        'from' => 'Venice',
        'to' => 'Florence',
        'departure_date' => '2025-04-24',
        'departure_time' => '10:15',
        'arrival_time' => '13:00',
        'price' => 18,
        'seats_available' => 2,
        'car' => 'Fiat 500',
        'features' => ['AC', 'Pets allowed']
    ],
    [
        'id' => 3,
        'driver_name' => 'Alessandro Verdi',
        'driver_rating' => 4.5,
        'driver_trips' => 23,
        'from' => 'Naples',
        'to' => 'Bari',
        'departure_date' => '2025-04-26',
        'departure_time' => '07:00',
        'arrival_time' => '11:30',
        'price' => 22,
        'seats_available' => 4,
        'car' => 'Renault Clio',
        'features' => ['AC', 'No smoking']
    ],
    [
        'id' => 4,
        'driver_name' => 'Francesca Neri',
        'driver_rating' => 4.7,
        'driver_trips' => 36,
        'from' => 'Turin',
        'to' => 'Genoa',
        'departure_date' => '2025-04-23',
        'departure_time' => '14:45',
        'arrival_time' => '16:30',
        'price' => 15,
        'seats_available' => 1,
        'car' => 'Volkswagen Golf',
        'features' => ['AC', 'WiFi', 'Music']
    ]
];

// Simulate filtering by checking if search parameters are set
$filtered = false;
if (isset($_GET['from']) && !empty($_GET['from'])) {
    $filtered = true;
    // In a real app, you'd filter the results based on search parameters
}

// Define search parameters from the form (or set defaults)
$searchFrom = $_GET['from'] ?? '';
$searchTo = $_GET['to'] ?? '';
$searchDate = $_GET['date'] ?? date('Y-m-d');
$searchPassengers = $_GET['passengers'] ?? 1;
?>

<!-- Search Section -->
<div class="container my-5">
    <!-- Search Form -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <h4 class="card-title mb-4">Find your perfect ride</h4>
            <form method="GET" action="search.php">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label for="from" class="form-label">From</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" class="form-control" id="from" name="from" placeholder="City or town" value="<?php echo htmlspecialchars($searchFrom); ?>">
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label for="to" class="form-label">To</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                            <input type="text" class="form-control" id="to" name="to" placeholder="City or town" value="<?php echo htmlspecialchars($searchTo); ?>">
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4">
                        <label for="date" class="form-label">Date</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($searchDate); ?>">
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4">
                        <label for="passengers" class="form-label">Passengers</label>
                        <select class="form-select" id="passengers" name="passengers">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($i == $searchPassengers) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i> Search
                        </button>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <a class="btn btn-link text-decoration-none" data-bs-toggle="collapse" href="#advancedSearch" role="button">
                            Advanced search <i class="bi bi-chevron-down ms-1"></i>
                        </a>
                    </div>
                </div>
                
                <div class="collapse" id="advancedSearch">
                    <div class="row g-3 mt-2">
                        <div class="col-md-3">
                            <label class="form-label">Price range</label>
                            <div class="d-flex align-items-center">
                                <input type="number" class="form-control" placeholder="Min" name="min_price">
                                <span class="mx-2">-</span>
                                <input type="number" class="form-control" placeholder="Max" name="max_price">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Departure time</label>
                            <select class="form-select" name="depart_time">
                                <option value="">Any time</option>
                                <option value="morning">Morning (6:00 - 12:00)</option>
                                <option value="afternoon">Afternoon (12:00 - 18:00)</option>
                                <option value="evening">Evening (18:00 - 00:00)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Features</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="soste" name="features[]" value="soste">
                                    <label class="form-check-label" for="soste">Stops along the way</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="bagaglio" name="features[]" value="bagaglio">
                                    <label class="form-check-label" for="bagaglio">Luggage allowed</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="animali" name="features[]" value="animali">
                                    <label class="form-check-label" for="animali">Pets allowed</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results Section -->
    <?php if (!$filtered && empty($searchFrom)): ?>
    <div class="text-center py-5">
        <div class="mb-4">
            <img src="<?php echo $rootPath; ?>assets/img/search-illustration.svg" alt="Search illustration" class="img-fluid" style="max-height: 200px;">
        </div>
        <h3>Start your search</h3>
        <p class="text-muted">Enter your departure and arrival cities to find available rides</p>
    </div>
    <?php else: ?>
    
    <!-- Results Stats -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><?php echo count($mockRides); ?> rides found</h4>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($searchFrom); ?> to <?php echo htmlspecialchars($searchTo); ?>, <?php echo date('j M Y', strtotime($searchDate)); ?></p>
        </div>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                Sort by: <span class="fw-medium">Departure time</span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                <li><a class="dropdown-item" href="#">Departure time</a></li>
                <li><a class="dropdown-item" href="#">Price: low to high</a></li>
                <li><a class="dropdown-item" href="#">Price: high to low</a></li>
                <li><a class="dropdown-item" href="#">Duration</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Ride Results -->
    <div class="row g-4 mb-5">
        <?php foreach ($mockRides as $ride): ?>
        <div class="col-12">
            <div class="card ride-card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Driver Info -->
                        <div class="col-md-3 border-end p-4 text-center">
                            <div class="driver-avatar mb-3">
                                <img src="<?php echo $rootPath; ?>assets/img/avatar-placeholder.jpg" alt="Driver profile" class="rounded-circle" width="80" height="80">
                            </div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($ride['driver_name']); ?></h6>
                            <div class="text-warning mb-2">
                                <i class="bi bi-star-fill"></i>
                                <span class="ms-1"><?php echo $ride['driver_rating']; ?></span>
                            </div>
                            <p class="small text-muted mb-0"><?php echo $ride['driver_trips']; ?> trips</p>
                        </div>
                        
                        <!-- Ride Details -->
                        <div class="col-md-6 p-4">
                            <div class="d-flex align-items-center">
                                <div class="timeline">
                                    <div class="timeline-start"></div>
                                    <div class="timeline-line"></div>
                                    <div class="timeline-end"></div>
                                </div>
                                
                                <div class="ms-3 flex-grow-1">
                                    <div class="departure mb-4">
                                        <p class="small text-muted mb-0"><?php echo $ride['departure_time']; ?></p>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($ride['from']); ?></h5>
                                    </div>
                                    
                                    <div class="arrival">
                                        <p class="small text-muted mb-0"><?php echo $ride['arrival_time']; ?></p>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($ride['to']); ?></h5>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="ride-features d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-calendar3 me-1"></i> <?php echo date('j M Y', strtotime($ride['departure_date'])); ?></span>
                                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-person me-1"></i> <?php echo $ride['seats_available']; ?> seats</span>
                                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-car-front me-1"></i> <?php echo htmlspecialchars($ride['car']); ?></span>
                                
                                <?php foreach ($ride['features'] as $feature): ?>
                                <span class="badge rounded-pill bg-light text-dark"><?php echo htmlspecialchars($feature); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Price & Booking -->
                        <div class="col-md-3 p-4 d-flex flex-column justify-content-center align-items-center">
                            <div class="price-tag mb-3">
                                <span class="h3 fw-bold">€<?php echo $ride['price']; ?></span>
                                <span class="text-muted">per seat</span>
                            </div>
                            
                            <div class="d-grid w-100">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="booking.php?ride_id=<?php echo $ride['id']; ?>" class="btn btn-primary">Book Now</a>
                                <?php else: ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginSignupModal" data-ride-id="<?php echo $ride['id']; ?>">
                                    Book Now
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <nav aria-label="Page navigation" class="d-flex justify-content-center">
        <ul class="pagination">
            <li class="page-item disabled">
                <a class="page-link" href="#" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
            <li class="page-item">
                <a class="page-link" href="#" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Login/Signup Modal for non-registered users -->
<?php if (!isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="loginSignupModal" tabindex="-1" aria-labelledby="loginSignupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <img src="<?php echo $rootPath; ?>assets/img/signup-illustration.svg" alt="Sign up" class="img-fluid mb-4" style="max-height: 150px;">
                <h4 class="mb-3">Join RideTogether to book this ride</h4>
                <p class="text-muted mb-4">Create an account or log in to book rides, message drivers, and enjoy all features</p>
                <div class="d-grid gap-2">
                    <a href="<?php echo $rootPath; ?>register.php?redirect=search&params=<?php echo urlencode(http_build_query($_GET)); ?>" class="btn btn-primary btn-lg">
                        Sign up
                    </a>
                    <a href="<?php echo $rootPath; ?>login.php?redirect=search&params=<?php echo urlencode(http_build_query($_GET)); ?>" class="btn btn-outline-primary">
                        Already have an account? Log in
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Extra JavaScript for this page
$extraJS = '
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // Store ride ID for modal
    var loginSignupModal = document.getElementById("loginSignupModal")
    if (loginSignupModal) {
        loginSignupModal.addEventListener("show.bs.modal", function (event) {
            var button = event.relatedTarget
            var rideId = button.getAttribute("data-ride-id")
            var signupLink = this.querySelector("a.btn-primary")
            var loginLink = this.querySelector("a.btn-outline-primary")
            
            // Update the links with the ride ID
            var currentSignupHref = signupLink.getAttribute("href")
            signupLink.setAttribute("href", currentSignupHref + "&ride_id=" + rideId)
            
            var currentLoginHref = loginLink.getAttribute("href")
            loginLink.setAttribute("href", currentLoginHref + "&ride_id=" + rideId)
        })
    }
</script>
';

// Include CSS for the search page
include $rootPath . 'includes/footer.php';
?>