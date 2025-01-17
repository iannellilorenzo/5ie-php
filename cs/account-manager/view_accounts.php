<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['token'])) {
    header("Location: sign_in.php");
    exit();
}

$username = $_SESSION['username'];
$message = '';
$accounts = [];

// Handle secret key submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['secret_key'])) {
    $secret_key = implode('', array_map('htmlspecialchars', $_POST['secret_key']));

    try {
        $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verify secret key
        $stmt = $conn->prepare("SELECT secret_key FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($secret_key, $user['secret_key'])) {
            // Fetch accounts after successful verification
            $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_reference = (SELECT email FROM users WHERE username = :username)");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $message = "Invalid secret key.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account_id'])) {
    $account_id = $_POST['delete_account_id'];
    $confirm_username = $_POST['confirm_username'];

    try {
        $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Retrieve the account to delete
        $stmt = $conn->prepare("SELECT username FROM accounts WHERE id = :id");
        $stmt->bindParam(':id', $account_id);
        $stmt->execute();
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($account && $account['username'] === $confirm_username) {
            // Delete the account
            $stmt = $conn->prepare("DELETE FROM accounts WHERE id = :id");
            $stmt->bindParam(':id', $account_id);
            $stmt->execute();
            $message = "Account deleted successfully.";
        } else {
            $message = "Username confirmation does not match.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account_id'])) {
    $account_id = $_POST['update_account_id'];
    $new_username = $_POST['new_username'];
    $new_email = $_POST['new_email'];
    $new_password = $_POST['new_password'];
    $new_description = $_POST['new_description'];
    $secret_key = implode('', array_map('htmlspecialchars', $_POST['secret_key']));

    try {
        $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Retrieve the user's hashed secret key
        $stmt = $conn->prepare("SELECT secret_key FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($secret_key, $user['secret_key'])) {
            // Encrypt the new password
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encrypted_password = openssl_encrypt($new_password, 'aes-256-cbc', $secret_key, 0, $iv);
            $encrypted_password = base64_encode($iv . $encrypted_password);

            // Update the account details
            $stmt = $conn->prepare("UPDATE accounts SET username = :new_username, email = :new_email, password = :new_password, description = :new_description WHERE id = :id");
            $stmt->bindParam(':new_username', $new_username);
            $stmt->bindParam(':new_email', $new_email);
            $stmt->bindParam(':new_password', $encrypted_password);
            $stmt->bindParam(':new_description', $new_description);
            $stmt->bindParam(':id', $account_id);
            $stmt->execute();
            $message = "Account updated successfully.";
        } else {
            $message = "Invalid secret key.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

function decrypt_password($encrypted_password, $secret_key) {
    $data = base64_decode($encrypted_password);
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted_password = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    return openssl_decrypt($encrypted_password, 'aes-256-cbc', $secret_key, 0, $iv);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Accounts - Lockr</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(135deg, #f6f9fc, #edf1f9, #e9ecf5);
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(to right, rgba(106, 17, 203, 0.9), rgba(37, 117, 252, 0.9)) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .navbar-light .navbar-brand,
        .navbar-light .nav-link {
            color: white !important;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(45deg, #6a11cb20, #2575fc20);
            border: none;
            padding: 1.5rem;
        }
        .otp-input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.5rem;
            margin: 0.3rem;
            border: 2px solid #eee;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .otp-input:focus {
            border-color: #6a11cb;
            box-shadow: none;
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106,17,203,0.4);
        }
        .btn-warning {
            background: linear-gradient(45deg, #f6d365, #fda085);
            border: none;
            color: white;
        }
        .btn-danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5253);
            border: none;
        }
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .modal-header {
            background: linear-gradient(45deg, #6a11cb20, #2575fc20);
            border: none;
        }
        .account-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #6a11cb20, #2575fc20);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .account-icon i {
            font-size: 1.5rem;
            color: #6a11cb;
        }
        .password-toggle {
            cursor: pointer;
            color: #6a11cb;
            transition: all 0.3s ease;
        }
        .password-toggle:hover {
            color: #2575fc;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="homepage.php">
                <img src="assets/images/logo_favicon.png" height="30" class="me-2">
                <span class="fw-bold">Lockr</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container" style="margin-top: 6rem;">
        <!-- Header with Search and Controls -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Your Accounts</h2>
            <?php if (!empty($accounts)): ?>
            <div class="d-flex gap-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" id="searchAccounts" placeholder="Search accounts...">
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary active" id="gridView">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="btn btn-outline-primary" id="listView">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($accounts)): ?>
            <div class="text-center py-5">
                <div class="feature-icon mb-4">
                    <i class="fas fa-folder-open fa-3x text-muted"></i>
                </div>
                <h3>No Accounts Found</h3>
                <p class="text-muted mb-4">Start securing your accounts by adding your first one.</p>
                <a href="add_account.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Account
                </a>
            </div>
        <?php else: ?>
            <!-- Existing accounts grid/list view -->
            <div class="row g-4" id="accountsContainer">
                <?php foreach ($accounts as $account): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <div class="account-icon me-3">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($account['website']); ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($account['username']); ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" value="********" readonly>
                                    <button class="btn btn-outline-primary" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="copyPassword('<?php echo htmlspecialchars($account['id']); ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $account['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $account['id']; ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Secret Key Modal -->
    <div class="modal fade" id="secretKeyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enter Your Secret Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="secretKeyForm" method="post">
                        <div class="d-flex justify-content-center mb-4">
                            <?php for($i = 0; $i < 6; $i++): ?>
                            <input type="password" class="otp-input no-paste" name="secret_key[]" maxlength="1" required>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(button) {
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Copy password to clipboard
        function copyPassword(accountId) {
            // Implementation here
        }

        // Toggle view (grid/list)
        document.getElementById('listView').addEventListener('click', function() {
            document.getElementById('accountsContainer').classList.remove('row');
            document.querySelectorAll('#accountsContainer > div').forEach(div => {
                div.className = 'mb-4';
            });
            this.classList.add('active');
            document.getElementById('gridView').classList.remove('active');
        });

        document.getElementById('gridView').addEventListener('click', function() {
            document.getElementById('accountsContainer').classList.add('row');
            document.querySelectorAll('#accountsContainer > div').forEach(div => {
                div.className = 'col-12 col-md-6 col-lg-4';
            });
            this.classList.add('active');
            document.getElementById('listView').classList.remove('active');
        });

        // Search functionality
        document.getElementById('searchAccounts').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('#accountsContainer .card').forEach(card => {
                const website = card.querySelector('h5').textContent.toLowerCase();
                const username = card.querySelector('small').textContent.toLowerCase();
                card.closest('.col-12').style.display = 
                    website.includes(search) || username.includes(search) ? '' : 'none';
            });
        });

        document.querySelectorAll('.otp-input').forEach((input, index, inputs) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>