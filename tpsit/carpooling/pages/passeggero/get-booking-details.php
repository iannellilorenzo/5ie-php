<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header for JSON response
header('Content-Type: application/json');

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passeggero') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Prenotazione.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Booking ID is required']);
    exit();
}

$bookingId = $_GET['id'];

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get booking details
    $prenotazioneModel = new Prenotazione($conn);
    $booking = $prenotazioneModel->getById($bookingId);
    
    // Verify that the booking belongs to the logged-in passenger
    if (!$booking) {
        echo json_encode(['error' => 'Booking not found']);
        exit();
    }
    
    if ($booking['id_passeggero'] != $_SESSION['user_id']) {
        echo json_encode(['error' => 'Access denied to this booking']);
        exit();
    }
    
    // Format the data for the response with safe defaults
    $response = [
        'id_prenotazione' => $booking['id_prenotazione'] ?? null,
        'citta_partenza' => $booking['citta_partenza'] ?? 'Not specified',
        'citta_destinazione' => $booking['citta_destinazione'] ?? 'Not specified',
        'departure_date' => date('l, F j, Y', strtotime($booking['timestamp_partenza'] ?? 'now')),
        'departure_time' => date('H:i', strtotime($booking['timestamp_partenza'] ?? 'now')),
        'booking_date' => date('M d, Y H:i', strtotime($booking['timestamp_prenotazione'] ?? 'now')),
        'stato' => $booking['stato'] ?? 'in attesa',
        'n_posti' => $booking['n_posti'] ?? 1,
        'note' => $booking['note'] ?? '',
        'prezzo_cadauno' => $booking['prezzo_cadauno'] ?? 0,
        'total_price' => number_format(($booking['prezzo_cadauno'] ?? 0) * ($booking['n_posti'] ?? 1), 2),
        'driver_name' => trim(($booking['nome_autista'] ?? '') . ' ' . ($booking['cognome_autista'] ?? '')),
        'driver_rating' => $booking['valutazione_autista'] ?? $booking['voto_autista'] ?? '0.0',
        'driver_photo' => $booking['foto_autista'] ?? '',
        'soste' => (int)($booking['soste'] ?? 0),
        'bagaglio' => (int)($booking['bagaglio'] ?? 0),
        'animali' => (int)($booking['animali'] ?? 0),
        'has_rating' => !empty($booking['voto_autista']),
        'marca' => $booking['marca'] ?? null,
        'modello' => $booking['modello'] ?? null, 
        'targa' => $booking['targa'] ?? null,
        'colore' => $booking['colore'] ?? null
    ];

    // Calculate arrival time if tempo_stimato is available
    if (!empty($booking['tempo_stimato'])) {
        try {
            $departureDateTime = new DateTime($booking['timestamp_partenza'] ?? 'now');
            $durationParts = explode(':', $booking['tempo_stimato'] ?? '00:00');
            $hours = isset($durationParts[0]) ? (int)$durationParts[0] : 0;
            $minutes = isset($durationParts[1]) ? (int)$durationParts[1] : 0;
            
            $durationInterval = new DateInterval('PT' . $hours . 'H' . $minutes . 'M');
            $arrivalDateTime = clone $departureDateTime;
            $arrivalDateTime->add($durationInterval);
            $response['arrival_time'] = $arrivalDateTime->format('H:i') . ' (estimated)';
        } catch (Exception $e) {
            $response['arrival_time'] = 'Unknown';
        }
    } else {
        $response['arrival_time'] = 'Unknown';
    }
    
    // Return the data as JSON
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error retrieving booking details',
        'message' => $e->getMessage()
    ]);
}
?>