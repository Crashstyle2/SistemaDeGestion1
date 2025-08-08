<?php
class MantenimientoUPS {
    private $conn;
    private $table_name = "mantenimiento_ups";

    public $patrimonio;
    public $cadena;
    public $sucursal;
    public $marca;
    public $tipo_bateria;
    public $cantidad;
    public $potencia_ups;
    public $fecha_ultimo_mantenimiento;
    public $fecha_proximo_mantenimiento;
    public $observaciones;
    public $estado_mantenimiento;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nuevo registro
    public function crear() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (patrimonio, cadena, sucursal, marca, tipo_bateria, 
                    cantidad, potencia_ups, fecha_ultimo_mantenimiento, 
                    fecha_proximo_mantenimiento, observaciones, estado_mantenimiento) 
                    VALUES 
                    (:patrimonio, :cadena, :sucursal, :marca, :tipo_bateria, 
                    :cantidad, :potencia_ups, :fecha_ultimo_mantenimiento, 
                    :fecha_proximo_mantenimiento, :observaciones, :estado_mantenimiento)";

            $stmt = $this->conn->prepare($query);

            // Calcular fecha próximo mantenimiento (2 años después del último)
            if($this->fecha_ultimo_mantenimiento) {
                $fecha_ultimo = new DateTime($this->fecha_ultimo_mantenimiento);
                $fecha_proximo = clone $fecha_ultimo;
                $fecha_proximo->modify('+2 years');
                $this->fecha_proximo_mantenimiento = $fecha_proximo->format('Y-m-d');
            }

            // Bind parameters
            $stmt->bindParam(":patrimonio", $this->patrimonio);
            $stmt->bindParam(":cadena", $this->cadena);
            $stmt->bindParam(":sucursal", $this->sucursal);
            $stmt->bindParam(":marca", $this->marca);
            $stmt->bindParam(":tipo_bateria", $this->tipo_bateria);
            $stmt->bindParam(":cantidad", $this->cantidad);
            $stmt->bindParam(":potencia_ups", $this->potencia_ups);
            $stmt->bindParam(":fecha_ultimo_mantenimiento", $this->fecha_ultimo_mantenimiento);
            $stmt->bindParam(":fecha_proximo_mantenimiento", $this->fecha_proximo_mantenimiento);
            $stmt->bindParam(":observaciones", $this->observaciones);
            $stmt->bindParam(":estado_mantenimiento", $this->estado_mantenimiento);

            if($stmt->execute()) {
                // Register the activity
                require_once 'RegistroActividad.php';
                $registro = new RegistroActividad($this->conn);
                $registro->registrar(
                    $_SESSION['user_id'],
                    'Mantenimiento UPS',
                    'Creación',
                    "Se creó nuevo registro UPS con patrimonio: {$this->patrimonio}"
                );
                return true;
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Error creating UPS maintenance: " . $e->getMessage());
            return false;
        }
    }

    // Leer todos los registros
    public function leerTodos() {
        $query = "SELECT * FROM mantenimiento_ups ORDER BY fecha_proximo_mantenimiento ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un registro
    public function leerUno($patrimonio = null) {
        $patrimonioToUse = $patrimonio ?? $this->patrimonio;
        
        $query = "SELECT * FROM " . $this->table_name . " WHERE patrimonio = :patrimonio";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":patrimonio", $patrimonioToUse);
        $stmt->execute();
        
        // Si se llama con parámetro, devolver el resultado directo
        if ($patrimonio !== null) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Si se llama sin parámetro, devolver el statement (compatibilidad con código existente)
        return $stmt;
    }

    // Actualizar registro
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                SET cadena = :cadena,
                    sucursal = :sucursal,
                    marca = :marca,
                    tipo_bateria = :tipo_bateria,
                    cantidad = :cantidad,
                    potencia_ups = :potencia_ups,
                    fecha_ultimo_mantenimiento = :fecha_ultimo_mantenimiento,
                    fecha_proximo_mantenimiento = :fecha_proximo_mantenimiento,
                    observaciones = :observaciones,
                    estado_mantenimiento = :estado_mantenimiento
                WHERE patrimonio = :patrimonio";

        $stmt = $this->conn->prepare($query);

        // Calcular fecha próximo mantenimiento (2 años después del último)
        if($this->fecha_ultimo_mantenimiento) {
            $fecha_ultimo = new DateTime($this->fecha_ultimo_mantenimiento);
            $fecha_proximo = clone $fecha_ultimo;
            $fecha_proximo->modify('+2 years');
            $this->fecha_proximo_mantenimiento = $fecha_proximo->format('Y-m-d');
        }

        $stmt->bindParam(":patrimonio", $this->patrimonio);
        $stmt->bindParam(":cadena", $this->cadena);
        $stmt->bindParam(":sucursal", $this->sucursal);
        $stmt->bindParam(":marca", $this->marca);
        $stmt->bindParam(":tipo_bateria", $this->tipo_bateria);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":potencia_ups", $this->potencia_ups);
        $stmt->bindParam(":fecha_ultimo_mantenimiento", $this->fecha_ultimo_mantenimiento);
        $stmt->bindParam(":fecha_proximo_mantenimiento", $this->fecha_proximo_mantenimiento);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":estado_mantenimiento", $this->estado_mantenimiento);

        return $stmt->execute();
    }

    // Eliminar registro
    public function eliminar() {
        try {
            // Comenzar transacción
            $this->conn->beginTransaction();
            
            // Primero eliminar los registros de mantenimiento relacionados
            $query_mantenimientos = "DELETE FROM mantenimientos WHERE patrimonio_ups = :patrimonio";
            $stmt = $this->conn->prepare($query_mantenimientos);
            $stmt->bindParam(":patrimonio", $this->patrimonio);
            $stmt->execute();
            
            // Luego eliminar el UPS
            $query = "DELETE FROM " . $this->table_name . " WHERE patrimonio = :patrimonio";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":patrimonio", $this->patrimonio);
            $stmt->execute();
            
            // Confirmar transacción
            $this->conn->commit();
            return true;
        } catch(PDOException $e) {
            // Revertir cambios si hay error
            $this->conn->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // Agregar este método nuevo
    public function realizarMantenimiento($patrimonio, $fecha_mantenimiento, $observaciones, $estado, $usuario_mantenimiento) {
        try {
            $this->conn->beginTransaction();
            
            // Calcular la fecha del próximo mantenimiento (2 años después)
            $fecha_proximo = date('Y-m-d', strtotime($fecha_mantenimiento . ' +2 years'));
            
            // Actualizar el UPS primero
            $query_ups = "UPDATE " . $this->table_name . "
                    SET fecha_ultimo_mantenimiento = :fecha_mantenimiento,
                        fecha_proximo_mantenimiento = :fecha_proximo,
                        estado_mantenimiento = :estado
                    WHERE patrimonio = :patrimonio";
            
            $stmt_ups = $this->conn->prepare($query_ups);
            $stmt_ups->bindParam(":fecha_mantenimiento", $fecha_mantenimiento);
            $stmt_ups->bindParam(":fecha_proximo", $fecha_proximo);
            $stmt_ups->bindParam(":estado", $estado);
            $stmt_ups->bindParam(":patrimonio", $patrimonio);
            
            if (!$stmt_ups->execute()) {
                throw new PDOException("Error al actualizar UPS");
            }
            
            // Luego guardar en el historial
            $query = "INSERT INTO mantenimientos 
                    (patrimonio_ups, fecha_mantenimiento, observaciones, estado, usuario_mantenimiento) 
                    VALUES 
                    (:patrimonio, :fecha, :observaciones, :estado, :usuario)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":patrimonio", $patrimonio);
            $stmt->bindParam(":fecha", $fecha_mantenimiento);
            $stmt->bindParam(":observaciones", $observaciones);
            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":usuario", $usuario_mantenimiento);
            
            if (!$stmt->execute()) {
                throw new PDOException("Error al guardar en historial");
            }
            
            $this->conn->commit();
            return true;
            
        } catch(PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en mantenimiento: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerHistorial($patrimonio) {
        $query = "SELECT * FROM mantenimientos 
                WHERE patrimonio_ups = :patrimonio 
                ORDER BY fecha_mantenimiento DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":patrimonio", $patrimonio);
        $stmt->execute();
        
        return $stmt;
    }

    public function agregarMantenimiento($datos) {
        $query = "INSERT INTO mantenimientos 
                (patrimonio_ups, fecha_mantenimiento, observaciones, estado, usuario_mantenimiento) 
                VALUES 
                (:patrimonio, :fecha, :observaciones, :estado, :usuario)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":patrimonio", $datos['patrimonio_ups']);
        $stmt->bindParam(":fecha", $datos['fecha_mantenimiento']);
        $stmt->bindParam(":observaciones", $datos['observaciones']);
        $stmt->bindParam(":estado", $datos['estado']);
        $stmt->bindParam(":usuario", $datos['usuario_mantenimiento']);
        
        return $stmt->execute();
    }

    public function getLastError() {
        return $this->conn->errorInfo()[2];
    }
    
    // Método para contar total de registros
    public function contarTodos($busqueda = '') {
        if (!empty($busqueda)) {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                     WHERE cadena LIKE :busqueda 
                     OR sucursal LIKE :busqueda 
                     OR marca LIKE :busqueda 
                     OR tipo_bateria LIKE :busqueda 
                     OR potencia_ups LIKE :busqueda 
                     OR observaciones LIKE :busqueda 
                     OR estado_mantenimiento LIKE :busqueda";
            $stmt = $this->conn->prepare($query);
            $busqueda_param = "%{$busqueda}%";
            $stmt->bindParam(":busqueda", $busqueda_param);
        } else {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    // Método para leer con paginación
    public function leerConPaginacion($limite = 25, $offset = 0, $busqueda = '') {
        if (!empty($busqueda)) {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE cadena LIKE :busqueda 
                     OR sucursal LIKE :busqueda 
                     OR marca LIKE :busqueda 
                     OR tipo_bateria LIKE :busqueda 
                     OR potencia_ups LIKE :busqueda 
                     OR observaciones LIKE :busqueda 
                     OR estado_mantenimiento LIKE :busqueda
                     ORDER BY fecha_proximo_mantenimiento ASC 
                     LIMIT :limite OFFSET :offset";
            $stmt = $this->conn->prepare($query);
            $busqueda_param = "%{$busqueda}%";
            $stmt->bindParam(":busqueda", $busqueda_param);
        } else {
            $query = "SELECT * FROM " . $this->table_name . " 
                     ORDER BY fecha_proximo_mantenimiento ASC 
                     LIMIT :limite OFFSET :offset";
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
}
?>