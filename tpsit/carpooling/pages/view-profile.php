<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Profilo Utente";

// Set root path for includes
$rootPath = "./";

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Autista.php';
require_once $rootPath . 'api/models/Passeggero.php';

// Initialize
$error = null;
$profile = null;
$userType = null;

// Check if user ID and type is provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['type']) || empty($_GET['type'])) {
    $error = "ID utente e tipo richiesti";
} else {
    $userId = $_GET['id'];
    $userType = $_GET['type'];
    
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get user profile with ratings based on type
        if ($userType === 'autista') {
            $model = new Autista($conn);
            $profile = $model->getDriverWithRatings($userId);
        } else if ($userType === 'passeggero') {
            $model = new Passeggero($conn);
            $profile = $model->getPassengerWithRatings($userId);
        } else {
            $error = "Tipo utente non valido";
        }
        
        if (!$profile) {
            $error = "Utente non trovato";
        }
        
    } catch (Exception $e) {
        $error = "Errore: " . $e->getMessage();
    }
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';

// Funzione per visualizzare le stelle in base al rating
function displayStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= round($rating)) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            $html .= '<i class="bi bi-star text-muted"></i>';
        }
    }
    return $html;
}
?>

<!-- Aggiungiamo stili personalizzati per migliorare il design -->
<style>
    .profile-header {
        position: relative;
        background: linear-gradient(135deg, #4a89dc, #2574f4);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('<?php echo $rootPath; ?>assets/img/profile-wave.svg') no-repeat bottom;
        background-size: contain;
        opacity: 0.1;
        z-index: 0;
    }
    
    .profile-header .container {
        position: relative;
        z-index: 1;
    }
    
    .profile-avatar {
        width: 150px;
        height: 150px;
        border: 5px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        object-fit: cover;
    }
    
    .review-card {
        transition: transform 0.2s ease-in-out;
        margin-bottom: 1.25rem;
    }
    
    .review-card:hover {
        transform: translateY(-5px);
    }
    
    .rating-stars i {
        color: #ffc107;
        margin-right: 2px;
    }

    .stats-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(74, 137, 220, 0.1);
        color: #4a89dc;
        border-radius: 50%;
        margin-right: 1rem;
        font-size: 1.5rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: #333;
    }

    .stat-label {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 0;
    }

    @media (max-width: 767px) {
        .profile-avatar {
            width: 120px;
            height: 120px;
        }
        
        .profile-name {
            font-size: 1.5rem;
        }
    }
</style>

<?php if ($error): ?>
    <div class="container py-5">
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
        <div class="text-center mt-3">
            <a href="<?php echo $rootPath; ?>index.php" class="btn btn-primary">Torna alla Home</a>
        </div>
    </div>
<?php elseif ($profile): ?>
    <!-- Header del profilo con stile migliorato -->
    <div class="profile-header mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-start mb-3 mb-md-0">
                    <?php if (!empty($profile['fotografia']) && file_exists($rootPath . $profile['fotografia'])): ?>
                        <img src="<?php echo $rootPath . $profile['fotografia']; ?>" alt="Profile" class="profile-avatar rounded-circle">
                    <?php else: ?>
                        <img src="<?php echo $rootPath; ?>assets/img/default-pfp.png" alt="Profile" class="profile-avatar rounded-circle">
                    <?php endif; ?>
                </div>
                <div class="col-md-9 text-center text-md-start">
                    <h1 class="profile-name mb-1"><?php echo htmlspecialchars($profile['nome'] . ' ' . $profile['cognome']); ?></h1>
                    <p class="mb-2">
                        <?php if ($userType === 'autista'): ?>
                            <span class="badge bg-primary">Autista</span>
                        <?php else: ?>
                            <span class="badge bg-info">Passeggero</span>
                        <?php endif; ?>

                        <?php if (isset($profile['data_registrazione'])): ?>
                            <span class="ms-2 text-light opacity-75">
                                <i class="bi bi-calendar3"></i> Membro dal <?php echo date('M Y', strtotime($profile['data_registrazione'])); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    
                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start align-items-center mt-2">
                        <div class="me-4 mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <span class="h4 mb-0 me-2"><?php echo number_format($profile['rating_avg'] ?? 0, 1); ?></span>
                                <div class="rating-stars">
                                    <?php 
                                        $rating = $profile['rating_avg'] ?? 0;
                                        for ($i = 1; $i <= 5; $i++): 
                                            if ($i <= round($rating)): ?>
                                                <i class="bi bi-star-fill"></i>
                                            <?php else: ?>
                                                <i class="bi bi-star"></i>
                                            <?php endif;
                                        endfor; 
                                    ?>
                                </div>
                            </div>
                            <small class="text-light"><?php echo $profile['rating_count']; ?> valutazioni</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container pb-5">
        <div class="row">
            <!-- Colonna sinistra: Statistiche e Informazioni Utente -->
            <div class="col-lg-4">
                <!-- Statistiche -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="bi bi-star"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo number_format($profile['rating_avg'] ?? 0, 1); ?>/5.0</div>
                                    <p class="stat-label">Valutazione media</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="bi bi-chat-square-text"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $profile['rating_count']; ?></div>
                                    <p class="stat-label">Recensioni ricevute</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($userType === 'autista'): ?>
                    <div class="col-12">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="bi bi-car-front"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo isset($profile['trip_count']) ? $profile['trip_count'] : 'N/A'; ?></div>
                                    <p class="stat-label">Viaggi offerti</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Card Informazioni -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Informazioni</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if ($userType === 'autista'): ?>
                                <?php if (!empty($profile['numero_patente'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-card-text text-primary me-2"></i>
                                            Patente
                                        </div>
                                        <span class="badge bg-success rounded-pill">Verificata</span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($profile['scadenza_patente'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-calendar-date text-primary me-2"></i>
                                            Scadenza patente
                                        </div>
                                        <span class="text-muted"><?php echo date('d/m/Y', strtotime($profile['scadenza_patente'])); ?></span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($profile['numero_telefono'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-telephone text-primary me-2"></i>
                                            Telefono
                                        </div>
                                        <span class="text-muted"><?php echo htmlspecialchars($profile['numero_telefono']); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (!empty($profile['documento_identita'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-person-vcard text-primary me-2"></i>
                                            Documento
                                        </div>
                                        <span class="badge bg-success rounded-pill">Verificato</span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($profile['telefono'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-telephone text-primary me-2"></i>
                                            Telefono
                                        </div>
                                        <span class="text-muted"><?php echo htmlspecialchars($profile['telefono']); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Data registrazione -->
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-calendar-check text-primary me-2"></i>
                                    Membro dal
                                </div>
                                <span class="text-muted"><?php echo date('d/m/Y', strtotime($profile['data_registrazione'] ?? date('Y-m-d'))); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Colonna destra: Recensioni -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recensioni (<?php echo count($profile['reviews'] ?? []); ?>)</h5>
                        
                        <!-- Opzioni di ordinamento -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sort-down me-1"></i> Ordina
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                                <li><a class="dropdown-item" href="#" data-sort="date-desc">Data (più recenti)</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="date-asc">Data (meno recenti)</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="rating-desc">Valutazione (alta-bassa)</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="rating-asc">Valutazione (bassa-alta)</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($profile['reviews'])): ?>
                            <div id="reviewsContainer">
                                <?php foreach ($profile['reviews'] as $review): ?>
                                    <div class="review-card card border-0 mb-3" 
                                         data-date="<?php echo strtotime($review['timestamp_partenza'] ?? ''); ?>" 
                                         data-rating="<?php echo $userType === 'autista' ? 
                                            intval($review['voto_autista'] ?? 0) : 
                                            intval($review['voto_passeggero'] ?? 0); ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-light p-2 me-3">
                                                        <i class="bi bi-person text-primary" style="font-size: 1.5rem;"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php 
                                                            if ($userType === 'autista') {
                                                                echo htmlspecialchars($review['nome'] . ' ' . $review['cognome']);
                                                            } else {
                                                                echo htmlspecialchars($review['nome_autista'] . ' ' . $review['cognome_autista']);
                                                            }
                                                        ?></strong>
                                                        
                                                        <div class="rating-stars">
                                                            <?php 
                                                                $reviewRating = $userType === 'autista' ? 
                                                                    ($review['voto_autista'] ?? 0) : 
                                                                    ($review['voto_passeggero'] ?? 0);
                                                                    
                                                                for ($i = 1; $i <= 5; $i++) {
                                                                    if ($i <= $reviewRating) {
                                                                        echo '<i class="bi bi-star-fill"></i>';
                                                                    } else {
                                                                        echo '<i class="bi bi-star"></i>';
                                                                    }
                                                                }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (isset($review['timestamp_partenza'])): ?>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y', strtotime($review['timestamp_partenza'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (($userType === 'autista' && !empty($review['feedback_autista'])) || 
                                                    ($userType === 'passeggero' && !empty($review['feedback_passeggero']))): ?>
                                                <p class="review-text mb-2">
                                                    <?php 
                                                        echo htmlspecialchars($userType === 'autista' ? 
                                                            $review['feedback_autista'] : 
                                                            $review['feedback_passeggero']); 
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if (($userType === 'autista' && isset($review['citta_partenza'], $review['citta_destinazione']))): ?>
                                                <div class="trip-route mt-2 pt-2 border-top">
                                                    <small class="text-muted d-flex align-items-center">
                                                        <i class="bi bi-geo-alt me-2"></i> 
                                                        <?php echo htmlspecialchars($review['citta_partenza']); ?>
                                                        <i class="bi bi-arrow-right mx-2"></i>
                                                        <?php echo htmlspecialchars($review['citta_destinazione']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bi bi-chat-square-text" style="font-size: 3.5rem; color: #dee2e6;"></i>
                                </div>
                                <h5>Nessuna recensione</h5>
                                <p class="text-muted">Questo utente non ha ancora ricevuto recensioni.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Script per l'ordinamento delle recensioni -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funzioni di ordinamento
    const sortReviews = (sortType) => {
        const reviewsContainer = document.getElementById('reviewsContainer');
        if (!reviewsContainer) return;
        
        const reviews = Array.from(reviewsContainer.querySelectorAll('.review-card'));
        
        reviews.sort((a, b) => {
            if (sortType === 'date-desc') {
                return parseInt(b.dataset.date) - parseInt(a.dataset.date);
            } else if (sortType === 'date-asc') {
                return parseInt(a.dataset.date) - parseInt(b.dataset.date);
            } else if (sortType === 'rating-desc') {
                return parseInt(b.dataset.rating) - parseInt(a.dataset.rating);
            } else if (sortType === 'rating-asc') {
                return parseInt(a.dataset.rating) - parseInt(b.dataset.rating);
            }
            return 0;
        });
        
        // Rimuovi e reinserisci gli elementi ordinati
        reviews.forEach(review => reviewsContainer.appendChild(review));
    };
    
    // Aggiungi listener ai link del dropdown
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            sortReviews(this.dataset.sort);
        });
    });
    
    // Ordina per default per data più recente
    sortReviews('date-desc');
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>