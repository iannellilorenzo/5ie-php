<?php
require_once 'database.php';

class Student extends Database {
    public $id;
    public $nome;
    public $matricola;
    public $corso_di_studio;
    public $password;

    public function create() {
        $sql = "INSERT INTO studente (nome, matricola, corso_di_studio, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $this->nome, $this->matricola, $this->corso_di_studio, $this->password);
        return $stmt->execute();
    }

    public function read($id) {
        $sql = "SELECT * FROM studente WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if($data) {
            $this->id = $data['id'];
            $this->nome = $data['nome'];
            $this->matricola = $data['matricola'];
            $this->corso_di_studio = $data['corso_di_studio'];
            $this->password = $data['password'];
            return true;
        }
        return false;
    }

    public function update() {
        $sql = "UPDATE studente SET nome=?, matricola=?, corso_di_studio=?, password=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $this->nome, $this->matricola, $this->corso_di_studio, $this->password, $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $sql = "DELETE FROM studente WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public static function getAll() {
        $db = new Database();
        $result = $db->conn->query("SELECT * FROM studente");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function authenticate($nome, $password) {
        $sql = "SELECT id, password FROM studente WHERE nome = ?";
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