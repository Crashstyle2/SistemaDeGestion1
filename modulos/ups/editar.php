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

// Obtener el ID del registro a editar
$mantenimiento->patrimonio = isset($_GET['id']) ? $_GET['id'] : die('ERROR: ID no encontrado.');

// Obtener los datos del registro
$stmt = $mantenimiento->leerUno();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if($_POST) {
    $mantenimiento->cadena = $_POST['cadena'];
    $mantenimiento->sucursal = $_POST['sucursal'];
    $mantenimiento->marca = $_POST['marca'];
    $mantenimiento->tipo_bateria = $_POST['tipo_bateria'];
    $mantenimiento->cantidad = $_POST['cantidad'];
    $mantenimiento->potencia_ups = $_POST['potencia_ups'];
    $mantenimiento->fecha_ultimo_mantenimiento = $_POST['fecha_ultimo_mantenimiento'];
    $mantenimiento->fecha_proximo_mantenimiento = $_POST['fecha_proximo_mantenimiento'];
    $mantenimiento->observaciones = $_POST['observaciones'];
    $mantenimiento->estado_mantenimiento = $_POST['estado_mantenimiento'];

    if($mantenimiento->actualizar()) {
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Registro - Sistema de Mantenimiento UPS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Sistema UPS</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h2>Editar Registro</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Patrimonio</label>
                        <input type="text" name="patrimonio" class="form-control" value="<?php echo htmlspecialchars($row['patrimonio']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Cadena</label>
                        <input type="text" name="cadena" class="form-control" value="<?php echo htmlspecialchars($row['cadena']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Sucursal</label>
                        <input type="text" name="sucursal" class="form-control" value="<?php echo htmlspecialchars($row['sucursal']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" name="marca" class="form-control" value="<?php echo htmlspecialchars($row['marca']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Batería</label>
                        <input type="text" name="tipo_bateria" class="form-control" value="<?php echo htmlspecialchars($row['tipo_bateria']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" value="<?php echo htmlspecialchars($row['cantidad']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Potencia UPS</label>
                        <input type="text" name="potencia_ups" class="form-control" value="<?php echo htmlspecialchars($row['potencia_ups']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Último Mantenimiento</label>
                        <input type="date" name="fecha_ultimo_mantenimiento" class="form-control" value="<?php echo htmlspecialchars($row['fecha_ultimo_mantenimiento']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Próximo Mantenimiento</label>
                        <input type="date" name="fecha_proximo_mantenimiento" class="form-control" value="<?php echo htmlspecialchars($row['fecha_proximo_mantenimiento']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"><?php echo htmlspecialchars($row['observaciones'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estado del Mantenimiento</label>
                        <select name="estado_mantenimiento" class="form-control">
                            <option value="Pendiente" <?php echo ($row['estado_mantenimiento'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Realizado" <?php echo ($row['estado_mantenimiento'] == 'Realizado') ? 'selected' : ''; ?>>Realizado</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="index.php" class="btn btn-secondary">Volver</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>