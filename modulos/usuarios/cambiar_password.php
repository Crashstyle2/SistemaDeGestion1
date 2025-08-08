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

if(isset($_GET['id'])) {
    $usuario->id = $_GET['id'];
    if($_POST) {
        $nueva_password = $_POST['nueva_password'];
        $confirmar_password = $_POST['confirmar_password'];
        
        if($nueva_password === $confirmar_password) {
            if($usuario->cambiarPassword($_GET['id'], $nueva_password)) {
                header("Location: index.php");
                exit;
            } else {
                $message = "Error al actualizar la contraseña";
            }
        } else {
            $message = "Las contraseñas no coinciden";
        }
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Sistema UPS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin: 40px auto;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Cambiar Contraseña</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>

        <?php if($message): ?>
            <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nueva Contraseña</label>
                <input type="password" name="nueva_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Confirmar Contraseña</label>
                <input type="password" name="confirmar_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Guardar Cambios
            </button>
        </form>
    </div>
</body>
</html>