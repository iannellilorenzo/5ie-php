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
            
            $conditions = [];
            $params = [];
            
            if (!empty($filters)) {
                foreach ($filters as $key => $value) {
                    // Handle special case for viaggio_ids - array of trip IDs
                    if ($key === 'viaggio_ids' && is_array($value) && !empty($value)) {
                        $placeholders = implode(',', array_fill(0, count($value), '?'));
                        $conditions[] = "p.id_viaggio IN ($placeholders)";
                        $params = array_merge($params, $value);
                        continue;
                    }
                    
                    // Skip filters with special handling or non-existent columns
                    if ($key === 'stato') continue;
                    
                    $conditions[] = "p." . $key . " = ?";
                    $params[] = $value;
                }
                
                if (!empty($conditions)) {
                    $query .= " WHERE " . implode(" AND ", $conditions);
                }
            }
            
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
                        v.citta_partenza, v.citta_destinazione, v.timestamp_partenza, 
                        v.prezzo_cadauno, v.tempo_stimato, v.soste, v.bagaglio, v.animali,
                        v.id_autista, v.stato as stato, a.nome as nome_autista,
                        a.cognome as cognome_autista, a.fotografia as foto_autista,
                        au.targa, au.marca, au.modello
                    FROM prenotazioni p
                    JOIN passeggeri pa ON p.id_passeggero = pa.id_passeggero
                    JOIN viaggi v ON p.id_viaggio = v.id_viaggio
                    JOIN autisti a ON v.id_autista = a.id_autista
                    LEFT JOIN automobili au ON a.id_autista = au.id_autista
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
            if (!isset($data['id_viaggio']) || !isset($data['id_passeggero'])) {
                throw new Exception("Required data missing");
            }
            
            // Simplify to match the actual database schema
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