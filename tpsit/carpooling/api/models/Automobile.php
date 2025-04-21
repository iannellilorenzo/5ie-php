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
                        a.id, a.autista_id, a.marca, a.modello, a.anno, 
                        a.targa, a.colore, a.n_posti, 
                        at.nome as nome_autista, at.cognome as cognome_autista
                    FROM " . $this->table . " a
                    JOIN autisti at ON a.autista_id = at.id";
            
            $whereConditions = [];
            $params = [];
            
            if (isset($filters['autista_id'])) {
                $whereConditions[] = "a.autista_id = ?";
                $params[] = $filters['autista_id'];
            }
            
            if (isset($filters['n_posti'])) {
                $whereConditions[] = "a.n_posti >= ?";
                $params[] = $filters['n_posti'];
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
                        a.id, a.autista_id, a.marca, a.modello, a.anno, 
                        a.targa, a.colore, a.n_posti, a.data_registrazione,
                        at.nome as nome_autista, at.cognome as cognome_autista
                    FROM " . $this->table . " a
                    JOIN autisti at ON a.autista_id = at.id
                    WHERE a.id = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function create($data) {
        try {
            $requiredFields = ['autista_id', 'marca', 'modello', 'anno', 'targa', 'n_posti'];
            validateRequired($data, $requiredFields);
            
            // Check if driver exists
            $checkDriverQuery = "SELECT COUNT(*) FROM autisti WHERE id = ?";
            $checkDriverStmt = $this->conn->prepare($checkDriverQuery);
            $checkDriverStmt->execute([$data['autista_id']]);
            
            if ($checkDriverStmt->fetchColumn() == 0) {
                throw new Exception("Driver not found");
            }
            
            // Check if license plate is unique
            $checkPlateQuery = "SELECT COUNT(*) FROM " . $this->table . " WHERE targa = ?";
            $checkPlateStmt = $this->conn->prepare($checkPlateQuery);
            $checkPlateStmt->execute([$data['targa']]);
            
            if ($checkPlateStmt->fetchColumn() > 0) {
                throw new Exception("License plate already exists");
            }
            
            $query = "INSERT INTO " . $this->table . " 
                      (autista_id, marca, modello, anno, targa, colore, n_posti)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['autista_id'],
                $data['marca'],
                $data['modello'],
                $data['anno'],
                $data['targa'],
                $data['colore'] ?? null,
                $data['n_posti']
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    public function update($id, $data) {
        try {
            $checkQuery = "SELECT autista_id FROM " . $this->table . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$id]);
            
            $car = $checkStmt->fetch();
            if (!$car) {
                throw new Exception("Car not found");
            }
            
            $fields = [];
            $params = [];
            
            // Cannot change owner (autista_id) of the car
            
            if (isset($data['marca'])) {
                $fields[] = "marca = ?";
                $params[] = $data['marca'];
            }
            
            if (isset($data['modello'])) {
                $fields[] = "modello = ?";
                $params[] = $data['modello'];
            }
            
            if (isset($data['anno'])) {
                $fields[] = "anno = ?";
                $params[] = $data['anno'];
            }
            
            if (isset($data['colore'])) {
                $fields[] = "colore = ?";
                $params[] = $data['colore'];
            }
            
            if (isset($data['n_posti'])) {
                $fields[] = "n_posti = ?";
                $params[] = $data['n_posti'];
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
                throw new Exception("Car not found");
            }
            
            $checkTripsQuery = "SELECT COUNT(*) FROM viaggi WHERE auto_id = ?";
            $checkTripsStmt = $this->conn->prepare($checkTripsQuery);
            $checkTripsStmt->execute([$id]);
            
            if ($checkTripsStmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete car with active trips");
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