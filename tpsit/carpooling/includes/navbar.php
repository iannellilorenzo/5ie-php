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
                    <!-- Show both options for non-logged in users -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>pages/passeggero/search.php">Find a Ride</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>register.php">Offer a Ride</a>
                    </li>
                <?php elseif($_SESSION['user_type'] === 'passeggero'): ?>
                    <!-- Passenger-specific navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>pages/passeggero/search.php">Find a Ride</a>
                    </li>
                <?php elseif($_SESSION['user_type'] === 'autista'): ?>
                    <!-- Driver-specific navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $rootPath; ?>pages/autista/trips.php">Offer a Ride</a>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $rootPath; ?>index.php#how-it-works">How it Works</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" 
                           id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            Account
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'autista'): ?>
                                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>pages/autista/profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>pages/autista/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>pages/autista/trips.php">My Trips</a></li>
                                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>pages/autista/vehicles.php">My Vehicles</a></li>
                            <?php elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'passeggero'): ?>
                                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>pages/passeggero/profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>pages/passeggero/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo $rootPath; ?>pages/passeggero/bookings.php">My Bookings</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $rootPath; ?>logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $rootPath; ?>login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="<?php echo $rootPath; ?>register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<div style="margin-top: 76px;"></div> <!-- Spacer for fixed navbar -->