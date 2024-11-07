<?php
// login.php

// use a query that adds a standard user, hashing the password

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "your_database_name";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);

    // Execute the statement
    $stmt->execute();

    // Store the result
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Login successful!";
    } else {
        echo "Invalid username or password.";
    }

    // Close the statement and connection
   $stmt->close();
    $conn->close();
}