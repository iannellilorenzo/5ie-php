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
                        p.id_prenotazione, p.id_viaggio, p.id_passeggero, 
                        p.voto_autista, p.voto_passeggero,
                        p.feedback_autista, p.feedback_passeggero,
                        pa.nome as nome_passeggero, pa.cognome as cognome_passeggero,
                        v.citta_partenza, v.citta_destinazione, v.timestamp_partenza, 
                        v.prezzo_cadauno
                    FROM " . $this->table . " p
                    JOIN passeggeri pa ON p.id_passeggero = pa.id_passeggero
                    JOIN viaggi v ON p.id_viaggio = v.id_viaggio";
            
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['id_viaggio'])) {
                $whereConditions[] = "p.id_viaggio = ?";
                $params[] = $filters['id_viaggio'];
            }
            
            if (isset($filters['id_passeggero'])) {
                $whereConditions[] = "p.id_passeggero = ?";
                $params[] = $filters['id_passeggero'];
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $query .= " ORDER BY v.timestamp_partenza DESC";
            
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
                        p.id_prenotazione, p.id_viaggio, p.id_passeggero,
                        p.voto_autista, p.voto_passeggero,
                        p.feedback_autista, p.feedback_passeggero,
                        pa.nome as nome_passeggero, pa.cognome as cognome_passeggero, 
                        pa.email as email_passeggero, pa.telefono as telefono_passeggero,
                        v.citta_partenza, v.citta_destinazione, v.timestamp_partenza,
                        v.prezzo_cadauno, v.id_autista,
                        a.nome as nome_autista, a.cognome as cognome_autista,
                        a.email as email_autista, a.numero_telefono as telefono_autista,
                        au.marca, au.modello
                    FROM " . $this->table . " p
                    JOIN passeggeri pa ON p.id_passeggero = pa.id_passeggero
                    JOIN viaggi v ON p.id_viaggio = v.id_viaggio
                    JOIN autisti a ON v.id_autista = a.id_autista
                    LEFT JOIN automobili au ON au.id_autista = v.id_autista
                    WHERE p.id_prenotazione = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            $requiredFields = ['id_viaggio', 'id_passeggero'];
            validateRequired($data, $requiredFields);
            
            // Check if passenger exists
            $checkPassengerQuery = "SELECT COUNT(*) FROM passeggeri WHERE id_passeggero = ?";
            $checkPassengerStmt = $this->conn->prepare($checkPassengerQuery);
            $checkPassengerStmt->execute([$data['id_passeggero']]);
            
            if ($checkPassengerStmt->fetchColumn() == 0) {
                throw new Exception("Passenger not found");
            }
            
            // Check if trip exists
            $checkTripQuery = "SELECT id_autista FROM viaggi WHERE id_viaggio = ?";
            $checkTripStmt = $this->conn->prepare($checkTripQuery);
            $checkTripStmt->execute([$data['id_viaggio']]);
            
            $trip = $checkTripStmt->fetch();
            if (!$trip) {
                throw new Exception("Trip not found");
            }
            
            // Check if passenger is not the driver
            $checkDriverQuery = "SELECT COUNT(*) FROM autisti WHERE id_autista = ? AND id_autista = ?";
            $checkDriverStmt = $this->conn->prepare($checkDriverQuery);
            $checkDriverStmt->execute([$data['id_passeggero'], $trip['id_autista']]);
            
            if ($checkDriverStmt->fetchColumn() > 0) {
                throw new Exception("Driver cannot book their own trip");
            }
            
            // Check if passenger already has a booking for this trip
            $existingBookingQuery = "SELECT COUNT(*) FROM prenotazioni
                                    WHERE id_viaggio = ? AND id_passeggero = ?";
            $existingBookingStmt = $this->conn->prepare($existingBookingQuery);
            $existingBookingStmt->execute([$data['id_viaggio'], $data['id_passeggero']]);
            
            if ($existingBookingStmt->fetchColumn() > 0) {
                throw new Exception("You already have a booking for this trip");
            }
            
            $query = "INSERT INTO " . $this->table . " 
                      (id_viaggio, id_passeggero)
                      VALUES (?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['id_viaggio'],
                $data['id_passeggero']
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $checkQuery = "SELECT 
                            p.id_viaggio, p.id_passeggero,
                            v.id_autista
                          FROM " . $this->table . " p
                          JOIN viaggi v ON p.id_viaggio = v.id_viaggio
                          WHERE p.id_prenotazione = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            $booking = $checkStmt->fetch();
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            $fields = [];
            $params = [];
            
            // Cannot change id_viaggio or id_passeggero
            
            if (isset($data['voto_autista'])) {
                // Only passengers can rate drivers
                if ($booking['id_autista'] == $data['user_id']) {
                    throw new Exception("Drivers cannot rate themselves");
                }
                
                $fields[] = "voto_autista = ?";
                $params[] = $data['voto_autista'];
            }
            
            if (isset($data['voto_passeggero'])) {
                // Only drivers can rate passengers
                if ($booking['id_autista'] != $data['user_id']) {
                    throw new Exception("Only the driver can rate passengers");
                }
                
                $fields[] = "voto_passeggero = ?";
                $params[] = $data['voto_passeggero'];
            }
            
            if (isset($data['feedback_autista'])) {
                // Only passengers can leave feedback for drivers
                if ($booking['id_autista'] == $data['user_id']) {
                    throw new Exception("Drivers cannot leave feedback for themselves");
                }
                
                $fields[] = "feedback_autista = ?";
                $params[] = $data['feedback_autista'];
            }
            
            if (isset($data['feedback_passeggero'])) {
                // Only drivers can leave feedback for passengers
                if ($booking['id_autista'] != $data['user_id']) {
                    throw new Exception("Only the driver can leave feedback for passengers");
                }
                
                $fields[] = "feedback_passeggero = ?";
                $params[] = $data['feedback_passeggero'];
            }
            
            if (empty($fields)) {
                return true; // Nothing to update
            }
            
            $params[] = $id;
            
            $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id_prenotazione = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function delete($id) {
        try {
            $checkQuery = "SELECT id_prenotazione FROM " . $this->table . " WHERE id_prenotazione = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() == 0) {
                throw new Exception("Booking not found");
            }
            
            $query = "DELETE FROM " . $this->table . " WHERE id_prenotazione = ?";
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
                        p.id_prenotazione, p.id_viaggio, 
                        p.voto_autista, p.voto_passeggero,
                        v.citta_partenza, v.citta_destinazione, 
                        v.timestamp_partenza,
                        a.nome as nome_autista, a.cognome as cognome_autista,
                        v.prezzo_cadauno
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