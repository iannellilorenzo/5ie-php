<?php
require_once 'config.php';

if (isset($_GET['token'])) {
    $verification_token = $_GET['token'];

    try {
        $conn = new PDO("mysql:host=$server_name;dbname=$db_name", $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM users WHERE verification_token IS NOT NULL");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            if (password_verify($verification_token, $user['verification_token'])) {
                $stmt = $conn->prepare("UPDATE users SET status_id = 1, verification_token = NULL WHERE email = :email");
                $stmt->bindParam(':email', $user['email']);
                $stmt->execute();

                echo "Email verified successfully. You can now <a href='sign_in.php'>sign in</a>.";
                exit();
            }
        }

        echo "Invalid verification token.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No verification token provided.";
}