<?php
// get-bookings.php
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'autista') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Make sure trip ID is provided
if (!isset($_GET['trip_id']) || empty($_GET['trip_id'])) {
    echo json_encode(['error' => 'Trip ID is required']);
    exit();
}

$tripId = $_GET['trip_id'];

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Prenotazione.php';

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get bookings for this trip
    $prenotazioneModel = new Prenotazione($conn);
    $bookings = $prenotazioneModel->getAll(['id_viaggio' => $tripId]);
    
    echo json_encode($bookings);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}