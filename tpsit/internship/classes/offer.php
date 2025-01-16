<?php
require_once 'database.php';

class Offer extends Database {
    public $id;
    public $titolo;
    public $descrizione;
    public $stato;
    public $azienda_id;

    public function create() {
        $sql = "INSERT INTO offerta (titolo, descrizione, stato, azienda_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $this->titolo, $this->descrizione, $this->stato, $this->azienda_id);
        return $stmt->execute();
    }

    public function read($id) {
        $sql = "SELECT * FROM offerta WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if($data) {
            $this->id = $data['id'];
            $this->titolo = $data['titolo'];
            $this->descrizione = $data['descrizione'];
            $this->stato = $data['stato'];
            $this->azienda_id = $data['azienda_id'];
            return true;
        }
        return false;
    }

    public function update() {
        $sql = "UPDATE offerta SET titolo=?, descrizione=?, stato=?, azienda_id=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssii", $this->titolo, $this->descrizione, $this->stato, $this->azienda_id, $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $sql = "DELETE FROM offerta WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public static function getAll() {
        $db = new Database();
        $result = $db->conn->query("SELECT * FROM offerta");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}