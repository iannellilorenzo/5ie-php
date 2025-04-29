<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Gestione Viaggio";

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
require_once $rootPath . 'api/models/Viaggio.php';
require_once $rootPath . 'api/models/Prenotazione.php';

// Initialize variables
$error = null;
$success = false;
$trip = null;
$bookings = [];
$message = '';

// Check if trip ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = "ID viaggio richiesto";
} else {
    $tripId = $_GET['id'];
    
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get trip details
        $viaggioModel = new Viaggio($conn);
        $trip = $viaggioModel->getTripWithBookings($tripId);
        
        // Verify that the trip belongs to the logged-in driver
        if (!$trip) {
            $error = "Viaggio non trovato";
        } else if ($trip['id_autista'] != $_SESSION['user_id']) {
            $error = "Non sei autorizzato a modificare questo viaggio";
        }
        
        // Handle form submission for trip update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Check if close bookings button was pressed
            if (isset($_POST['close_bookings'])) {
                
                // Update trip status to close bookings
                $updateData = ['stato' => 'chiuso'];
                $viaggioModel->update($tripId, $updateData);
                
                // Send email notification
                require_once $rootPath . 'api/utils/EmailService.php';
                $emailService = new EmailService();
                
                // Get driver info (we already know the driver is the logged-in user)
                $driverName = $_SESSION['user']['nome'] . ' ' . $_SESSION['user']['cognome'];
                $driverEmail = $_SESSION['user']['email'];
                
                // Send email notification to the driver
                $emailService->sendBookingClosedNotification($driverEmail, $driverName, $trip);
                
                $message = "Le prenotazioni per questo viaggio sono state chiuse con successo.";
                $trip['stato'] = 'chiuso'; // Update local trip data to reflect change
                
            } else {
                // Regular trip update logic
                $updateData = [
                    'citta_partenza' => $_POST['citta_partenza'] ?? $trip['citta_partenza'],
                    'citta_destinazione' => $_POST['citta_destinazione'] ?? $trip['citta_destinazione'],
                    'timestamp_partenza' => $_POST['timestamp_partenza'] ?? $trip['timestamp_partenza'],
                    'prezzo_cadauno' => $_POST['prezzo_cadauno'] ?? $trip['prezzo_cadauno'],
                    'tempo_stimato' => $_POST['tempo_stimato'] ?? $trip['tempo_stimato']
                ];
                
                // Optional fields
                if (isset($_POST['soste'])) {
                    $updateData['soste'] = $_POST['soste'] === 'on' ? 1 : 0;
                }
                
                if (isset($_POST['bagaglio'])) {
                    $updateData['bagaglio'] = $_POST['bagaglio'] === 'on' ? 1 : 0;
                }
                
                if (isset($_POST['animali'])) {
                    $updateData['animali'] = $_POST['animali'] === 'on' ? 1 : 0;
                }
                
                // Update trip
                $viaggioModel->update($tripId, $updateData);
                
                // Reload trip data
                $trip = $viaggioModel->getTripWithBookings($tripId);
                $message = "Informazioni del viaggio aggiornate con successo.";
            }
            
            $success = true;
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

<div class="container py-5">
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
        <div class="text-center mt-3">
            <a href="<?php echo $rootPath; ?>pages/autista/trips.php" class="btn btn-primary">Torna ai Miei Viaggi</a>
        </div>
    <?php elseif ($trip): ?>
        <?php if ($success && $message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Modifica Viaggio</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" id="tripForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="citta_partenza" class="form-label">Città di partenza</label>
                                    <input type="text" class="form-control" id="citta_partenza" name="citta_partenza" 
                                           value="<?php echo htmlspecialchars($trip['citta_partenza']); ?>" 
                                           <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label for="citta_destinazione" class="form-label">Città di destinazione</label>
                                    <input type="text" class="form-control" id="citta_destinazione" name="citta_destinazione" 
                                           value="<?php echo htmlspecialchars($trip['citta_destinazione']); ?>"
                                           <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="timestamp_partenza" class="form-label">Data e ora di partenza</label>
                                    <input type="datetime-local" class="form-control" id="timestamp_partenza" name="timestamp_partenza" 
                                           value="<?php echo date('Y-m-d\TH:i', strtotime($trip['timestamp_partenza'])); ?>"
                                           <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label for="prezzo_cadauno" class="form-label">Prezzo per passeggero</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" step="0.01" class="form-control" id="prezzo_cadauno" name="prezzo_cadauno" 
                                               value="<?php echo htmlspecialchars($trip['prezzo_cadauno']); ?>"
                                               <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tempo_stimato" class="form-label">Tempo stimato di percorrenza (minuti)</label>
                                <input type="number" class="form-control" id="tempo_stimato" name="tempo_stimato" 
                                       value="<?php echo htmlspecialchars($trip['tempo_stimato']); ?>"
                                       <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="soste" name="soste" 
                                               <?php echo $trip['soste'] ? 'checked' : ''; ?>
                                               <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="soste">
                                            Previste soste
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="bagaglio" name="bagaglio" 
                                               <?php echo $trip['bagaglio'] ? 'checked' : ''; ?>
                                               <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="bagaglio">
                                            Bagaglio consentito
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="animali" name="animali" 
                                               <?php echo $trip['animali'] ? 'checked' : ''; ?>
                                               <?php echo $trip['stato'] === 'chiuso' ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="animali">
                                            Animali domestici ammessi
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="<?php echo $rootPath; ?>pages/autista/trips.php" class="btn btn-secondary">Annulla</a>
                                
                                <?php if ($trip['stato'] !== 'chiuso'): ?>
                                    <div>
                                        <button type="button" class="btn btn-warning" id="closeBookingsBtn">
                                            <i class="bi bi-lock-fill me-1"></i> Chiudi Prenotazioni
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i> Aggiorna Viaggio
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0 py-2">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Le prenotazioni per questo viaggio sono chiuse.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <!-- Form for closing bookings - will be submitted by JavaScript -->
                        <form method="POST" id="closeBookingsForm" style="display: none;">
                            <input type="hidden" name="close_bookings" value="1">
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Prenotazioni</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($trip['prenotazioni'])): ?>
                            <ul class="list-group">
                            <?php foreach ($trip['prenotazioni'] as $booking): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($booking['nome'] . ' ' . $booking['cognome']); ?></strong>
                                        <span class="badge bg-<?php 
                                            switch ($booking['stato']) {
                                                case 'in attesa': echo 'warning'; break;
                                                case 'accettata': echo 'success'; break;
                                                case 'rifiutata': echo 'danger'; break;
                                                case 'completata': echo 'info'; break;
                                                default: echo 'secondary';
                                            }
                                        ?> ms-2"><?php echo htmlspecialchars($booking['stato']); ?></span>
                                    </div>
                                    
                                    <?php if ($booking['stato'] === 'in attesa'): ?>
                                    <div>
                                        <a href="<?php echo $rootPath; ?>pages/autista/view-booking.php?id=<?php echo $booking['id_prenotazione']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-3 text-muted">
                                <i class="bi bi-calendar-x mb-2" style="font-size: 2rem;"></i>
                                <p>Nessuna prenotazione per questo viaggio.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal per conferma chiusura prenotazioni -->
<div class="modal fade" id="closeBookingsModal" tabindex="-1" aria-labelledby="closeBookingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closeBookingsModalLabel">Conferma chiusura prenotazioni</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Stai per chiudere le prenotazioni per questo viaggio. Questo impedirà a nuovi passeggeri di prenotare.
                </div>
                <p>Sei sicuro di voler continuare?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning" id="confirmCloseBookings">
                    <i class="bi bi-lock-fill me-1"></i> Chiudi Prenotazioni
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal per conferma chiusura prenotazioni
    const closeBookingsBtn = document.getElementById('closeBookingsBtn');
    const closeBookingsForm = document.getElementById('closeBookingsForm');
    const confirmCloseBookings = document.getElementById('confirmCloseBookings');
    const closeBookingsModal = new bootstrap.Modal(document.getElementById('closeBookingsModal'));
    
    if (closeBookingsBtn) {
        closeBookingsBtn.addEventListener('click', function() {
            closeBookingsModal.show();
        });
    }
    
    if (confirmCloseBookings) {
        confirmCloseBookings.addEventListener('click', function() {
            closeBookingsForm.submit();
        });
    }
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>