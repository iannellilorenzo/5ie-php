<?php
require_once 'Database.php';

class Internship extends Database {
    public $id;
    public $descrizione;
    public $durata;
    public $azienda_id;

    public function create() {
        $sql = "INSERT INTO tirocinio (descrizione, durata, azienda_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sii", $this->descrizione, $this->durata, $this->azienda_id);
        return $stmt->execute();
    }

    public function read($id) {
        $sql = "SELECT * FROM tirocinio WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if($data) {
            $this->id = $data['id'];
            $this->descrizione = $data['descrizione'];
            $this->durata = $data['durata'];
            $this->azienda_id = $data['azienda_id'];
            return true;
        }
        return false;
    }

    public function update() {
        $sql = "UPDATE tirocinio SET descrizione=?, durata=?, azienda_id=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("siii", $this->descrizione, $this->durata, $this->azienda_id, $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $sql = "DELETE FROM tirocinio WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public static function getAll() {
        $db = new Database();
        $result = $db->conn->query("SELECT * FROM tirocinio");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}