<?php
class Usuario {
    private $conn;
    public $id;
    public $username;
    public $password;
    public $nombre;
    public $rol;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function validarCredenciales() {
        try {
            $query = "SELECT * FROM usuarios WHERE username = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$this->username]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Agregar logging para depuración
            error_log("Intento de login - Usuario: " . $this->username);
            if ($user) {
                error_log("Usuario encontrado en BD");
                $passwordMatch = password_verify($this->password, $user['password']);
                error_log("Verificación de password: " . ($passwordMatch ? "correcta" : "incorrecta"));
            } else {
                error_log("Usuario no encontrado en BD");
            }
            
            if ($user && password_verify($this->password, $user['password'])) {
                $this->id = $user['id'];
                $this->nombre = $user['nombre'];
                $this->rol = $user['rol'];
                
                $this->registrarSesion($this->id);
                
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error en validación: " . $e->getMessage());
            return false;
        }
    }

    public function cambiarPassword($user_id, $nueva_password) {
        try {
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET password = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$password_hash, $user_id]);
        } catch(PDOException $e) {
            error_log("Error al cambiar contraseña: " . $e->getMessage());
            return false;
        }
    }

    public function crearUsuario() {
        try {
            if (empty($this->username) || empty($this->password) || empty($this->nombre) || empty($this->rol)) {
                throw new Exception("Todos los campos son requeridos");
            }

            if ($this->usernameExiste()) {
                throw new Exception("El nombre de usuario ya existe");
            }

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $query = "INSERT INTO usuarios (username, password, nombre, rol) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $this->username,
                $password_hash,
                $this->nombre,
                $this->rol
            ]);

            if ($result) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch(Exception $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function listarUsuarios() {
        try {
            $query = "SELECT id, username, nombre, rol FROM usuarios ORDER BY nombre";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error al listar usuarios: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorId($id = null) {
        try {
            if ($id === null || !is_numeric($id)) {
                error_log("ID de usuario inválido o no proporcionado: " . $id);
                return false;
            }
            
            $query = "SELECT id, username, nombre, rol FROM usuarios WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->nombre = $row['nombre'];
                $this->rol = $row['rol'];
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error al obtener usuario por ID: " . $e->getMessage());
            return false;
        }
    }
    
    public function usernameExiste($username = null) {
        try {
            $username = $username ?? $this->username;
            
            if(empty($username)) {
                return false;
            }

            $query = "SELECT id FROM usuarios WHERE username = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username]);
            
            return $stmt->fetch() ? true : false;
        } catch(PDOException $e) {
            error_log("Error al verificar username: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizarUsuario() {
        try {
            if (empty($this->username) || empty($this->nombre) || empty($this->rol) || empty($this->id)) {
                throw new Exception("Faltan datos requeridos para la actualización");
            }

            $checkQuery = "SELECT id FROM usuarios WHERE username = ? AND id != ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$this->username, $this->id]);
            
            if ($checkStmt->fetch()) {
                throw new Exception("El nombre de usuario ya está en uso");
            }

            $query = "UPDATE usuarios SET username = ?, nombre = ?, rol = ?";
            $params = [$this->username, $this->nombre, $this->rol];
            
            if (!empty($this->password)) {
                $query .= ", password = ?";
                $params[] = password_hash($this->password, PASSWORD_DEFAULT);
            }
            
            $query .= " WHERE id = ?";
            $params[] = $this->id;

            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);
            
        } catch(Exception $e) {
            error_log("Error en actualización: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function eliminar($id) {
        try {
            $query = "DELETE FROM usuarios WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function editarUsuario($id) {
        try {
            if (!is_numeric($id)) {
                throw new Exception("ID inválido");
            }
            
            $query = "SELECT * FROM usuarios WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->nombre = $row['nombre'];
                $this->rol = $row['rol'];
                return true;
            }
            
            throw new Exception("Usuario no encontrado");
            
        } catch (Exception $e) {
            error_log("Error en edición: " . $e->getMessage());
            return false;
        }
    }

    public function procesarAccion($accion, $id) {
        try {
            switch($accion) {
                case 'editar':
                    if($this->obtenerPorId($id)) {
                        header("Location: editar_usuario.php?id=" . $id);
                        exit();
                    }
                    break;
                
                case 'eliminar':
                    if($this->eliminar($id)) {
                        header("Location: index.php?mensaje=Usuario eliminado correctamente");
                        exit();
                    }
                    break;
                
                case 'cambiar_password':
                    // Actualizar la ruta para el cambio de contraseña del administrador
                    header("Location: ../../modulos/usuarios/cambiar_password.php?id=" . $id);
                    exit();
                    break;
                
                default:
                    throw new Exception("Acción no válida");
            }
            
        } catch(Exception $e) {
            error_log("Error al procesar acción: " . $e->getMessage());
            return false;
        }
    }

    // Agregar método para verificar contraseña actual
    public function verificarPassword($user_id, $current_password) {
        try {
            $query = "SELECT password FROM usuarios WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return password_verify($current_password, $row['password']);
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error al verificar contraseña: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerSesionesActivas() {
        try {
            $query = "SELECT * FROM sesiones_activas WHERE fecha_fin IS NULL ORDER BY fecha_inicio DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error al obtener sesiones activas: " . $e->getMessage());
            return false;
        }
    }

    public function registrarSesion($user_id) {
        try {
            $query = "INSERT INTO sesiones_activas (user_id, fecha_inicio) VALUES (?, NOW())";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id]);
        } catch(PDOException $e) {
            error_log("Error al registrar sesión: " . $e->getMessage());
            return false;
        }
    }

    public function cerrarSesion($user_id) {
        try {
            $query = "UPDATE sesiones_activas SET fecha_fin = NOW() WHERE user_id = ? AND fecha_fin IS NULL";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id]);
        } catch(PDOException $e) {
            error_log("Error al cerrar sesión: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerTecnicos() {
        $query = "SELECT id, nombre FROM usuarios WHERE rol = 'tecnico' ORDER BY nombre";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>