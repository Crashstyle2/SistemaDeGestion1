<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header("Location: ../../dashboard.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);
$message = '';

if($_POST) {
    $usuario->username = $_POST['username'];
    $usuario->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $usuario->nombre = $_POST['nombre'];
    $usuario->rol = $_POST['rol'];

    if($usuario->usernameExiste()) {
        $message = "Error: El nombre de usuario ya existe";
    } else if($usuario->crearUsuario()) {
        // Agregar registro de actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'usuarios',
            'crear',
            "Usuario creado - Username: {$_POST['username']}, Nombre: {$_POST['nombre']}, Rol: {$_POST['rol']}"
        );
        
        $_SESSION['mensaje'] = "Usuario creado exitosamente";
        header("Location: index.php");
        exit;
    } else {
        $message = "Error al crear el usuario";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Sistema UPS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .card {
            box-shadow: none;
            border: 1px solid rgba(0,0,0,.125);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,.125);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-bolt mr-2"></i>Sistema UPS
            </a>
            <div class="navbar-text text-white">
                <i class="fas fa-user mr-2"></i>
                Bienvenido, <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?>
                <span class="badge badge-light ml-2"><?php echo obtenerRolAmigable($_SESSION['user_rol']); ?></span>
            </div>
            <a href="../../dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-home"></i> Volver al Panel
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Crear Nuevo Usuario</h5>
            </div>
            <div class="card-body">
                <?php if($message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user mr-2"></i>Nombre de Usuario</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock mr-2"></i>Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card mr-2"></i>Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-tag mr-2"></i>Rol</label>
                        <select name="rol" class="form-control" required>
                            <option value="tecnico">Técnico</option>
                            <option value="administrador">Administrador</option>
                            <option value="administrativo">Administrativo</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="index.php" class="btn btn-secondary mr-2">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>