<?php
class Viaggio {
    private $conn;
    private $table = "viaggi";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll($filters = []) {
        try {
            $query = "SELECT 
                        v.id_viaggio, v.citta_partenza, v.citta_destinazione, v.timestamp_partenza, 
                        v.prezzo_cadauno, v.tempo_stimato, v.soste, v.bagaglio, v.animali,
                        v.id_autista, a.nome as nome_autista, a.cognome as cognome_autista
                    FROM " . $this->table . " v
                    JOIN autisti a ON v.id_autista = a.id_autista";
            
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['citta_partenza'])) {
                $whereConditions[] = "v.citta_partenza LIKE ?";
                $params[] = "%" . $filters['citta_partenza'] . "%";
            }
            
            if (isset($filters['citta_destinazione'])) {
                $whereConditions[] = "v.citta_destinazione LIKE ?";
                $params[] = "%" . $filters['citta_destinazione'] . "%";
            }
            
            if (isset($filters['data'])) {
                $whereConditions[] = "DATE(v.timestamp_partenza) = ?";
                $params[] = $filters['data'];
            }
            
            if (isset($filters['id_autista'])) {
                $whereConditions[] = "v.id_autista = ?";
                $params[] = $filters['id_autista'];
            }
            
            // Add other filters as needed
            if (isset($filters['prezzo_max'])) {
                $whereConditions[] = "v.prezzo_cadauno <= ?";
                $params[] = $filters['prezzo_max'];
            }
            
            if (isset($filters['soste'])) {
                $whereConditions[] = "v.soste = ?";
                $params[] = $filters['soste'] ? 1 : 0;
            }
            
            if (isset($filters['bagaglio'])) {
                $whereConditions[] = "v.bagaglio = ?";
                $params[] = $filters['bagaglio'] ? 1 : 0;
            }
            
            if (isset($filters['animali'])) {
                $whereConditions[] = "v.animali = ?";
                $params[] = $filters['animali'] ? 1 : 0;
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $query .= " ORDER BY v.timestamp_partenza";
            
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
                        v.id_viaggio, v.citta_partenza, v.citta_destinazione, v.timestamp_partenza, 
                        v.prezzo_cadauno, v.tempo_stimato, v.soste, v.bagaglio, v.animali, 
                        v.stato, v.id_autista, a.nome as nome_autista, a.cognome as cognome_autista,
                        a.numero_telefono as telefono_autista, a.fotografia as foto_autista
                    FROM " . $this->table . " v
                    JOIN autisti a ON v.id_autista = a.id_autista
                    WHERE v.id_viaggio = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $trip = $stmt->fetch();
            
            // Set default value for stato if not present
            if ($trip && !isset($trip['stato'])) {
                $trip['stato'] = 'attivo';
            }
            
            return $trip;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get bookings for a specific trip
     * 
     * @param int $tripId The trip ID
     * @return array Array of booking records
     */
    public function getTripBookings($tripId) {
        try {
            $query = "SELECT 
                        p.id_prenotazione, p.id_passeggero, p.stato, p.numero_posti, 
                        p.timestamp_prenotazione,
                        pa.nome, pa.cognome
                      FROM prenotazioni p
                      JOIN passeggeri pa ON p.id_passeggero = pa.id_passeggero
                      WHERE p.id_viaggio = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$tripId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get trip with bookings included
     * 
     * @param int $id The trip ID
     * @return array|null Trip with bookings or null if not found
     */
    public function getTripWithBookings($id) {
        try {
            $trip = $this->getById($id);
            
            if (!$trip) {
                return null;
            }
            
            $trip['prenotazioni'] = $this->getTripBookings($id);
            
            return $trip;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function getByDriverId($driverId) {
        try {
            $query = "SELECT 
                        v.id_viaggio, v.citta_partenza, v.citta_destinazione, v.timestamp_partenza, 
                        v.prezzo_cadauno, v.tempo_stimato, v.soste, v.bagaglio, v.animali,
                        v.stato, v.id_autista,
                        a.marca, a.modello
                    FROM " . $this->table . " v
                    LEFT JOIN automobili a ON a.id_autista = v.id_autista
                    WHERE v.id_autista = ?
                    ORDER BY v.timestamp_partenza DESC";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$driverId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            include_once __DIR__ . '/../utils/validation.php';
            
            $requiredFields = ['id_autista', 'citta_partenza', 'citta_destinazione', 'timestamp_partenza', 
                              'prezzo_cadauno'];
            validateRequired($data, $requiredFields);
            
            // Check if driver exists
            $checkDriverQuery = "SELECT COUNT(*) FROM autisti WHERE id_autista = ?";
            $checkDriverStmt = $this->conn->prepare($checkDriverQuery);
            $checkDriverStmt->execute([$data['id_autista']]);
            
            if ($checkDriverStmt->fetchColumn() == 0) {
                throw new Exception("Driver not found");
            }
            
            $query = "INSERT INTO " . $this->table . " 
                      (id_autista, citta_partenza, citta_destinazione, timestamp_partenza, 
                       prezzo_cadauno, tempo_stimato, soste, bagaglio, animali)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['id_autista'],
                $data['citta_partenza'],
                $data['citta_destinazione'],
                $data['timestamp_partenza'],
                $data['prezzo_cadauno'],
                $data['tempo_stimato'] ?? NULL,
                $data['soste'] ?? FALSE,
                $data['bagaglio'] ?? FALSE,
                $data['animali'] ?? FALSE
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $checkQuery = "SELECT id_autista FROM " . $this->table . " WHERE id_viaggio = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            $trip = $checkStmt->fetch();
            if (!$trip) {
                throw new Exception("Trip not found");
            }
            
            $fields = [];
            $params = [];
            
            // Can't change id_autista
            
            if (isset($data['citta_partenza'])) {
                $fields[] = "citta_partenza = ?";
                $params[] = $data['citta_partenza'];
            }
            
            if (isset($data['citta_destinazione'])) {
                $fields[] = "citta_destinazione = ?";
                $params[] = $data['citta_destinazione'];
            }
            
            if (isset($data['timestamp_partenza'])) {
                $fields[] = "timestamp_partenza = ?";
                $params[] = $data['timestamp_partenza'];
            }
            
            if (isset($data['prezzo_cadauno'])) {
                $fields[] = "prezzo_cadauno = ?";
                $params[] = $data['prezzo_cadauno'];
            }
            
            if (isset($data['tempo_stimato'])) {
                $fields[] = "tempo_stimato = ?";
                $params[] = $data['tempo_stimato'];
            }
            
            if (isset($data['soste'])) {
                $fields[] = "soste = ?";
                $params[] = $data['soste'] ? 1 : 0;
            }
            
            if (isset($data['bagaglio'])) {
                $fields[] = "bagaglio = ?";
                $params[] = $data['bagaglio'] ? 1 : 0;
            }
            
            if (isset($data['animali'])) {
                $fields[] = "animali = ?";
                $params[] = $data['animali'] ? 1 : 0;
            }
            
            if (isset($data['stato'])) {
                $fields[] = "stato = ?";
                $params[] = $data['stato'];
            }
            
            if (empty($fields)) {
                return true; // Nothing to update
            }
            
            $params[] = $id;
            
            $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id_viaggio = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function delete($id) {
        try {
            $checkQuery = "SELECT id_viaggio FROM " . $this->table . " WHERE id_viaggio = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() == 0) {
                throw new Exception("Trip not found");
            }
            
            // DELETE all associated bookings first (not needed with ON DELETE CASCADE, but kept for clarity)
            // $deleteBookingsQuery = "DELETE FROM prenotazioni WHERE id_viaggio = ?";
            // $deleteBookingsStmt = $this->conn->prepare($deleteBookingsQuery);
            // $deleteBookingsStmt->execute([$id]);
            
            // Then delete the trip
            $query = "DELETE FROM " . $this->table . " WHERE id_viaggio = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Search for trips based on criteria
     * 
     * @param array $filters Associative array of search filters
     * @return array Found trips
     */
    public function search($filters = []) {
        try {
            $conditions = [];
            $params = [];
            
            $query = "SELECT 
                        v.id_viaggio, v.citta_partenza, v.citta_destinazione, v.timestamp_partenza, 
                        v.prezzo_cadauno, v.tempo_stimato, v.soste, v.bagaglio, v.animali, v.stato,
                        v.id_autista, a.marca, a.modello 
                    FROM " . $this->table . " v 
                    LEFT JOIN automobili a ON a.id_autista = v.id_autista
                    WHERE v.stato = 'attivo'";
            
            // Apply filters
            if (isset($filters['citta_partenza']) && !empty($filters['citta_partenza'])) {
                $conditions[] = "v.citta_partenza LIKE ?";
                $params[] = '%' . $filters['citta_partenza'] . '%';
            }
            
            if (isset($filters['citta_destinazione']) && !empty($filters['citta_destinazione'])) {
                $conditions[] = "v.citta_destinazione LIKE ?";
                $params[] = '%' . $filters['citta_destinazione'] . '%';
            }
            
            if (isset($filters['data_partenza']) && !empty($filters['data_partenza'])) {
                $conditions[] = "DATE(v.timestamp_partenza) = ?";
                $params[] = $filters['data_partenza'];
            }
            
            if (isset($filters['prezzo_min']) && !empty($filters['prezzo_min'])) {
                $conditions[] = "v.prezzo_cadauno >= ?";
                $params[] = $filters['prezzo_min'];
            }
            
            if (isset($filters['prezzo_max']) && !empty($filters['prezzo_max'])) {
                $conditions[] = "v.prezzo_cadauno <= ?";
                $params[] = $filters['prezzo_max'];
            }
            
            // Apply time of day filter
            if (isset($filters['orario_partenza']) && !empty($filters['orario_partenza'])) {
                switch ($filters['orario_partenza']) {
                    case 'morning':
                        $conditions[] = "TIME(v.timestamp_partenza) BETWEEN '06:00:00' AND '12:00:00'";
                        break;
                    case 'afternoon':
                        $conditions[] = "TIME(v.timestamp_partenza) BETWEEN '12:00:00' AND '18:00:00'";
                        break;
                    case 'evening':
                        $conditions[] = "TIME(v.timestamp_partenza) BETWEEN '18:00:00' AND '24:00:00'";
                        break;
                }
            }
            
            // Apply feature filters
            if (isset($filters['soste']) && $filters['soste'] == 1) {
                $conditions[] = "v.soste = 1";
            }
            
            if (isset($filters['bagaglio']) && $filters['bagaglio'] == 1) {
                $conditions[] = "v.bagaglio = 1";
            }
            
            if (isset($filters['animali']) && $filters['animali'] == 1) {
                $conditions[] = "v.animali = 1";
            }
            
            // Only future trips
            $conditions[] = "v.timestamp_partenza > NOW()";
            
            // Add conditions to query
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }
            
            // Order by departure time
            $query .= " ORDER BY v.timestamp_partenza ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}