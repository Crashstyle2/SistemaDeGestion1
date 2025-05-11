<?php
class LogSistema {
    private $rutaLog;
    
    public function __construct() {
        $this->rutaLog = 'c:/laragon/www/MantenimientodeUPS/logs/actividad_sistema.log';
        
        if (!file_exists(dirname($this->rutaLog))) {
            mkdir(dirname($this->rutaLog), 0777, true);
        }
    }
    
    public function registrar($datos) {
        $entrada = [
            'fecha_hora' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'usuario' => $_SESSION['nombre_usuario'] ?? 'Sistema',
            'url' => $_SERVER['REQUEST_URI'],
            'metodo' => $_SERVER['REQUEST_METHOD'],
            'datos' => $datos
        ];
        
        file_put_contents(
            $this->rutaLog, 
            json_encode($entrada, JSON_UNESCAPED_UNICODE) . "\n", 
            FILE_APPEND
        );
    }
}