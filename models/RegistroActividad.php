<?php
class RegistroActividad {
    private $conn;
    private $table_name = "registro_actividades";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrar($usuario_id, $modulo, $accion, $descripcion) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (usuario_id, modulo, accion, descripcion, fecha_hora, ip_address) 
                    VALUES 
                    (:usuario_id, :modulo, :accion, :descripcion, NOW(), :ip_address)";

            $stmt = $this->conn->prepare($query);

            // Obtener IP del usuario
            $ip_address = $_SERVER['REMOTE_ADDR'];

            $stmt->bindParam(":usuario_id", $usuario_id);
            $stmt->bindParam(":modulo", $modulo);
            $stmt->bindParam(":accion", $accion);
            $stmt->bindParam(":descripcion", $descripcion);
            $stmt->bindParam(":ip_address", $ip_address);

            if(!$stmt->execute()) {
                error_log("Error en la ejecución de la consulta: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            return true;
            
        } catch(PDOException $e) {
            error_log("Error en registro de actividad: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerRegistros() {
        try {
            $query = "SELECT r.*, u.nombre as nombre_usuario, 
                            DATE_FORMAT(r.fecha_hora, '%d-%m-%Y %H:%i:%s') as fecha_formateada
                     FROM " . $this->table_name . " r
                     LEFT JOIN usuarios u ON r.usuario_id = u.id
                     ORDER BY r.fecha_hora DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error al obtener registros de actividad: " . $e->getMessage());
            return [];
        }
    }
}