<?php
class ReporteCierres {
    private $conn;
    private $table_name = "reporte_cierres";
    private $error = null; // Agregar esta propiedad

    public $id;
    public $tecnico_id;
    public $mes;
    public $anio;
    public $cantidad_cierres;
    public $estado;
    public $justificacion;
    public $comentario_medida;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getError() {
        return $this->error;
    }

    private $justificacionMap = [
        'ninguna' => 'N',
        'reposo' => 'R',
        'vacaciones' => 'V',
        'permiso especial' => 'P'
    ];

    // ELIMINAR todo el método determinarEstado que está aquí
    // y mantener solo el que está más abajo en el archivo

    public function crear() {
        try {
            // Validar si ya existe un registro
            $queryCheck = "SELECT id FROM " . $this->table_name . " 
                          WHERE tecnico_id = :tecnico_id 
                          AND mes = :mes 
                          AND anio = :anio";
            
            $stmt = $this->conn->prepare($queryCheck);
            $stmt->bindParam(":tecnico_id", $this->tecnico_id);
            $stmt->bindParam(":mes", $this->mes);
            $stmt->bindParam(":anio", $this->anio);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $this->error = "Ya existe un registro para este técnico en el mes y año seleccionados";
                return false;
            }

            // Convertir justificación al formato ENUM
            $this->justificacion = $this->justificacionMap[$this->justificacion] ?? 'N';
            
            // Determinar estado
            if ($this->justificacion !== 'N') {
                $this->estado = 'justificado';
                if (empty($this->comentario_medida)) {
                    $this->error = "Debe agregar un comentario para la justificación";
                    return false;
                }
            } else {
                if ($this->cantidad_cierres <= 45) {
                    $this->estado = 'bajo';
                    $mesesBajos = $this->verificarMesesBajos($this->tecnico_id);
                    if ($mesesBajos >= 1 && empty($this->comentario_medida)) {
                        $this->error = "Debe agregar un comentario por bajo rendimiento consecutivo";
                        return false;
                    }
                } else {
                    $this->estado = 'normal';
                }
            }

            $query = "INSERT INTO " . $this->table_name . " 
                    (tecnico_id, mes, anio, cantidad_cierres, estado, justificacion, comentario_medida) 
                    VALUES 
                    (:tecnico_id, :mes, :anio, :cantidad_cierres, :estado, :justificacion, :comentario_medida)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":tecnico_id", $this->tecnico_id);
            $stmt->bindParam(":mes", $this->mes);
            $stmt->bindParam(":anio", $this->anio);
            $stmt->bindParam(":cantidad_cierres", $this->cantidad_cierres);
            $stmt->bindParam(":estado", $this->estado);
            $stmt->bindParam(":justificacion", $this->justificacion);
            $stmt->bindParam(":comentario_medida", $this->comentario_medida);

            $result = $stmt->execute();
            if (!$result) {
                $this->error = $stmt->errorInfo()[2];
                error_log("Error en la ejecución: " . $this->error);
            }
            return $result;
            
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Error en crear reporte: " . $this->error);
            return false;
        }
    }

    public function verificarMesesBajos($tecnico_id) {
        $query = "SELECT COUNT(*) as meses_bajos 
                 FROM " . $this->table_name . "
                 WHERE tecnico_id = :tecnico_id 
                 AND estado = 'bajo' 
                 AND justificacion = 'ninguna'
                 AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 2 MONTH)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":tecnico_id", $tecnico_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['meses_bajos'];
        } catch(PDOException $e) {
            return 0;
        }
    }

    public function obtenerReporteMensual($mes, $anio) {
        $query = "SELECT r.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " r
                 LEFT JOIN usuarios u ON r.tecnico_id = u.id
                 WHERE r.mes = :mes AND r.anio = :anio
                 ORDER BY u.nombre";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":mes", $mes);
            $stmt->bindParam(":anio", $anio);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function actualizar() {
        try {
            // Actualizar el mapeo de justificaciones para usar los nuevos valores ENUM
            $justificacionMap = [
                'ninguna' => 'N',
                'reposo' => 'R',
                'vacaciones' => 'V',
                'permiso especial' => 'P'
            ];

            // Asegurar valores correctos para justificación
            $this->justificacion = isset($justificacionMap[$this->justificacion]) 
                ? $justificacionMap[$this->justificacion] 
                : 'N';

            // Lógica para el estado
            if ($this->justificacion !== 'N') {
                $this->estado = 'justificado';
                if (empty($this->comentario_medida)) {
                    $this->error = "Debe agregar un comentario explicando la razón de la justificación";
                    return false;
                }
            } else {
                $this->estado = ($this->cantidad_cierres <= 45) ? 'bajo' : 'normal';
            }
    
            $query = "UPDATE " . $this->table_name . " 
                    SET tecnico_id = :tecnico_id, 
                        mes = :mes, 
                        anio = :anio, 
                        cantidad_cierres = :cantidad_cierres, 
                        estado = :estado,
                        justificacion = :justificacion, 
                        comentario_medida = :comentario_medida
                    WHERE id = :id";
    
            $stmt = $this->conn->prepare($query);
            
            // Asegurar que la justificación sea un valor válido del ENUM
            $justificacion = isset($justificacionMap[$this->justificacion]) 
                ? $justificacionMap[$this->justificacion] 
                : 'ninguna';
    
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':tecnico_id', $this->tecnico_id, PDO::PARAM_INT);
            $stmt->bindParam(':mes', $this->mes, PDO::PARAM_INT);
            $stmt->bindParam(':anio', $this->anio, PDO::PARAM_INT);
            $stmt->bindParam(':cantidad_cierres', $this->cantidad_cierres, PDO::PARAM_INT);
            $stmt->bindParam(':estado', $this->estado, PDO::PARAM_STR);
            $stmt->bindParam(':justificacion', $justificacion, PDO::PARAM_STR);
            $stmt->bindParam(':comentario_medida', $this->comentario_medida, PDO::PARAM_STR);
    
            return $stmt->execute();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function obtenerUno($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
    
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error al obtener registro: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerReporteAnual($anio, $meses_seleccionados = []) {
        $query = "SELECT 
                    u.id,
                    u.nombre as nombre_tecnico,
                    u.username as usuario,
                    u.codigo_tecnico as cod_tec";
    
        $meses_map = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
    
        // Ensure $meses_seleccionados is an array
        if (!is_array($meses_seleccionados)) {
            $meses_seleccionados = [];
        }
    
        // Si no hay meses seleccionados, mostrar todos
        $meses_a_mostrar = empty($meses_seleccionados) ? 
            $meses_map : 
            array_intersect_key($meses_map, array_combine($meses_seleccionados, $meses_seleccionados));
    
        foreach ($meses_map as $num => $mes) {
            $query .= ", MAX(CASE WHEN r.mes = $num THEN r.cantidad_cierres END) as $mes";
            $query .= ", MAX(CASE WHEN r.mes = $num THEN r.justificacion END) as justificacion_$mes";
            $query .= ", MAX(CASE WHEN r.mes = $num THEN r.comentario_medida END) as comentario_$mes";
        }
    
        $query .= ", SUM(r.cantidad_cierres) as total_anual
                    FROM usuarios u 
                    LEFT JOIN " . $this->table_name . " r ON u.id = r.tecnico_id AND r.anio = :anio
                    WHERE u.rol = 'tecnico'
                    GROUP BY u.id, u.nombre, u.username, u.codigo_tecnico
                    ORDER BY u.nombre";
    
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":anio", $anio);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error en reporte anual: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarCantidad($tecnico_id, $mes, $anio, $valor) {
        // Primero verificar si existe el registro
        $queryCheck = "SELECT id FROM " . $this->table_name . " 
                      WHERE tecnico_id = :tecnico_id 
                      AND mes = :mes 
                      AND anio = :anio";

        try {
            $stmt = $this->conn->prepare($queryCheck);
            $stmt->bindParam(":tecnico_id", $tecnico_id);
            $stmt->bindParam(":mes", $mes);
            $stmt->bindParam(":anio", $anio);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Actualizar registro existente
                $query = "UPDATE " . $this->table_name . " 
                         SET cantidad_cierres = :valor 
                         WHERE tecnico_id = :tecnico_id 
                         AND mes = :mes 
                         AND anio = :anio";
            } else {
                // Crear nuevo registro
                $query = "INSERT INTO " . $this->table_name . " 
                         (tecnico_id, mes, anio, cantidad_cierres, justificacion) 
                         VALUES (:tecnico_id, :mes, :anio, :valor, 'ninguna')";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":tecnico_id", $tecnico_id);
            $stmt->bindParam(":mes", $mes);
            $stmt->bindParam(":anio", $anio);
            $stmt->bindParam(":valor", $valor);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error en actualización: " . $e->getMessage());
            return false;
        }
    }

    private function determinarEstado($cantidad, $justificacion) {
        // Si hay justificación (reposo, vacaciones o permiso), usar verde claro
        if ($justificacion !== 'N') {
            return 'normal';    // verde claro para todos los casos justificados
        }
        
        // Sin justificación, el estado depende de la cantidad
        if ($cantidad >= 0 && $cantidad <= 10) {
            return 'muy_bajo';  // rojo oscuro
        } elseif ($cantidad <= 45) {
            return 'bajo';      // rojo claro
        } else {
            return 'normal';    // verde claro
        }
    }

    public function actualizarCierre($tecnico_id, $mes, $anio, $cantidad, $justificacion, $comentario, $cod_tec) {
        try {
            $this->conn->beginTransaction();

            // Actualizar código técnico si se proporciona
            if (!empty($cod_tec)) {
                $sqlUser = "UPDATE usuarios 
                           SET codigo_tecnico = :cod_tec 
                           WHERE id = :tecnico_id";
                $stmtUser = $this->conn->prepare($sqlUser);
                $stmtUser->bindParam(':cod_tec', $cod_tec);
                $stmtUser->bindParam(':tecnico_id', $tecnico_id);
                $stmtUser->execute();
            }

            // Determinar estado basado en cantidad y justificación
            $estado = $this->determinarEstado($cantidad, $justificacion);

            // Verificar si existe el registro
            $checkSql = "SELECT id FROM " . $this->table_name . " 
                        WHERE tecnico_id = :tecnico_id 
                        AND mes = :mes 
                        AND anio = :anio";
            
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->bindParam(':tecnico_id', $tecnico_id);
            $checkStmt->bindParam(':mes', $mes);
            $checkStmt->bindParam(':anio', $anio);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $sql = "UPDATE " . $this->table_name . " 
                       SET cantidad_cierres = :cantidad,
                           justificacion = :justificacion,
                           comentario_medida = :comentario,
                           estado = :estado
                       WHERE tecnico_id = :tecnico_id 
                       AND mes = :mes 
                       AND anio = :anio";
            } else {
                $sql = "INSERT INTO " . $this->table_name . " 
                       (tecnico_id, mes, anio, cantidad_cierres, justificacion, comentario_medida, estado) 
                       VALUES 
                       (:tecnico_id, :mes, :anio, :cantidad, :justificacion, :comentario, :estado)";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':tecnico_id', $tecnico_id);
            $stmt->bindParam(':mes', $mes);
            $stmt->bindParam(':anio', $anio);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':justificacion', $justificacion);
            $stmt->bindParam(':comentario', $comentario);
            $stmt->bindParam(':estado', $estado);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->conn->commit();
                return ['success' => true, 'mensaje' => 'Datos guardados correctamente'];
            } else {
                $this->conn->rollBack();
                return ['success' => false, 'mensaje' => 'Error al guardar los datos'];
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en actualizarCierre: " . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error en la base de datos'];
        }
    }

    private function obtenerNombreMes($numero) {
        $meses = [
            1 => "ENERO", 2 => "FEBRERO", 3 => "MARZO", 4 => "ABRIL",
            5 => "MAYO", 6 => "JUNIO", 7 => "JULIO", 8 => "AGOSTO",
            9 => "SEPTIEMBRE", 10 => "OCTUBRE", 11 => "NOVIEMBRE", 12 => "DICIEMBRE"
        ];
        return $meses[$numero] ?? '';
    }

    public function obtenerRegistroExistente($tecnico_id, $mes, $anio) {
        $query = "SELECT rc.cantidad_cierres, rc.justificacion, rc.comentario_medida, 
                  u.codigo_tecnico as cod_tec
                  FROM " . $this->table_name . " rc
                  LEFT JOIN usuarios u ON rc.tecnico_id = u.id
                  WHERE rc.tecnico_id = :tecnico_id 
                  AND rc.mes = :mes 
                  AND rc.anio = :anio";
    
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":tecnico_id", $tecnico_id, PDO::PARAM_INT);
            $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
            $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
            $stmt->execute();
            
            // Si no hay resultados o si el resultado es null
            if (!$result) {
                // Obtener solo el código del técnico
                $queryUser = "SELECT codigo_tecnico as cod_tec FROM usuarios WHERE id = :tecnico_id";
                $stmtUser = $this->conn->prepare($queryUser);
                $stmtUser->bindParam(":tecnico_id", $tecnico_id, PDO::PARAM_INT);
                $stmtUser->execute();
                $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                
                return [
                    'cantidad_cierres' => 0,
                    'justificacion' => 'N',
                    'comentario_medida' => '',
                    'cod_tec' => $userData['cod_tec'] ?? ''
                ];
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Error al obtener registro existente: " . $e->getMessage());
            return null;
        }
    }

    public function actualizarCodigoTecnico($tecnico_id, $codigo_tecnico) {
        try {
            $query = "UPDATE usuarios 
                     SET codigo_tecnico = :codigo_tecnico 
                     WHERE id = :tecnico_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":tecnico_id", $tecnico_id, PDO::PARAM_INT);
            $stmt->bindParam(":codigo_tecnico", $codigo_tecnico, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}