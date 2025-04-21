<?php
require_once 'BaseController.php';

class DriverController extends BaseController {
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
}