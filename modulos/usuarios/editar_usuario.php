<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header("Location: ../../dashboard.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

if(isset($_GET['id'])) {
    $usuario->obtenerPorId($_GET['id']);
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario->id = $_POST['id'];
    $usuario->username = $_POST['username'];
    $usuario->nombre = $_POST['nombre'];
    $usuario->rol = $_POST['rol'];
    $usuario->estado = $_POST['estado'];
    
    if($usuario->actualizarUsuario()) {
        // Agregar registro de actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'usuarios',
            'editar',
            "Usuario actualizado - Username: {$_POST['username']}, Nombre: {$_POST['nombre']}, Rol: {$_POST['rol']}, Estado: {$_POST['estado']}"
        );

        $_SESSION['mensaje'] = "Usuario actualizado correctamente";
        header("Location: index.php");
        exit;
    } else {
        $error = "Error al actualizar el usuario";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema UPS</title>
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
            </div>
            <a href="../../dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-home"></i> Volver al Panel
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit mr-2"></i>Editar Usuario - <?php echo htmlspecialchars($usuario->nombre); ?></h5>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario->id); ?>">
                    
                    <div class="form-group">
                        <label for="username">Nombre de Usuario:</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($usuario->username); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre Completo:</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario->nombre); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select name="rol" id="rol" class="form-control" required>
                            <option value="administrador" <?php echo $usuario->rol === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                            <option value="tecnico" <?php echo $usuario->rol === 'tecnico' ? 'selected' : ''; ?>>Técnico</option>
                            <option value="analista" <?php echo $usuario->rol === 'analista' ? 'selected' : ''; ?>>Analista</option>
                        <option value="supervisor" <?php echo $usuario->rol === 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select class="form-control" id="estado" name="estado" required>
                            <option value="activo" <?php echo $usuario->estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo $usuario->estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>

                    <div class="form-group text-right">
                        <a href="index.php" class="btn btn-secondary mr-2">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>