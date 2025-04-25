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
            // Start with base query that matches the actual database schema
            $query = "SELECT 
                        a.id_autista, a.nome, a.cognome, a.data_nascita, 
                        a.numero_patente, a.scadenza_patente, a.numero_telefono, 
                        a.email, a.fotografia
                    FROM " . $this->table . " a";
            
            // Add WHERE conditions for filters
            $whereConditions = [];
            $params = [];
            
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
    
    /**
     * Get driver by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT 
                        a.id_autista, a.nome, a.cognome, a.data_nascita,
                        a.numero_patente, a.scadenza_patente, a.numero_telefono, 
                        a.email, a.fotografia
                    FROM " . $this->table . " a
                    WHERE a.id_autista = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $driver = $stmt->fetch();
            
            if (!$driver) {
                return null;
            }

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
            include_once __DIR__ . '/../utils/validation.php';

            // Validate required fields
            $requiredFields = ['nome', 'cognome', 'data_nascita', 'numero_patente', 'scadenza_patente', 'numero_telefono', 'email', 'password'];
            validateRequired($data, $requiredFields);
            
            // Validate email
            validateEmail($data['email']);
            
            // Check if email, phone or license already exists
            $checkEmailQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE email = ?";
            $checkEmailStmt = $this->conn->prepare($checkEmailQuery);
            $checkEmailStmt->execute([$data['email']]);
            
            if ($checkEmailStmt->fetchColumn() > 0) {
                throw new Exception("Email already exists");
            }
            
            $checkPhoneQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE numero_telefono = ?";
            $checkPhoneStmt = $this->conn->prepare($checkPhoneQuery);
            $checkPhoneStmt->execute([$data['numero_telefono']]);
            
            if ($checkPhoneStmt->fetchColumn() > 0) {
                throw new Exception("Phone number already exists");
            }
            
            $checkLicenseQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE numero_patente = ?";
            $checkLicenseStmt = $this->conn->prepare($checkLicenseQuery);
            $checkLicenseStmt->execute([$data['numero_patente']]);
            
            if ($checkLicenseStmt->fetchColumn() > 0) {
                throw new Exception("License number already exists");
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID);
            
            // Query corretta che rispetta la struttura della tabella
            $query = "INSERT INTO " . $this->table . " 
                      (nome, cognome, data_nascita, numero_patente, scadenza_patente, 
                      numero_telefono, email, password, fotografia)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['nome'],
                $data['cognome'],
                $data['data_nascita'],
                $data['numero_patente'],
                $data['scadenza_patente'],
                $data['numero_telefono'],
                $data['email'],
                $hashedPassword,
                $data['fotografia'] ?? null
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
            // Check if driver exists using the correct column name id_autista
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE id_autista = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Driver not found");
            }
            
            // Build update query
            $fields = [];
            $params = [];
            
            // Only update fields that were provided, matching database schema
            if (isset($data['nome'])) {
                $fields[] = "nome = ?";
                $params[] = $data['nome'];
            }
            
            if (isset($data['cognome'])) {
                $fields[] = "cognome = ?";
                $params[] = $data['cognome'];
            }
            
            if (isset($data['data_nascita'])) {
                $fields[] = "data_nascita = ?";
                $params[] = $data['data_nascita'];
            }
            
            if (isset($data['numero_patente'])) {
                $fields[] = "numero_patente = ?";
                $params[] = $data['numero_patente'];
            }
            
            if (isset($data['scadenza_patente'])) {
                $fields[] = "scadenza_patente = ?";
                $params[] = $data['scadenza_patente'];
            }
            
            if (isset($data['numero_telefono'])) {
                $fields[] = "numero_telefono = ?";
                $params[] = $data['numero_telefono'];
            }
            
            if (isset($data['email'])) {
                $fields[] = "email = ?";
                $params[] = $data['email'];
            }
            
            if (isset($data['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_ARGON2ID);
            }
            
            if (isset($data['fotografia'])) {
                $fields[] = "fotografia = ?";
                $params[] = $data['fotografia'];
            }
            
            // Add ID to params array
            $params[] = $id;
            
            $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id_autista = ?";
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
            $checkQuery = "SELECT id_autista FROM " . $this->table . " WHERE id_autista = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() == 0) {
                throw new Exception("Driver not found");
            }
            
            // No need to check for related automobiles, trips, etc. - CASCADE will handle it
            $query = "DELETE FROM " . $this->table . " WHERE id_autista = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}