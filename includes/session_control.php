<?php
class SessionControl {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function actualizarActividad($usuario_id) {
        // Simply update the session last activity
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function verificarSesion() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        return true;
    }
}
?>