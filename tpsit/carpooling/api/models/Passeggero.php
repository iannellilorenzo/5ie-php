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
                        p.id, p.nome, p.cognome, p.email, p.telefono, 
                        p.data_nascita, p.citta, p.bio, p.foto_profilo, 
                        p.data_registrazione
                    FROM " . $this->table . " p
                    WHERE p.id = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $passenger = $stmt->fetch();
            
            if (!$passenger) {
                return null;
            }
            
            // Get passenger's bookings
            $bookingsQuery = "SELECT 
                                pr.id, pr.viaggio_id, pr.stato, pr.n_posti, 
                                pr.data_prenotazione, v.partenza, v.destinazione, 
                                v.data_partenza, v.ora_partenza
                              FROM prenotazioni pr
                              JOIN viaggi v ON pr.viaggio_id = v.id
                              WHERE pr.passeggero_id = ?";
            $bookingsStmt = $this->conn->prepare($bookingsQuery);
            $bookingsStmt->execute([$id]);
            $bookings = $bookingsStmt->fetchAll();
            
            $passenger['prenotazioni'] = $bookings;
            
            return $passenger;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            $requiredFields = ['nome', 'cognome', 'email', 'password', 'telefono', 'data_nascita', 'citta'];
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
                      (nome, cognome, email, password, telefono, data_nascita, citta, bio)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['nome'],
                $data['cognome'],
                $data['email'],
                $hashedPassword,
                $data['telefono'],
                $data['data_nascita'],
                $data['citta'],
                $data['bio'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Passenger not found");
            }
            
            $fields = [];
            $params = [];
            
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
            
            if (isset($data['citta'])) {
                $fields[] = "citta = ?";
                $params[] = $data['citta'];
            }
            
            if (isset($data['bio'])) {
                $fields[] = "bio = ?";
                $params[] = $data['bio'];
            }
            
            if (isset($data['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_ARGON2ID);
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
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Passenger not found");
            }
            
            $checkBookingsQuery = "SELECT COUNT(*) FROM prenotazioni WHERE passeggero_id = ?";
            $checkBookingsStmt = $this->conn->prepare($checkBookingsQuery);
            $checkBookingsStmt->execute([$id]);
            
            if ($checkBookingsStmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete passenger with active bookings");
            }
            
            $query = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}