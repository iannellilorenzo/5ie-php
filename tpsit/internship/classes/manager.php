<?php
require_once 'database.php';

class Manager extends Database {
    public $id;
    public $nome;
    public $email;
    public $password;

    public function create() {
        $sql = "INSERT INTO responsabile (nome, email, password) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $this->nome, $this->email, $this->password);
        return $stmt->execute();
    }

    public function read($id) {
        $sql = "SELECT * FROM responsabile WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if($data) {
            $this->id = $data['id'];
            $this->nome = $data['nome'];
            $this->email = $data['email'];
            $this->password = $data['password'];
            return true;
        }
        return false;
    }

    public function update() {
        $sql = "UPDATE responsabile SET nome=?, email=?, password=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $this->nome, $this->email, $this->password, $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $sql = "DELETE FROM responsabile WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public static function getAll() {
        $db = new Database();
        $result = $db->conn->query("SELECT * FROM responsabile");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function authenticate($nome, $password) {
        $sql = "SELECT id, password FROM responsabile WHERE nome = ?";
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

    public function approveAgreement($agreement_id) {
        $sql = "UPDATE accordo SET stato='approvato' WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $agreement_id);
        return $stmt->execute();
    }

    public function rejectAgreement($agreement_id) {
        $sql = "UPDATE accordo SET stato='rifiutato' WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $agreement_id);
        return $stmt->execute();
    }
}