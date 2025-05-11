<?php
class RegistroActividad {
    private $conn;
    private $table_name = "registro_actividades";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerRegistros() {
        $query = "SELECT * FROM " . $this->table_name . " 
                 ORDER BY fecha_hora DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrar($usuario_id, $modulo, $accion, $descripcion) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (usuario_id, modulo, accion, descripcion, fecha_hora, ip_address) 
                     VALUES 
                     (:usuario_id, :modulo, :accion, :descripcion, NOW(), :ip_address)";
            
            $stmt = $this->conn->prepare($query);
            
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $stmt->bindParam(":usuario_id", $usuario_id);
            $stmt->bindParam(":modulo", $modulo);
            $stmt->bindParam(":accion", $accion);
            $stmt->bindParam(":descripcion", $descripcion);
            $stmt->bindParam(":ip_address", $ip);
            
            if($stmt->execute()) {
                error_log("Actividad registrada correctamente");
                return true;
            }
            
            error_log("Error al registrar actividad: " . print_r($stmt->errorInfo(), true));
            return false;
            
        } catch(PDOException $e) {
            error_log("Error en registro de actividad: " . $e->getMessage());
            return false;
        }
    }
}