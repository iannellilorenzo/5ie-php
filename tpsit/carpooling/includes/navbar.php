<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $rootPath; ?>index.php">
            <i class="bi bi-car-front me-2"></i>
            RideTogether
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $rootPath; ?>index.php">Home</a>
                </li>
                
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <!-- Opzioni per utenti non loggati -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>pages/passeggero/search.php">Cerca un Passaggio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>register.php">Offri un Passaggio</a>
                    </li>
                <?php elseif($_SESSION['user_type'] === 'passeggero'): ?>
                    <!-- Navigazione specifica per passeggeri -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>pages/passeggero/search.php">Cerca un Passaggio</a>
                    </li>
                <?php elseif($_SESSION['user_type'] === 'autista'): ?>
                    <!-- Navigazione specifica per autisti -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>pages/autista/trips.php">Offri un Passaggio</a>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $rootPath; ?>index.php#how-it-works">Come Funziona</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a class="btn nav-user-btn dropdown-toggle" href="#" role="button" 
                           id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php 
                            $photoPath = "";
                            $userType = $_SESSION['user_type'] ?? '';
                            $userId = $_SESSION['user_id'] ?? '';
                            
                            if ($userType == 'autista') {
                                $photoPath = $rootPath . "uploads/drivers/" . $userId . ".jpg";
                            } elseif ($userType == 'passeggero') {
                                $photoPath = $rootPath . "uploads/passengers/" . $userId . ".jpg";
                            }
                            
                            if (!empty($photoPath) && file_exists($_SERVER['DOCUMENT_ROOT'] . parse_url($photoPath, PHP_URL_PATH))):
                            ?>
                                <img src="<?php echo $photoPath; ?>" alt="User" class="avatar-img me-1">
                            <?php else: ?>
                                <i class="bi bi-person-circle me-1"></i>
                            <?php endif; ?>
                            
                            <?php 
                            // Show name if available, otherwise "Account"
                            if (isset($_SESSION['user_name'])) {
                                echo htmlspecialchars($_SESSION['user_name']);
                            } else {
                                echo "Account";
                            }
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'autista'): ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>pages/autista/profile.php">
                                        <i class="bi bi-person me-2 text-primary"></i>Il mio Profilo
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>pages/autista/dashboard.php">
                                        <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>pages/autista/trips.php">
                                        <i class="bi bi-map me-2 text-primary"></i>I miei Viaggi
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>pages/autista/vehicles.php">
                                        <i class="bi bi-car-front me-2 text-primary"></i>I miei Veicoli
                                    </a>
                                </li>
                            <?php elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'passeggero'): ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>pages/passeggero/profile.php">
                                        <i class="bi bi-person me-2 text-primary"></i>Il mio Profilo
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>pages/passeggero/dashboard.php">
                                        <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo $rootPath; ?>pages/passeggero/booking.php">
                                        <i class="bi bi-calendar-check me-2 text-primary"></i>Le mie Prenotazioni
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center text-danger" href="<?php echo $rootPath; ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $rootPath; ?>login.php" class="btn btn-outline-primary me-2">Accedi</a>
                    <a href="<?php echo $rootPath; ?>register.php" class="btn btn-primary">Registrati</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Stili aggiuntivi per la navbar -->
<style>
.navbar {
    background-color: rgba(255, 255, 255, 0.97);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    padding-top: 12px;
    padding-bottom: 12px;
}

.navbar-brand {
    font-weight: 700;
    color: #2574f4;
    transition: all 0.2s;
}

.navbar-brand:hover {
    color: #1a5fc7;
    transform: translateY(-1px);
}

.nav-link {
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: all 0.2s;
}

.nav-link:hover {
    background-color: rgba(37, 116, 244, 0.05);
    color: #2574f4;
    transform: translateY(-1px);
}

.navbar-toggler {
    border: none;
    padding: 0.5rem;
}

.navbar-toggler:focus {
    box-shadow: none;
    outline: none;
}

/* Stile per il dropdown dell'utente */
.nav-user-btn {
    display: flex;
    align-items: center;
    background: transparent;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
    font-weight: 500;
}

.nav-user-btn:hover, 
.nav-user-btn:focus {
    background-color: #f8f9fa;
    border-color: #ced4da;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Avatar utente */
.avatar-img {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #dee2e6;
}

/* Stile per i link del dropdown */
.dropdown-item {
    padding: 0.5rem 1rem;
    font-weight: 500;
}

.dropdown-item:hover {
    background-color: rgba(37, 116, 244, 0.05);
}

.dropdown-item i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

/* Animazione sottile per il menu a discesa */
.dropdown-menu {
    margin-top: 10px;
    animation: dropdownFade 0.2s ease;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: none;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Spazio per il navbar fisso */
.navbar-spacer {
    height: 76px;
}

@media (max-width: 992px) {
    .navbar-nav .nav-link {
        padding: 0.75rem 1rem;
    }
    
    .navbar-collapse {
        margin-top: 1rem;
        padding: 1rem;
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
}
</style>

<div class="navbar-spacer"></div> <!-- Spacer per la navbar fissa -->