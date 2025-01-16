<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/company.php';
require_once '../classes/manager.php';
require_once '../classes/student.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $userType = $_POST['userType'];
    
    $success = false;
    
    switch($userType) {
        case 'company':
            $company = new Company();
            $success = $company->authenticate($username, $password);
            if($success) {
                $_SESSION['id'] = $company->id;
                $_SESSION['username'] = $company->nome;
            }
            break;
            
        case 'manager':
            $manager = new Manager();
            $success = $manager->authenticate($username, $password);
            if($success) {
                $_SESSION['id'] = $manager->id;
                $_SESSION['username'] = $manager->nome;
            }
            break;
            
        case 'student':
            $student = new Student();
            $success = $student->authenticate($username, $password);
            if($success) {
                $_SESSION['id'] = $student->id;
                $_SESSION['username'] = $student->nome;
            }
            break;
    }
    
    if($success) {
        $_SESSION['user_type'] = $userType;
        $dashboard = match($userType) {
            'company' => 'company_dashboard.php',
            'manager' => 'manager_dashboard.php',
            'student' => 'student_dashboard.php',
            default => 'login.php'
        };
        header("location: $dashboard");
        exit;
    } else {
        $login_err = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexusLink - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.png">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Login</h2>
                        
                        <?php 
                        if (!empty($login_err)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($login_err) . '</div>';
                        }        
                        ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="userType" id="company" value="company" checked>
                                    <label class="btn btn-outline-primary" for="company">Company</label>

                                    <input type="radio" class="btn-check" name="userType" id="manager" value="manager">
                                    <label class="btn btn-outline-primary" for="manager">Manager</label>

                                    <input type="radio" class="btn-check" name="userType" id="student" value="student">
                                    <label class="btn btn-outline-primary" for="student">Student</label>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                                <label for="username">Username</label>
                            </div>

                            <div class="mb-3">
                                <div class="input-group">
                                    <div class="form-floating">
                                        <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                                        <label for="password">Password</label>
                                    </div>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
		<script>
				document.getElementById('togglePassword').addEventListener('click', function() {
						const password = document.getElementById('password');
						const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
						password.setAttribute('type', type);
						this.querySelector('i').classList.toggle('bi-eye');
						this.querySelector('i').classList.toggle('bi-eye-slash');
				});
		</script>
</body>
</html>