<?php
require_once 'BaseController.php';

class PasseggeroController extends BaseController {
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

    /**
     * Get passenger profile with ratings
     * 
     * @param int $passengerId The passenger ID
     * @return void
     */
    public function getPassengerWithRatings($passengerId) {
        try {
            global $user;
            
            $passenger = $this->model->getPassengerWithRatings($passengerId);
            
            if (!$passenger) {
                sendError('Passenger not found', 404);
                return;
            }
            
            // Remove sensitive information
            unset($passenger['password']);
            
            // For privacy reasons, only allow autisti to see full passenger profiles with ratings
            // or if the passenger is viewing their own profile
            if (!isset($user['tipo']) || 
                ($user['tipo'] != 'autista' && 
                 ($user['tipo'] != 'passeggero' || $user['id'] != $passengerId))) {
                // Remove reviews if not authorized
                unset($passenger['reviews']);
            }
            
            sendSuccess($passenger);
        } catch (Exception $e) {
            sendError('Failed to retrieve passenger profile: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get all bookings for a passenger
     * 
     * @param int $passengerId The passenger ID
     * @return void
     */
    public function getPassengerBookings($passengerId) {
        try {
            global $user;
            
            // Only the passenger themselves can view their bookings
            if (!isset($user['tipo']) || 
                $user['tipo'] != 'passeggero' || 
                $user['id'] != $passengerId) {
                sendError('You are not authorized to view these bookings', 403);
                return;
            }
            
            $bookings = $this->model->getPassengerBookings($passengerId);
            sendSuccess($bookings);
        } catch (Exception $e) {
            sendError('Failed to retrieve passenger bookings: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Login for passengers
     * 
     * @param string $email Passenger email
     * @param string $password Passenger password
     * @return void
     */
    public function login($email, $password) {
        try {
            // Implement login functionality based on your authentication setup
            $query = "SELECT id_passeggero, nome, cognome, email, password, documento_identita, telefono 
                      FROM passeggeri 
                      WHERE email = ?";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            $passenger = $stmt->fetch();
            
            if (!$passenger || !password_verify($password, $passenger['password'])) {
                sendError('Invalid credentials', 401);
                return;
            }
            
            // Remove password from the response
            unset($passenger['password']);
            
            // Add the user type to the response
            $passenger['tipo'] = 'passeggero';
            
            // Create session
            $_SESSION['user'] = $passenger;
            $_SESSION['user_type'] = 'passeggero';
            
            sendSuccess($passenger, 'Login successful');
        } catch (Exception $e) {
            sendError('Login failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Register a new passenger
     * 
     * @param array $data Passenger registration data
     * @return void
     */
    public function register($data) {
        try {
            $passengerId = $this->model->create($data);
            
            if (!$passengerId) {
                sendError('Failed to register passenger', 500);
                return;
            }
            
            $passenger = $this->model->getById($passengerId);
            
            // Remove password from the response
            unset($passenger['password']);
            
            // Add the user type to the response
            $passenger['tipo'] = 'passeggero';
            
            // Create session if auto-login is desired
            $_SESSION['user'] = $passenger;
            $_SESSION['user_type'] = 'passeggero';
            
            sendSuccess($passenger, 'Registration successful');
        } catch (Exception $e) {
            sendError('Registration failed: ' . $e->getMessage(), 500);
        }
    }
}