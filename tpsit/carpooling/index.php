<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Home";

// Root path for includes
$rootPath = "";

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container-wrapper">
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="display-4 fw-bold mb-4">Share rides, save money, reduce emissions</h1>
                <p class="lead mb-4">Find or offer rides for your daily commute or long-distance trips. Join our community of users making travel more affordable and sustainable.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                    <a href="pages/passeggero/search.php" class="btn btn-light btn-lg px-4 me-md-2">Find a Ride</a>
                    <a href="pages/autista/trips.php" class="btn btn-outline-light btn-lg px-4">Offer a Ride</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="assets/img/hero-illustration.svg" alt="Carpooling Illustration" class="img-fluid d-none d-lg-block">
            </div>
        </div>
    </div>
</section>

<!-- Search Form Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h3 class="card-title mb-4">Find a ride</h3>
                <form>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="departure" class="form-label">From</label>
                            <input type="text" class="form-control" id="departure" placeholder="City or town">
                        </div>
                        <div class="col-md-4">
                            <label for="destination" class="form-label">To</label>
                            <input type="text" class="form-control" id="destination" placeholder="City or town">
                        </div>
                        <div class="col-md-2">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">How It Works</h2>
            <p class="text-muted">Simple steps to start carpooling today</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon mx-auto mb-3">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3>1. Create an account</h3>
                    <p class="text-muted">Sign up as a passenger or driver in just a few clicks</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon mx-auto mb-3">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3>2. Find or post a ride</h3>
                    <p class="text-muted">Search for available rides or offer your own journey</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon mx-auto mb-3">
                        <i class="bi bi-car-front"></i>
                    </div>
                    <h3>3. Travel together</h3>
                    <p class="text-muted">Connect with your carpool buddy and share the journey</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="section-padding bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Choose RideTogether</h2>
            <p class="text-muted">The smart way to travel together</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 feature-card p-4">
                    <div class="card-body">
                        <div class="feature-icon mb-3">
                            <i class="bi bi-piggy-bank"></i>
                        </div>
                        <h4>Save Money</h4>
                        <p class="text-muted">Share travel costs and reduce your expenses on fuel and car maintenance.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 feature-card p-4">
                    <div class="card-body">
                        <div class="feature-icon mb-3">
                            <i class="bi bi-tree"></i>
                        </div>
                        <h4>Go Green</h4>
                        <p class="text-muted">Reduce carbon emissions by sharing rides with others heading the same way.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 feature-card p-4">
                    <div class="card-body">
                        <div class="feature-icon mb-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4>Meet New People</h4>
                        <p class="text-muted">Expand your network and make new connections during your journeys.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 feature-card p-4">
                    <div class="card-body">
                        <div class="feature-icon mb-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4>Safe & Secure</h4>
                        <p class="text-muted">User verification and reviews ensure a safe carpooling experience.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 feature-card p-4">
                    <div class="card-body">
                        <div class="feature-icon mb-3">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <h4>Flexible Routes</h4>
                        <p class="text-muted">Find rides that match your exact route and schedule.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 feature-card p-4">
                    <div class="card-body">
                        <div class="feature-icon mb-3">
                            <i class="bi bi-phone"></i>
                        </div>
                        <h4>Easy to Use</h4>
                        <p class="text-muted">Simple interface makes finding or offering rides quick and hassle-free.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section-padding bg-primary text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Ready to start your journey?</h2>
        <p class="lead mb-4">Join thousands of users already carpooling with RideTogether</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="register.php" class="btn btn-light btn-lg px-4">Sign Up Now</a>
            <a href="pages/passeggero/search.php" class="btn btn-outline-light btn-lg px-4">Find a Ride</a>
        </div>
    </div>
</section>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>