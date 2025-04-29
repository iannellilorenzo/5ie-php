<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include configuration files
require_once 'config/database.php';
require_once 'config/config.php';

// Include utilities
require_once 'utils/response.php';
require_once 'utils/validation.php';

// Parse the request
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = parse_url($request_uri);
$path = $uri_parts['path'];

// Extract the API endpoint from the path
// Format: /5ie-php/tpsit/carpooling/api/resource/id
$path_parts = explode('/', trim($path, '/'));
$api_index = array_search('api', $path_parts);
$resource = $path_parts[$api_index + 1] ?? null;
$id = $path_parts[$api_index + 2] ?? null;
$action = $path_parts[$api_index + 3] ?? null;

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// Connect to database
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch(Exception $e) {
    sendResponse(500, ['error' => 'Database connection failed: ' . $e->getMessage()]);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Route the request to the appropriate handler
switch ($resource) {
    case 'autista':
        require_once 'controllers/BaseController.php';
        require_once 'models/Autista.php';
        require_once 'controllers/AutistaController.php'; // Corretto da DriverController a AutistaController
        $controller = new AutistaController($conn);       // Corretto da DriverController a AutistaController
        
        // Gestione delle azioni speciali per autisti
        if ($action === 'ratings' && $id) {
            $controller->getDriverWithRatings($id);
        } else if ($action === 'trips' && $id) {
            $controller->getDriverTrips($id);
        } else {
            handleRequest($controller, $method, $id, $data);
        }
        break;
        
    case 'passeggero':
        require_once 'controllers/BaseController.php';
        require_once 'models/Passeggero.php';
        require_once 'controllers/PasseggeroController.php'; // Corretto da PassengerController a PasseggeroController
        $controller = new PasseggeroController($conn);       // Corretto da PassengerController a PasseggeroController
        
        // Gestione delle azioni speciali per passeggeri
        if ($action === 'ratings' && $id) {
            $controller->getPassengerWithRatings($id);
        } else if ($action === 'bookings' && $id) {
            $controller->getPassengerBookings($id);
        } else {
            handleRequest($controller, $method, $id, $data);
        }
        break;
        
    case 'automobile':
        require_once 'controllers/BaseController.php';
        require_once 'models/Automobile.php';
        require_once 'controllers/AutomobileController.php'; // Corretto da CarController a AutomobileController
        $controller = new AutomobileController($conn);       // Corretto da CarController a AutomobileController
        handleRequest($controller, $method, $id, $data);
        break;
        
    case 'viaggio':
        require_once 'controllers/BaseController.php';
        require_once 'models/Viaggio.php';
        require_once 'controllers/ViaggioController.php'; // Corretto da TripController a ViaggioController
        $controller = new ViaggioController($conn);        // Corretto da TripController a ViaggioController
        
        // Gestione delle azioni speciali per viaggi
        if ($action === 'close-bookings' && $id) {
            $controller->closeBookings($id);
        } else {
            handleRequest($controller, $method, $id, $data);
        }
        break;
        
    case 'prenotazione':
        require_once 'controllers/BaseController.php';
        require_once 'models/Prenotazione.php';
        require_once 'controllers/PrenotazioneController.php'; // Corretto da BookingController a PrenotazioneController
        $controller = new PrenotazioneController($conn);       // Corretto da BookingController a PrenotazioneController
        
        // Gestione delle azioni speciali per prenotazioni
        if ($action === 'accept' && $id) {
            $controller->acceptBooking($id);
        } else if ($action === 'reject' && $id) {
            $controller->rejectBooking($id);
        } else if ($action === 'feedback' && $id) {
            $controller->submitFeedback($id, $data);
        } else {
            handleRequest($controller, $method, $id, $data);
        }
        break;
        
    default:
        sendResponse(404, ['error' => 'Resource not found']);
        break;
}

/**
 * Route the request to the appropriate controller method based on HTTP method
 */
function handleRequest($controller, $method, $id, $data) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $controller->getOne($id);
            } else {
                $controller->getAll();
            }
            break;
            
        case 'POST':
            $controller->create($data);
            break;
            
        case 'PUT':
            if ($id) {
                $controller->update($id, $data);
            } else {
                sendError('ID is required for update', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $controller->delete($id);
            } else {
                sendError('ID is required for deletion', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
            break;
    }
}
?>