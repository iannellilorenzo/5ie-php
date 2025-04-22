<?php
class Prenotazione {
    private $conn;
    private $table = "prenotazioni";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll($filters = []) {
        try {
            $query = "SELECT 
                        p.id, p.viaggio_id, p.passeggero_id, p.stato, p.n_posti, p.data_prenotazione,
                        pa.nome as nome_passeggero, pa.cognome as cognome_passeggero,
                        v.partenza, v.destinazione, v.data_partenza, v.ora_partenza, v.prezzo
                    FROM " . $this->table . " p
                    JOIN passeggeri pa ON p.passeggero_id = pa.id
                    JOIN viaggi v ON p.viaggio_id = v.id";
            
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['viaggio_id'])) {
                $whereConditions[] = "p.viaggio_id = ?";
                $params[] = $filters['viaggio_id'];
            }
            
            if (isset($filters['passeggero_id'])) {
                $whereConditions[] = "p.passeggero_id = ?";
                $params[] = $filters['passeggero_id'];
            }
            
            if (isset($filters['stato'])) {
                $whereConditions[] = "p.stato = ?";
                $params[] = $filters['stato'];
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $query .= " ORDER BY p.data_prenotazione DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function getById($id) {
        try {
            $query = "SELECT 
                        p.id, p.viaggio_id, p.passeggero_id, p.stato, p.n_posti, 
                        p.data_prenotazione, p.note_passeggero, p.note_autista,
                        pa.nome as nome_passeggero, pa.cognome as cognome_passeggero, 
                        pa.email as email_passeggero, pa.telefono as telefono_passeggero,
                        v.partenza, v.destinazione, v.data_partenza, v.ora_partenza, 
                        v.prezzo, v.autista_id,
                        a.nome as nome_autista, a.cognome as cognome_autista,
                        a.email as email_autista, a.telefono as telefono_autista,
                        au.marca, au.modello
                    FROM " . $this->table . " p
                    JOIN passeggeri pa ON p.passeggero_id = pa.id
                    JOIN viaggi v ON p.viaggio_id = v.id
                    JOIN autisti a ON v.autista_id = a.id
                    LEFT JOIN automobili au ON v.auto_id = au.id
                    WHERE p.id = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            $requiredFields = ['viaggio_id', 'passeggero_id', 'n_posti'];
            validateRequired($data, $requiredFields);
            
            // Check if passenger exists
            $checkPassengerQuery = "SELECT COUNT(*) FROM passeggeri WHERE id = ?";
            $checkPassengerStmt = $this->conn->prepare($checkPassengerQuery);
            $checkPassengerStmt->execute([$data['passeggero_id']]);
            
            if ($checkPassengerStmt->fetchColumn() == 0) {
                throw new Exception("Passenger not found");
            }
            
            // Check if trip exists and has enough seats
            $checkTripQuery = "SELECT autista_id, posti_disponibili, stato FROM viaggi WHERE id = ?";
            $checkTripStmt = $this->conn->prepare($checkTripQuery);
            $checkTripStmt->execute([$data['viaggio_id']]);
            
            $trip = $checkTripStmt->fetch();
            if (!$trip) {
                throw new Exception("Trip not found");
            }
            
            if ($trip['stato'] != 'attivo') {
                throw new Exception("This trip is not active");
            }
            
            // Check if passenger is not the driver
            $checkDriverQuery = "SELECT COUNT(*) FROM autisti WHERE id = ? AND id = ?";
            $checkDriverStmt = $this->conn->prepare($checkDriverQuery);
            $checkDriverStmt->execute([$data['passeggero_id'], $trip['autista_id']]);
            
            if ($checkDriverStmt->fetchColumn() > 0) {
                throw new Exception("Driver cannot book their own trip");
            }
            
            // Check if there are enough seats
            $bookedSeatsQuery = "SELECT COALESCE(SUM(n_posti), 0) as booked_seats FROM prenotazioni
                                WHERE viaggio_id = ? AND stato != 'annullata'";
            $bookedSeatsStmt = $this->conn->prepare($bookedSeatsQuery);
            $bookedSeatsStmt->execute([$data['viaggio_id']]);
            $bookedSeats = $bookedSeatsStmt->fetch()['booked_seats'];
            
            if ($bookedSeats + $data['n_posti'] > $trip['posti_disponibili']) {
                throw new Exception("Not enough available seats");
            }
            
            // Check if passenger already has a booking for this trip
            $existingBookingQuery = "SELECT COUNT(*) FROM prenotazioni
                                    WHERE viaggio_id = ? AND passeggero_id = ? AND stato != 'annullata'";
            $existingBookingStmt = $this->conn->prepare($existingBookingQuery);
            $existingBookingStmt->execute([$data['viaggio_id'], $data['passeggero_id']]);
            
            if ($existingBookingStmt->fetchColumn() > 0) {
                throw new Exception("You already have a booking for this trip");
            }
            
            $query = "INSERT INTO " . $this->table . " 
                      (viaggio_id, passeggero_id, stato, n_posti, note_passeggero)
                      VALUES (?, ?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['viaggio_id'],
                $data['passeggero_id'],
                'in_attesa', // Default status
                $data['n_posti'],
                $data['note_passeggero'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $checkQuery = "SELECT 
                            p.stato, p.viaggio_id, p.passeggero_id, p.n_posti,
                            v.autista_id, v.posti_disponibili
                          FROM " . $this->table . " p
                          JOIN viaggi v ON p.viaggio_id = v.id
                          WHERE p.id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            $booking = $checkStmt->fetch();
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            $fields = [];
            $params = [];
            
            // Cannot change viaggio_id or passeggero_id
            
            if (isset($data['stato'])) {
                // Only driver can confirm/reject booking
                if (($data['stato'] == 'confermata' || $data['stato'] == 'rifiutata') && 
                    !isset($data['autista_id'])) {
                    throw new Exception("Only the driver can confirm or reject bookings");
                }
                
                // If status is being changed to confirmed, check if there are enough seats
                if ($data['stato'] == 'confermata' && $booking['stato'] != 'confermata') {
                    // Check available seats
                    $bookedSeatsQuery = "SELECT COALESCE(SUM(n_posti), 0) as booked_seats FROM prenotazioni
                                        WHERE viaggio_id = ? AND stato = 'confermata' AND id != ?";
                    $bookedSeatsStmt = $this->conn->prepare($bookedSeatsQuery);
                    $bookedSeatsStmt->execute([$booking['viaggio_id'], $id]);
                    $bookedSeats = $bookedSeatsStmt->fetch()['booked_seats'];
                    
                    if ($bookedSeats + $booking['n_posti'] > $booking['posti_disponibili']) {
                        throw new Exception("Not enough available seats to confirm this booking");
                    }
                }
                
                $fields[] = "stato = ?";
                $params[] = $data['stato'];
            }
            
            if (isset($data['n_posti'])) {
                // Check if new number of seats is available
                if ($booking['stato'] == 'confermata' || $data['stato'] == 'confermata') {
                    $bookedSeatsQuery = "SELECT COALESCE(SUM(n_posti), 0) as booked_seats FROM prenotazioni
                                        WHERE viaggio_id = ? AND stato = 'confermata' AND id != ?";
                    $bookedSeatsStmt = $this->conn->prepare($bookedSeatsQuery);
                    $bookedSeatsStmt->execute([$booking['viaggio_id'], $id]);
                    $bookedSeats = $bookedSeatsStmt->fetch()['booked_seats'];
                    
                    if ($bookedSeats + $data['n_posti'] > $booking['posti_disponibili']) {
                        throw new Exception("Not enough available seats");
                    }
                }
                
                $fields[] = "n_posti = ?";
                $params[] = $data['n_posti'];
            }
            
            if (isset($data['note_passeggero'])) {
                $fields[] = "note_passeggero = ?";
                $params[] = $data['note_passeggero'];
            }
            
            if (isset($data['note_autista'])) {
                // Only driver can add notes
                if (!isset($data['autista_id'])) {
                    throw new Exception("Only the driver can add driver notes");
                }
                
                $fields[] = "note_autista = ?";
                $params[] = $data['note_autista'];
            }
            
            $params[] = $id;
            
            $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function delete($id) {
        try {
            $checkQuery = "SELECT stato, data_prenotazione FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            $booking = $checkStmt->fetch();
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            // For confirmed bookings, don't allow deletion within 24 hours of booking
            if ($booking['stato'] == 'confermata') {
                $bookingTime = strtotime($booking['data_prenotazione']);
                $currentTime = time();
                $timeDiff = ($currentTime - $bookingTime) / 3600; // hours
                
                if ($timeDiff > 24) {
                    throw new Exception("Cannot cancel confirmed bookings after 24 hours");
                }
            }
            
            // Instead of deleting, change status to canceled
            $query = "UPDATE " . $this->table . " SET stato = 'annullata' WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getBookingsByPassengerId($passengerId) {
        try {
            $query = "SELECT 
                        p.id_prenotazione as id, p.id_viaggio, p.stato,
                        v.citta_partenza as partenza, v.citta_destinazione as destinazione, 
                        v.timestamp_partenza as data_partenza,
                        a.nome as nome_autista, a.cognome as cognome_autista,
                        v.prezzo_cadauno as prezzo
                    FROM " . $this->table . " p
                    JOIN viaggi v ON p.id_viaggio = v.id_viaggio
                    JOIN autisti a ON v.id_autista = a.id_autista
                    WHERE p.id_passeggero = ?
                    ORDER BY v.timestamp_partenza";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$passengerId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}