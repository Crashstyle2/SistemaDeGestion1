<?php
class ReclamosZonas {
    private $conn;
    private $table_name = "reclamos_zonas";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerReporteMensual($mes_actual, $mes_anterior, $anio) {
        $query = "SELECT 
                    rz1.zona,
                    COALESCE(rz2.cantidad_reclamos, 0) as reporte_anterior,
                    COALESCE(rz1.cantidad_reclamos, 0) as reporte_actual
                 FROM 
                    (SELECT DISTINCT zona FROM " . $this->table_name . ") zonas
                 LEFT JOIN " . $this->table_name . " rz1 
                    ON zonas.zona = rz1.zona 
                    AND rz1.mes = :mes_actual 
                    AND rz1.anio = :anio
                 LEFT JOIN " . $this->table_name . " rz2 
                    ON zonas.zona = rz2.zona 
                    AND rz2.mes = :mes_anterior 
                    AND rz2.anio = :anio
                 ORDER BY zonas.zona";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":mes_actual", $mes_actual);
        $stmt->bindParam(":mes_anterior", $mes_anterior);
        $stmt->bindParam(":anio", $anio);
        $stmt->execute();

        return $stmt;
    }

    public function actualizarReclamos($zona, $mes, $anio, $cantidad) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (zona, mes, anio, cantidad_reclamos) 
                     VALUES (:zona, :mes, :anio, :cantidad)
                     ON DUPLICATE KEY UPDATE 
                     cantidad_reclamos = :cantidad";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":zona", $zona);
            $stmt->bindParam(":mes", $mes);
            $stmt->bindParam(":anio", $anio);
            $stmt->bindParam(":cantidad", $cantidad);

            if($stmt->execute()) {
                return ['success' => true, 'mensaje' => 'Datos actualizados correctamente'];
            }
            return ['success' => false, 'mensaje' => 'Error al actualizar los datos'];
        } catch(PDOException $e) {
            error_log("Error en actualizarReclamos: " . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error en la base de datos'];
        }
    }
}