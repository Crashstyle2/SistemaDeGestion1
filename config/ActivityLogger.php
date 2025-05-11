<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/RegistroActividad.php';

class ActivityLogger {
    private static $instance = null;
    private $registro;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->registro = new RegistroActividad($db);
    }
    
    public static function logAccion($usuario_id, $modulo, $accion, $detalles = '') {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $descripcion = $detalles . " | IP: " . $ip;

        return self::$instance->registro->registrar(
            $usuario_id,
            $modulo,
            $accion,
            $descripcion
        );
    }

    public static function logLogin($usuario_id, $tipo = 'Inicio') {
        $ip = $_SERVER['REMOTE_ADDR'];
        self::logAccion(
            $usuario_id,
            'Sistema',
            $tipo . ' de sesión',
            'Usuario ' . ($tipo == 'Inicio' ? 'inició' : 'cerró') . ' sesión | IP: ' . $ip
        );
    }
}