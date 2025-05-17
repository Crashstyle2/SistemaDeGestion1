<?php
mb_internal_encoding('UTF-8');
class InformeTecnico {
    private $conn;
    private $table_name = "informe_tecnico";

    // Propiedades
    public $id;
    public $local;
    public $sector;
    public $equipo_asistido;
    public $orden_trabajo;
    public $patrimonio;
    public $jefe_turno;
    public $observaciones;
    public $firma_digital;
    public $tecnico_id;
    public $fecha_creacion;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // En el método crear()
    public function crear($data) {
        $query = "INSERT INTO informe_tecnico (
            local, 
            sector, 
            equipo_asistido, 
            orden_trabajo, 
            patrimonio, 
            jefe_turno, 
            observaciones, 
            firma_digital, 
            tecnico_id,
            foto_trabajo
        ) VALUES (
            :local, 
            :sector, 
            :equipo, 
            :orden, 
            :patrimonio, 
            :jefe, 
            :observaciones, 
            :firma, 
            :tecnico_id,
            :foto
        )";
    
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            ':local' => $data['local'],
            ':sector' => $data['sector'],
            ':equipo' => $data['equipo_asistido'],
            ':orden' => $data['orden_trabajo'],
            ':patrimonio' => $data['patrimonio'],
            ':jefe' => $data['jefe_turno'],
            ':observaciones' => $data['observaciones'],
            ':firma' => $data['firma_digital'],
            ':tecnico_id' => $data['tecnico_id'],
            ':foto' => $data['foto_trabajo']
        ]);
    }

    public function obtenerTodos() {
        $query = "SELECT i.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " i 
                 LEFT JOIN usuarios u ON i.tecnico_id = u.id 
                 ORDER BY i.fecha_creacion DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function obtenerUno($id) {
        $query = "SELECT i.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " i 
                 LEFT JOIN usuarios u ON i.tecnico_id = u.id 
                 WHERE i.id = :id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function leerTodos($tecnico_id = null) {
        $query = "SELECT i.*, u.nombre as nombre_tecnico 
                FROM " . $this->table_name . " i 
                LEFT JOIN usuarios u ON i.tecnico_id = u.id";
        
        if ($tecnico_id) {
            $query .= " WHERE i.tecnico_id = :tecnico_id";
        }
        
        $query .= " ORDER BY i.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($tecnico_id) {
            $stmt->bindParam(":tecnico_id", $tecnico_id);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function eliminar($id) {
        try {
            $id = intval($id);
            
            // Primero verificamos si existe el informe
            $check = "SELECT id FROM " . $this->table_name . " WHERE id = ?";
            $checkStmt = $this->conn->prepare($check);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() === 0) {
                return false;
            }
            
            // Si existe, procedemos a eliminar
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$id]);
            
            return $result && $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error en eliminación: " . $e->getMessage());
            return false;
        }
    }
}