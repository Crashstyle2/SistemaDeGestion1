public function crear() {
    try {
        $this->conn->beginTransaction();

        $query = "INSERT INTO ups (nombre, ubicacion, modelo, numero_serie, capacidad, fecha_instalacion, estado) 
                 VALUES (:nombre, :ubicacion, :modelo, :numero_serie, :capacidad, :fecha_instalacion, :estado)";
        
        $stmt = $this->conn->prepare($query);
        
        // Vincular valores
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":ubicacion", $this->ubicacion);
        $stmt->bindParam(":modelo", $this->modelo);
        $stmt->bindParam(":numero_serie", $this->numero_serie);
        $stmt->bindParam(":capacidad", $this->capacidad);
        $stmt->bindParam(":fecha_instalacion", $this->fecha_instalacion);
        $stmt->bindParam(":estado", $this->estado);
        
        $result = $stmt->execute();
        
        if($result) {
            $ups_id = $this->conn->lastInsertId();
            
            // Registrar la actividad
            require_once 'RegistroActividad.php';
            $registro = new RegistroActividad($this->conn);
            $registro->registrar(
                $_SESSION['user_id'],
                'UPS',
                'Creación',
                "Se creó un nuevo UPS: {$this->nombre} - Serie: {$this->numero_serie}"
            );
            
            $this->conn->commit();
            return true;
        }
        
        $this->conn->rollBack();
        return false;
        
    } catch(PDOException $e) {
        $this->conn->rollBack();
        return false;
    }
}