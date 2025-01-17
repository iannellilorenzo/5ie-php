<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lockr - Secure Password Manager</title>
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        .navbar {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .gradient-custom {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            position: relative;
            overflow: hidden;
        }

        .wave-divider {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }

        .wave-divider svg {
            position: relative;
            display: block;
            width: calc(120% + 1.3px); /* Extended width */
            height: 70px;
            transform: translateX(-10%); /* Center the extended wave */
        }

        .wave-divider .shape-fill {
            fill: #FFFFFF;
        }

        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #6a11cb;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .feature-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 0.75rem;
            background: linear-gradient(45deg, #6a11cb20, #2575fc20);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .feature-icon i {
            font-size: 2rem;
            color: #6a11cb;
        }
        .btn-gradient {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            border: none;
            color: white;
            transition: transform 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
        }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column">
    <div id="app">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="assets/images/logo_favicon.png" height="30" class="me-2">
                    <span class="fw-bold">Lockr</span>
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item mx-2">
                            <a class="nav-link px-3" href="sign_in.php">Sign In</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="sign_up.php">Sign Up</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="gradient-custom text-white py-5">
            <div class="container py-5">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <h1 class="display-4 fw-bold mb-4">Secure Your Digital Life with Lockr</h1>
                        <p class="lead mb-4">The most trusted password manager for your personal and business needs.</p>
                        <div class="d-grid gap-3 d-sm-flex">
                            <a href="sign_up.php" class="btn btn-light btn-lg px-4">Get Started Free</a>
                            <a href="sign_in.php" class="btn btn-outline-light btn-lg px-4">Sign In</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <img src="assets/images/logo_luxury.png" class="img-fluid" alt="Lockr Dashboard">
                    </div>
                </div>
            </div>
            <!-- Add wave divider -->
            <div class="wave-divider">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 120" preserveAspectRatio="none">
                    <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1400V95.8C1332.19,118.92,1055.71,111.31,985.66,92.83Z" class="shape-fill"></path>
                </svg>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Military-grade Encryption</h3>
                        <p class="text-muted">Your data is protected with AES-256 bit encryption, the same standard used by governments.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-icon">
                            <i class="fas fa-sync"></i>
                        </div>
                        <h3>Auto-Sync</h3>
                        <p class="text-muted">Access your passwords across all your devices with real-time synchronization.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="feature-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h3>Password Generator</h3>
                        <p class="text-muted">Create strong, unique passwords with our built-in generator tool.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cookie Modal -->
        <div v-if="!cookiesAccepted" class="modal fade" id="cookieModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">🍪 Cookie Policy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-gradient px-4" @click="acceptCookies">Accept All Cookies</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-auto bg-light py-4">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3">Contact Us</h5>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i>customersupport@lockr.com</p>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i>+39 347 247 5354</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="fw-bold mb-3">Follow Us</h5>
                        <a href="#" class="btn btn-outline-dark btn-sm me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <hr class="my-4">
                <div class="text-center">
                    <small class="text-muted">&copy; 2025 Lockr. All rights reserved.</small>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        new Vue({
            el: '#app',
            data: {
                cookiesAccepted: document.cookie.includes('acceptCookies=true')
            },
            methods: {
                acceptCookies() {
                    document.cookie = "acceptCookies=true; path=/; max-age=" + 60*60*24*30;
                    this.cookiesAccepted = true;
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cookieModal'));
                    modal.hide();
                }
            },
            mounted() {
                if (!this.cookiesAccepted) {
                    const modal = new bootstrap.Modal(document.getElementById('cookieModal'));
                    modal.show();
                }
            }
        });
    </script>
</body>
</html>