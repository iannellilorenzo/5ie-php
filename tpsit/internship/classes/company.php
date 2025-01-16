<?php
require_once 'database.php';

class Company extends Database {
    public $id;
    public $nome;
    public $indirizzo;
    public $telefono;
    public $password;

    public function create() {
        $sql = "INSERT INTO azienda (nome, indirizzo, telefono, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $this->nome, $this->indirizzo, $this->telefono, $this->password);
        return $stmt->execute();
    }

    public function read($id) {
        $sql = "SELECT * FROM azienda WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if($data) {
            $this->id = $data['id'];
            $this->nome = $data['nome'];
            $this->indirizzo = $data['indirizzo'];
            $this->telefono = $data['telefono'];
            $this->password = $data['password'];
            return true;
        }
        return false;
    }

    public function update() {
        $sql = "UPDATE azienda SET nome=?, indirizzo=?, telefono=?, password=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $this->nome, $this->indirizzo, $this->telefono, $this->password, $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $sql = "DELETE FROM azienda WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public static function getAll() {
        $db = new Database();
        $result = $db->conn->query("SELECT * FROM azienda");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function authenticate($nome, $password) {
        $sql = "SELECT id, password FROM azienda WHERE nome = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $nome);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            if(password_verify($password, $row['password'])) {
                $this->read($row['id']);
                return true;
            }
        }
        return false;
    }
}