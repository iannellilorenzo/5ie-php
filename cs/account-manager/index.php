<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Account Manager Service</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .cookie-banner {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Account Manager</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="signin.php">Sign In</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="signup.php">Sign Up</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container text-center" style="margin-top: 50px;">
        <h1>Welcome to Account Manager Service</h1>
        <p>Manage your accounts efficiently and securely.</p>
        <img src="images/account_manager.jpg" class="img-fluid" alt="Account Manager">
        <div class="mt-4">
            <a href="signin.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Sign In</a>
            <a href="signup.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Sign Up</a>
        </div>
    </div>

    <div class="cookie-banner" id="cookie-banner">
        <p>We use cookies to ensure you get the best experience on our website. <button class="btn btn-sm btn-primary" onclick="acceptCookies()">Accept</button></p>
    </div>

    <footer class="bg-light text-center text-lg-start mt-5">
        <div class="container p-4">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Contact Us</h5>
                    <p>Email: support@accountmanager.com</p>
                    <p>Phone: +123 456 7890</p>
                </div>
                <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Follow Us</h5>
                    <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-dark btn-sm"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center p-3 bg-dark text-white">
            &copy; 2025 Account Manager Service. All rights reserved.
        </div>
    </footer>

    <script>
        function acceptCookies() {
            document.cookie = "cookies_accepted=true; max-age=" + 60*60*24*30 + "; path=/";
            document.getElementById('cookie-banner').style.display = 'none';
        }

        window.onload = function() {
            if (!document.cookie.split('; ').find(row => row.startsWith('cookies_accepted'))) {
                document.getElementById('cookie-banner').style.display = 'block';
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
</body>
</html>