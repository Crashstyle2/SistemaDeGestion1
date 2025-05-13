<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/AcuseRecibo.php';

$database = new Database();
$db = $database->getConnection();
$acuse = new AcuseRecibo($db);

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$acuse->id = $_GET['id'];
$stmt = $acuse->leerUno();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row) {
    header("Location: index.php");
    exit;
}

// Verificar que el usuario tenga acceso a este acuse
if($_SESSION['user_rol'] === 'tecnico' && $row['tecnico_id'] !== $_SESSION['user_id']) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Acuse de Recibo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <h2><i class="fas fa-file-alt"></i> Ver Acuse de Recibo</h2>
            </div>
            <div class="col-md-6 text-right">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a href="generar_pdf.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-file-pdf"></i> Generar PDF
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Información General</h5>
                        <table class="table">
                            <tr>
                                <th>Local:</th>
                                <td><?php echo htmlspecialchars($row['local']); ?></td>
                            </tr>
                            <tr>
                                <th>Sector:</th>
                                <td><?php echo htmlspecialchars($row['sector']); ?></td>
                            </tr>
                            <tr>
                                <th>Documento:</th>
                                <td><?php echo htmlspecialchars($row['documento']); ?></td>
                            </tr>
                            <tr>
                                <th>Jefe/Encargado:</th>
                                <td><?php echo htmlspecialchars($row['jefe_encargado']); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Técnico:</th>
                                <td><?php echo htmlspecialchars($row['nombre_tecnico']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Foto del Documento</h5>
                        <?php if($row['foto']): ?>
                            <img src="data:image/jpeg;base64,<?php echo $row['foto']; ?>" class="preview-image">
                        <?php else: ?>
                            <p class="text-muted">No hay foto disponible</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Observaciones</h5>
                        <p><?php echo nl2br(htmlspecialchars($row['observaciones'])); ?></p>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Firma Digital</h5>
                        <?php if($row['firma_digital']): ?>
                            <img src="<?php echo $row['firma_digital']; ?>" alt="Firma digital" class="firma-image">
                        <?php else: ?>
                            <p class="text-muted">No hay firma disponible</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>