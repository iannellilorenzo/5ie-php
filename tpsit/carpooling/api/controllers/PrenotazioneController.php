<?php
require_once 'BaseController.php';

class PrenotazioneController extends BaseController {
    public function __construct($conn) {
        parent::__construct($conn);
        $this->model = new Prenotazione($conn);
    }
    
    // Override getAll to add filtering capability
    public function getAll() {
        try {
            global $user;
            $filters = [];
            
            // Adjust this section to properly handle the trip_id parameter
            // Add support for the trip_id parameter that the current endpoint uses
            if (isset($_GET['trip_id']) || isset($_GET['viaggio_id'])) {
                $filters['id_viaggio'] = $_GET['trip_id'] ?? $_GET['viaggio_id'];
                
                // If user is a driver, verify they own this trip
                if (isset($user['tipo']) && $user['tipo'] == 'autista') {
                    if (!class_exists('Viaggio')) {
                        require_once 'api/models/Viaggio.php';
                    }
                    
                    $tripModel = new Viaggio($this->conn);
                    $trip = $tripModel->getById($filters['id_viaggio']);
                    
                    if (!$trip || $trip['id_autista'] != $user['id']) {
                        sendError('You can only view bookings for your own trips', 403);
                        return;
                    }
                }
            }
            
            // Passengers can only see their own bookings
            if (isset($user['tipo']) && $user['tipo'] == 'passeggero') {
                $filters['passeggero_id'] = $user['id'];
            }
            // Drivers can only see bookings for their trips
            else if (isset($user['tipo']) && $user['tipo'] == 'autista') {
                // We need to get trips by this driver first
                if (!class_exists('Viaggio')) {
                    require_once 'models/Viaggio.php';
                }
                
                $tripModel = new Viaggio($this->conn);
                $trips = $tripModel->getByDriverId($user['id']);
                
                // If driver has no trips, return empty array
                if (empty($trips)) {
                    sendSuccess([]);
                    return;
                }
                
                // Otherwise filter by trip IDs
                $tripIds = array_column($trips, 'id');
                $bookings = [];
                
                foreach ($tripIds as $tripId) {
                    $result = $this->model->getAll(['viaggio_id' => $tripId]);
                    $bookings = array_merge($bookings, $result);
                }
                
                sendSuccess($bookings);
                return;
            }
            
            // Add other filters
            if (isset($_GET['viaggio_id'])) {
                $filters['viaggio_id'] = $_GET['viaggio_id'];
            }
            
            if (isset($_GET['stato'])) {
                $filters['stato'] = $_GET['stato'];
            }
            
            $items = $this->model->getAll($filters);
            sendSuccess($items);
        } catch (Exception $e) {
            sendError('Failed to retrieve bookings: ' . $e->getMessage(), 500);
        }
    }
    
    // Override getOne to check authorization
    public function getOne($id) {
        try {
            global $user;
            
            // Get the booking first
            $booking = $this->model->getById($id);
            
            if (!$booking) {
                sendError('Booking not found', 404);
                return;
            }
            
            // Check authorization - only the booking passenger or trip driver can view
            if (isset($user['tipo'])) {
                if ($user['tipo'] == 'passeggero' && $user['id'] == $booking['passeggero_id']) {
                    // Passenger viewing their own booking - ok
                    sendSuccess($booking);
                    return;
                } else if ($user['tipo'] == 'autista' && $user['id'] == $booking['autista_id']) {
                    // Driver viewing booking for their trip - ok
                    sendSuccess($booking);
                    return;
                }
            }
            
            sendError('You are not authorized to view this booking', 403);
        } catch (Exception $e) {
            sendError('Failed to retrieve booking: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Accept a booking request
     * 
     * @param int $bookingId The booking ID
     * @return void
     */
    public function acceptBooking($bookingId) {
        try {
            global $user;
            
            // Get the booking first
            $booking = $this->model->getById($bookingId);
            
            if (!$booking) {
                sendError('Booking not found', 404);
                return;
            }
            
            // Check if user is the driver of the trip
            if (!isset($user['tipo']) || $user['tipo'] != 'autista' || $user['id'] != $booking['id_autista']) {
                sendError('Only the trip driver can accept bookings', 403);
                return;
            }
            
            // Check if the booking is in a pending state
            if (isset($booking['stato']) && $booking['stato'] != 'in attesa') {
                sendError('This booking is not in a pending state', 400);
                return;
            }
            
            // Update booking status
            $updateData = ['stato' => 'accettata'];
            $success = $this->model->update($bookingId, $updateData);
            
            if (!$success) {
                sendError('Failed to accept booking', 500);
                return;
            }
            
            // Send email notification to passenger
            require_once 'utils/EmailService.php';
            $emailService = new EmailService();
            
            // Get passenger info
            require_once 'models/Passeggero.php';
            $passengerModel = new Passeggero($this->conn);
            $passenger = $passengerModel->getById($booking['id_passeggero']);
            
            // Get driver info
            require_once 'models/Autista.php';
            $driverModel = new Autista($this->conn);
            $driver = $driverModel->getById($booking['id_autista']);
            
            // Get trip info
            require_once 'models/Viaggio.php';
            $tripModel = new Viaggio($this->conn);
            $trip = $tripModel->getById($booking['id_viaggio']);
            
            // Get vehicle info
            require_once 'models/Automobile.php';
            $vehicleModel = new Automobile($this->conn);
            $vehicle = $vehicleModel->getByDriverId($booking['id_autista']);
            
            // Send email
            if ($passenger && $driver && $trip && $vehicle) {
                $emailService->sendBookingAcceptedNotification(
                    $passenger['email'],
                    $passenger['nome'] . ' ' . $passenger['cognome'],
                    $driver,
                    $trip,
                    $vehicle
                );
            }
            
            sendSuccess(null, 'Booking accepted successfully');
        } catch (Exception $e) {
            sendError('Failed to accept booking: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Reject a booking request
     * 
     * @param int $bookingId The booking ID
     * @return void
     */
    public function rejectBooking($bookingId) {
        try {
            global $user;
            
            // Get the booking first
            $booking = $this->model->getById($bookingId);
            
            if (!$booking) {
                sendError('Booking not found', 404);
                return;
            }
            
            // Check if user is the driver of the trip
            if (!isset($user['tipo']) || $user['tipo'] != 'autista' || $user['id'] != $booking['id_autista']) {
                sendError('Only the trip driver can reject bookings', 403);
                return;
            }
            
            // Check if the booking is in a pending state
            if (isset($booking['stato']) && $booking['stato'] != 'in attesa') {
                sendError('This booking is not in a pending state', 400);
                return;
            }
            
            // Update booking status
            $updateData = ['stato' => 'rifiutata'];
            $success = $this->model->update($bookingId, $updateData);
            
            if (!$success) {
                sendError('Failed to reject booking', 500);
                return;
            }
            
            // Send email notification to passenger
            require_once 'utils/EmailService.php';
            $emailService = new EmailService();
            
            // Get passenger info
            require_once 'models/Passeggero.php';
            $passengerModel = new Passeggero($this->conn);
            $passenger = $passengerModel->getById($booking['id_passeggero']);
            
            // Get trip info
            require_once 'models/Viaggio.php';
            $tripModel = new Viaggio($this->conn);
            $trip = $tripModel->getById($booking['id_viaggio']);
            
            // Send email
            if ($passenger && $trip) {
                $emailService->sendBookingRejectedNotification(
                    $passenger['email'],
                    $passenger['nome'] . ' ' . $passenger['cognome'],
                    $trip
                );
            }
            
            sendSuccess(null, 'Booking rejected successfully');
        } catch (Exception $e) {
            sendError('Failed to reject booking: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Submit feedback for a completed trip
     * 
     * @param int $bookingId The booking ID
     * @param array $data The feedback data
     * @return void
     */
    public function submitFeedback($bookingId, $data) {
        try {
            global $user;
            
            // Get the booking first
            $booking = $this->model->getById($bookingId);
            
            if (!$booking) {
                sendError('Booking not found', 404);
                return;
            }
            
            // Check if trip is completed
            if ($booking['timestamp_partenza'] > date('Y-m-d H:i:s')) {
                sendError('Feedback can only be submitted for completed trips', 400);
                return;
            }
            
            $updateData = [];
            
            // Check if user is passenger or driver and handle accordingly
            if (isset($user['tipo']) && $user['tipo'] == 'passeggero' && $user['id'] == $booking['id_passeggero']) {
                // Passenger submitting feedback for driver
                if (!isset($data['voto']) || !is_numeric($data['voto']) || $data['voto'] < 1 || $data['voto'] > 5) {
                    sendError('Rating must be a number between 1 and 5', 400);
                    return;
                }
                
                $updateData['voto_autista'] = $data['voto'];
                
                if (isset($data['feedback']) && !empty($data['feedback'])) {
                    $updateData['feedback_autista'] = $data['feedback'];
                }
                
            } else if (isset($user['tipo']) && $user['tipo'] == 'autista' && $user['id'] == $booking['id_autista']) {
                // Driver submitting feedback for passenger
                if (!isset($data['voto']) || !is_numeric($data['voto']) || $data['voto'] < 1 || $data['voto'] > 5) {
                    sendError('Rating must be a number between 1 and 5', 400);
                    return;
                }
                
                $updateData['voto_passeggero'] = $data['voto'];
                
                if (isset($data['feedback']) && !empty($data['feedback'])) {
                    $updateData['feedback_passeggero'] = $data['feedback'];
                }
                
            } else {
                sendError('You are not authorized to submit feedback for this booking', 403);
                return;
            }
            
            if (empty($updateData)) {
                sendError('No valid feedback data provided', 400);
                return;
            }
            
            // Update the booking with feedback
            $success = $this->model->update($bookingId, $updateData);
            
            if (!$success) {
                sendError('Failed to submit feedback', 500);
                return;
            }
            
            sendSuccess(null, 'Feedback submitted successfully');
        } catch (Exception $e) {
            sendError('Failed to submit feedback: ' . $e->getMessage(), 500);
        }
    }
    
    // Override create to check authorization
    public function create($data) {
        try {
            global $user;
            
            // Ensure only passengers can create bookings
            if (!isset($user['tipo']) || $user['tipo'] != 'passeggero') {
                sendError('Only passengers can create bookings', 403);
                return;
            }
            
            // Ensure passenger can only create bookings for themselves
            if ($user['id'] != $data['passeggero_id']) {
                sendError('You can only create bookings for yourself', 403);
                return;
            }
            
            $bookingId = $this->model->create($data);
            
            // After successful booking creation, send email notification to driver
            if ($bookingId) {
                // Get the booking details including trip and passenger info
                require_once 'api/utils/EmailService.php';
                require_once 'api/models/Viaggio.php';
                require_once 'api/models/Autista.php';
                require_once 'api/models/Passeggero.php';
                
                $booking = $this->model->getById($bookingId);
                
                // Get driver email
                $autistaModel = new Autista($this->conn);
                $driver = $autistaModel->getById($booking['id_autista']);
                
                // Get passenger info
                $passeggeroModel = new Passeggero($this->conn);
                $passenger = $passeggeroModel->getById($booking['id_passeggero']);
                
                // Get trip details
                $viaggioModel = new Viaggio($this->conn);
                $trip = $viaggioModel->getById($booking['id_viaggio']);
                
                // Send email notification
                $emailService = new EmailService();
                $emailService->sendBookingNotification(
                    $driver['email'],
                    $driver['nome'] . ' ' . $driver['cognome'],
                    $passenger,
                    array_merge($trip, ['n_posti' => $booking['n_posti']])
                );
            }
            
            sendSuccess(['id' => $bookingId]);
        } catch (Exception $e) {
            sendError('Failed to create booking: ' . $e->getMessage(), 500);
        }
    }
    
    // Override update to check authorization
    public function update($id, $data) {
        try {
            global $user;
            
            // Get the booking to check ownership
            $booking = $this->model->getById($id);
            
            if (!$booking) {
                sendError('Booking not found', 404);
                return;
            }
            
            // Add user info to the data for authorization checks in the model
            if (isset($user['tipo']) && $user['tipo'] == 'autista') {
                $data['autista_id'] = $user['id'];
            }
            
            // Check authorization - passenger can only update their own booking
            if (isset($user['tipo'])) {
                if ($user['tipo'] == 'passeggero' && $user['id'] == $booking['passeggero_id']) {
                    // Passenger can only update their notes or cancel
                    $allowedFields = ['note_passeggero'];
                    if (isset($data['stato']) && $data['stato'] == 'annullata') {
                        $allowedFields[] = 'stato';
                    }
                    
                    foreach (array_keys($data) as $field) {
                        if (!in_array($field, $allowedFields)) {
                            unset($data[$field]);
                        }
                    }
                } else if ($user['tipo'] == 'autista' && $user['id'] == $booking['autista_id']) {
                    // Driver can update status and their notes
                    $allowedFields = ['stato', 'note_autista'];
                    
                    foreach (array_keys($data) as $field) {
                        if (!in_array($field, $allowedFields)) {
                            unset($data[$field]);
                        }
                    }
                } else {
                    sendError('You are not authorized to update this booking', 403);
                    return;
                }
            }
            
            parent::update($id, $data);
        } catch (Exception $e) {
            sendError('Failed to update booking: ' . $e->getMessage(), 500);
        }
    }
    
    // Override delete to check authorization
    public function delete($id) {
        try {
            global $user;
            
            // Get the booking to check ownership
            $booking = $this->model->getById($id);
            
            if (!$booking) {
                sendError('Booking not found', 404);
                return;
            }
            
            // Only the booking passenger can cancel their booking
            if (!isset($user['tipo']) || $user['tipo'] != 'passeggero' || $user['id'] != $booking['passeggero_id']) {
                sendError('You are not authorized to cancel this booking', 403);
                return;
            }
            
            parent::delete($id);
        } catch (Exception $e) {
            sendError('Failed to delete booking: ' . $e->getMessage(), 500);
        }
    }
}