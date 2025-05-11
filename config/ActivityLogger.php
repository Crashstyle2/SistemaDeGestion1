<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/RegistroActividad.php';

class ActivityLogger {
    private static $instance = null;
    private $registro;
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            
            if ($this->db === null) {
                throw new Exception("Error de conexión a la base de datos");
            }
            
            $this->registro = new RegistroActividad($this->db);
            
            // Verificar conexión directamente
            $testQuery = "SELECT 1 FROM registro_actividades LIMIT 1";
            $stmt = $this->db->prepare($testQuery);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("ActivityLogger Error de inicialización: " . $e->getMessage());
            throw $e; // Propagar el error para saber si hay problemas
        }
    }
    
    public static function logAccion($usuario_id, $modulo, $accion, $detalles = '') {
        try {
            error_log("ActivityLogger::logAccion llamado con: " . json_encode([
                'usuario_id' => $usuario_id,
                'modulo' => $modulo,
                'accion' => $accion,
                'detalles' => $detalles
            ]));

            if (self::$instance === null) {
                self::$instance = new self();
                error_log("Nueva instancia de ActivityLogger creada");
            }

            // Validar datos antes de insertar
            if (empty($usuario_id) || !is_numeric($usuario_id)) {
                error_log("Error: ID de usuario inválido - " . var_export($usuario_id, true));
                throw new Exception("ID de usuario inválido");
            }

            $ip = $_SERVER['REMOTE_ADDR'];
            error_log("IP detectada: " . $ip);
            
            // Asegurar que los campos cumplan con la estructura
            $modulo = substr(trim($modulo), 0, 100);
            $accion = substr(trim($accion), 0, 255);
            $ip = substr($ip, 0, 45);
            
            $resultado = self::$instance->registro->registrar(
                intval($usuario_id),
                $modulo,
                $accion,
                $detalles
            );
            
            error_log("Resultado del registro: " . ($resultado ? "Exitoso" : "Fallido"));
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error crítico en ActivityLogger: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
}