<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Rate Your Driver";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passeggero') {
    // Redirect to login
    header("Location: {$rootPath}login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Prenotazione.php';

// Initialize
$error = null;
$success = false;
$booking = null;

// Check if booking ID is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    $error = "Booking ID is required";
} else {
    $bookingId = $_GET['booking_id'];
    
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get booking details
        $prenotazioneModel = new Prenotazione($conn);
        $booking = $prenotazioneModel->getById($bookingId);
        
        // Verify that the booking belongs to the logged-in passenger and is completed
        if (!$booking) {
            $error = "Booking not found";
        } else if ($booking['id_passeggero'] != $_SESSION['user_id']) {
            $error = "You cannot rate this booking";
        } else if ($booking['stato'] !== 'completato') {
            $error = "You can only rate completed trips";
        } else if (!empty($booking['voto_autista'])) {
            $error = "You have already rated this driver";
        }
        
        // Process the form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
            $feedback = trim(filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_SPECIAL_CHARS));
            
            // Validate input
            if ($rating < 1 || $rating > 5) {
                $error = "Rating must be between 1 and 5";
            } else {
                // Update booking with rating
                $updateData = [
                    'voto_autista' => $rating
                ];
                
                // Feedback is optional
                if (!empty($feedback)) {
                    $updateData['feedback_autista'] = $feedback;
                }
                
                // Update booking
                $prenotazioneModel->update($bookingId, $updateData);
                $success = true;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
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
                <div class="card border-0 shadow-sm text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-3">Thank You for Rating!</h2>
                    <p class="text-muted mb-4">Your feedback helps improve our community.</p>
                    <div class="d-grid">
                        <a href="<?php echo $rootPath; ?>pages/passeggero/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                </div>
            <?php elseif ($error !== null): ?>
                <div class="card border-0 shadow-sm text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-3">Error</h2>
                    <p class="text-danger mb-4"><?php echo $error; ?></p>
                    <div class="d-grid">
                        <a href="<?php echo $rootPath; ?>pages/passeggero/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                </div>
            <?php elseif ($booking): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Rate Your Driver</h2>
                            <p class="text-muted">Your feedback helps improve our community</p>
                        </div>
                        
                        <div class="trip-info mb-4 p-3 bg-light rounded">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <?php if (!empty($booking['foto_autista']) && file_exists($rootPath . $booking['foto_autista'])): ?>
                                        <img src="<?php echo $rootPath . $booking['foto_autista']; ?>" alt="Driver" class="rounded-circle" width="60" height="60" style="object-fit: cover;">
                                    <?php else: ?>
                                        <!-- Default profile photo -->
                                        <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" alt="Driver" class="rounded-circle" width="60" height="60">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($booking['nome_autista'] . ' ' . $booking['cognome_autista']); ?></h5>
                                    <p class="text-muted mb-0">Your driver</p>
                                </div>
                            </div>
                            
                            <div class="trip-details">
                                <div class="mb-2">
                                    <strong>Trip:</strong> 
                                    <?php echo htmlspecialchars($booking['citta_partenza'] . ' → ' . $booking['citta_destinazione']); ?>
                                </div>
                                <div>
                                    <strong>Date:</strong> 
                                    <?php echo date('F j, Y', strtotime($booking['timestamp_partenza'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                            <div class="mb-4">
                                <label class="form-label fw-bold">How was your trip?</label>
                                <div class="star-rating text-center my-3">
                                    <div class="rating-container d-flex justify-content-center" style="font-size: 2rem;">
                                        <!-- Inversione dell'ordine: ora 1 stella è a sinistra e 5 stelle a destra -->
                                        <div class="star-item">
                                            <input type="radio" id="star1" name="rating" value="1" class="star-input" required>
                                            <label for="star1"><i class="bi bi-star"></i></label>
                                        </div>
                                        
                                        <div class="star-item">
                                            <input type="radio" id="star2" name="rating" value="2" class="star-input">
                                            <label for="star2"><i class="bi bi-star"></i></label>
                                        </div>
                                        
                                        <div class="star-item">
                                            <input type="radio" id="star3" name="rating" value="3" class="star-input">
                                            <label for="star3"><i class="bi bi-star"></i></label>
                                        </div>
                                        
                                        <div class="star-item">
                                            <input type="radio" id="star4" name="rating" value="4" class="star-input">
                                            <label for="star4"><i class="bi bi-star"></i></label>
                                        </div>
                                        
                                        <div class="star-item">
                                            <input type="radio" id="star5" name="rating" value="5" class="star-input">
                                            <label for="star5"><i class="bi bi-star"></i></label>
                                        </div>
                                    </div>
                                    <div id="ratingText" class="text-muted mt-2">Please select a rating</div>
                                </div>
                            </div>

                            <style>
                            /* Stile per nascondere i radio button ma mantenere la funzionalità */
                            .star-input {
                                position: absolute;
                                opacity: 0;
                                width: 0;
                                height: 0;
                            }

                            .star-item {
                                cursor: pointer;
                                padding: 0 2px; /* Spazio controllato tra le stelle */
                            }

                            .star-item label {
                                cursor: pointer;
                            }
                            </style>
                            
                            <div class="mb-4">
                                <label for="feedback" class="form-label fw-bold">Additional feedback (optional)</label>
                                <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Share your experience..."></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Submit Rating</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Star rating functionality
    const starLabels = document.querySelectorAll('.rating-container label');
    const starInputs = document.querySelectorAll('.rating-container input');
    const ratingText = document.getElementById('ratingText');
    
    const ratingDescriptions = [
        '',
        'Poor - 1 star',
        'Fair - 2 stars',
        'Average - 3 stars',
        'Good - 4 stars',
        'Excellent - 5 stars'
    ];
    
    // Handle star hover and click events
    starLabels.forEach((label, index) => {
        // Con l'ordine invertito, index è già corretto (0 = 1 stella, 4 = 5 stelle)
        const starNumber = index + 1;
        
        label.addEventListener('mouseover', () => {
            // Aggiorna tutte le stelle al passaggio del mouse
            starLabels.forEach((s, i) => {
                s.innerHTML = i < starNumber ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
            });
            ratingText.textContent = ratingDescriptions[starNumber];
        });
        
        label.addEventListener('click', () => {
            // Imposta il valore dell'input
            starInputs[index].checked = true;
            
            // Aggiorna tutte le stelle al click
            starLabels.forEach((s, i) => {
                s.innerHTML = i < starNumber ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
            });
            ratingText.textContent = ratingDescriptions[starNumber];
        });
    });
    
    // Gestisce l'uscita del mouse dal contenitore (ripristina lo stato selezionato)
    document.querySelector('.rating-container').addEventListener('mouseleave', () => {
        let selected = 0;
        starInputs.forEach((input, i) => {
            if (input.checked) {
                selected = i + 1;
            }
        });
        
        starLabels.forEach((s, i) => {
            s.innerHTML = i < selected ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
        });
        
        ratingText.textContent = selected > 0 ? ratingDescriptions[selected] : 'Please select a rating';
    });
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>