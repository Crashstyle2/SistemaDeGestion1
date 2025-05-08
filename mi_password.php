<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';
require_once 'models/Usuario.php';

$message = '';
$type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password !== $confirm_password) {
        $message = "Las contraseñas nuevas no coinciden";
        $type = "danger";
    } else if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Todos los campos son requeridos";
        $type = "danger";
    } else {
        // Verificar la contraseña actual
        if($usuario->verificarPassword($_SESSION['user_id'], $current_password)) {
            // Cambiar la contraseña
            if($usuario->cambiarPassword($_SESSION['user_id'], $new_password)) {
                $message = "Contraseña actualizada correctamente";
                $type = "success";
            } else {
                $message = "Error al actualizar la contraseña";
                $type = "danger";
            }
        } else {
            $message = "La contraseña actual es incorrecta";
            $type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Cambiar Contraseña</h2>
        
        <?php if($message): ?>
        <div class="alert alert-<?php echo $type; ?>" role="alert">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Contraseña Actual</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Nueva Contraseña</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Confirmar Nueva Contraseña</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
            <a href="dashboard.php" class="btn btn-secondary">Volver</a>
        </form>
    </div>
</body>
</html>