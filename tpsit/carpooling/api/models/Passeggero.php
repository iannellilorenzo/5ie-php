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
                        p.documento_identita, p.telefono
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
            
            if (isset($data['documento_identita'])) {
                $fields[] = "documento_identita = ?";
                $params[] = $data['documento_identita'];
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
}