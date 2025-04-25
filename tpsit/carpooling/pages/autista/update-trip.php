<?php
// update-trip.php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'autista') {
    header("Location: {$rootPath}login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $tripId = $_GET['id'];
    
    // Include database and models
    require_once $rootPath . 'api/config/database.php';
    require_once $rootPath . 'api/models/Viaggio.php';
    
    try {
        // Connect to database
        $db = new Database();
        $conn = $db->getConnection();
        
        $viaggioModel = new Viaggio($conn);
        
        // Create timestamp from date and time
        $timestamp = $_POST['data'] . ' ' . $_POST['ora'] . ':00';
        
        // Format time_stimato
        $hours = empty($_POST['ore']) ? 0 : intval($_POST['ore']);
        $minutes = empty($_POST['minuti']) ? 0 : intval($_POST['minuti']);
        $tempo_stimato = sprintf('%02d:%02d:00', $hours, $minutes);
        
        // Update trip data
        $tripData = [
            'citta_partenza' => $_POST['citta_partenza'],
            'citta_destinazione' => $_POST['citta_destinazione'],
            'timestamp_partenza' => $timestamp,
            'prezzo_cadauno' => $_POST['prezzo_cadauno'],
            'tempo_stimato' => $tempo_stimato,
            'soste' => isset($_POST['soste']) ? 1 : 0,
            'bagaglio' => isset($_POST['bagaglio']) ? 1 : 0,
            'animali' => isset($_POST['animali']) ? 1 : 0
        ];
        
        $viaggioModel->update($tripId, $tripData);
        
        // Redirect back with success message
        header("Location: trips.php?success=Trip+updated+successfully");
        exit();
    } catch (Exception $e) {
        // Redirect back with error
        header("Location: trips.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Invalid request
    header("Location: trips.php");
    exit();
}