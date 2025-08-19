<?php
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $recorridos_table = "uso_combustible_recorridos";

    // Propiedades principales (sin kilómetros)
    public $id;
    public $fecha;
    public $conductor;
    public $chapa;
    public $numero_voucher;
    public $tarjeta;  // Agregar esta línea
    public $litros_cargados;
    public $tipo_vehiculo;
    public $documento;
    public $fecha_carga;
    public $hora_carga;
    public $foto_voucher;
    public $user_id;
    public $usuario_id;
    public $foto_voucher_ruta;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear registro sin kilómetros
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (fecha, nombre_conductor, chapa, numero_baucher, tarjeta, litros_cargados, 
                   tipo_vehiculo, documento, fecha_carga, hora_carga, foto_voucher, foto_voucher_ruta, user_id, usuario_id) 
                  VALUES 
                  (:fecha, :conductor, :chapa, :numero_voucher, :tarjeta, :litros_cargados,
                   :tipo_vehiculo, :documento, :fecha_carga, :hora_carga, :foto_voucher, :foto_voucher_ruta, :user_id, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->conductor = htmlspecialchars(strip_tags($this->conductor));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->numero_voucher = htmlspecialchars(strip_tags($this->numero_voucher));
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->documento = htmlspecialchars(strip_tags($this->documento));

        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":conductor", $this->conductor);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":numero_voucher", $this->numero_voucher);
        $stmt->bindParam(":tarjeta", $this->tarjeta);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha_carga", $this->fecha_carga);
        $stmt->bindParam(":hora_carga", $this->hora_carga);
        $stmt->bindParam(":foto_voucher", $this->foto_voucher);
        $stmt->bindParam(":foto_voucher_ruta", $this->foto_voucher_ruta);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Actualizar registro
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                SET fecha = :fecha,
                    nombre_conductor = :conductor,
                    chapa = :chapa,
                    numero_baucher = :numero_voucher,
                    tarjeta = :tarjeta,
                    litros_cargados = :litros_cargados,
                    tipo_vehiculo = :tipo_vehiculo,
                    documento = :documento,
                    fecha_carga = :fecha_carga,
                    hora_carga = :hora_carga,
                    foto_voucher_ruta = :foto_voucher_ruta
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->conductor = htmlspecialchars(strip_tags($this->conductor));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->numero_voucher = htmlspecialchars(strip_tags($this->numero_voucher));
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->documento = htmlspecialchars(strip_tags($this->documento));

        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":conductor", $this->conductor);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":numero_voucher", $this->numero_voucher);
        $stmt->bindParam(":tarjeta", $this->tarjeta);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha_carga", $this->fecha_carga);
        $stmt->bindParam(":hora_carga", $this->hora_carga);
        $stmt->bindParam(":foto_voucher_ruta", $this->foto_voucher_ruta);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Agregar recorrido con kilómetros y comentarios
    // Buscar el método agregarRecorrido y modificar:
    public function agregarRecorrido($uso_combustible_id, $origen, $destino, $km_sucursales, $comentarios_sector = null, $orden_secuencial = 1) {
        $query = "INSERT INTO uso_combustible_recorridos 
                  (uso_combustible_id, origen, destino, km_sucursales, comentarios_sector, orden_secuencial) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $uso_combustible_id, 
            $origen, 
            $destino, 
            $km_sucursales, 
            $comentarios_sector,
            $orden_secuencial
        ]);
    }

    // Actualizar método leerTodos para incluir kilómetros
    public function leerTodos() {
        $query = "SELECT uc.*, 
                         GROUP_CONCAT(CONCAT(ucr.origen, ' → ', ucr.destino, ' (', IFNULL(ucr.km_sucursales, 0), ' km)') SEPARATOR '; ') as recorridos,
                         u.nombre as nombre_usuario
                  FROM " . $this->table_name . " uc
                  LEFT JOIN " . $this->recorridos_table . " ucr ON uc.id = ucr.uso_combustible_id
                  LEFT JOIN usuarios u ON uc.user_id = u.id OR uc.usuario_id = u.id
                  GROUP BY uc.id, u.nombre
                  ORDER BY uc.fecha_carga DESC, uc.hora_carga DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->recorridos_table . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }

    // Método para leer (alias de leerTodos para compatibilidad)
    public function leer() {
        return $this->leerTodos();
    }

    // Eliminar registro y sus recorridos asociados
    public function eliminar($id) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Primero eliminar los recorridos asociados
            $query_recorridos = "DELETE FROM " . $this->recorridos_table . " WHERE uso_combustible_id = :id";
            $stmt_recorridos = $this->conn->prepare($query_recorridos);
            $stmt_recorridos->bindParam(":id", $id);
            $stmt_recorridos->execute();
            
            // Luego eliminar el registro principal
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $result = $stmt->execute();
            
            // Confirmar transacción
            $this->conn->commit();
            return $result;
            
        } catch(Exception $e) {
            // Revertir transacción en caso de error
            $this->conn->rollback();
            return false;
        }
    }
    
    // Método para guardar foto de voucher como archivo
    public function guardarFotoVoucher($archivo_subido, $uso_combustible_id) {
        if (!isset($archivo_subido) || $archivo_subido['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Crear directorio si no existe
        $directorio = '../../img/uso_combustible/vouchers/';
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $extension = pathinfo($archivo_subido['name'], PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg'; // Extensión por defecto
        }
        
        $nombre_archivo = 'voucher_' . $uso_combustible_id . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio . $nombre_archivo;
        
        // Mover archivo subido
        if (move_uploaded_file($archivo_subido['tmp_name'], $ruta_completa)) {
            // Actualizar base de datos con la ruta
            $query = "UPDATE " . $this->table_name . " SET foto_voucher_ruta = :ruta WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ruta', $nombre_archivo);
            $stmt->bindParam(':id', $uso_combustible_id);
            
            return $stmt->execute() ? $nombre_archivo : false;
        }
        
        return false;
    }


    // Método para cerrar recorrido - SOLUCIÓN DEFINITIVA
    public function cerrarRecorrido($id, $usuario_id) {
        // Verificar que el registro existe y está abierto
        $checkQuery = "SELECT id, estado_recorrido FROM " . $this->table_name . " WHERE id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        
        $registro = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registro) {
            return ['success' => false, 'message' => 'Recorrido no encontrado'];
        }
        
        if ($registro['estado_recorrido'] === 'cerrado') {
            return ['success' => false, 'message' => 'El recorrido ya está cerrado'];
        }
        
        // Realizar el cierre
        $query = "UPDATE " . $this->table_name . " 
                 SET estado_recorrido = 'cerrado', 
                     fecha_cierre = NOW(), 
                     cerrado_por = :usuario_id 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        if ($stmt->execute()) {
            // Verificar que el cambio se aplicó correctamente
            $verifyQuery = "SELECT estado_recorrido FROM " . $this->table_name . " WHERE id = :id";
            $verifyStmt = $this->conn->prepare($verifyQuery);
            $verifyStmt->bindParam(':id', $id);
            $verifyStmt->execute();
            $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['estado_recorrido'] === 'cerrado') {
                return ['success' => true, 'message' => 'Recorrido cerrado exitosamente'];
            }
        }
        
        return ['success' => false, 'message' => 'Error al cerrar el recorrido'];
    }
    
    // Método para reabrir recorrido - SOLUCIÓN DEFINITIVA
    public function reabrirRecorrido($id, $usuario_id, $motivo) {
        // Verificar que el registro existe y está cerrado
        $checkQuery = "SELECT id, estado_recorrido FROM " . $this->table_name . " WHERE id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        
        $registro = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registro) {
            return ['success' => false, 'message' => 'Recorrido no encontrado'];
        }
        
        if ($registro['estado_recorrido'] === 'abierto') {
            return ['success' => false, 'message' => 'El recorrido ya está abierto'];
        }
        
        // Realizar la reapertura
        $query = "UPDATE " . $this->table_name . " 
                 SET estado_recorrido = 'abierto', 
                 reabierto_por = :usuario_id, 
                 fecha_reapertura = NOW(), 
                 motivo_reapertura = :motivo 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':motivo', $motivo);
        
        if ($stmt->execute()) {
            // Verificar que el cambio se aplicó correctamente
            $verifyQuery = "SELECT estado_recorrido FROM " . $this->table_name . " WHERE id = :id";
            $verifyStmt = $this->conn->prepare($verifyQuery);
            $verifyStmt->bindParam(':id', $id);
            $verifyStmt->execute();
            $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['estado_recorrido'] === 'abierto') {
                return ['success' => true, 'message' => 'Recorrido reabierto exitosamente'];
            }
        }
        
        return ['success' => false, 'message' => 'Error al reabrir el recorrido'];
    }

    // Verificar si hay recorridos abiertos para exportación
    public function verificarRecorridosAbiertos($filtros = []) {
        $sql = "SELECT COUNT(*) as total_abiertos, 
                       GROUP_CONCAT(CONCAT(u.nombre, ' (', DATE_FORMAT(uc.fecha_carga, '%d/%m/%Y'), ')') SEPARATOR ', ') as usuarios_abiertos
                FROM uso_combustible uc 
                LEFT JOIN usuarios u ON uc.user_id = u.id 
                WHERE uc.estado_recorrido = 'abierto'";
        
        $params = [];
        
        // Aplicar filtros si existen
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND uc.fecha_carga >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND uc.fecha_carga <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function verificarRecorridosAbiertosPorIds($ids) {
        try {
            if (empty($ids) || !is_array($ids)) {
                return ['total_abiertos' => 0, 'recorridos_abiertos' => []];
            }
            
            // Crear placeholders para la consulta
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $sql = "SELECT COUNT(*) as total_abiertos,
                           GROUP_CONCAT(DISTINCT u.nombre SEPARATOR ', ') as usuarios_abiertos
                    FROM uso_combustible uc
                    LEFT JOIN usuarios u ON uc.user_id = u.id
                    WHERE uc.id IN ($placeholders) 
                    AND uc.estado_recorrido = 'abierto'";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($ids);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_abiertos' => (int)$result['total_abiertos'],
                'recorridos_abiertos' => $result['usuarios_abiertos'] ?? ''
            ];
            
        } catch (Exception $e) {
            error_log("Error verificando recorridos abiertos por IDs: " . $e->getMessage());
            return ['total_abiertos' => 0, 'recorridos_abiertos' => []];
        }
    }
}
?>