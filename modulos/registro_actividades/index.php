<?php
require_once '../../config/session_handler.php';
require_once '../../config/database.php';
require_once '../../models/RegistroActividad.php';

$database = new Database();
$db = $database->getConnection();
$registro = new RegistroActividad($db);

$actividades = $registro->obtenerRegistros();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Actividades</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .input-group {
            background: white;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 300px;
        }
        #searchInput {
            border: none;
            box-shadow: none;
            padding: 6px 15px;
            font-size: 14px;
            border-radius: 20px;
        }
        #searchInput:focus {
            outline: none;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <?php include_once '../../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-history mr-2"></i>Registro de Actividades</h2>
            <a href="/MantenimientodeUPS/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Volver al Inicio
            </a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="mb-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Buscar en cualquier campo...">
                    </div>
                    
                    <table class="table table-hover" id="dataTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Módulo</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividades as $actividad): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($actividad['fecha_hora'])); ?></td>
                                <td><?php echo htmlspecialchars($actividad['nombre_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($actividad['modulo']); ?></td>
                                <td><?php echo htmlspecialchars($actividad['accion']); ?></td>
                                <td><?php echo htmlspecialchars($actividad['descripcion']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        $("#searchInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#dataTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
    </script>
</body>
</html>
<style>
    #searchInput {
        background: white;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        max-width: 300px;
        border: none;
        padding: 6px 15px;
        font-size: 14px;
    }
    #searchInput:focus {
        outline: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
</style>