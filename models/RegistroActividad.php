<?php
class RegistroActividad {
    private $conn;
    private $table_name = "registro_actividades";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerRegistros() {
        try {
            // Verificar si hay registros
            $countQuery = "SELECT COUNT(*) FROM " . $this->table_name;
            $stmt = $this->conn->prepare($countQuery);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            error_log("Total de registros en la tabla: " . $count);

            // Obtener los registros
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY fecha_hora DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Registros recuperados: " . count($registros));
            
            return $registros;
        } catch(PDOException $e) {
            error_log("Error al obtener registros: " . $e->getMessage());
            error_log("Query que falló: " . $query);
            return [];
        }
    }

    public function registrar($usuario_id, $modulo, $accion, $descripcion) {
        try {
            error_log("Iniciando registro de actividad...");
            
            // Verificar conexión y base de datos
            $testQuery = "SELECT DATABASE()";
            $stmt = $this->conn->query($testQuery);
            $dbName = $stmt->fetchColumn();
            error_log("Base de datos actual: " . $dbName);
            
            // Verificar si la tabla existe y su estructura
            $checkStructure = $this->conn->query("DESCRIBE " . $this->table_name);
            $columns = $checkStructure->fetchAll(PDO::FETCH_COLUMN);
            error_log("Columnas en la tabla: " . json_encode($columns));
            
            // Verificar conexión
            if (!$this->conn) {
                error_log("Error: No hay conexión a la base de datos");
                return false;
            }

            // Verificar parámetros
            if (empty($usuario_id)) {
                error_log("Error: usuario_id está vacío");
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     (usuario_id, modulo, accion, descripcion, fecha_hora, ip_address) 
                     VALUES 
                     (:usuario_id, :modulo, :accion, :descripcion, NOW(), :ip_address)";
            
            // Verificar si la tabla existe
            $checkTable = $this->conn->query("SHOW TABLES LIKE '" . $this->table_name . "'");
            if ($checkTable->rowCount() == 0) {
                error_log("Error: La tabla {$this->table_name} no existe en la base de datos {$dbName}");
                return false;
            }
            
            $stmt = $this->conn->prepare($query);
            
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Debug más detallado antes de ejecutar
            error_log("Preparando para ejecutar con valores: " . json_encode([
                'tabla' => $this->table_name,
                'usuario_id' => $usuario_id,
                'modulo' => $modulo,
                'accion' => $accion,
                'descripcion' => $descripcion,
                'ip' => $ip
            ]));
            
            // Bind con tipos específicos
            $stmt->bindValue(":usuario_id", $usuario_id, PDO::PARAM_INT);
            $stmt->bindValue(":modulo", $modulo, PDO::PARAM_STR);
            $stmt->bindValue(":accion", $accion, PDO::PARAM_STR);
            $stmt->bindValue(":descripcion", $descripcion, PDO::PARAM_STR);
            $stmt->bindValue(":ip_address", $ip, PDO::PARAM_STR);
            
            // Ejecutar y verificar resultado
            if($stmt->execute()) {
                $lastId = $this->conn->lastInsertId();
                error_log("Registro insertado correctamente. ID: " . $lastId);
                return true;
            }
            
            error_log("Error al ejecutar insert: " . json_encode($stmt->errorInfo()));
            return false;
            
        } catch(PDOException $e) {
            error_log("Excepción PDO en registro: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        } catch(Exception $e) {
            error_log("Excepción general en registro: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }
}