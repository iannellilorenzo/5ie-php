<?php
require_once 'BaseController.php';

class AutistaController extends BaseController {
    public function __construct($conn) {
        parent::__construct($conn);
        $this->model = new Autista($conn);
    }
    
    // Custom method example: Get trips by driver
    public function getTripsByDriver($id) {
        try {
            // Load the Viaggio model if not already loaded
            if (!class_exists('Viaggio')) {
                require_once 'models/Viaggio.php';
            }
            
            $tripModel = new Viaggio($this->conn);
            $trips = $tripModel->getByDriverId($id);
            
            sendSuccess($trips);
        } catch (Exception $e) {
            sendError('Failed to retrieve driver trips: ' . $e->getMessage(), 500);
        }
    }
    
    // Override getAll to add filtering capability
    public function getAll() {
        try {
            $filters = [];
            
            // Add query parameters as filters
            if (isset($_GET['city'])) {
                $filters['city'] = $_GET['city'];
            }
            
            if (isset($_GET['rating'])) {
                $filters['rating'] = $_GET['rating'];
            }
            
            $items = $this->model->getAll($filters);
            sendSuccess($items);
        } catch (Exception $e) {
            sendError('Failed to retrieve drivers: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get driver profile with ratings
     * 
     * @param int $driverId The driver ID
     * @return void
     */
    public function getDriverWithRatings($driverId) {
        try {
            $driver = $this->model->getDriverWithRatings($driverId);
            
            if (!$driver) {
                sendError('Driver not found', 404);
                return;
            }
            
            // Remove sensitive information
            unset($driver['password']);
            
            sendSuccess($driver);
        } catch (Exception $e) {
            sendError('Failed to retrieve driver profile: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get all trips created by a driver
     * 
     * @param int $driverId The driver ID
     * @return void
     */
    public function getDriverTrips($driverId) {
        try {
            global $user;
            
            // Only the driver themselves or an admin can view all their trips
            if (!isset($user['tipo']) || 
                ($user['tipo'] != 'admin' && 
                 ($user['tipo'] != 'autista' || $user['id'] != $driverId))) {
                sendError('You are not authorized to view all trips for this driver', 403);
                return;
            }
            
            $trips = $this->model->getDriverTrips($driverId);
            sendSuccess($trips);
        } catch (Exception $e) {
            sendError('Failed to retrieve driver trips: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Register a new driver
     * 
     * @param array $data Driver registration data
     * @return void
     */
    public function register($data) {
        try {
            $driverId = $this->model->create($data);
            
            if (!$driverId) {
                sendError('Failed to register driver', 500);
                return;
            }
            
            $driver = $this->model->getById($driverId);
            
            // Remove password from the response
            unset($driver['password']);
            
            // Add the user type to the response
            $driver['tipo'] = 'autista';
            
            // Create session if auto-login is desired
            $_SESSION['user'] = $driver;
            $_SESSION['user_type'] = 'autista';
            
            sendSuccess($driver, 'Registration successful');
        } catch (Exception $e) {
            sendError('Registration failed: ' . $e->getMessage(), 500);
        }
    }
}