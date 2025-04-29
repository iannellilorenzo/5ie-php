<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Trova un Viaggio";

// Root path for includes
$rootPath = "../../";

// Extra CSS for this page
$extraCSS = '<link rel="stylesheet" href="' . $rootPath . 'assets/css/search.css">';

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Viaggio.php';
require_once $rootPath . 'api/models/Autista.php';
require_once $rootPath . 'api/models/Prenotazione.php';

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';

// Define search parameters from the form (or set defaults)
$searchFrom = $_GET['from'] ?? '';
$searchTo = $_GET['to'] ?? '';
$searchDate = $_GET['date'] ?? date('Y-m-d');
$searchPassengers = $_GET['passengers'] ?? 1;
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$departTime = $_GET['depart_time'] ?? '';
$features = $_GET['features'] ?? [];

// Initialize variables
$trips = [];
$filtered = false;
$error = null;

// Check if there are search parameters
if (!empty($searchFrom) || !empty($searchTo)) {
    $filtered = true;
    
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        // Create filter criteria
        $filters = [];
        
        if (!empty($searchFrom)) {
            $filters['citta_partenza'] = $searchFrom;
        }
        
        if (!empty($searchTo)) {
            $filters['citta_destinazione'] = $searchTo;
        }
        
        if (!empty($searchDate)) {
            $filters['data_partenza'] = $searchDate;
        }
        
        if (!empty($searchPassengers)) {
            $filters['posti_disponibili'] = $searchPassengers;
        }
        
        if (!empty($minPrice)) {
            $filters['prezzo_min'] = $minPrice;
        }
        
        if (!empty($maxPrice)) {
            $filters['prezzo_max'] = $maxPrice;
        }
        
        if (!empty($departTime)) {
            $filters['orario_partenza'] = $departTime;
        }
        
        if (!empty($features)) {
            foreach ($features as $feature) {
                $filters[$feature] = 1;
            }
        }
        
        // Get trips from database
        $viaggioModel = new Viaggio($conn);
        $allTrips = $viaggioModel->search($filters);
        
        // Format the trips for display
        $trips = [];
        $autistaModel = new Autista($conn);
        $prenotazioneModel = new Prenotazione($conn);
        
        foreach ($allTrips as $trip) {
            // Get driver information
            $driver = $autistaModel->getDriverWithRatings($trip['id_autista']);
            
            // Get driver trip count and rating
            $driverTrips = $viaggioModel->getByDriverId($trip['id_autista']);
            $tripCount = count($driverTrips);
            
            // Get available seats
            $totalSeats = $trip['posti_totali'] ?? 4; // Default 4 if not specified
            $bookings = $prenotazioneModel->getAll(['id_viaggio' => $trip['id_viaggio'], 'stato' => 'confermata']);
            $bookedSeats = array_sum(array_column($bookings, 'n_posti'));
            $availableSeats = $totalSeats - $bookedSeats;
            
            // Skip if not enough seats
            if ($availableSeats < $searchPassengers) {
                continue;
            }
            
            // Format features
            $tripFeatures = [];
            if ($trip['soste'] == 1) $tripFeatures[] = ['icon' => 'bi-signpost-2', 'text' => 'Soste'];
            if ($trip['bagaglio'] == 1) $tripFeatures[] = ['icon' => 'bi-briefcase', 'text' => 'Bagaglio'];
            if ($trip['animali'] == 1) $tripFeatures[] = ['icon' => 'bi-emoji-smile', 'text' => 'Animali'];
            
            // Calculate arrival time based on departure time and estimated duration
            $departureDateTime = new DateTime($trip['timestamp_partenza']);
            $durationParts = explode(':', $trip['tempo_stimato'] ?? '00:00');
            $durationInterval = new DateInterval('PT' . $durationParts[0] . 'H' . $durationParts[1] . 'M');
            $arrivalDateTime = clone $departureDateTime;
            $arrivalDateTime->add($durationInterval);
            
            $trips[] = [
                'id' => $trip['id_viaggio'],
                'driver_id' => $trip['id_autista'],
                'driver_name' => $driver['nome'] . ' ' . $driver['cognome'],
                'driver_rating' => $driver['rating_avg'] ?? 0,
                'driver_trips' => $tripCount,
                'driver_photo' => $driver['fotografia'] ?? '', 
                'from' => $trip['citta_partenza'],
                'to' => $trip['citta_destinazione'],
                'departure_date' => date('Y-m-d', strtotime($trip['timestamp_partenza'])),
                'departure_time' => date('H:i', strtotime($trip['timestamp_partenza'])),
                'arrival_time' => $arrivalDateTime->format('H:i'),
                'price' => $trip['prezzo_cadauno'],
                'seats_available' => $availableSeats,
                'seats_total' => $totalSeats,
                'car' => $trip['marca'] . ' ' . $trip['modello'],
                'car_color' => $trip['colore'] ?? '',
                'features' => $tripFeatures,
                'duration' => $trip['tempo_stimato'] ?? '00:00'
            ];
        }
    } catch (Exception $e) {
        $error = "Errore nel recupero dei viaggi: " . $e->getMessage();
    }
}

// Funzione per visualizzare le stelle in base al rating
function displayStars($rating) {
    $html = '';
    $rating = round($rating);
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            $html .= '<i class="bi bi-star text-muted"></i>';
        }
    }
    return $html;
}

// Funzione per formattare la durata
function formatDuration($duration) {
    $parts = explode(':', $duration);
    $hours = intval($parts[0]);
    $minutes = intval($parts[1]);
    
    if ($hours > 0) {
        return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
    } else {
        return $minutes . ' min';
    }
}
?>

<!-- Custom CSS -->
<style>
    .search-hero {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border-radius: 16px;
        padding: 2.5rem;
        margin-bottom: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .search-hero h2 {
        color: white;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }
    
    .form-card {
        background-color: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }
    
    .form-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.18);
    }
    
    .ride-card {
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .ride-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
    }
    
    .timeline {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        height: 100%;
    }
    
    .timeline-start, .timeline-end {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    
    .timeline-start {
        background-color: #4e73df;
    }
    
    .timeline-end {
        background-color: #1cc88a;
    }
    
    .timeline-line {
        flex-grow: 1;
        width: 2px;
        background-color: #e3e6f0;
        margin: 8px 0;
    }
    
    .driver-avatar img {
        border: 3px solid white;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .ride-features .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
    }
    
    .price-tag {
        text-align: center;
    }
    
    .price-tag .h3 {
        color: #4e73df;
    }
    
    .search-results-header {
        border-bottom: 1px solid #e3e6f0;
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }
    
    .empty-results {
        text-align: center;
        padding: 5rem 0;
    }
    
    .empty-results-icon {
        font-size: 4rem;
        color: #e3e6f0;
        margin-bottom: 1.5rem;
    }
    
    .driver-info {
        position: relative;
    }
    
    .driver-info::after {
        content: '';
        position: absolute;
        top: 15%;
        right: 0;
        height: 70%;
        width: 1px;
        background-color: #e3e6f0;
    }
    
    .driver-link {
        text-decoration: none;
        color: inherit;
        transition: color 0.3s ease;
    }
    
    .driver-link:hover {
        color: #4e73df;
    }
    
    .seats-progress {
        height: 6px;
        border-radius: 3px;
    }
    
    .feature-icon {
        width: 18px;
        margin-right: 5px;
    }
    
    @media (max-width: 767.98px) {
        .search-hero {
            padding: 1.5rem;
        }
        
        .driver-info::after {
            display: none;
        }
        
        .ride-card .row > div {
            padding: 1rem;
        }
    }
</style>

<div class="container py-5">
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Search Hero Section -->
    <div class="search-hero animate__animated animate__fadeIn">
        <h2>Trova il tuo prossimo viaggio</h2>
        <div class="form-card">
            <form method="GET" action="search.php">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label for="from" class="form-label">Partenza</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" class="form-control border-start-0" id="from" name="from" placeholder="Città o località" value="<?php echo htmlspecialchars($searchFrom); ?>">
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label for="to" class="form-label">Destinazione</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-geo-alt-fill"></i></span>
                            <input type="text" class="form-control border-start-0" id="to" name="to" placeholder="Città o località" value="<?php echo htmlspecialchars($searchTo); ?>">
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4">
                        <label for="date" class="form-label">Data</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control border-start-0" id="date" name="date" value="<?php echo htmlspecialchars($searchDate); ?>">
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4">
                        <label for="passengers" class="form-label">Passeggeri</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                            <select class="form-select border-start-0" id="passengers" name="passengers">
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i == $searchPassengers) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-search me-2"></i> Cerca
                        </button>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <a class="btn btn-link text-white text-decoration-none" data-bs-toggle="collapse" href="#advancedSearch" role="button">
                            Ricerca avanzata <i class="bi bi-chevron-down ms-1"></i>
                        </a>
                    </div>
                </div>
                
                <div class="collapse" id="advancedSearch">
                    <div class="row g-3 mt-2">
                        <div class="col-md-3">
                            <label class="form-label">Fascia di prezzo</label>
                            <div class="d-flex align-items-center">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">€</span>
                                    <input type="number" class="form-control" placeholder="Min" name="min_price" value="<?php echo htmlspecialchars($minPrice); ?>">
                                </div>
                                <span class="mx-2">-</span>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">€</span>
                                    <input type="number" class="form-control" placeholder="Max" name="max_price" value="<?php echo htmlspecialchars($maxPrice); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Orario partenza</label>
                            <select class="form-select" name="depart_time">
                                <option value="">Qualsiasi orario</option>
                                <option value="morning" <?php echo ($departTime === 'morning') ? 'selected' : ''; ?>>Mattina (6:00 - 12:00)</option>
                                <option value="afternoon" <?php echo ($departTime === 'afternoon') ? 'selected' : ''; ?>>Pomeriggio (12:00 - 18:00)</option>
                                <option value="evening" <?php echo ($departTime === 'evening') ? 'selected' : ''; ?>>Sera (18:00 - 00:00)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Caratteristiche</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="soste" name="features[]" value="soste" <?php echo (in_array('soste', $features)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="soste">Soste durante il viaggio</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="bagaglio" name="features[]" value="bagaglio" <?php echo (in_array('bagaglio', $features)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bagaglio">Bagaglio consentito</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="animali" name="features[]" value="animali" <?php echo (in_array('animali', $features)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="animali">Animali consentiti</label>
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
    <div class="text-center py-5 animate__animated animate__fadeIn">
        <div class="mb-4">
            <i class="bi bi-signpost-2" style="font-size: 5rem; color: #4e73df;"></i>
        </div>
        <h3 class="mb-3">Inizia la tua ricerca</h3>
        <p class="text-muted">Inserisci la tua città di partenza e di arrivo per trovare i viaggi disponibili</p>
        <div class="mt-4">
            <div class="d-flex justify-content-center gap-4">
                <div class="text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; margin: 0 auto;">
                        <i class="bi bi-search" style="font-size: 2rem; color: #4e73df;"></i>
                    </div>
                    <h6>Cerca</h6>
                    <small class="text-muted">Trova viaggi che corrispondono ai tuoi criteri</small>
                </div>
                <div class="text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; margin: 0 auto;">
                        <i class="bi bi-calendar-check" style="font-size: 2rem; color: #1cc88a;"></i>
                    </div>
                    <h6>Prenota</h6>
                    <small class="text-muted">Scegli e prenota il tuo posto</small>
                </div>
                <div class="text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; margin: 0 auto;">
                        <i class="bi bi-car-front" style="font-size: 2rem; color: #f6c23e;"></i>
                    </div>
                    <h6>Viaggia</h6>
                    <small class="text-muted">Goditi il tuo viaggio condiviso</small>
                </div>
            </div>
        </div>
    </div>
    <?php elseif($filtered && empty($trips)): ?>
    <!-- No Results Found -->
    <div class="empty-results animate__animated animate__fadeIn">
        <i class="bi bi-calendar-x empty-results-icon"></i>
        <h4>Nessun viaggio trovato</h4>
        <p class="text-muted mb-4">Non abbiamo trovato viaggi corrispondenti ai tuoi criteri di ricerca.</p>
        <div class="mt-4">
            <a href="search.php" class="btn btn-outline-primary me-2">Reimposta filtri</a>
            <a href="#" class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#advancedSearch">Modifica ricerca avanzata</a>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Results Stats -->
    <div class="search-results-header animate__animated animate__fadeIn">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-1"><?php echo count($trips); ?> <?php echo (count($trips) == 1) ? 'viaggio trovato' : 'viaggi trovati'; ?></h4>
                <p class="text-muted mb-0">
                    <span class="fw-medium"><?php echo htmlspecialchars($searchFrom); ?></span> → 
                    <span class="fw-medium"><?php echo htmlspecialchars($searchTo); ?></span>, 
                    <span class="fw-medium"><?php echo date('j M Y', strtotime($searchDate)); ?></span>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Ordina per: <span class="fw-medium">Orario di partenza</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item active" href="#"><i class="bi bi-clock me-2"></i> Orario di partenza</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-currency-euro me-2"></i> Prezzo: dal più basso</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-currency-euro me-2"></i> Prezzo: dal più alto</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-stopwatch me-2"></i> Durata</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ride Results -->
    <div class="row g-4 mb-5">
        <?php foreach ($trips as $index => $ride): ?>
        <div class="col-12 animate__animated animate__fadeIn" style="animation-delay: <?php echo 0.1 * $index; ?>s;">
            <div class="card ride-card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Driver Info -->
                        <div class="col-md-3 driver-info p-4 text-center">
                            <a href="<?php echo $rootPath; ?>pages/view-profile.php?id=<?php echo $ride['driver_id']; ?>&type=autista" class="driver-link">
                                <div class="driver-avatar mb-3">
                                    <?php if (!empty($ride['driver_photo']) && file_exists($rootPath . $ride['driver_photo'])): ?>
                                        <img src="<?php echo $rootPath . $ride['driver_photo']; ?>" alt="Driver profile" class="rounded-circle" width="80" height="80">
                                    <?php else: ?>
                                        <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" alt="Driver profile" class="rounded-circle" width="80" height="80">
                                    <?php endif; ?>
                                </div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($ride['driver_name']); ?></h6>
                                <div class="mb-2">
                                    <?php echo displayStars($ride['driver_rating']); ?>
                                    <div class="small text-muted"><?php echo number_format($ride['driver_rating'], 1); ?></div>
                                </div>
                                <p class="small text-muted mb-0"><?php echo $ride['driver_trips']; ?> viaggi</p>
                            </a>
                        </div>
                        
                        <!-- Ride Details -->
                        <div class="col-md-6 p-4">
                            <div class="d-flex align-items-center mb-4">
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
                                
                                <div class="text-center text-md-end ms-3">
                                    <span class="badge bg-light text-dark px-3 py-2">
                                        <i class="bi bi-clock me-1"></i> <?php echo formatDuration($ride['duration']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <div class="ride-features d-flex flex-wrap gap-2">
                                        <span class="badge rounded-pill bg-light text-dark">
                                            <i class="bi bi-calendar3 me-1"></i> <?php echo date('j M Y', strtotime($ride['departure_date'])); ?>
                                        </span>
                                        
                                        <?php if (!empty($ride['car'])): ?>
                                            <span class="badge rounded-pill bg-light text-dark" data-bs-toggle="tooltip" data-bs-placement="top" title="Veicolo">
                                                <i class="bi bi-car-front me-1"></i> <?php echo htmlspecialchars($ride['car']); ?>
                                                <?php if(!empty($ride['car_color'])): ?> 
                                                    <span class="ms-1">(<?php echo htmlspecialchars($ride['car_color']); ?>)</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php foreach ($ride['features'] as $feature): ?>
                                            <span class="badge rounded-pill bg-light text-dark">
                                                <i class="bi <?php echo $feature['icon']; ?> me-1"></i> <?php echo htmlspecialchars($feature['text']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-5 mt-3 mt-md-0">
                                    <div>
                                        <small class="text-muted d-flex justify-content-between mb-1">
                                            <span>Posti disponibili</span>
                                            <span><?php echo $ride['seats_available']; ?> / <?php echo $ride['seats_total']; ?></span>
                                        </small>
                                        <div class="progress seats-progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                style="width: <?php echo 100 * ($ride['seats_available'] / $ride['seats_total']); ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Price & Booking -->
                        <div class="col-md-3 p-4 d-flex flex-column justify-content-center align-items-center">
                            <div class="price-tag mb-3">
                                <span class="h3 fw-bold">€<?php echo number_format($ride['price'], 2); ?></span>
                                <span class="text-muted">a persona</span>
                            </div>
                            
                            <div class="d-grid gap-2 w-100">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="booking.php?ride_id=<?php echo $ride['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i> Prenota
                                </a>
                                <?php else: ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginSignupModal" data-ride-id="<?php echo $ride['id']; ?>">
                                    <i class="bi bi-check-circle me-2"></i> Prenota
                                </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#tripDetailsModal<?php echo $ride['id']; ?>">
                                    <i class="bi bi-info-circle me-1"></i> Dettagli
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Trip Details Modal -->
            <div class="modal fade" id="tripDetailsModal<?php echo $ride['id']; ?>" tabindex="-1" aria-labelledby="tripDetailsModalLabel<?php echo $ride['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tripDetailsModalLabel<?php echo $ride['id']; ?>">
                                Dettagli viaggio
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="timeline">
                                            <div class="timeline-start"></div>
                                            <div class="timeline-line"></div>
                                            <div class="timeline-end"></div>
                                        </div>
                                        
                                        <div class="ms-3">
                                            <div class="mb-3">
                                                <p class="small text-muted mb-1">Partenza - <?php echo $ride['departure_time']; ?></p>
                                                <h5 class="mb-0"><?php echo htmlspecialchars($ride['from']); ?></h5>
                                            </div>
                                            
                                            <div>
                                                <p class="small text-muted mb-1">Arrivo - <?php echo $ride['arrival_time']; ?></p>
                                                <h5 class="mb-0"><?php echo htmlspecialchars($ride['to']); ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Date e orari</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="bi bi-calendar3 me-2"></i> Data: <?php echo date('d/m/Y', strtotime($ride['departure_date'])); ?></li>
                                            <li><i class="bi bi-clock me-2"></i> Orario di partenza: <?php echo $ride['departure_time']; ?></li>
                                            <li><i class="bi bi-clock-history me-2"></i> Durata stimata: <?php echo formatDuration($ride['duration']); ?></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 border-start">
                                    <div class="d-flex align-items-center mb-4">
                                        <?php if (!empty($ride['driver_photo']) && file_exists($rootPath . $ride['driver_photo'])): ?>
                                            <img src="<?php echo $rootPath . $ride['driver_photo']; ?>" alt="Driver profile" class="rounded-circle" width="60" height="60">
                                        <?php else: ?>
                                            <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" alt="Driver profile" class="rounded-circle" width="60" height="60">
                                        <?php endif; ?>
                                        
                                        <div class="ms-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($ride['driver_name']); ?></h6>
                                            <div>
                                                <?php echo displayStars($ride['driver_rating']); ?>
                                                <small class="text-muted ms-1">(<?php echo number_format($ride['driver_rating'], 1); ?>)</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Veicolo</h6>
                                        <p class="mb-1">
                                            <i class="bi bi-car-front me-2"></i> <?php echo htmlspecialchars($ride['car']); ?>
                                            <?php if(!empty($ride['car_color'])): ?> 
                                                <span class="ms-1">(<?php echo htmlspecialchars($ride['car_color']); ?>)</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Caratteristiche</h6>
                                        <ul class="list-unstyled">
                                            <?php foreach ($ride['features'] as $feature): ?>
                                                <li class="mb-1"><i class="bi <?php echo $feature['icon']; ?> me-2"></i> <?php echo htmlspecialchars($feature['text']); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-top pt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-0">
                                            Prezzo totale: €<?php echo number_format($ride['price'] * $searchPassengers, 2); ?>
                                            <small class="text-muted">(€<?php echo number_format($ride['price'], 2); ?> × <?php echo $searchPassengers; ?>)</small>
                                        </h5>
                                    </div>
                                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="booking.php?ride_id=<?php echo $ride['id']; ?>" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i> Prenota questo viaggio
                                        </a>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginSignupModal" data-ride-id="<?php echo $ride['id']; ?>" data-bs-dismiss="modal">
                                            <i class="bi bi-check-circle me-2"></i> Prenota questo viaggio
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (count($trips) > 10): ?>
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
                <div class="mb-4">
                    <i class="bi bi-person-plus-fill" style="font-size: 3rem; color: #4e73df;"></i>
                </div>
                <h4 class="mb-3">Iscriviti a Carpooling per prenotare questo viaggio</h4>
                <p class="text-muted mb-4">Crea un account o accedi per prenotare viaggi, messaggiare con gli autisti e goderti tutte le funzionalità</p>
                <div class="d-grid gap-2">
                    <a href="<?php echo $rootPath; ?>register.php?redirect=search&params=<?php echo urlencode(http_build_query($_GET)); ?>" class="btn btn-primary btn-lg">
                        Registrati
                    </a>
                    <a href="<?php echo $rootPath; ?>login.php?redirect=search&params=<?php echo urlencode(http_build_query($_GET)); ?>" class="btn btn-outline-primary">
                        Hai già un account? Accedi
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
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
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
            });
        }
        
        // Add animation class to elements
        document.querySelectorAll(".animate__fadeIn").forEach(function(element) {
            element.classList.add("animate__animated");
        });
    });
</script>
';

// Include the link to animate.css library
$extraCSS .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">';

// Include footer
include $rootPath . 'includes/footer.php';
?>