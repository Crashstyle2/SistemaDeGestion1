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
                (local, sector, documento, foto, jefe_encargado, observaciones, firma_digital, tecnico_id)
                VALUES
                (:local, :sector, :documento, :foto, :jefe_encargado, :observaciones, :firma_digital, :tecnico_id)";

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
        $stmt->bindParam(":jefe_encargado", $this->jefe_encargado);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":firma_digital", $this->firma_digital);
        $stmt->bindParam(":tecnico_id", $this->tecnico_id);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT ar.*, u.nombre as nombre_tecnico 
                 FROM " . $this->table_name . " ar 
                 LEFT JOIN usuarios u ON ar.tecnico_id = u.id 
                 ORDER BY ar.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
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
}