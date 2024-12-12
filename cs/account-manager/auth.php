<?php
require_once 'config.php';

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "
    <div style='color: red; text-align: center; margin-top: 20px;'>
        <strong>Error:</strong> Unable to connect to the database. Please try again later.
    </div>";
exit();
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    exit();
}

$username = $_POST['username'];
$password = $_POST['password'];
