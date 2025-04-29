<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Valuta il Passeggero";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'autista') {
    // Redirect to login
    header("Location: {$rootPath}login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Prenotazione.php';
require_once $rootPath . 'api/models/Passeggero.php';

// Initialize
$error = null;
$success = false;
$booking = null;

// Check if booking ID is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    $error = "ID prenotazione richiesto";
} else {
    $bookingId = $_GET['booking_id'];
    
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get booking details
        $prenotazioneModel = new Prenotazione($conn);
        $booking = $prenotazioneModel->getById($bookingId);
        
        // Verify that the booking belongs to a trip by the logged-in driver and is completed
        if (!$booking) {
            $error = "Prenotazione non trovata";
        } else if ($booking['id_autista'] != $_SESSION['user_id']) {
            $error = "Non puoi valutare questa prenotazione";
        } else if ($booking['stato'] !== 'completato') {
            $error = "Puoi valutare solo viaggi completati";
        } else if (!empty($booking['voto_passeggero'])) {
            $error = "Hai già valutato questo passeggero";
        }
        
        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
            $feedback = trim(filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_SPECIAL_CHARS));
            
            // Validate input
            if ($rating < 1 || $rating > 5) {
                $error = "La valutazione deve essere compresa tra 1 e 5";
            } else {
                // Update booking with rating
                $updateData = [
                    'voto_passeggero' => $rating
                ];
                
                // Feedback is optional
                if (!empty($feedback)) {
                    $updateData['feedback_passeggero'] = $feedback;
                }
                
                // Update booking
                $prenotazioneModel->update($bookingId, $updateData);
                $success = true;
            }
        }
    } catch (Exception $e) {
        $error = "Errore: " . $e->getMessage();
    }
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<div class="container-wrapper">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <?php if ($success): ?>
                <div class="card border-0 shadow-sm text-center p-5 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-success opacity-10"></div>
                    <div class="position-relative">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="fw-bold mb-3">Grazie per la Valutazione!</h2>
                        <p class="text-muted mb-4">Il tuo feedback aiuta a migliorare la nostra community e promuove viaggi più sicuri e piacevoli per tutti.</p>
                        <div class="d-grid gap-2">
                            <a href="<?php echo $rootPath; ?>pages/autista/dashboard.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-house me-2"></i>Torna alla Dashboard
                            </a>
                            <a href="<?php echo $rootPath; ?>pages/autista/trips.php" class="btn btn-outline-primary">
                                <i class="bi bi-car-front me-2"></i>Gestisci i tuoi viaggi
                            </a>
                        </div>
                    </div>
                </div>
            <?php elseif ($error !== null): ?>
                <div class="card border-0 shadow-sm text-center p-5 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-danger opacity-10"></div>
                    <div class="position-relative">
                        <div class="mb-4">
                            <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="fw-bold mb-3">Si è verificato un errore</h2>
                        <p class="text-danger mb-4"><?php echo $error; ?></p>
                        <div class="d-grid gap-2">
                            <a href="<?php echo $rootPath; ?>pages/autista/dashboard.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-house me-2"></i>Torna alla Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php elseif ($booking): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white py-3 text-center position-relative">
                        <div class="position-absolute top-0 start-0 p-3">
                            <a href="<?php echo $rootPath; ?>pages/autista/trips.php" class="text-white">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                        <h4 class="mb-0 fw-bold">Valuta il Passeggero</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="rating-intro">
                                <p class="text-muted">Il tuo feedback aiuta a migliorare la nostra community e promuove viaggi più sicuri e piacevoli per tutti.</p>
                            </div>
                        </div>
                        
                        <!-- Passenger & Trip Info -->
                        <div class="passenger-info mb-4 p-4 bg-light rounded-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3 position-relative">
                                    <?php if (!empty($booking['fotografia']) && file_exists($rootPath . $booking['fotografia'])): ?>
                                        <img src="<?php echo $rootPath . $booking['fotografia']; ?>" alt="Passenger" class="rounded-circle shadow-sm" width="70" height="70" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:70px;height:70px">
                                            <i class="bi bi-person text-primary" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($booking['nome_passeggero'] . ' ' . $booking['cognome_passeggero']); ?></h5>
                                    <p class="text-muted mb-0 fs-6">
                                        <i class="bi bi-person me-1"></i> Passeggero
                                    </p>
                                </div>
                            </div>
                            
                            <div class="trip-details p-3 bg-white rounded-3 border border-1 mt-3">
                                <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
                                    <div class="mb-2 mb-md-0">
                                        <span class="text-muted fs-6 fw-light">Viaggio del</span>
                                        <div class="fs-5 fw-semibold">
                                            <i class="bi bi-calendar3 me-2 text-primary"></i>
                                            <?php echo date('d F Y', strtotime($booking['timestamp_partenza'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <span class="text-muted fs-6 fw-light">Ora partenza</span>
                                        <div class="fs-5 fw-semibold">
                                            <i class="bi bi-clock me-2 text-primary"></i>
                                            <?php echo date('H:i', strtotime($booking['timestamp_partenza'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="route-display">
                                    <div class="d-flex align-items-center">
                                        <div class="route-dots me-3">
                                            <div class="route-dot-start bg-success rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                                                <i class="bi bi-geo-alt-fill text-white"></i>
                                            </div>
                                            <div class="route-line flex-grow-1 mx-auto my-1" style="width:2px;height:30px;background-color:#dee2e6"></div>
                                            <div class="route-dot-end bg-danger rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px">
                                                <i class="bi bi-geo-alt-fill text-white"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="route-info flex-grow-1">
                                            <div class="mb-2">
                                                <div class="fw-bold"><?php echo htmlspecialchars($booking['citta_partenza']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['indirizzo_partenza'] ?? ''); ?></small>
                                            </div>
                                            
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($booking['citta_destinazione']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['indirizzo_destinazione'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rating Form -->
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="rating-form">
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-5">Com'è stato il tuo passeggero?</label>
                                <div class="star-rating text-center my-3">
                                    <div class="rating-container d-flex justify-content-center rating-lg">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <div class="star-item mx-1">
                                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" class="star-input" <?php echo $i === 1 ? 'required' : ''; ?>>
                                            <label for="star<?php echo $i; ?>" class="star-label">
                                                <i class="bi bi-star"></i>
                                            </label>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                    <div id="ratingText" class="rating-text mt-2 fw-light fst-italic">Seleziona una valutazione</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="feedback" class="form-label fw-bold fs-5">Feedback (opzionale)</label>
                                <div class="form-text text-muted mb-2">
                                    <i class="bi bi-info-circle me-1"></i> La tua esperienza aiuterà gli altri autisti a conoscere meglio questo passeggero.
                                </div>
                                <textarea class="form-control form-control-lg shadow-sm" id="feedback" name="feedback" rows="4" placeholder="Descrivi la tua esperienza con questo passeggero. È stato puntuale? Educato? Consiglieresti ad altri autisti di dargli un passaggio?"></textarea>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg py-3">
                                    <i class="bi bi-check-circle me-2"></i>Invia Valutazione
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<style>
/* Stili per il sistema di rating */
.rating-lg {
    font-size: 2.5rem;
}

.star-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.star-item {
    cursor: pointer;
    padding: 0 2px;
}

.star-label {
    cursor: pointer;
    color: #ced4da;
    transition: all 0.2s ease;
}

.star-label:hover {
    transform: scale(1.2);
}

.rating-text {
    color: #6c757d;
    font-size: 0.95rem;
    height: 24px;
}

/* Animazioni */
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}

.btn-primary {
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 116, 244, 0.35);
}

.card {
    transition: all 0.3s ease;
}

.bg-success.opacity-10 {
    opacity: 0.1;
}

.bg-danger.opacity-10 {
    opacity: 0.1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema di rating migliorato
    const starLabels = document.querySelectorAll('.star-label');
    const starInputs = document.querySelectorAll('.star-input');
    const ratingText = document.getElementById('ratingText');
    
    const ratingDescriptions = [
        'Seleziona una valutazione',
        'Molto negativo - 1 stella',
        'Insoddisfacente - 2 stelle',
        'Nella media - 3 stelle',
        'Buono - 4 stelle',
        'Eccellente - 5 stelle'
    ];
    
    // Gestione eventi hover e click per le stelle
    starLabels.forEach((label, index) => {
        const starNumber = index + 1;
        
        label.addEventListener('mouseover', () => {
            // Aggiorna tutte le stelle al passaggio del mouse
            updateStarsDisplay(starNumber);
            ratingText.textContent = ratingDescriptions[starNumber];
        });
        
        label.addEventListener('click', () => {
            // Imposta il valore dell'input radio
            starInputs[index].checked = true;
            
            // Aggiorna la visualizzazione delle stelle
            updateStarsDisplay(starNumber);
            ratingText.textContent = ratingDescriptions[starNumber];
            
            // Effetto animato al click
            label.style.transform = 'scale(1.3)';
            setTimeout(() => {
                label.style.transform = 'scale(1)';
            }, 200);
        });
    });
    
    // Gestione dell'uscita del mouse dal contenitore (ripristina lo stato selezionato)
    const ratingContainer = document.querySelector('.rating-container');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', () => {
            let selected = 0;
            starInputs.forEach((input, i) => {
                if (input.checked) {
                    selected = i + 1;
                }
            });
            
            updateStarsDisplay(selected);
            ratingText.textContent = selected > 0 ? ratingDescriptions[selected] : ratingDescriptions[0];
        });
    }
    
    // Funzione per aggiornare la visualizzazione delle stelle
    function updateStarsDisplay(activeStars) {
        starLabels.forEach((s, i) => {
            if (i < activeStars) {
                s.innerHTML = '<i class="bi bi-star-fill text-warning"></i>';
            } else {
                s.innerHTML = '<i class="bi bi-star"></i>';
            }
        });
    }
    
    // Inizializza con zero stelle selezionate
    updateStarsDisplay(0);
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>