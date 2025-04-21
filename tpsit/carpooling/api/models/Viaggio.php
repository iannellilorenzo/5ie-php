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
                        v.id, v.autista_id, v.auto_id, v.partenza, v.destinazione, 
                        v.data_partenza, v.ora_partenza, v.durata_stimata, v.prezzo, 
                        v.posti_disponibili, v.note, v.stato,
                        a.nome as nome_autista, a.cognome as cognome_autista, a.valutazione as valutazione_autista,
                        au.marca, au.modello, au.colore
                    FROM " . $this->table . " v
                    JOIN autisti a ON v.autista_id = a.id
                    LEFT JOIN automobili au ON v.auto_id = au.id";
            
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['partenza'])) {
                $whereConditions[] = "v.partenza LIKE ?";
                $params[] = "%" . $filters['partenza'] . "%";
            }
            
            if (isset($filters['destinazione'])) {
                $whereConditions[] = "v.destinazione LIKE ?";
                $params[] = "%" . $filters['destinazione'] . "%";
            }
            
            if (isset($filters['data_partenza'])) {
                $whereConditions[] = "v.data_partenza = ?";
                $params[] = $filters['data_partenza'];
            }
            
            if (isset($filters['autista_id'])) {
                $whereConditions[] = "v.autista_id = ?";
                $params[] = $filters['autista_id'];
            }
            
            if (isset($filters['posti'])) {
                $whereConditions[] = "v.posti_disponibili >= ?";
                $params[] = $filters['posti'];
            }
            
            if (isset($filters['prezzo_max'])) {
                $whereConditions[] = "v.prezzo <= ?";
                $params[] = $filters['prezzo_max'];
            }
            
            if (isset($filters['stato'])) {
                $whereConditions[] = "v.stato = ?";
                $params[] = $filters['stato'];
            } else {
                // By default, only show active trips
                $whereConditions[] = "v.stato = 'attivo'";
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $query .= " ORDER BY v.data_partenza, v.ora_partenza";
            
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
                        v.id, v.autista_id, v.auto_id, v.partenza, v.destinazione, 
                        v.data_partenza, v.ora_partenza, v.durata_stimata, v.prezzo, 
                        v.posti_disponibili, v.note, v.stato,
                        a.nome as nome_autista, a.cognome as cognome_autista, a.valutazione as valutazione_autista,
                        a.foto_profilo as foto_autista, a.telefono as telefono_autista,
                        au.marca, au.modello, au.colore, au.n_posti
                    FROM " . $this->table . " v
                    JOIN autisti a ON v.autista_id = a.id
                    LEFT JOIN automobili au ON v.auto_id = au.id
                    WHERE v.id = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $trip = $stmt->fetch();
            
            if (!$trip) {
                return null;
            }
            
            // Get trip bookings
            $bookingsQuery = "SELECT 
                                p.id, p.passeggero_id, p.stato, p.n_posti, p.data_prenotazione,
                                pa.nome, pa.cognome, pa.foto_profilo
                              FROM prenotazioni p
                              JOIN passeggeri pa ON p.passeggero_id = pa.id
                              WHERE p.viaggio_id = ?";
            $bookingsStmt = $this->conn->prepare($bookingsQuery);
            $bookingsStmt->execute([$id]);
            $bookings = $bookingsStmt->fetchAll();
            
            $trip['prenotazioni'] = $bookings;
            
            return $trip;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function getByDriverId($driverId) {
        try {
            $query = "SELECT 
                        v.id, v.partenza, v.destinazione, v.data_partenza, 
                        v.ora_partenza, v.prezzo, v.posti_disponibili, 
                        v.stato, au.marca, au.modello
                    FROM " . $this->table . " v
                    LEFT JOIN automobili au ON v.auto_id = au.id
                    WHERE v.autista_id = ?
                    ORDER BY v.data_partenza DESC";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$driverId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            $requiredFields = ['autista_id', 'auto_id', 'partenza', 'destinazione', 
                              'data_partenza', 'ora_partenza', 'prezzo', 'posti_disponibili'];
            validateRequired($data, $requiredFields);
            
            // Check if driver exists
            $checkDriverQuery = "SELECT COUNT(*) FROM autisti WHERE id = ?";
            $checkDriverStmt = $this->conn->prepare($checkDriverQuery);
            $checkDriverStmt->execute([$data['autista_id']]);
            
            if ($checkDriverStmt->fetchColumn() == 0) {
                throw new Exception("Driver not found");
            }
            
            // Check if car exists and belongs to the driver
            $checkCarQuery = "SELECT autista_id, n_posti FROM automobili WHERE id = ?";
            $checkCarStmt = $this->conn->prepare($checkCarQuery);
            $checkCarStmt->execute([$data['auto_id']]);
            
            $car = $checkCarStmt->fetch();
            if (!$car) {
                throw new Exception("Car not found");
            }
            
            if ($car['autista_id'] != $data['autista_id']) {
                throw new Exception("This car doesn't belong to the specified driver");
            }
            
            // Check if seats are valid
            if ($data['posti_disponibili'] > $car['n_posti']) {
                throw new Exception("Cannot offer more seats than the car has");
            }
            
            $query = "INSERT INTO " . $this->table . " 
                      (autista_id, auto_id, partenza, destinazione, data_partenza, 
                       ora_partenza, durata_stimata, prezzo, posti_disponibili, note, stato)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['autista_id'],
                $data['auto_id'],
                $data['partenza'],
                $data['destinazione'],
                $data['data_partenza'],
                $data['ora_partenza'],
                $data['durata_stimata'] ?? null,
                $data['prezzo'],
                $data['posti_disponibili'],
                $data['note'] ?? null,
                'attivo' // Default status
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $checkQuery = "SELECT autista_id, stato FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            $trip = $checkStmt->fetch();
            if (!$trip) {
                throw new Exception("Trip not found");
            }
            
            // Only allow updates if trip is active
            if ($trip['stato'] != 'attivo' && !isset($data['stato'])) {
                throw new Exception("Cannot update a trip that's not active");
            }
            
            $fields = [];
            $params = [];
            
            // Can't change autista_id
            
            if (isset($data['auto_id'])) {
                // Check if car exists and belongs to the driver
                $checkCarQuery = "SELECT autista_id FROM automobili WHERE id = ?";
                $checkCarStmt = $this->conn->prepare($checkCarQuery);
                $checkCarStmt->execute([$data['auto_id']]);
                
                $car = $checkCarStmt->fetch();
                if (!$car) {
                    throw new Exception("Car not found");
                }
                
                if ($car['autista_id'] != $trip['autista_id']) {
                    throw new Exception("This car doesn't belong to the trip's driver");
                }
                
                $fields[] = "auto_id = ?";
                $params[] = $data['auto_id'];
            }
            
            if (isset($data['partenza'])) {
                $fields[] = "partenza = ?";
                $params[] = $data['partenza'];
            }
            
            if (isset($data['destinazione'])) {
                $fields[] = "destinazione = ?";
                $params[] = $data['destinazione'];
            }
            
            if (isset($data['data_partenza'])) {
                $fields[] = "data_partenza = ?";
                $params[] = $data['data_partenza'];
            }
            
            if (isset($data['ora_partenza'])) {
                $fields[] = "ora_partenza = ?";
                $params[] = $data['ora_partenza'];
            }
            
            if (isset($data['durata_stimata'])) {
                $fields[] = "durata_stimata = ?";
                $params[] = $data['durata_stimata'];
            }
            
            if (isset($data['prezzo'])) {
                $fields[] = "prezzo = ?";
                $params[] = $data['prezzo'];
            }
            
            if (isset($data['posti_disponibili'])) {
                // Check if seats are valid
                $checkBookingsQuery = "SELECT COUNT(*) as num_bookings FROM prenotazioni WHERE viaggio_id = ? AND stato != 'annullata'";
                $checkBookingsStmt = $this->conn->prepare($checkBookingsQuery);
                $checkBookingsStmt->execute([$id]);
                $bookings = $checkBookingsStmt->fetch();
                
                if ($data['posti_disponibili'] < $bookings['num_bookings']) {
                    throw new Exception("Cannot reduce seats below number of current bookings");
                }
                
                $fields[] = "posti_disponibili = ?";
                $params[] = $data['posti_disponibili'];
            }
            
            if (isset($data['note'])) {
                $fields[] = "note = ?";
                $params[] = $data['note'];
            }
            
            if (isset($data['stato'])) {
                $fields[] = "stato = ?";
                $params[] = $data['stato'];
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
            $checkQuery = "SELECT stato FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            $trip = $checkStmt->fetch();
            if (!$trip) {
                throw new Exception("Trip not found");
            }
            
            $checkBookingsQuery = "SELECT COUNT(*) FROM prenotazioni WHERE viaggio_id = ? AND stato = 'confermata'";
            $checkBookingsStmt = $this->conn->prepare($checkBookingsQuery);
            $checkBookingsStmt->execute([$id]);
            
            if ($checkBookingsStmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete trip with confirmed bookings");
            }
            
            // First, set all pending bookings to canceled
            $cancelBookingsQuery = "UPDATE prenotazioni SET stato = 'annullata' WHERE viaggio_id = ? AND stato = 'in_attesa'";
            $cancelBookingsStmt = $this->conn->prepare($cancelBookingsQuery);
            $cancelBookingsStmt->execute([$id]);
            
            $query = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}