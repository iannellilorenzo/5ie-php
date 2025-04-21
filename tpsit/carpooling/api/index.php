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

// Route the request to the appropriate handler
switch ($resource) {
    case 'autista':
        require_once 'controllers/BaseController.php';
        require_once 'models/Autista.php';
        require_once 'controllers/DriverController.php';
        $controller = new DriverController($conn);
        handleRequest($controller, $method, $id, $data);
        break;
        
    case 'passeggero':
        require_once 'controllers/BaseController.php';
        require_once 'models/Passeggero.php';
        require_once 'controllers/PassengerController.php';
        $controller = new PassengerController($conn);
        handleRequest($controller, $method, $id, $data);
        break;
        
    case 'automobile':
        require_once 'controllers/BaseController.php';
        require_once 'models/Automobile.php';
        require_once 'controllers/CarController.php';
        $controller = new CarController($conn);
        handleRequest($controller, $method, $id, $data);
        break;
        
    case 'viaggio':
        require_once 'controllers/BaseController.php';
        require_once 'models/Viaggio.php';
        require_once 'controllers/TripController.php';
        $controller = new TripController($conn);
        handleRequest($controller, $method, $id, $data);
        break;
        
    case 'prenotazione':
        require_once 'controllers/BaseController.php';
        require_once 'models/Prenotazione.php';
        require_once 'controllers/BookingController.php';
        $controller = new BookingController($conn);
        handleRequest($controller, $method, $id, $data);
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