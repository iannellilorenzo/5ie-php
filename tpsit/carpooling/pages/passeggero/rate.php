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
        } else if ($booking['stato'] !== 'completata') {
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
                                    <?php if (!empty($booking['foto_autista'])): ?>
                                        <img src="<?php echo $rootPath . $booking['foto_autista']; ?>" alt="Driver" class="rounded-circle" width="60" height="60">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:60px;height:60px;">
                                            <i class="bi bi-person" style="font-size: 1.5rem;"></i>
                                        </div>
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
                                    <div class="rating-container d-flex justify-content-center gap-2" style="font-size: 2rem;">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5"><i class="bi bi-star"></i></label>
                                        
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4"><i class="bi bi-star"></i></label>
                                        
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3"><i class="bi bi-star"></i></label>
                                        
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2"><i class="bi bi-star"></i></label>
                                        
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1"><i class="bi bi-star"></i></label>
                                    </div>
                                    <div id="ratingText" class="text-muted mt-2">Please select a rating</div>
                                </div>
                            </div>
                            
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
        const starNumber = 5 - index;
        
        label.addEventListener('mouseover', () => {
            // Update all stars on hover
            starLabels.forEach((s, i) => {
                const current = 5 - i;
                s.innerHTML = current <= starNumber ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
            });
            ratingText.textContent = ratingDescriptions[starNumber];
        });
        
        label.addEventListener('click', () => {
            // Set the input value
            starInputs[index].checked = true;
            
            // Update all stars on click
            starLabels.forEach((s, i) => {
                const current = 5 - i;
                s.innerHTML = current <= starNumber ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
            });
            ratingText.textContent = ratingDescriptions[starNumber];
        });
    });
    
    // Handle mouseleave for the container (reset to selected state)
    document.querySelector('.rating-container').addEventListener('mouseleave', () => {
        let selected = 0;
        starInputs.forEach((input, i) => {
            if (input.checked) {
                selected = 5 - i;
            }
        });
        
        starLabels.forEach((s, i) => {
            const current = 5 - i;
            s.innerHTML = current <= selected ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
        });
        
        ratingText.textContent = selected > 0 ? ratingDescriptions[selected] : 'Please select a rating';
    });
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>