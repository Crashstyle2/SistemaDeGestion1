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
$sesiones = $usuario->obtenerSesionesActivas();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesiones Activas - Sistema UPS</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Sesiones Activas</h2>
            </div>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Inicio de Sesión</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($sesiones):
                                while ($row = $sesiones->fetch(PDO::FETCH_ASSOC)):
                                    $usuarioSesion = new Usuario($db);
                                    $usuarioSesion->obtenerPorId($row['user_id']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuarioSesion->nombre); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($row['fecha_inicio'])); ?></td>
                                    <td>
                                        <span class="badge badge-success">Activa</span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-danger" onclick="cerrarSesion(<?php echo $row['user_id']; ?>)">
                                            <i class="fas fa-power-off mr-2"></i>Cerrar Sesión
                                        </button>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cerrarSesion(userId) {
        if(confirm('¿Está seguro de cerrar esta sesión?')) {
            $.post('cerrar_sesion.php', {user_id: userId}, function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Error al cerrar la sesión');
                }
            });
        }
    }
    </script>
</body>
</html>