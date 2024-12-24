<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['token'])) {
    header("Location: sign_in.php");
    exit();
}

$username = $_SESSION['username'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['secret_key'])) {
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
            // Retrieve the user's accounts
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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Accounts</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="assets/images/logo_favicon.png" type="image/x-icon">
    <style>
        .otp-input {
            width: 2rem;
            height: 2rem;
            text-align: center;
            font-size: 1.5rem;
            margin: 0.2rem;
        }
        .container {
            padding-bottom: 2rem;
        }
        .no-paste {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .modal-dialog-centered {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100% - 1rem);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Lockr</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="homepage.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-info text-center">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'view_accounts.php';
                }, 2000);
            </script>
        <?php else: ?>
            <h2 class="text-center">Your Accounts</h2>
            <?php if (!isset($accounts)): ?>
                <div class="row justify-content-center align-items-center" style="height:80vh">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title text-center">Enter Secret Key</h3>
                                <form action="view_accounts.php" method="post">
                                    <div class="form-group text-center">
                                        <label for="secret_key">Secret Key</label>
                                        <div class="form-group text-center">
                                            <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                            <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                            <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                            <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                            <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                            <input type="password" class="otp-input" maxlength="1" name="secret_key[]" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php if (count($accounts) > 0): ?>
                        <?php foreach ($accounts as $account): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Username: <?php echo htmlspecialchars($account['username']); ?></h5>
                                        <p class="card-text">Email: <?php echo htmlspecialchars($account['email']); ?></p>
                                        <button class="btn btn-primary" data-toggle="modal" data-target="#accountModal<?php echo $account['id']; ?>">See All</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="accountModal<?php echo $account['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="accountModalLabel<?php echo $account['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="accountModalLabel<?php echo $account['id']; ?>">Account Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Username:</strong> <?php echo htmlspecialchars($account['username']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($account['email']); ?></p>
                                            <p><strong>Password:</strong> <span id="password<?php echo $account['id']; ?>" class="password-field">********</span> <a href="#" class="toggle-password" onclick="togglePasswordVisibility(this, '<?php echo decrypt_password($account['password'], $secret_key); ?>')">Show</a></p>
                                            <p><strong>Description:</strong> <?php echo htmlspecialchars($account['description']); ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger" onclick="showDeleteModal('<?php echo $account['id']; ?>', '<?php echo $account['username']; ?>')">Delete</button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $account['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $account['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $account['id']; ?>">Confirm Deletion</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="hideDeleteModal('<?php echo $account['id']; ?>')">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Type the username <strong><?php echo htmlspecialchars($account['username']); ?></strong> to confirm deletion:</p>
                                            <form action="view_accounts.php" method="post" onsubmit="return confirmDeletion('<?php echo $account['username']; ?>')">
                                                <input type="hidden" name="delete_account_id" value="<?php echo $account['id']; ?>">
                                                <div class="form-group">
                                                    <input type="text" name="confirm_username" class="form-control no-paste" placeholder="Type username to confirm" required>
                                                </div>
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="hideDeleteModal('<?php echo $account['id']; ?>')">Cancel</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">No accounts found.</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function togglePasswordVisibility(link, password) {
            const passwordField = link.previousElementSibling;
            if (link.textContent === 'Show') {
                passwordField.textContent = password;
                link.textContent = 'Hide';
            } else {
                passwordField.textContent = '********';
                link.textContent = 'Show';
            }
        }

        function showDeleteModal(accountId, username) {
            $('#accountModal' + accountId).modal('hide');
            $('#deleteModal' + accountId).modal('show');
        }

        function hideDeleteModal(accountId) {
            $('#deleteModal' + accountId).modal('hide');
            $('#accountModal' + accountId).modal('show');
        }

        function confirmDeletion(username) {
            const input = document.querySelector('input[name="confirm_username"]');
            if (input.value !== username) {
                alert('Username confirmation does not match.');
                return false;
            }
            return true;
        }

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

        document.querySelectorAll('.no-paste').forEach(input => {
            input.addEventListener('paste', (e) => {
                e.preventDefault();
            });
        });
    </script>
</body>
</html>