<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'passeggero') {
    header('Location: ../../login.php');
    exit;
}

// Set page title
$pageTitle = "Prenota Viaggio";

// Root path for includes
$rootPath = "../../";

// Extra CSS for this page
$extraCSS = '
<link rel="stylesheet" href="' . $rootPath . 'assets/css/booking.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
';

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Viaggio.php';
require_once $rootPath . 'api/models/Autista.php';
require_once $rootPath . 'api/models/Automobile.php';
require_once $rootPath . 'api/models/Prenotazione.php';
require_once $rootPath . 'api/models/Passeggero.php';

// Get ride ID from GET parameter
$rideId = isset($_GET['ride_id']) ? intval($_GET['ride_id']) : 0;

// Initialize variables
$ride = null;
$driver = null;
$passenger = null;
$car = null;
$error = null;
$success = null;
$availableSeats = 0;
$passengerRating = 0;
$bookingComplete = false;

if ($rideId > 0) {
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get ride details
        $viaggioModel = new Viaggio($conn);
        $ride = $viaggioModel->getById($rideId);
        
        if (!$ride) {
            $error = "Viaggio non trovato.";
        } else {
            // Get driver details
            $autistaModel = new Autista($conn);
            $driver = $autistaModel->getDriverWithRatings($ride['id_autista']);
            
            // Get vehicle details
            $automobileModel = new Automobile($conn);
            $car = $automobileModel->getById($ride['id_automobile']);
            
            // Get passenger details
            $passeggerModel = new Passeggero($conn);
            $passenger = $passeggerModel->getById($_SESSION['user_id']);
            $passengerRating = $passeggerModel->getAverageRating($_SESSION['user_id']);
            
            // Calculate available seats
            $prenotazioneModel = new Prenotazione($conn);
            $confirmedBookings = $prenotazioneModel->getAll(['id_viaggio' => $rideId, 'stato' => 'confermata']);
            $totalBookedSeats = 0;
            
            foreach ($confirmedBookings as $booking) {
                $totalBookedSeats += $booking['n_posti'];
            }
            
            $availableSeats = $ride['posti_totali'] - $totalBookedSeats;
            
            // Check if passenger has already booked this ride
            $existingBooking = $prenotazioneModel->getOne([
                'id_viaggio' => $rideId, 
                'id_passeggero' => $_SESSION['user_id']
            ]);
            
            if ($existingBooking) {
                $bookingComplete = true;
            }
        }
    } catch (Exception $e) {
        $error = "Errore nel recupero dei dettagli del viaggio: " . $e->getMessage();
    }
}

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && !$bookingComplete) {
    $numSeats = isset($_POST['num_seats']) ? intval($_POST['num_seats']) : 1;
    $notes = isset($_POST['note']) ? $_POST['note'] : '';
    
    if ($numSeats <= 0 || $numSeats > $availableSeats) {
        $error = "Numero di posti non valido.";
    } else {
        try {
            // Create booking
            $bookingData = [
                'id_viaggio' => $rideId,
                'id_passeggero' => $_SESSION['user_id'],
                'timestamp' => date('Y-m-d H:i:s'),
                'n_posti' => $numSeats,
                'stato' => 'in attesa',
                'note' => $notes
            ];
            
            $prenotazioneModel = new Prenotazione($conn);
            $result = $prenotazioneModel->create($bookingData);
            
            if ($result) {
                $success = "Prenotazione effettuata con successo! In attesa di conferma dall'autista.";
                $bookingComplete = true;
                
                // Redirect after successful booking
                header('Refresh: 3; URL=dashboard.php');
            } else {
                $error = "Errore durante la prenotazione.";
            }
        } catch (Exception $e) {
            $error = "Errore durante la prenotazione: " . $e->getMessage();
        }
    }
}

// Function to display stars based on rating
function displayStars($rating) {
    $html = '';
    $rating = round($rating * 2) / 2;  // Round to nearest 0.5
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="bi bi-star-fill text-warning"></i>';
    }
    
    if ($halfStar) {
        $html .= '<i class="bi bi-star-half text-warning"></i>';
    }
    
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="bi bi-star text-muted"></i>';
    }
    
    return $html;
}

// Format duration function 
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

<div class="container py-5">
    <?php if ($error): ?>
        <div class="alert alert-danger animate__animated animate__fadeIn" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success animate__animated animate__fadeIn" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
            <div class="mt-2">
                <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                Reindirizzamento alla dashboard...
            </div>
        </div>
    <?php endif; ?>

    <?php if ($ride && !$error): ?>
        <!-- Booking Section -->
        <div class="row">
            <div class="col-lg-8 animate__animated animate__fadeIn">
                <div class="card shadow-sm border-0 rounded-lg mb-4">
                    <div class="card-body p-0">
                        <!-- Ride Header -->
                        <div class="bg-primary bg-gradient p-4 text-white rounded-top">
                            <div class="d-md-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($ride['citta_partenza']); ?> → <?php echo htmlspecialchars($ride['citta_destinazione']); ?>
                                    </h5>
                                    <p class="mb-0">
                                        <?php echo date('l, j F Y', strtotime($ride['timestamp_partenza'])); ?>
                                    </p>
                                </div>
                                <?php if (!$bookingComplete): ?>
                                    <span class="badge bg-white text-primary mt-2 mt-md-0 px-3 py-2 fs-6">
                                        €<?php echo number_format($ride['prezzo_cadauno'], 2); ?> a persona
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Ride Details -->
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-7">
                                    <!-- Journey Details -->
                                    <h6 class="fw-bold mb-3">Dettagli del viaggio</h6>
                                    <div class="d-flex mb-4">
                                        <div class="timeline">
                                            <div class="timeline-start"></div>
                                            <div class="timeline-line"></div>
                                            <div class="timeline-end"></div>
                                        </div>
                                        
                                        <div class="ms-3">
                                            <div class="mb-3">
                                                <p class="text-muted mb-1 small">Partenza - <?php echo date('H:i', strtotime($ride['timestamp_partenza'])); ?></p>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($ride['citta_partenza']); ?></h6>
                                                <?php if(!empty($ride['indirizzo_partenza'])): ?>
                                                    <p class="small text-muted mb-0"><?php echo htmlspecialchars($ride['indirizzo_partenza']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (!empty($ride['tempo_stimato'])): ?>
                                                <div class="small text-muted mb-2">
                                                    <i class="bi bi-clock"></i> <?php echo formatDuration($ride['tempo_stimato']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <p class="text-muted mb-1 small">Arrivo - 
                                                    <?php 
                                                        $departureTime = new DateTime($ride['timestamp_partenza']);
                                                        $durationParts = explode(':', $ride['tempo_stimato'] ?? '00:00');
                                                        $durationInterval = new DateInterval('PT' . $durationParts[0] . 'H' . $durationParts[1] . 'M');
                                                        $arrivalTime = clone $departureTime;
                                                        $arrivalTime->add($durationInterval);
                                                        echo $arrivalTime->format('H:i');
                                                    ?>
                                                </p>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($ride['citta_destinazione']); ?></h6>
                                                <?php if(!empty($ride['indirizzo_destinazione'])): ?>
                                                    <p class="small text-muted mb-0"><?php echo htmlspecialchars($ride['indirizzo_destinazione']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Info -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="features-list">
                                                <h6 class="fw-bold mb-2">Caratteristiche</h6>
                                                <ul class="list-unstyled">
                                                    <li class="mb-2">
                                                        <i class="bi bi-person me-2"></i>
                                                        <span class="badge bg-<?php echo ($availableSeats > 0) ? 'success' : 'danger'; ?> badge-pill">
                                                            <?php echo $availableSeats; ?> posti disponibili
                                                        </span>
                                                    </li>
                                                    <?php if ($ride['soste'] == 1): ?>
                                                        <li class="mb-2"><i class="bi bi-signpost-2 me-2"></i> Soste durante il viaggio</li>
                                                    <?php endif; ?>
                                                    <?php if ($ride['bagaglio'] == 1): ?>
                                                        <li class="mb-2"><i class="bi bi-briefcase me-2"></i> Bagaglio consentito</li>
                                                    <?php endif; ?>
                                                    <?php if ($ride['animali'] == 1): ?>
                                                        <li class="mb-2"><i class="bi bi-emoji-smile me-2"></i> Animali consentiti</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php if ($car): ?>
                                            <div class="col-md-6">
                                                <h6 class="fw-bold mb-2">Veicolo</h6>
                                                <div class="d-flex align-items-center">
                                                    <div class="car-icon bg-light rounded-circle p-2 d-flex align-items-center justify-content-center me-3">
                                                        <i class="bi bi-car-front text-primary" style="font-size: 1.5rem;"></i>
                                                    </div>
                                                    <div>
                                                        <p class="mb-1"><?php echo htmlspecialchars($car['marca'] . ' ' . $car['modello']); ?></p>
                                                        <?php if (!empty($car['colore'])): ?>
                                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($car['colore']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Driver Info -->
                                <div class="col-md-5 border-start">
                                    <h6 class="fw-bold mb-3">Autista</h6>
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="driver-avatar me-3">
                                            <?php if (!empty($driver['fotografia']) && file_exists($rootPath . $driver['fotografia'])): ?>
                                                <img src="<?php echo $rootPath . $driver['fotografia']; ?>" alt="Driver profile" class="rounded-circle shadow-sm" width="60" height="60">
                                            <?php else: ?>
                                                <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" alt="Driver profile" class="rounded-circle shadow-sm" width="60" height="60">
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div>
                                            <h5 class="mb-1">
                                                <a href="<?php echo $rootPath; ?>pages/view-profile.php?id=<?php echo $driver['id']; ?>&type=autista" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($driver['nome'] . ' ' . $driver['cognome']); ?>
                                                </a>
                                            </h5>
                                            
                                            <div class="d-flex align-items-center">
                                                <?php echo displayStars($driver['rating_avg'] ?? 0); ?>
                                                <span class="ms-2 text-muted small">
                                                    <?php echo number_format($driver['rating_avg'] ?? 0, 1); ?> (<?php echo $driver['rating_count'] ?? 0; ?> recensioni)
                                                </span>
                                            </div>
                                            
                                            <?php if ($driver['viaggi_count'] ?? 0 > 0): ?>
                                                <p class="text-muted small mb-0">
                                                    <i class="bi bi-car-front me-1"></i> <?php echo $driver['viaggi_count']; ?> viaggi effettuati
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($driver['bio'])): ?>
                                        <div class="mb-3">
                                            <h6 class="fw-bold mb-2">Bio</h6>
                                            <p class="small"><?php echo htmlspecialchars($driver['bio']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($ride['note_autista'])): ?>
                                        <div class="alert alert-info p-3 small mb-0">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            <strong>Note:</strong> <?php echo htmlspecialchars($ride['note_autista']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!$bookingComplete): ?>
                    <!-- Booking Form -->
                    <div class="card shadow-sm border-0 rounded-lg animate__animated animate__fadeIn animate__delay-1s">
                        <div class="card-header bg-white p-4">
                            <h5 class="mb-0">Completa la prenotazione</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="booking.php?ride_id=<?php echo $rideId; ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="num_seats" class="form-label">Numero di posti <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                            <select class="form-select" id="num_seats" name="num_seats" required>
                                                <?php for ($i = 1; $i <= min(8, $availableSeats); $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo ($i == 1) ? 'posto' : 'posti'; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="form-text">
                                            <?php echo $availableSeats; ?> posti disponibili per questo viaggio
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Costo totale</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-currency-euro"></i></span>
                                            <input type="text" class="form-control" id="total_price" disabled value="<?php echo number_format($ride['prezzo_cadauno'], 2); ?>">
                                        </div>
                                        <div class="form-text">
                                            €<?php echo number_format($ride['prezzo_cadauno'], 2); ?> a persona
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="note" class="form-label">Note per l'autista (opzionale)</label>
                                    <textarea class="form-control" id="note" name="note" rows="3" placeholder="Aggiungi informazioni utili per l'autista..."></textarea>
                                    <div class="form-text">
                                        Ad esempio: bagaglio ingombrante, richieste specifiche, ecc.
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning p-3 mb-4">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Importante</h6>
                                            <p class="mb-0 small">La tua prenotazione sarà inviata all'autista per l'approvazione. Una volta confermata, riceverai una notifica e potrai vedere tutti i dettagli nella tua dashboard. Ricordati di presentarti puntuale al punto di incontro.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-check-circle me-2"></i> Conferma prenotazione
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Booking Complete Info -->
                    <div class="card shadow-sm border-0 rounded-lg bg-success bg-opacity-10 animate__animated animate__fadeIn animate__delay-1s">
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <div class="rounded-circle bg-success bg-opacity-25 d-inline-flex align-items-center justify-content-center p-3 mb-3">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                                </div>
                                <h5>Prenotazione già effettuata!</h5>
                                <p class="text-muted">Hai già effettuato una richiesta di prenotazione per questo viaggio.</p>
                            </div>
                            
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="d-grid gap-2">
                                        <a href="dashboard.php" class="btn btn-primary">
                                            <i class="bi bi-speedometer2 me-2"></i> Vai alla dashboard
                                        </a>
                                        <a href="search.php" class="btn btn-outline-primary">
                                            <i class="bi bi-search me-2"></i> Cerca altri viaggi
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Side Details and Tips -->
            <div class="col-lg-4 mt-4 mt-lg-0">
                <!-- Passenger Info Card -->
                <div class="card shadow-sm border-0 rounded-lg mb-4 animate__animated animate__fadeIn animate__delay-2s">
                    <div class="card-header bg-white p-4">
                        <h5 class="mb-0">Il tuo profilo</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <?php if (!empty($passenger['fotografia']) && file_exists($rootPath . $passenger['fotografia'])): ?>
                                    <img src="<?php echo $rootPath . $passenger['fotografia']; ?>" alt="Your profile" class="rounded-circle" width="60" height="60">
                                <?php else: ?>
                                    <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" alt="Your profile" class="rounded-circle" width="60" height="60">
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <h6 class="mb-1">
                                    <?php echo htmlspecialchars($passenger['nome'] . ' ' . $passenger['cognome']); ?>
                                </h6>
                                <div class="d-flex align-items-center">
                                    <?php echo displayStars($passengerRating); ?>
                                    <span class="ms-2 text-muted small">
                                        <?php echo number_format($passengerRating, 1); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-0">
                            <a href="<?php echo $rootPath; ?>pages/passeggero/profile.php" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-pencil me-2"></i> Modifica profilo
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Tips Card -->
                <div class="card shadow-sm border-0 rounded-lg animate__animated animate__fadeIn animate__delay-2s">
                    <div class="card-header bg-white p-4">
                        <h5 class="mb-0">Consigli di viaggio</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 py-3 border-bottom d-flex">
                                <div class="text-primary me-3">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Sii puntuale</h6>
                                    <p class="small text-muted mb-0">Arriva al punto di incontro con almeno 5-10 minuti di anticipo.</p>
                                </div>
                            </li>
                            <li class="list-group-item px-0 py-3 border-bottom d-flex">
                                <div class="text-primary me-3">
                                    <i class="bi bi-chat-dots"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Comunicazione</h6>
                                    <p class="small text-muted mb-0">Mantieni attivi i contatti con l'autista, specialmente in caso di imprevisti.</p>
                                </div>
                            </li>
                            <li class="list-group-item px-0 py-3 border-bottom d-flex">
                                <div class="text-primary me-3">
                                    <i class="bi bi-briefcase"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Bagaglio leggero</h6>
                                    <p class="small text-muted mb-0">Porta solo l'essenziale per rispettare lo spazio degli altri passeggeri.</p>
                                </div>
                            </li>
                            <li class="list-group-item px-0 py-3 d-flex">
                                <div class="text-primary me-3">
                                    <i class="bi bi-star"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Lascia una recensione</h6>
                                    <p class="small text-muted mb-0">Dopo il viaggio, ricordati di valutare la tua esperienza con l'autista.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif(!$error): ?>
        <!-- No Ride Selected -->
        <div class="text-center py-5 animate__animated animate__fadeIn">
            <div class="mb-4">
                <i class="bi bi-car-front" style="font-size: 5rem; color: #4e73df;"></i>
            </div>
            <h3 class="mb-3">Nessun viaggio selezionato</h3>
            <p class="text-muted mb-4">Seleziona un viaggio dalla pagina di ricerca per effettuare una prenotazione.</p>
            <a href="search.php" class="btn btn-primary btn-lg">
                <i class="bi bi-search me-2"></i> Cerca viaggi
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for updating total price -->
<?php
$extraJS = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Update total price when number of seats changes
    const seatSelect = document.getElementById("num_seats");
    const totalPriceInput = document.getElementById("total_price");
    
    if (seatSelect && totalPriceInput) {
        const pricePerSeat = ' . $ride['prezzo_cadauno'] . ';
        
        seatSelect.addEventListener("change", function() {
            const numSeats = parseInt(this.value);
            const totalPrice = (numSeats * pricePerSeat).toFixed(2);
            totalPriceInput.value = totalPrice;
        });
    }
    
    // Add animation class to elements
    document.querySelectorAll(".animate__fadeIn").forEach(function(element) {
        element.classList.add("animate__animated");
    });
});
</script>
';

// Include footer
include $rootPath . 'includes/footer.php';
?>