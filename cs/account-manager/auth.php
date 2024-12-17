<?php
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header("Location: sign_up.php?message=" . urlencode("Unable to connect to the database. Please try again later."));
    exit();
}

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'signup') {
        if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['phone_number'])) {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_ARGON2ID);
            $phone_number = $_POST['phone_number'];
            $status_id = 1; // Active status

            if (strpos($username, 'Lockr!') === 0) {
                $role_id = 1; // Admin role
            } else {
                $role_id = 2; // User role
            }

            try {
                $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, password_hash, phone_number, status_id, role_id) VALUES (:username, :first_name, :last_name, :password_hash, :phone_number, :status_id, :role_id)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':password_hash', $password);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':status_id', $status_id);
                $stmt->bindParam(':role_id', $role_id);

                if ($stmt->execute()) {
                    header("Location: sign_up.php?message=" . urlencode("User created successfully!"));
                } else {
                    header("Location: sign_up.php?message=" . urlencode("Error creating user."));
                }
            } catch (PDOException $e) {
                header("Location: sign_up.php?message=" . urlencode("Error: " . $e->getMessage()));
            }
        } else {
            header("Location: sign_up.php?message=" . urlencode("Please provide all required fields."));
        }
    } elseif ($_POST['action'] == 'signin') {
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            try {
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    header("Location: index.php?message=" . urlencode("Welcome, " . $user['username'] . "!"));
                    exit();
                } else {
                    header("Location: sign_in.php?message=" . urlencode("Invalid username or password."));
                }
            } catch (PDOException $e) {
                header("Location: sign_in.php?message=" . urlencode("Error: " . $e->getMessage()));
            }
        } else {
            header("Location: sign_in.php?message=" . urlencode("Please provide both username and password."));
        }
    }
}