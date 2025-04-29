<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "Register";

// Set root path for includes
$rootPath = "";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard based on user type
    $dashboardUrl = ($rootPath . ($_SESSION['user_type'] === 'autista' ? 'pages/autista/dashboard.php' : 'pages/passeggero/dashboard.php'));
    header("Location: $dashboardUrl");
    exit();
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<div class="container-wrapper">
<!-- Registration Selection Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-2">Join RideTogether</h1>
            <p class="lead text-muted">Choose how you want to use our service</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-12 col-md-10">
                <!-- Important notice -->
                <div class="alert alert-info mb-5" role="alert">
                    <h4 class="alert-heading mb-2"><i class="bi bi-info-circle me-2"></i>Important Information</h4>
                    <p>You can only register as <strong>either</strong> a driver or a passenger. The two account types have different features and responsibilities.</p>
                    <hr>
                    <p class="mb-0">Already have an account? <a href="<?= $rootPath ?>login.php" class="alert-link">Log in here</a></p>
                </div>
            </div>
        </div>
        
        <div class="row g-4 justify-content-center">
            <!-- Passenger Option -->
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4 p-md-5">
                        <div class="mb-4">
                            <i class="bi bi-person-fill text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="card-title mb-3">Register as a Passenger</h3>
                        <p class="card-text mb-4">Find and book rides to your destination. Connect with drivers going your way.</p>
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Search for available rides</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Book seats with trusted drivers</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Save money on transport costs</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Rate your experience</li>
                        </ul>
                        <div class="d-grid">
                            <a href="<?= $rootPath ?>pages/passeggero/register.php" class="btn btn-primary btn-lg">Register as Passenger</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Driver Option -->
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4 p-md-5">
                        <div class="mb-4">
                            <i class="bi bi-car-front-fill text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="card-title mb-3">Register as a Driver</h3>
                        <p class="card-text mb-4">Offer rides to passengers and earn money by sharing your journey.</p>
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Create and manage ride offers</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Share trip costs with passengers</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Meet new people during your travels</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Build your driver reputation</li>
                        </ul>
                        <div class="d-grid">
                            <a href="<?= $rootPath ?>pages/autista/register.php" class="btn btn-primary btn-lg">Register as Driver</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row justify-content-center mt-5">
            <div class="col-md-10 text-center">
                <p class="text-muted">
                    <i class="bi bi-shield-check me-1"></i> 
                    Your information is secure with us. We use advanced encryption to protect your data.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Frequently Asked Questions</h2>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="registerFaq">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Why can't I be both a driver and a passenger?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#registerFaq">
                            <div class="accordion-body">
                                Our platform is designed with specific features for each user type. Drivers need to provide additional information like vehicle details and driver's license, while passengers have different booking capabilities. This separation helps ensure a better user experience and clearer responsibilities for each role.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Can I switch my account type later?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#registerFaq">
                            <div class="accordion-body">
                                Currently, you cannot switch between account types. If you wish to use the platform in a different capacity, you would need to create a new account with a different email address.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                What information do I need to register?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#registerFaq">
                            <div class="accordion-body">
                                <p><strong>For passengers:</strong> You'll need to provide your name, email, phone number, and create a password. Additional information like city and date of birth is optional but recommended.</p>
                                <p><strong>For drivers:</strong> In addition to the basic information, you'll need to provide your driver's license details, date of birth, and information about your vehicle.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Is my personal information secure?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#registerFaq">
                            <div class="accordion-body">
                                Yes! We take data security seriously. Your personal information is encrypted and stored securely. We never share your private information with other users without your consent. Drivers and passengers only see information necessary for coordinating rides.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>