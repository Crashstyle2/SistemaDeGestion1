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

// Corregir el nombre del método
$informe->id = $_GET['id'];
$stmt = $informe->leerUno();
$informeData = $stmt->fetch(PDO::FETCH_ASSOC);

$fotos = $informe->obtenerFotos($_GET['id']);

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
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(to right, #3498db, #2c3e50);
            color: white;
            padding: 12px;
            border-radius: 8px 8px 0 0;
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
            text-align: center;
        }
        .card-body {
            padding: 15px 20px;
        }
        .info-row {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            width: 160px;
            font-weight: 600;
            color: #2c3e50;
        }
        .info-value {
            flex: 1;
        }
        .observaciones-title {
            color: #2c3e50;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }
        .observaciones-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        .firma-section {
            text-align: center;
            margin: 20px 0;
        }
        .registro-fotografico {
            margin-top: 20px;
        }
        .foto-container {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .foto-header {
            background: #f8f9fa;
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            color: #2c3e50;
        }
        .foto-body {
            padding: 10px;
            text-align: center;
        }
        .foto-body img {
            max-height: 300px;
            width: auto;
            object-fit: contain;
        }
        .action-buttons {
            text-align: center;
            margin-top: 20px;
            padding: 10px 0;
            border-top: 1px solid #eee;
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
            <div class="firma-section text-center">
                <h5 class="mb-2">Firma Digital del Jefe de Turno</h5>
                <img src="<?php echo $informeData['firma_digital']; ?>" 
                     alt="Firma Digital" 
                     style="max-width: 200px;">
            </div>
            <?php endif; ?>

            <!-- Registro Fotográfico integrado -->
            <!-- Sección de fotos mejorada -->
            <?php if (!empty($fotos)): ?>
            <div class="registro-fotografico">
                <h4 class="observaciones-title">Registro Fotográfico</h4>
                <div class="row">
                    <?php foreach ($fotos as $foto): ?>
                    <div class="col-md-6">
                        <div class="foto-container">
                            <div class="foto-header">
                                <?php echo ucfirst($foto['tipo']); ?>
                            </div>
                            <div class="foto-body">
                                <?php if(!empty($foto['foto_ruta'])): ?>
                                    <!-- Nueva lógica para archivos -->
                                    <img src="../../img/informe_tecnicos/fotos/<?php echo htmlspecialchars($foto['foto_ruta']); ?>" 
                                         class="img-fluid" alt="Foto" 
                                         style="max-width: 100%; height: auto;">
                                <?php elseif(!empty($foto['foto'])): ?>
                                    <!-- Mantener compatibilidad con fotos Base64 existentes -->
                                    <img src="data:image/jpeg;base64,<?php echo $foto['foto']; ?>" 
                                         class="img-fluid" alt="Foto" 
                                         style="max-width: 100%; height: auto;">
                                <?php else: ?>
                                    <p class="text-muted">No hay imagen disponible</p>
                                <?php endif; ?>
                                
                                <?php if(!empty($foto['descripcion'])): ?>
                                    <p class="mt-2"><small><?php echo htmlspecialchars($foto['descripcion']); ?></small></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <form action="generar_pdf.php" method="GET" class="d-inline">
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </form>
                <a href="index.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</body>
</html>