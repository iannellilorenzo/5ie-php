<?php
require_once 'BaseController.php';

class PassengerController extends BaseController {
    public function __construct($conn) {
        parent::__construct($conn);
        $this->model = new Passeggero($conn);
    }
    
    // Get bookings for a passenger
    public function getBookings($passengerId) {
        try {
            // Load the Prenotazione model if not already loaded
            if (!class_exists('Prenotazione')) {
                require_once 'models/Prenotazione.php';
            }
            
            $bookingModel = new Prenotazione($this->conn);
            $bookings = $bookingModel->getAll(['passeggero_id' => $passengerId]);
            
            sendSuccess($bookings);
        } catch (Exception $e) {
            sendError('Failed to retrieve passenger bookings: ' . $e->getMessage(), 500);
        }
    }
    
    // Override getAll to add filtering capability
    public function getAll() {
        try {
            $filters = [];
            
            if (isset($_GET['city'])) {
                $filters['city'] = $_GET['city'];
            }
            
            $items = $this->model->getAll($filters);
            sendSuccess($items);
        } catch (Exception $e) {
            sendError('Failed to retrieve passengers: ' . $e->getMessage(), 500);
        }
    }
}