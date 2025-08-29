<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../includes/session_control.php';

$database = new Database();
$db = $database->getConnection();
$sessionControl = new SessionControl($db);

$sesiones = $sessionControl->obtenerSesionesActivas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sesiones Activas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #2980b9, #8e44ad);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #4a90e2;
            color: white;
        }
        .badge {
            padding: 8px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users mr-2"></i>Sesiones Activas</h2>
            <a href="../../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Volver al Panel
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th><i class="fas fa-user mr-2"></i>Usuario</th>
                        <th><i class="fas fa-clock mr-2"></i>Última Actividad</th>
                        <th><i class="fas fa-signal mr-2"></i>Estado</th>
                        <th><i class="fas fa-cog mr-2"></i>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sesiones as $sesion): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($sesion['nombre']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($sesion['rol']); ?></small>
                            </td>
                            <td>
                                <?php 
                                $fecha = new DateTime($sesion['last_activity']);
                                echo $fecha->format('d/m/Y H:i:s'); 
                                ?>
                            </td>
                            <td>
                                <?php 
                                $tiempo = strtotime($sesion['last_activity']);
                                $diferencia = time() - $tiempo;
                                // Aumentamos el tiempo a 30 minutos (1800 segundos)
                                if($diferencia <= 1800): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle mr-1"></i>Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times-circle mr-1"></i>Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="cerrarSesion(<?php echo $sesion['id']; ?>)">
                                    <i class="fas fa-sign-out-alt mr-1"></i>Cerrar Sesión
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cerrarSesion(id) {
        if(confirm('¿Está seguro de cerrar esta sesión?')) {
            $.post('cerrar_sesion.php', {id: id}, function(response) {
                if(response.success) {
                    location.reload();
                }
            });
        }
    }
    </script>
</body>
</html>