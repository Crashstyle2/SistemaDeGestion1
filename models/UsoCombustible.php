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
    public $litros_cargados;
    public $tipo_vehiculo;
    public $documento;
    public $fecha_carga;
    public $hora_carga;
    public $user_id;
    public $usuario_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear registro sin kilómetros
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (fecha, nombre_conductor, chapa, numero_baucher, litros_cargados, 
                   tipo_vehiculo, documento, fecha_carga, hora_carga, user_id, usuario_id) 
                  VALUES 
                  (:fecha, :conductor, :chapa, :numero_voucher, :litros_cargados,
                   :tipo_vehiculo, :documento, :fecha_carga, :hora_carga, :user_id, :usuario_id)";

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
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha_carga", $this->fecha_carga);
        $stmt->bindParam(":hora_carga", $this->hora_carga);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Agregar recorrido sin kilómetros
    public function agregarRecorrido($uso_combustible_id, $origen, $destino) {
        $query = "INSERT INTO " . $this->recorridos_table . " 
                  (uso_combustible_id, origen, destino) 
                  VALUES 
                  (:uso_combustible_id, :origen, :destino)";

        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $origen = htmlspecialchars(strip_tags($origen));
        $destino = htmlspecialchars(strip_tags($destino));
        
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->bindParam(":origen", $origen);
        $stmt->bindParam(":destino", $destino);

        return $stmt->execute();
    }

    // Leer todos los registros
    public function leerTodos() {
        $query = "SELECT uc.*, 
                         GROUP_CONCAT(CONCAT(ucr.origen, ' → ', ucr.destino) SEPARATOR '; ') as recorridos,
                         u.nombre as nombre_usuario
                  FROM " . $this->table_name . " uc
                  LEFT JOIN " . $this->recorridos_table . " ucr ON uc.id = ucr.uso_combustible_id
                  LEFT JOIN usuarios u ON uc.user_id = u.id OR uc.usuario_id = u.id
                  GROUP BY uc.id
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
}
?>

class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bindParam(":origen", $recorrido['origen']);
        $stmt->bindParam(":destino", $recorrido['destino']);
        $stmt->bindParam(":km_inicial", $recorrido['km_inicial']);
        $stmt->bindParam(":km_final", $recorrido['km_final']);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT 
                    uc.*, 
                    u.nombre as nombre_usuario
                FROM 
                    " . $this->table_name . " uc
                    LEFT JOIN usuarios u ON uc.usuario_id = u.id
                ORDER BY 
                    uc.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecorridos($uso_combustible_id) {
        $query = "SELECT * FROM " . $this->table_recorridos . "
                WHERE uso_combustible_id = :uso_combustible_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uso_combustible_id", $uso_combustible_id);
        $stmt->execute();

        return $stmt;
    }
}
?>
class UsoCombustible {
    private $conn;
    private $table_name = "uso_combustible";
    private $table_recorridos = "uso_combustible_recorridos";

    // Propiedades
    public $id;
    public $tipo_vehiculo;
    public $chapa;
    public $nombre_conductor;
    public $documento;
    public $fecha;
    public $numero_baucher;
    public $litros_cargados;
    public $usuario_id;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                (tipo_vehiculo, chapa, nombre_conductor, documento, fecha, 
                numero_baucher, litros_cargados, usuario_id)
                VALUES
                (:tipo_vehiculo, :chapa, :nombre_conductor, :documento, :fecha, 
                :numero_baucher, :litros_cargados, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y vincular datos
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->chapa = htmlspecialchars(strip_tags($this->chapa));
        $this->nombre_conductor = htmlspecialchars(strip_tags($this->nombre_conductor));
        $this->documento = htmlspecialchars(strip_tags($this->documento));
        $this->numero_baucher = htmlspecialchars(strip_tags($this->numero_baucher));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":chapa", $this->chapa);
        $stmt->bindParam(":nombre_conductor", $this->nombre_conductor);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_baucher", $this->numero_baucher);
        $stmt->bindParam(":litros_cargados", $this->litros_cargados);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarRecorrido($recorrido) {
        $query = "INSERT INTO " . $this->table_recorridos . "
                (uso_combustible_id, origen, destino, km_inicial, km_final)
                VALUES
                (:uso_combustible_id, :origen, :destino, :km_inicial, :km_final)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $recorrido['origen'] = htmlspecialchars(strip_tags($recorrido['origen']));
        $recorrido['destino'] = htmlspecialchars(strip_tags($recorrido['destino']));

        // Vincular valores
        $stmt->bindParam(":uso_combustible_id", $recorrido['uso_combustible_id']);
        $stmt->bind