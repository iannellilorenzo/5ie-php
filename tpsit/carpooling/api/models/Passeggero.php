<?php
class Passeggero {
    private $conn;
    private $table = "passeggeri";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll($filters = []) {
        try {
            $query = "SELECT 
                        p.id, p.nome, p.cognome, p.email, p.telefono, 
                        p.data_nascita, p.citta, p.bio, p.foto_profilo, 
                        p.data_registrazione
                    FROM " . $this->table . " p";
                    
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['city'])) {
                $whereConditions[] = "p.citta = ?";
                $params[] = $filters['city'];
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
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
                        p.id_passeggero, p.nome, p.cognome, p.email,
                        p.documento_identita, p.telefono, p.password, p.fotografia
                    FROM " . $this->table . " p
                    WHERE p.id_passeggero = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $passenger = $stmt->fetch();
            
            if (!$passenger) {
                return null;
            }

            return $passenger;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Get passenger with average rating and reviews
     * 
     * @param int $passengerId The passenger ID
     * @return array|null Passenger info with ratings or null
     */
    public function getPassengerWithRatings($passengerId) {
        try {
            // Get the basic passenger info
            $passenger = $this->getById($passengerId);
            
            if (!$passenger) {
                return null;
            }
            
            // Get ratings through bookings
            $query = "SELECT 
                        AVG(p.voto_passeggero) as rating_avg,
                        COUNT(p.voto_passeggero) as rating_count
                      FROM prenotazioni p
                      WHERE p.id_passeggero = ? AND p.voto_passeggero IS NOT NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$passengerId]);
            
            $ratingInfo = $stmt->fetch();
            
            // Add rating info to passenger
            $passenger['rating_avg'] = $ratingInfo['rating_avg'] ? round($ratingInfo['rating_avg'], 1) : null;
            $passenger['rating_count'] = $ratingInfo['rating_count'] ? (int)$ratingInfo['rating_count'] : 0;
            
            // Get reviews (feedback)
            $query = "SELECT 
                        p.id_prenotazione, p.voto_passeggero, p.feedback_passeggero,
                        v.id_autista, a.nome as nome_autista, a.cognome as cognome_autista,
                        v.citta_partenza, v.citta_destinazione, v.timestamp_partenza
                      FROM prenotazioni p
                      JOIN viaggi v ON p.id_viaggio = v.id_viaggio
                      JOIN autisti a ON v.id_autista = a.id_autista
                      WHERE p.id_passeggero = ? AND p.feedback_passeggero IS NOT NULL
                      ORDER BY v.timestamp_partenza DESC
                      LIMIT 10";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$passengerId]);
            
            $passenger['reviews'] = $stmt->fetchAll();
            
            return $passenger;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Get passenger's bookings history
     * 
     * @param int $passengerId The passenger ID
     * @return array Passenger's booking history
     */
    public function getPassengerBookings($passengerId) {
        try {
            $query = "SELECT 
                        p.id_prenotazione, p.id_viaggio, p.stato,
                        p.voto_autista, p.feedback_autista,
                        p.voto_passeggero, p.feedback_passeggero,
                        v.citta_partenza, v.citta_destinazione, v.timestamp_partenza,
                        v.prezzo_cadauno, v.id_autista,
                        a.nome as nome_autista, a.cognome as cognome_autista
                      FROM prenotazioni p
                      JOIN viaggi v ON p.id_viaggio = v.id_viaggio
                      JOIN autisti a ON v.id_autista = a.id_autista
                      WHERE p.id_passeggero = ?
                      ORDER BY v.timestamp_partenza DESC";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$passengerId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            include_once __DIR__ . '/../utils/validation.php';

            $requiredFields = ['nome', 'cognome', 'documento_identita', 'telefono', 'email', 'password' ];
            validateRequired($data, $requiredFields);
            
            validateEmail($data['email']);
            
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE email = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$data['email']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Email already exists");
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID);
            
            $query = "INSERT INTO " . $this->table . " 
                      (nome, cognome, documento_identita, telefono, email, password)
                      VALUES (?, ?, ?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['nome'],
                $data['cognome'],
                $data['documento_identita'],
                $data['telefono'],
                $data['email'],
                $hashedPassword
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id_passeggero = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Passenger not found");
            }
            
            $fields = [];
            $params = [];
            
            // All your field checks
            if (isset($data['nome'])) {
                $fields[] = "nome = ?";
                $params[] = $data['nome'];
            }
            
            if (isset($data['cognome'])) {
                $fields[] = "cognome = ?";
                $params[] = $data['cognome'];
            }
            
            if (isset($data['telefono'])) {
                $fields[] = "telefono = ?";
                $params[] = $data['telefono'];
            }
            
            if (isset($data['documento_identita'])) {
                $fields[] = "documento_identita = ?";
                $params[] = $data['documento_identita'];
            }
            
            if (isset($data['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_ARGON2ID);
            }
            
            if (isset($data['fotografia'])) {
                $fields[] = "fotografia = ?";
                $params[] = $data['fotografia'];
            }
            
            $params[] = $id;
            
            $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id_passeggero = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function delete($id) {
        try {
            $checkQuery = "SELECT id_passeggero FROM " . $this->table . " WHERE id_passeggero = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() == 0) {
                throw new Exception("Passenger not found");
            }
            
            // No need to check for related bookings - CASCADE will handle it
            $query = "DELETE FROM " . $this->table . " WHERE id_passeggero = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get passenger's average rating
     *
     * @param int $passengerId The passenger ID
     * @return float The average rating (0-5) or 0 if no ratings
     */
    public function getAverageRating($passengerId) {
        try {
            $query = "SELECT AVG(p.voto_passeggero) as avg_rating
                      FROM prenotazioni p
                      WHERE p.id_passeggero = ? AND p.voto_passeggero IS NOT NULL";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$passengerId]);
            $result = $stmt->fetch();
            
            return $result && $result['avg_rating'] ? floatval($result['avg_rating']) : 0;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}