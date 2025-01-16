<?php
require_once 'database.php';

class Agreement extends Database {
    public $id;
    public $studente_id;
    public $tirocinio_id;
    public $stato;

    public function create() {
        $sql = "INSERT INTO accordo (studente_id, tirocinio_id, stato) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $this->studente_id, $this->tirocinio_id, $this->stato);
        return $stmt->execute();
    }

    public function read($id) {
        $sql = "SELECT * FROM accordo WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if($data) {
            $this->id = $data['id'];
            $this->studente_id = $data['studente_id'];
            $this->tirocinio_id = $data['tirocinio_id'];
            $this->stato = $data['stato'];
            return true;
        }
        return false;
    }

    public function update() {
        $sql = "UPDATE accordo SET studente_id=?, tirocinio_id=?, stato=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisi", $this->studente_id, $this->tirocinio_id, $this->stato, $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $sql = "DELETE FROM accordo WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public static function getAll() {
        $db = new Database();
        $result = $db->conn->query("SELECT * FROM accordo");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}