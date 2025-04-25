<?php
class Automobile {
    private $conn;
    private $table = "automobili";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll($filters = []) {
        try {
            $query = "SELECT 
                        a.targa, a.marca, a.modello, a.id_autista, 
                        at.nome as nome_autista, at.cognome as cognome_autista
                    FROM " . $this->table . " a
                    JOIN autisti at ON a.id_autista = at.id_autista";
            
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['id_autista'])) {
                $whereConditions[] = "a.id_autista = ?";
                $params[] = $filters['id_autista'];
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
    
    public function getById($targa) {
        try {
            $query = "SELECT 
                        a.targa, a.marca, a.modello, a.id_autista,
                        at.nome as nome_autista, at.cognome as cognome_autista
                    FROM " . $this->table . " a
                    JOIN autisti at ON a.id_autista = at.id_autista
                    WHERE a.targa = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$targa]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
                $data['id_autista'] = $_SESSION['user_id'];
            }

            $requiredFields = ['id_autista', 'marca', 'modello', 'targa'];
            validateRequired($data, $requiredFields);
            
            // Check if driver exists
            $checkDriverQuery = "SELECT COUNT(*) FROM autisti WHERE id_autista = ?";
            $checkDriverStmt = $this->conn->prepare($checkDriverQuery);
            $checkDriverStmt->execute([$data['id_autista']]);
            
            if ($checkDriverStmt->fetchColumn() == 0) {
                throw new Exception("Driver not found");
            }
            
            // Check if license plate is unique - since it's the primary key
            $checkPlateQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE targa = ?";
            $checkPlateStmt = $this->conn->prepare($checkPlateQuery);
            $checkPlateStmt->execute([$data['targa']]);
            
            if ($checkPlateStmt->fetchColumn() > 0) {
                throw new Exception("License plate already exists");
            }
            
            $query = "INSERT INTO " . $this->table . " 
                      (targa, marca, modello, id_autista)
                      VALUES (?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['targa'],
                $data['marca'],
                $data['modello'],
                $data['id_autista']
            ]);
            
            return $data['targa']; // Return the license plate as ID
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($targa, $data) {
        try {
            $checkQuery = "SELECT id_autista FROM " . $this->table . " WHERE targa = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$targa]);
            
            $car = $checkStmt->fetch();
            if (!$car) {
                throw new Exception("Car not found");
            }
            
            $fields = [];
            $params = [];
            
            // Cannot change targa (primary key) or id_autista (foreign key)
            
            if (isset($data['marca'])) {
                $fields[] = "marca = ?";
                $params[] = $data['marca'];
            }
            
            if (isset($data['modello'])) {
                $fields[] = "modello = ?";
                $params[] = $data['modello'];
            }
            
            $params[] = $targa;
            
            $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE targa = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function delete($targa) {
        try {
            $checkQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE targa = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$targa]);
            
            if ($checkStmt->fetchColumn() == 0) {
                throw new Exception("Car not found");
            }
            
            $checkTripsQuery = "SELECT COUNT(*) FROM viaggi WHERE auto_id = ?";
            $checkTripsStmt = $this->conn->prepare($checkTripsQuery);
            $checkTripsStmt->execute([$targa]);
            
            if ($checkTripsStmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete car with active trips");
            }
            
            $query = "DELETE FROM " . $this->table . " WHERE targa = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$targa]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
}