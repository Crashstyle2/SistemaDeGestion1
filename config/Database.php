<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'mantenimiento_ups';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    public function getConnection() {
        try {
            if ($this->conn === null) {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8",
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
            return $this->conn;
        } catch(PDOException $e) {
            die(json_encode([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ]));
        }
    }
}