<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/InformeTecnico.php';

// Verificar que se recibió un ID
if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$informe = new InformeTecnico($db);

// Obtener el informe con el ID proporcionado
$informeData = $informe->obtenerUno($_GET['id']);

if(!$informeData) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Informe Técnico</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .card {
            max-width: 800px; /* Aumentado de 600px a 800px */
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(to right, #3498db, #8e44ad);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            text-align: center;
        }
        .card-body {
            padding: 20px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            width: 180px;
            font-weight: 500;
        }
        .info-value {
            flex: 1;
        }
        .observaciones-title {
            margin: 30px 20px 15px 20px;
            font-size: 1.2rem;
        }
        .observaciones-content {
            margin: 0 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            line-height: 1.6;
        }
        .firma-section {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .firma-container {
            border: 1px solid #ddd;
            padding: 15px;
            display: inline-block;
            margin-top: 10px;
            min-width: 300px;
            min-height: 150px;
            background-color: #f8f9fa;
        }
        .firma-container img {
            max-width: 100%;
            height: auto;
        }
        .action-buttons {
            margin-top: 30px;
            text-align: center;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h2>Detalles del Informe Técnico</h2>
        </div>
        <div class="card-body">
            <div class="info-row">
                <div class="info-label">Local:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['local']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Sector:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['sector']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Equipo con Problema:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['equipo_asistido']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Orden de Trabajo:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['orden_trabajo']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Patrimonio:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['patrimonio']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Jefe de Turno:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['jefe_turno']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Técnico:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['nombre_tecnico']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha:</div>
                <div class="info-value"><?php echo htmlspecialchars($informeData['fecha_creacion']); ?></div>
            </div>
        </div>

            <h4 class="observaciones-title">Observaciones del Trabajo Realizado</h4>
            <div class="observaciones-content">
                <?php echo nl2br(htmlspecialchars($informeData['observaciones'])); ?>
            </div>

            <?php if(!empty($informeData['firma_digital'])): ?>
            <div class="firma-section" style="margin: 30px 20px;">
                <h4>Firma Digital del Jefe de Turno</h4>
                <div class="firma-container" style="margin: 20px auto; text-align: center;">
                    <img src="<?php echo $informeData['firma_digital']; ?>" 
                         alt="Firma Digital del Jefe de Turno" 
                         style="max-width: 300px; padding: 15px;">
                    <div style="margin-top: 10px; color: #666;">Firma de conformidad del trabajo realizado</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!empty($informeData['foto_trabajo'])): ?>
            <div class="mt-4">
                <h4 class="observaciones-title">Foto del Trabajo Realizado</h4>
                <div class="text-center" style="margin: 20px;">
                    <img src="data:image/jpeg;base64,<?php echo $informeData['foto_trabajo']; ?>" 
                         class="img-fluid" 
                         style="max-height: 400px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                </div>
            </div>
            <?php endif; ?>

            <div class="action-buttons" style="margin-top: 40px;">
                <form action="generar_pdf.php" method="GET" style="display: inline;">
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-pdf mr-2"></i>Descargar PDF
                    </button>
                </form>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
    </div>
</body>
</html>