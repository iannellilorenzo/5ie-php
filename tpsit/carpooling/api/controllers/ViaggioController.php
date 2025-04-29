<?php
require_once 'BaseController.php';

class ViaggioController extends BaseController {
    public function __construct($conn) {
        parent::__construct($conn);
        $this->model = new Viaggio($conn);
    }
    
    // Override getAll to add filtering capability
    public function getAll() {
        try {
            $filters = [];
            
            if (isset($_GET['partenza'])) {
                $filters['partenza'] = $_GET['partenza'];
            }
            
            if (isset($_GET['destinazione'])) {
                $filters['destinazione'] = $_GET['destinazione'];
            }
            
            if (isset($_GET['data_partenza'])) {
                $filters['data_partenza'] = $_GET['data_partenza'];
            }
            
            if (isset($_GET['autista_id'])) {
                $filters['autista_id'] = $_GET['autista_id'];
            }
            
            if (isset($_GET['posti'])) {
                $filters['posti'] = $_GET['posti'];
            }
            
            if (isset($_GET['prezzo_max'])) {
                $filters['prezzo_max'] = $_GET['prezzo_max'];
            }
            
            if (isset($_GET['stato'])) {
                $filters['stato'] = $_GET['stato'];
            }
            
            $items = $this->model->getAll($filters);
            sendSuccess($items);
        } catch (Exception $e) {
            sendError('Failed to retrieve trips: ' . $e->getMessage(), 500);
        }
    }
    
    // Get bookings for a trip
    public function getBookings($tripId) {
        try {
            global $user;
            
            // Get the trip to check ownership
            $trip = $this->model->getById($tripId);
            
            if (!$trip) {
                sendError('Trip not found', 404);
                return;
            }
            
            // Ensure only the trip driver can view all bookings
            if (!isset($user['tipo']) || $user['tipo'] != 'autista' || $user['id'] != $trip['autista_id']) {
                sendError('You are not authorized to view all bookings for this trip', 403);
                return;
            }
            
            // Load the Prenotazione model if not already loaded
            if (!class_exists('Prenotazione')) {
                require_once 'models/Prenotazione.php';
            }
            
            $bookingModel = new Prenotazione($this->conn);
            $bookings = $bookingModel->getAll(['viaggio_id' => $tripId]);
            
            sendSuccess($bookings);
        } catch (Exception $e) {
            sendError('Failed to retrieve trip bookings: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Close bookings for a trip
     * 
     * @param int $tripId The trip ID
     * @return void
     */
    public function closeBookings($tripId) {
        try {
            global $user;
            
            // Get the trip to check ownership
            $trip = $this->model->getById($tripId);
            
            if (!$trip) {
                sendError('Trip not found', 404);
                return;
            }
            
            // Ensure only the trip driver can close bookings
            if (!isset($user['tipo']) || $user['tipo'] != 'autista' || $user['id'] != $trip['id_autista']) {
                sendError('You are not authorized to close bookings for this trip', 403);
                return;
            }
            
            // Update trip status to close bookings
            $updateData = ['stato' => 'chiuso'];
            $success = $this->model->update($tripId, $updateData);
            
            if (!$success) {
                sendError('Failed to close bookings for this trip', 500);
                return;
            }
            
            // Send email notification to the driver
            require_once 'utils/EmailService.php';
            $emailService = new EmailService();
            
            // Get driver info
            require_once 'models/Autista.php';
            $driverModel = new Autista($this->conn);
            $driver = $driverModel->getById($trip['id_autista']);
            
            if ($driver && isset($driver['email'])) {
                $emailService->sendBookingClosedNotification(
                    $driver['email'],
                    $driver['nome'] . ' ' . $driver['cognome'],
                    $trip
                );
            }
            
            sendSuccess(null, 'Bookings for this trip have been closed');
        } catch (Exception $e) {
            sendError('Failed to close bookings: ' . $e->getMessage(), 500);
        }
    }
    
    // Override create to check authorization
    public function create($data) {
        try {
            global $user;
            
            // Ensure only drivers can create trips
            if (!isset($user['tipo']) || $user['tipo'] != 'autista') {
                sendError('Only drivers can create trips', 403);
                return;
            }
            
            // Ensure driver can only create trips for themselves
            if ($user['id'] != $data['autista_id']) {
                sendError('You can only create trips for yourself', 403);
                return;
            }
            
            parent::create($data);
        } catch (Exception $e) {
            sendError('Failed to create trip: ' . $e->getMessage(), 500);
        }
    }
    
    // Override update to check authorization
    public function update($id, $data) {
        try {
            global $user;
            
            // Get the trip to check ownership
            $trip = $this->model->getById($id);
            
            if (!$trip) {
                sendError('Trip not found', 404);
                return;
            }
            
            // Ensure only the trip driver can update
            if (!isset($user['tipo']) || $user['tipo'] != 'autista' || $user['id'] != $trip['autista_id']) {
                sendError('You are not authorized to update this trip', 403);
                return;
            }
            
            parent::update($id, $data);
        } catch (Exception $e) {
            sendError('Failed to update trip: ' . $e->getMessage(), 500);
        }
    }
}