<?php
require_once 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

$param = explode('/', trim($path, '/'));

try {
    $GLOBALS['conn'] = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
    $GLOBALS['conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit();
}

// Endpoints:
// /user/register
// /user/login
// user/{email}
// /account
// /account/{id}

switch ($method) {
    case 'GET':
        if (isset($param[3]) && $param[3] == 'account' && isset($param[4])) {
            if (is_numeric($param[4])) {
                viewAccount($param[4]);
            } else {
                viewAllAccounts($param[4]);
            }
        } else if (isset($param[3]) && $param[3] == 'user' && isset($param[4])) {
            getPasswordHash($param[4]);
        } else {
            echo json_encode(["message" => "Invalid path"]);
        }
        break;
    case 'POST':
        if (isset($param[3]) && $param[3] == 'user') {
            if (isset($param[4]) && $param[4] == 'register') {
                registerUser();
            } elseif (isset($param[4]) && $param[4] == 'login') {
                loginUser();
            } else {
                echo json_encode(["message" => "Invalid path"]);
            }
        } elseif (isset($param[3]) && $param[3] == 'account') {
            addAccount();
        } else {
            echo json_encode(["message" => "Invalid path"]);
        }
        break;
    case 'PUT':
        if (isset($param[3]) && $param[3] == 'account' && isset($param[4]) && is_numeric($param[4])) {
            updateAccount($param[4]);
        } else {
            echo json_encode(["message" => "Invalid path or invalid ID"]);
        }
        break;
    case 'DELETE':
        if (isset($param[3]) && $param[3] == 'account' && isset($param[4]) && is_numeric($param[4])) {
            deleteAccount($param[4]);
        } else {
            echo json_encode(["message" => "Invalid path or invalid ID"]);
        }
        break;
    default:
        echo json_encode(["message" => "Invalid request method"]);
        break;
}

function registerUser() {
    $conn = $GLOBALS['conn'];
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['Username'];
    $password_hash = password_hash($data['PasswordHash'], PASSWORD_ARGON2ID);
    $email = $data['Email'];
    $phone_number = $data['PhoneNumber'];
    $first_name = $data['FirstName'] ?? null;
    $last_name = $data['LastName'] ?? null;
    $secret_key = password_hash($data['SecretKey'], PASSWORD_ARGON2ID);
    $status_id = 2; // Inactive status until email is verified
    $role_id = 2; // User role
    $session_token = bin2hex(random_bytes(32)); // Session token
    $verification_token = bin2hex(random_bytes(32)); // Email verification token
    $hashed_verification_token = password_hash($verification_token, PASSWORD_ARGON2ID); // Hash the verification token

    try {
        $stmt = $conn->prepare("INSERT INTO users (email, username, first_name, last_name, password_hash, phone_number, secret_key, status_id, role_id, session_token, verification_token) VALUES (:email, :username, :first_name, :last_name, :password_hash, :phone_number, :secret_key, :status_id, :role_id, :session_token, :verification_token)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->bindParam(':secret_key', $secret_key);
        $stmt->bindParam(':status_id', $status_id);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':session_token', $session_token);
        $stmt->bindParam(':verification_token', $hashed_verification_token);
        $stmt->execute();

        $verification_link = "http://localhost/5ie-php/cs/account-manager/verify_email.php?token=$verification_token";
        $subject = "Email Verification - Lockr Account Activation";
        $message = "Be aware that this is still a test, and you won't be able to actually verify your email via our link. Please click the following link to verify your email: $verification_link";
        $headers = "From: iannelli.lorenzo.studente@itispaleocapa.it";

        mail($email, $subject, $message, $headers);

        echo json_encode(['message' => 'User registered successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function loginUser() {
    $conn = $GLOBALS['conn'];
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['Username'];
    $password = $data['PasswordHash'];
    $secret_key = $data['SecretKey'];

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash']) && password_verify($secret_key, $user['secret_key'])) {
            $session_token = bin2hex(random_bytes(32));
            $stmt = $conn->prepare("UPDATE users SET session_token = :session_token WHERE username = :username");
            $stmt->bindParam(':session_token', $session_token);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            echo json_encode(['message' => 'Login successful.', 'session_token' => $session_token]);
        } else {
            echo json_encode(['error' => 'Invalid username, password, or secret key.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function addAccount() {
    $conn = $GLOBALS['conn'];
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['Username'];
    $email = $data['Email'];
    $password = $data['Password'];
    $description = $data['Description'];
    $user_reference = $data['UserReference'];
    $secret_key = $data['SecretKey'];

    try {
        // Verify the secret key
        $stmt = $conn->prepare("SELECT secret_key FROM users WHERE email = :user_reference");
        $stmt->bindParam(':user_reference', $user_reference);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($secret_key, $user['secret_key'])) {
            $stmt = $conn->prepare("INSERT INTO accounts (username, email, password, description, user_reference) VALUES (:username, :email, :password, :description, :user_reference)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':user_reference', $user_reference);
            $stmt->execute();

            echo json_encode(['message' => 'Account added successfully.']);
        } else {
            echo json_encode(['error' => 'Invalid secret key.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function viewAccount($account_id) {
    $conn = $GLOBALS['conn'];

    try {
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = :account_id");
        $stmt->bindParam(':account_id', $account_id);
        $stmt->execute();
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($account);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function viewAllAccounts($email) {
    $conn = $GLOBALS['conn'];

    try {
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_reference = :user_reference");
        $stmt->bindParam(':user_reference', $email);
        $stmt->execute();
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($accounts);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getPasswordHash($email) {
    $conn = $GLOBALS['conn'];

    try {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(['password_hash' => $user['password_hash']]);
        } else {
            echo json_encode(['error' => 'User not found.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateAccount($account_id) {
    $conn = $GLOBALS['conn'];
    $data = json_decode(file_get_contents('php://input'), true);
    $fields = [];
    $params = [':account_id' => $account_id];
    $secret_key = $data['SecretKey'];

    // Verify the secret key
    $stmt = $conn->prepare("SELECT secret_key FROM users WHERE email = :user_reference");
    $stmt->bindParam(':user_reference', $data['UserReference']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($secret_key, $user['secret_key'])) {
        if (!empty($data['Username'])) {
            $fields[] = 'username = :new_username';
            $params[':new_username'] = $data['Username'];
        }
        if (!empty($data['Email'])) {
            $fields[] = 'email = :new_email';
            $params[':new_email'] = $data['Email'];
        }
        if (!empty($data['Password'])) {
            $fields[] = 'password = :new_password';
            $params[':new_password'] = $data["Password"];
        }
        if (!empty($data['Description'])) {
            $fields[] = 'description = :new_description';
            $params[':new_description'] = $data['Description'];
        }

        if (empty($fields)) {
            echo json_encode(['error' => 'No fields to update.']);
            return;
        }

        $sql = 'UPDATE accounts SET ' . implode(', ', $fields) . ' WHERE id = :account_id';

        try {
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => &$val) {
                $stmt->bindParam($key, $val);
            }
            $stmt->execute();

            echo json_encode(['message' => 'Account updated successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Invalid secret key.']);
    }
}

function deleteAccount($account_id) {
    $conn = $GLOBALS['conn'];

    try {
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id = :account_id");
        $stmt->bindParam(':account_id', $account_id);
        $stmt->execute();

        echo json_encode(['message' => 'Account deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}