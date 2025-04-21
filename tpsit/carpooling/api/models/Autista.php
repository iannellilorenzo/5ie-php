<?php
class Autista {
    private $conn;
    private $table = "autisti";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all drivers
     */
    public function getAll($filters = []) {
        try {
            // Start with base query
            $query = "SELECT 
                        a.id, a.nome, a.cognome, a.email, a.telefono, 
                        a.data_nascita, a.citta, a.valutazione, a.bio, 
                        a.foto_profilo, a.data_registrazione
                    FROM " . $this->table . " a";
            
            // Add WHERE conditions for filters
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['city'])) {
                $whereConditions[] = "a.citta = ?";
                $params[] = $filters['city'];
            }
            
            if (isset($filters['rating'])) {
                $whereConditions[] = "a.valutazione >= ?";
                $params[] = $filters['rating'];
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $query .= " ORDER BY a.valutazione DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Get driver by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT 
                        a.id, a.nome, a.cognome, a.email, a.telefono, 
                        a.data_nascita, a.citta, a.valutazione, a.bio, 
                        a.foto_profilo, a.data_registrazione
                    FROM " . $this->table . " a
                    WHERE a.id = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $driver = $stmt->fetch();
            
            if (!$driver) {
                return null;
            }
            
            // Get driver's cars
            $carsQuery = "SELECT * FROM automobili WHERE autista_id = ?";
            $carsStmt = $this->conn->prepare($carsQuery);
            $carsStmt->execute([$id]);
            $cars = $carsStmt->fetchAll();
            
            $driver['automobili'] = $cars;
            
            return $driver;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Create new driver
     */
    public function create($data) {
        try {
            // Validate required fields
            $requiredFields = ['nome', 'cognome', 'email', 'password', 'telefono', 'data_nascita', 'citta'];
            validateRequired($data, $requiredFields);
            
            // Validate email
            validateEmail($data['email']);
            
            // Check if email already exists
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE email = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$data['email']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Email already exists");
            }
            
            // Hash password
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
    
    /**
     * Update driver
     */
    public function update($id, $data) {
        try {
            // Check if driver exists
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Driver not found");
            }
            
            // Build update query
            $fields = [];
            $params = [];
            
            // Only update fields that were provided
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
            
            // Add ID to params array
            $params[] = $id;
            
            $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Delete driver
     */
    public function delete($id) {
        try {
            // Check if driver exists
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Driver not found");
            }
            
            // Check for related records in other tables
            $checkTripsQuery = "SELECT COUNT(*) FROM viaggi WHERE autista_id = ?";
            $checkTripsStmt = $this->conn->prepare($checkTripsQuery);
            $checkTripsStmt->execute([$id]);
            
            if ($checkTripsStmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete driver with active trips");
            }
            
            // Delete related cars
            $deleteCarsQuery = "DELETE FROM automobili WHERE autista_id = ?";
            $deleteCarsStmt = $this->conn->prepare($deleteCarsQuery);
            $deleteCarsStmt->execute([$id]);
            
            // Delete driver
            $query = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}