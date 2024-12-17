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
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_ARGON2ID);

            try {
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $password);

                if ($stmt->execute()) {
                    header("Location: sign_up.php?message=" . urlencode("User created successfully!"));
                } else {
                    header("Location: sign_up.php?message=" . urlencode("Error creating user."));
                }
            } catch (PDOException $e) {
                header("Location: sign_up.php?message=" . urlencode("Error: " . $e->getMessage()));
            }
        } else {
            header("Location: sign_up.php?message=" . urlencode("Please provide both username and password."));
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

                if ($user && password_verify($password, $user['password'])) {
                    session_start();
                    $_SESSION['username'] = $user['username'];
                    header("Location: index.php");
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