<?php
class AcuseRecibo {
    private $conn;
    private $table_name = "acuse_recibo";

    // Propiedades
    public $id;
    public $local;
    public $sector;
    public $documento;
    public $foto;
    public $foto_ruta;
    public $jefe_encargado;
    public $observaciones;
    public $firma_digital;
    public $fecha_creacion;
    public $tecnico_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (local, sector, documento, foto, foto_ruta, jefe_encargado, observaciones, firma_digital, tecnico_id)
                VALUES
                (:local, :sector, :documento, :foto, :foto_ruta, :jefe_encargado, :observaciones, :firma_digital, :tecnico_id)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->local = htmlspecialchars(strip_tags($this->local));
        $this->sector = htmlspecialchars(strip_tags($this->sector));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->jefe_encargado = htmlspecialchars(strip_tags($this->jefe_encargado));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones));

        // Vincular valores
        $stmt->bindParam(":local", $this->local);
        $stmt->bindParam(":sector", $this->sector);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":foto", $this->foto);
        $stmt->bindParam(":foto_ruta", $this->foto_ruta);
        $stmt->bindParam(":jefe_encargado", $this->jefe_encargado);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":firma_digital", $this->firma_digital);
        $stmt->bindParam(":tecnico_id", $this->tecnico_id);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT a.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " a 
                 LEFT JOIN usuarios u ON a.tecnico_id = u.id";
        
        // Filtrar por técnico si se especifica
        if($this->tecnico_id) {
            $query .= " WHERE a.tecnico_id = :tecnico_id";
        }
        
        $query .= " ORDER BY a.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if($this->tecnico_id) {
            $stmt->bindParam(":tecnico_id", $this->tecnico_id);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function leerUno() {
        $query = "SELECT ar.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " ar 
                 LEFT JOIN usuarios u ON ar.tecnico_id = u.id 
                 WHERE ar.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                SET local = :local,
                    sector = :sector,
                    documento = :documento,
                    foto = :foto,
                    foto_ruta = :foto_ruta,
                    jefe_encargado = :jefe_encargado,
                    observaciones = :observaciones,
                    firma_digital = :firma_digital
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Vincular valores
        $stmt->bindParam(":local", $this->local);
        $stmt->bindParam(":sector", $this->sector);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":foto", $this->foto);
        $stmt->bindParam(":foto_ruta", $this->foto_ruta);
        $stmt->bindParam(":jefe_encargado", $this->jefe_encargado);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":firma_digital", $this->firma_digital);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function eliminar($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
    
    // Método para guardar foto como archivo
    public function guardarFoto($archivo_subido, $acuse_id) {
        if (!isset($archivo_subido) || $archivo_subido['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Crear directorio si no existe
        $directorio = '../../img/acuse_recibo/fotos/';
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $extension = pathinfo($archivo_subido['name'], PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg';
        }
        
        $nombre_archivo = 'acuse_' . $acuse_id . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio . $nombre_archivo;
        
        // Mover archivo subido
        if (move_uploaded_file($archivo_subido['tmp_name'], $ruta_completa)) {
            return $nombre_archivo;
        }
        
        return false;
    }
    
    // Método para guardar foto de cámara como archivo
    public function guardarFotoCamara($foto_base64, $acuse_id) {
        if (empty($foto_base64)) {
            return false;
        }
        
        // Crear directorio si no existe
        $directorio = '../../img/acuse_recibo/fotos/';
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Limpiar el Base64 y decodificar
        $foto_data = str_replace('data:image/jpeg;base64,', '', $foto_base64);
        $foto_decoded = base64_decode($foto_data);
        
        if ($foto_decoded === false) {
            return false;
        }
        
        // Generar nombre único para el archivo
        $nombre_archivo = 'acuse_cam_' . $acuse_id . '_' . time() . '.jpg';
        $ruta_completa = $directorio . $nombre_archivo;
        
        // Guardar archivo
        if (file_put_contents($ruta_completa, $foto_decoded)) {
            return $nombre_archivo;
        }
        
        return false;
    }
}