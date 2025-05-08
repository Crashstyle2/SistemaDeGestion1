<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/MantenimientoUPS.php';
require_once '../../models/RegistroActividad.php';

$database = new Database();
$db = $database->getConnection();
$mantenimiento = new MantenimientoUPS($db);

// Obtener el patrimonio de la URL
$patrimonio = isset($_GET['id']) ? $_GET['id'] : die('ERROR: ID no proporcionado.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que todos los campos requeridos estén presentes
    if(empty($_POST['fecha_mantenimiento']) || empty($_POST['observaciones']) || empty($_POST['estado'])) {
        $mensaje = "Todos los campos son obligatorios";
    } else {
        $fecha_mantenimiento = $_POST['fecha_mantenimiento'];
        $observaciones = $_POST['observaciones'];
        $estado = $_POST['estado'];
        $usuario_mantenimiento = $_SESSION['user_name'];

        try {
            // Depuración: imprimir valores antes de guardar
            error_log("Intentando guardar mantenimiento - Patrimonio: $patrimonio, Fecha: $fecha_mantenimiento, Estado: $estado");
            
            if($mantenimiento->realizarMantenimiento($patrimonio, $fecha_mantenimiento, $observaciones, $estado, $usuario_mantenimiento)) {
                $registro = new RegistroActividad($db);
                $registro->registrar(
                    $_SESSION['user_id'],
                    'Mantenimiento UPS',
                    'Realizar Mantenimiento',
                    "Se realizó mantenimiento al UPS con patrimonio: {$patrimonio}"
                );
                
                header("Location: index.php");
                exit;
            } else {
                error_log("Falló al guardar mantenimiento - Patrimonio: $patrimonio");
                $mensaje = "No se pudo guardar el mantenimiento. Contacte al administrador del sistema.";
            }
        } catch(Exception $e) {
            error_log("Error en realizar_mantenimiento.php - Detalles: " . $e->getMessage());
            error_log("Datos del mantenimiento - Patrimonio: $patrimonio, Fecha: $fecha_mantenimiento, Estado: $estado");
            $mensaje = "Ocurrió un error al procesar la solicitud. Detalles: " . $e->getMessage();
        }
    }
}

// Obtener datos actuales del UPS
$ups_data = $mantenimiento->leerUno($patrimonio);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Mantenimiento</title>
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
        
        textarea.form-control {
            min-height: 150px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            border: none;
        }
        
        .btn-secondary {
            background: #6c757d;
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
        
        .custom-select {
            height: calc(1.5em + 1rem + 2px);
            padding: .5rem 1rem;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
        }
        
        .custom-select:focus {
            border-color: #8e44ad;
            box-shadow: 0 0 5px rgba(142, 68, 173, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if(isset($mensaje)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>
        
        <h2>Realizar Mantenimiento - UPS <?php echo htmlspecialchars($patrimonio); ?></h2>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="form-group">
                <label>Fecha de Mantenimiento</label>
                <input type="date" name="fecha_mantenimiento" class="form-control" required
                       value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control custom-select" required>
                    <option value="">Seleccione un estado</option>
                    <option value="Realizado">Realizado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Proceso">En Proceso</option>
                </select>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    Guardar Mantenimiento
                </button>
                <a href="index.php" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>