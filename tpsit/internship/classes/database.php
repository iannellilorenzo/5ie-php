<?php
class Database {
    protected $conn;
    
    public function __construct() {
        $this->conn = new mysqli('localhost', 'root', '', 'gestionale_tirocini');
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function __destruct() {
        $this->conn->close();
    }
}