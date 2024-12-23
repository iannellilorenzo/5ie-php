<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    try {
        $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Clear the token from the database
        $stmt = $conn->prepare("UPDATE users SET token = NULL WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
    } catch (PDOException $e) {
        // Handle error
    }
}

// Clear the session
session_unset();
session_destroy();

// Clear the cookie
setcookie("auth_token", "", time() - 3600, "/");

// Redirect to the sign-in page
header("Location: sign_in.php");
exit();