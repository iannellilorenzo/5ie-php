<?php
require_once 'BaseController.php';

class AutomobileController extends BaseController {
    public function __construct($conn) {
        parent::__construct($conn);
        $this->model = new Automobile($conn);
    }
    
    // Override getAll to add filtering capability
    public function getAll() {
        try {
            $filters = [];
            
            if (isset($_GET['autista_id'])) {
                $filters['autista_id'] = $_GET['autista_id'];
            }
            
            if (isset($_GET['n_posti'])) {
                $filters['n_posti'] = $_GET['n_posti'];
            }
            
            $items = $this->model->getAll($filters);
            sendSuccess($items);
        } catch (Exception $e) {
            sendError('Failed to retrieve cars: ' . $e->getMessage(), 500);
        }
    }
    
    // Override create to check authorization
    public function create($data) {
        try {
            global $user;
            
            // Ensure only drivers can create cars
            if (!isset($user['tipo']) || $user['tipo'] != 'autista') {
                sendError('Only drivers can add cars', 403);
                return;
            }
            
            // Ensure driver can only create cars for themselves
            if ($user['id'] != $data['autista_id']) {
                sendError('You can only add cars for yourself', 403);
                return;
            }
            
            parent::create($data);
        } catch (Exception $e) {
            sendError('Failed to create car: ' . $e->getMessage(), 500);
        }
    }
    
    // Override update to check authorization
    public function update($id, $data) {
        try {
            global $user;
            
            // Get the car to check ownership
            $car = $this->model->getById($id);
            
            if (!$car) {
                sendError('Car not found', 404);
                return;
            }
            
            // Ensure only the car owner can update
            if (!isset($user['tipo']) || $user['tipo'] != 'autista' || $user['id'] != $car['autista_id']) {
                sendError('You are not authorized to update this car', 403);
                return;
            }
            
            parent::update($id, $data);
        } catch (Exception $e) {
            sendError('Failed to update car: ' . $e->getMessage(), 500);
        }
    }
}