<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lockr</title>
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">Lockr</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="sign_in.php">Sign In</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sign_up.php">Sign Up</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="container text-center" style="margin-top: 50px;">
            <h1>Welcome to Lockr</h1>
            <p>Manage your accounts efficiently and securely.</p>
            <img src="assets/images/logo_luxury.png" class="img-fluid" alt="Account Manager">
            <div class="mt-4">
                <a href="sign_in.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                <a href="sign_up.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Sign Up</a>
            </div>
        </div>

        <!-- Cookie Modal -->
        <div v-if="!cookiesAccepted" class="modal fade" id="cookieModal" tabindex="-1" role="dialog" aria-labelledby="cookieModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cookieModalLabel">Cookie Policy</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        We use cookies to enhance your experience on our website. By continuing to use our site, you accept our use of cookies. For more information, please review our Privacy Policy.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="acceptCookies">Accept</button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="bg-light text-center text-lg-start mt-5">
            <div class="container p-4">
                <div class="row">
                    <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                        <h5 class="text-uppercase">Contact Us</h5>
                        <p>Email: customersupport@lockr.com</p>
                        <p>Phone: +39 347 247 5354</p>
                    </div>
                    <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                        <h5 class="text-uppercase">Follow Us</h5>
                        <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center p-3 bg-dark text-white">
                &copy; 2025 Lockr. All rights reserved.
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        new Vue({
            el: '#app',
            data: {
                cookiesAccepted: document.cookie.includes('acceptCookies=true')
            },
            methods: {
                acceptCookies() {
                    document.cookie = "acceptCookies=true; path=/; max-age=" + 60*60*24*30;
                    this.cookiesAccepted = true;
                    $('#cookieModal').modal('hide');
                }
            },
            mounted() {
                if (!this.cookiesAccepted) {
                    $('#cookieModal').modal('show');
                }
            }
        });
    </script>
</body>
</html>