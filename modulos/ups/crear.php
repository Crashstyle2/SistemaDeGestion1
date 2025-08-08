<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/MantenimientoUPS.php';

$database = new Database();
$db = $database->getConnection();
$mantenimiento = new MantenimientoUPS($db);
$message = '';
$type = '';

if($_POST) {
    try {
        // Validaciones específicas
        if(empty($_POST['patrimonio'])) {
            throw new Exception("El campo Patrimonio es obligatorio");
        }
        
        $mantenimiento->patrimonio = $_POST['patrimonio'];
        $mantenimiento->cadena = $_POST['cadena'];
        $mantenimiento->sucursal = htmlspecialchars($_POST['sucursal'], ENT_QUOTES, 'UTF-8');
        $mantenimiento->marca = $_POST['marca'];
        $mantenimiento->tipo_bateria = $_POST['tipo_bateria'];
        $mantenimiento->cantidad = $_POST['cantidad'];
        $mantenimiento->potencia_ups = $_POST['potencia_ups'];
        $mantenimiento->fecha_ultimo_mantenimiento = $_POST['fecha_ultimo_mantenimiento'];
        $mantenimiento->fecha_proximo_mantenimiento = $_POST['fecha_proximo_mantenimiento'];
        $mantenimiento->estado_mantenimiento = $_POST['estado_mantenimiento'];
        $mantenimiento->observaciones = $_POST['observaciones'];

        if($mantenimiento->crear()) {
            // Agregar registro de actividad
            require_once '../../config/ActivityLogger.php';
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'ups',
                'crear',
                "Nuevo UPS registrado - Patrimonio: {$_POST['patrimonio']}, Sucursal: {$_POST['sucursal']}"
            );
            
            header("Location: index.php?mensaje=UPS registrado correctamente&tipo=success");
            exit;
        } else {
            // Obtener el error específico de la base de datos
            $error = $mantenimiento->getLastError();
            $message = "Error al registrar el UPS: " . ($error ? $error : "El patrimonio ya existe o hay campos inválidos");
            $type = "danger";
        }
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
        $type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Registro - Sistema de Mantenimiento UPS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 40px auto;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            box-shadow: 0 0 5px rgba(142, 68, 173, 0.3);
            border-color: #8e44ad;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 500;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            border: none;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        label {
            color: #34495e;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }

        .form-group {
            flex: 0 0 50%;
            padding: 0 10px;
        }

        @media (max-width: 768px) {
            .form-group {
                flex: 0 0 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Crear Nuevo Registro</h2>
        
        <?php if($message): ?>
        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Patrimonio</label>
                    <input type="text" name="patrimonio" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Cadena</label>
                    <input type="text" name="cadena" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Sucursal</label>
                    <input type="text" name="sucursal" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" name="marca" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Tipo de Batería</label>
                    <input type="text" name="tipo_bateria" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Potencia UPS</label>
                    <input type="text" name="potencia_ups" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Fecha Último Mantenimiento</label>
                    <input type="date" id="fecha_ultimo_mantenimiento" name="fecha_ultimo_mantenimiento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Fecha Próximo Mantenimiento</label>
                    <input type="date" id="fecha_proximo_mantenimiento" name="fecha_proximo_mantenimiento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Estado del Mantenimiento</label>
                    <select name="estado_mantenimiento" class="form-control" style="padding: 0.375rem 0.75rem;">
                        <option value="Pendiente" style="color: #000000;">Pendiente</option>
                        <option value="Realizado" style="color: #000000;">Realizado</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="flex: 0 0 100%;">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"></textarea>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Guardar
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#fecha_ultimo_mantenimiento').on('change', function() {
            let fechaUltimo = new Date($(this).val());
            fechaUltimo.setFullYear(fechaUltimo.getFullYear() + 2);
            
            // Formatear la fecha para el input
            let mes = (fechaUltimo.getMonth() + 1).toString().padStart(2, '0');
            let dia = fechaUltimo.getDate().toString().padStart(2, '0');
            let fechaProximo = fechaUltimo.getFullYear() + '-' + mes + '-' + dia;
            
            $('#fecha_proximo_mantenimiento').val(fechaProximo);
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>