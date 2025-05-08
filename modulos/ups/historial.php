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

$patrimonio = isset($_GET['id']) ? $_GET['id'] : null;
$historial = $mantenimiento->obtenerHistorial($patrimonio);
$ups_info = $mantenimiento->leerUno($patrimonio);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Mantenimiento - UPS <?php echo htmlspecialchars($patrimonio); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin: 40px auto;
        }
        .history-item {
            border-left: 3px solid #2980b9;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Historial de Mantenimiento - UPS <?php echo htmlspecialchars($patrimonio); ?></h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Información del UPS</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Marca:</strong> <?php echo !empty($ups_info['marca']) ? htmlspecialchars($ups_info['marca']) : '-'; ?></p>
                        <p><strong>Potencia:</strong> <?php echo !empty($ups_info['potencia_ups']) ? htmlspecialchars($ups_info['potencia_ups']) : '-'; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Sucursal:</strong> <?php echo !empty($ups_info['sucursal']) ? htmlspecialchars($ups_info['sucursal']) : '-'; ?></p>
                        <p><strong>Tipo Batería:</strong> <?php echo !empty($ups_info['tipo_bateria']) ? htmlspecialchars($ups_info['tipo_bateria']) : '-'; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Cantidad Baterías:</strong> <?php echo !empty($ups_info['cantidad']) ? htmlspecialchars($ups_info['cantidad']) : '-'; ?></p>
                        <p><strong>Estado Actual:</strong> <?php echo !empty($ups_info['estado_mantenimiento']) ? htmlspecialchars($ups_info['estado_mantenimiento']) : '-'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="timeline">
            <?php while ($registro = $historial->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="history-item">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-primary">
                            <i class="fas fa-calendar mr-2"></i>
                            <?php echo htmlspecialchars($registro['fecha_mantenimiento']); ?>
                        </h5>
                        <span class="badge badge-<?php echo $registro['estado'] === 'Realizado' ? 'success' : 'warning'; ?>">
                            <?php echo htmlspecialchars($registro['estado']); ?>
                        </span>
                    </div>
                    <p class="mb-2">
                        <strong>Técnico:</strong> <?php echo htmlspecialchars($registro['usuario_mantenimiento']); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Observaciones:</strong><br>
                        <?php echo nl2br(htmlspecialchars($registro['observaciones'])); ?>
                    </p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>