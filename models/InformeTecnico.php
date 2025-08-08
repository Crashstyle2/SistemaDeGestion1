<?php
class InformeTecnico {
    private $conn;
    private $table_name = "informe_tecnico";

    // Propiedades según la estructura real de la tabla
    public $id;
    public $local;
    public $sector;
    public $equipo_asistido;
    public $orden_trabajo;
    public $patrimonio;
    public $jefe_turno;
    public $observaciones;
    public $firma_digital;
    public $fecha_creacion;
    public $tecnico_id;
    public $foto_trabajo;
    public $foto_ruta;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($datos = null) {
        // Si se pasan datos como parámetro, establecer las propiedades
        if($datos && is_array($datos)) {
            $this->local = $datos['local'] ?? '';
            $this->sector = $datos['sector'] ?? '';
            $this->equipo_asistido = $datos['equipo_asistido'] ?? '';
            $this->orden_trabajo = $datos['orden_trabajo'] ?? '';
            $this->patrimonio = $datos['patrimonio'] ?? '';
            $this->jefe_turno = $datos['jefe_turno'] ?? '';
            $this->observaciones = $datos['observaciones'] ?? '';
            $this->firma_digital = $datos['firma_digital'] ?? null;
            $this->tecnico_id = $datos['tecnico_id'] ?? null;
            $this->foto_trabajo = $datos['foto_trabajo'] ?? null;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                (local, sector, equipo_asistido, orden_trabajo, patrimonio, jefe_turno, 
                 observaciones, firma_digital, tecnico_id, foto_trabajo) 
                VALUES 
                (:local, :sector, :equipo_asistido, :orden_trabajo, :patrimonio, :jefe_turno,
                 :observaciones, :firma_digital, :tecnico_id, :foto_trabajo)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos - verificar que no sean null antes de strip_tags
        $this->local = $this->local ? htmlspecialchars(strip_tags($this->local)) : '';
        $this->sector = $this->sector ? htmlspecialchars(strip_tags($this->sector)) : '';
        $this->equipo_asistido = $this->equipo_asistido ? htmlspecialchars(strip_tags($this->equipo_asistido)) : '';
        $this->orden_trabajo = $this->orden_trabajo ? htmlspecialchars(strip_tags($this->orden_trabajo)) : '';
        $this->patrimonio = $this->patrimonio ? htmlspecialchars(strip_tags($this->patrimonio)) : '';
        $this->jefe_turno = $this->jefe_turno ? htmlspecialchars(strip_tags($this->jefe_turno)) : '';
        $this->observaciones = $this->observaciones ? htmlspecialchars(strip_tags($this->observaciones)) : '';
        
        // Manejar firma_digital y foto_trabajo que pueden ser null
        $firma_digital_value = $this->firma_digital ?: null;
        $foto_trabajo_value = $this->foto_trabajo ?: null;

        // Vincular valores
        $stmt->bindParam(":local", $this->local);
        $stmt->bindParam(":sector", $this->sector);
        $stmt->bindParam(":equipo_asistido", $this->equipo_asistido);
        $stmt->bindParam(":orden_trabajo", $this->orden_trabajo);
        $stmt->bindParam(":patrimonio", $this->patrimonio);
        $stmt->bindParam(":jefe_turno", $this->jefe_turno);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":firma_digital", $firma_digital_value);
        $stmt->bindParam(":tecnico_id", $this->tecnico_id);
        $stmt->bindParam(":foto_trabajo", $foto_trabajo_value);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Método para guardar fotos como archivos
    public function guardarFotos($informe_id, $fotos) {
        error_log("=== INICIO guardarFotos ===");
        error_log("Informe ID: " . $informe_id);
        error_log("Número de fotos recibidas: " . count($fotos));
        
        foreach($fotos as $index => $foto) {
            error_log("--- Procesando foto " . ($index + 1) . " ---");
            error_log("Datos de foto: " . print_r($foto, true));
            
            if (isset($foto['tmp_name']) && isset($foto['error']) && $foto['error'] === UPLOAD_ERR_OK) {
                error_log("Foto válida, procesando...");
                
                // Crear directorio si no existe
                $directorio = __DIR__ . '/../img/informe_tecnicos/fotos/';
                error_log("Directorio: " . $directorio);
                
                if (!file_exists($directorio)) {
                    error_log("Creando directorio...");
                    mkdir($directorio, 0755, true);
                }
                
                // Generar nombre único
                $extension = 'jpg'; // Valor por defecto
                if (isset($foto['name']) && !empty($foto['name'])) {
                    $info = pathinfo($foto['name']);
                    $extension = isset($info['extension']) ? strtolower($info['extension']) : 'jpg';
                }
                error_log("Extensión detectada: " . $extension);
                
                // Validar extensión
                $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $extensiones_permitidas)) {
                    $extension = 'jpg';
                }
                
                $filename = 'informe_' . $informe_id . '_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $directorio . $filename;
                error_log("Archivo destino: " . $filepath);
                
                // Mover archivo
                if(move_uploaded_file($foto['tmp_name'], $filepath)) {
                    error_log("Archivo movido exitosamente");
                    
                    // Validar tipo para que coincida con el enum de la BD
                    $tipo_valido = in_array($foto['tipo'] ?? '', ['antes', 'despues']) ? $foto['tipo'] : 'antes';
                    error_log("Tipo validado: " . $tipo_valido);
                    
                    $query = "INSERT INTO fotos_informe_tecnico (informe_id, foto_ruta, descripcion, tipo) VALUES (?, ?, ?, ?)";
                    $stmt = $this->conn->prepare($query);
                    
                    try {
                        $result = $stmt->execute([
                            $informe_id, 
                            $filename, 
                            $foto['descripcion'] ?? '', 
                            $tipo_valido
                        ]);
                        
                        if ($result) {
                            error_log("Foto guardada en BD exitosamente. ID: " . $this->conn->lastInsertId());
                        } else {
                            error_log("Error al ejecutar query: " . print_r($stmt->errorInfo(), true));
                        }
                    } catch(PDOException $e) {
                        error_log("Error PDO al guardar foto en BD: " . $e->getMessage());
                        // Si hay error, eliminar el archivo subido
                        if(file_exists($filepath)) {
                            unlink($filepath);
                            error_log("Archivo eliminado por error en BD");
                        }
                    }
                } else {
                    error_log("Error al mover archivo desde: " . $foto['tmp_name'] . " a: " . $filepath);
                }
            } else {
                error_log("Foto inválida o con errores:");
                error_log("tmp_name existe: " . (isset($foto['tmp_name']) ? 'Sí' : 'No'));
                error_log("error existe: " . (isset($foto['error']) ? 'Sí' : 'No'));
                error_log("error value: " . ($foto['error'] ?? 'N/A'));
            }
        }
        error_log("=== FIN guardarFotos ===");
    }

    // Método para obtener fotos (compatible con Base64 y rutas)
    public function obtenerFotos($informe_id) {
        $query = "SELECT * FROM fotos_informe_tecnico WHERE informe_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$informe_id]);
        return $stmt;
    }

    public function leerUno() {
        $query = "SELECT it.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " it 
                 LEFT JOIN usuarios u ON it.tecnico_id = u.id 
                 WHERE it.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    public function leerTodos($tecnico_id = null, $limit = null, $offset = null, $search_term = null) {
        $query = "SELECT it.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " it 
                 LEFT JOIN usuarios u ON it.tecnico_id = u.id";
        
        $conditions = [];
        $params = [];
        
        if($tecnico_id) {
            $conditions[] = "it.tecnico_id = ?";
            $params[] = $tecnico_id;
        }
        
        if($search_term && !empty(trim($search_term))) {
            $conditions[] = "(it.local LIKE ? OR it.sector LIKE ? OR it.equipo_asistido LIKE ? OR it.observaciones LIKE ?)";
            $searchParam = "%{$search_term}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY it.fecha_creacion DESC";
        
        // Agregar LIMIT y OFFSET de forma segura
        if($limit && is_numeric($limit) && $limit > 0) {
            $query .= " LIMIT " . (int)$limit;
            
            if($offset && is_numeric($offset) && $offset >= 0) {
                $query .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Vincular solo los parámetros de WHERE
        for($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function eliminar($id) {
        // Primero eliminar las fotos asociadas
        $query_fotos = "DELETE FROM fotos_informe_tecnico WHERE informe_id = ?";
        $stmt_fotos = $this->conn->prepare($query_fotos);
        $stmt_fotos->execute([$id]);
        
        // Luego eliminar el informe
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function contarTodos($tecnico_id = null, $search_term = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " it";
        
        $conditions = [];
        $params = [];
        
        if($tecnico_id) {
            $conditions[] = "it.tecnico_id = ?";
            $params[] = $tecnico_id;
        }
        
        if($search_term && !empty(trim($search_term))) {
            $conditions[] = "(it.local LIKE ? OR it.sector LIKE ? OR it.equipo_asistido LIKE ? OR it.observaciones LIKE ?)";
            $searchParam = "%{$search_term}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if(!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->conn->prepare($query);
        
        for($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>