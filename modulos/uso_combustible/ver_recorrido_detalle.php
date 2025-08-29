<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/UsoCombustible.php';

// Verificar el rol del usuario
$rol = $_SESSION['user_rol'];
$roles_permitidos = ['tecnico', 'supervisor', 'administrador', 'analista'];

if (!in_array($rol, $roles_permitidos)) {
    header("Location: ../../dashboard.php?error=sin_permisos");
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Obtener IDs de registros seleccionados
$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$ids = array_filter(array_map('intval', $ids)); // Limpiar y convertir a enteros

if (empty($ids)) {
    header('Location: ver_registros.php?error=no_ids');
    exit;
}

// Construir consulta para obtener los recorridos detallados
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$sql = "SELECT uc.*, u.nombre as nombre_usuario, 
       ucr.origen, ucr.destino, ucr.km_sucursales, ucr.comentarios_sector,
       uc.fecha_registro, ucr.id as recorrido_id, uc.estado_recorrido,
       uc.fecha_cierre, uc.cerrado_por, uc.reabierto_por, uc.fecha_reapertura,
       cerrador.nombre as nombre_cerrador, reabridor.nombre as nombre_reabridor,
       s_origen.segmento as origen_segmento, s_origen.cebe as origen_cebe, 
       s_origen.local as origen_local, s_origen.m2_neto as origen_m2_neto, 
       s_origen.localidad as origen_localidad,
       s_destino.segmento as destino_segmento, s_destino.cebe as destino_cebe,
       s_destino.local as destino_local, s_destino.m2_neto as destino_m2_neto,
       s_destino.localidad as destino_localidad
       FROM uso_combustible uc 
       LEFT JOIN usuarios u ON uc.user_id = u.id 
       LEFT JOIN usuarios cerrador ON uc.cerrado_por = cerrador.id
       LEFT JOIN usuarios reabridor ON uc.reabierto_por = reabridor.id
       LEFT JOIN uso_combustible_recorridos ucr ON uc.id = ucr.uso_combustible_id 
       LEFT JOIN sucursales s_origen ON ucr.origen = s_origen.local
       LEFT JOIN sucursales s_destino ON ucr.destino = s_destino.local
       WHERE uc.id IN ($placeholders)
       ORDER BY uc.fecha_registro DESC, ucr.id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($ids);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar registros por uso_combustible.id
$groupedRecords = [];
foreach ($registros as $registro) {
    $combustibleId = $registro['id'];
    if (!isset($groupedRecords[$combustibleId])) {
        $groupedRecords[$combustibleId] = [
            'combustible' => $registro,
            'recorridos' => []
        ];
    }
    if (!empty($registro['recorrido_id'])) {
        $groupedRecords[$combustibleId]['recorridos'][] = $registro;
    }
}

$rol = $_SESSION['user_rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Recorridos - Mantenimiento UPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header personalizado para detalle de recorridos -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="ver_registros.php">
                <i class="fas fa-route mr-2"></i>Detalle de Recorridos
            </a>
            <div class="navbar-nav ml-auto">
                <a href="ver_registros.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Listado
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <!-- Sección de exportación -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-download mr-2"></i>Exportar Datos</h5>
                <form method="POST" action="ver_registros.php">
                    <input type="hidden" name="export" value="excel">
                    <input type="hidden" name="export_type" value="selected">
                    <input type="hidden" name="selected_ids" value="<?php echo implode(',', $ids); ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-excel mr-2"></i>Descargar Excel de estos recorridos
                    </button>
                </form>
            </div>
        </div>

        <?php if (empty($groupedRecords)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                No se encontraron recorridos para los IDs especificados.
            </div>
        <?php else: ?>
            <?php foreach ($groupedRecords as $combustibleId => $data): 
                $combustible = $data['combustible'];
                $recorridos = $data['recorridos'];
            ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1">
                                    <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($combustible['nombre_conductor']); ?>
                                </h4>
                                <p class="mb-0">
                                    <i class="fas fa-car mr-2"></i><?php echo htmlspecialchars($combustible['chapa']); ?> - 
                                    <?php echo ucfirst(str_replace('_', ' ', $combustible['tipo_vehiculo'] ?? '')); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge <?php echo $combustible['estado_recorrido'] === 'abierto' ? 'badge-success' : 'badge-danger'; ?> badge-lg">
                                    <?php echo ucfirst($combustible['estado_recorrido']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <strong>Fecha de Carga:</strong><br>
                                <span class="text-muted"><?php echo date('d/m/Y H:i', strtotime($combustible['fecha_carga'] . ' ' . $combustible['hora_carga'])); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Nº Voucher:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($combustible['numero_baucher']); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Nº Tarjeta:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($combustible['tarjeta']); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Litros Cargados:</strong><br>
                                <span class="text-muted"><?php echo number_format($combustible['litros_cargados'], 0); ?> L</span>
                            </div>
                        </div>

                        <!-- Sección de Voucher -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-toggle="collapse" data-target="#voucher-<?php echo $combustible['id']; ?>" aria-expanded="false" aria-controls="voucher-<?php echo $combustible['id']; ?>">
                                    <i class="fas fa-receipt mr-2"></i>Ver Voucher
                                </button>
                                <div class="collapse mt-3" id="voucher-<?php echo $combustible['id']; ?>">
                                    <?php if (!empty($combustible['foto_voucher_ruta'])): ?>
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><i class="fas fa-image mr-2"></i>Imagen del Voucher</h6>
                                                <a href="../../img/uso_combustible/vouchers/<?php echo htmlspecialchars($combustible['foto_voucher_ruta']); ?>" 
                                                   download="voucher_<?php echo htmlspecialchars($combustible['numero_baucher']); ?>.<?php echo pathinfo($combustible['foto_voucher_ruta'], PATHINFO_EXTENSION); ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-download mr-2"></i>Descargar
                                                </a>
                                            </div>
                                            <div class="card-body text-center">
                                                <img src="../../img/uso_combustible/vouchers/<?php echo htmlspecialchars($combustible['foto_voucher_ruta']); ?>" 
                                                     alt="Voucher <?php echo htmlspecialchars($combustible['numero_baucher']); ?>" 
                                                     class="img-fluid" 
                                                     style="max-height: 500px; cursor: pointer;" 
                                                     onclick="openImageModal('../../img/uso_combustible/vouchers/<?php echo htmlspecialchars($combustible['foto_voucher_ruta']); ?>', 'Voucher <?php echo htmlspecialchars($combustible['numero_baucher']); ?>')">
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>No hay imagen de voucher disponible para este registro.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($recorridos)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Origen</th>
                                            <th>Destino</th>
                                            <th>CEBE Destino</th>
                                            <th>KM Entre Sucursales</th>
                                            <th>Comentarios</th>
                                            <th>Fecha Registro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recorridos as $recorrido): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($recorrido['origen']); ?>
                                                    <?php if (!empty($recorrido['origen_localidad'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($recorrido['origen_localidad']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($recorrido['destino']); ?>
                                                    <?php if (!empty($recorrido['destino_localidad'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($recorrido['destino_localidad']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($recorrido['destino_cebe'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($recorrido['km_sucursales'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($recorrido['comentarios_sector'] ?? '-'); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($recorrido['fecha_registro'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle mr-2"></i>
                                No hay recorridos registrados para esta carga de combustible.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal para ver imagen en tamaño completo -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Voucher</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <a id="downloadModalBtn" href="" download="" class="btn btn-success">
                        <i class="fas fa-download mr-2"></i>Descargar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function openImageModal(imageSrc, imageTitle) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalImage').alt = imageTitle;
            document.getElementById('imageModalLabel').textContent = imageTitle;
            
            // Configurar botón de descarga
            const downloadBtn = document.getElementById('downloadModalBtn');
            downloadBtn.href = imageSrc;
            
            // Extraer nombre del archivo para la descarga
            const fileName = imageSrc.split('/').pop();
            downloadBtn.download = fileName;
            
            $('#imageModal').modal('show');
        }
        
        // Mejorar la experiencia de usuario con animaciones
        $(document).ready(function() {
            // Animar el botón de voucher al hacer hover
            $('.btn-outline-primary').hover(
                function() {
                    $(this).addClass('shadow-sm');
                },
                function() {
                    $(this).removeClass('shadow-sm');
                }
            );
            
            // Cambiar texto del botón cuando se expande/colapsa
            $('[data-toggle="collapse"]').on('click', function() {
                const target = $(this).attr('data-target');
                const isExpanded = $(target).hasClass('show');
                
                if (isExpanded) {
                    $(this).html('<i class="fas fa-receipt mr-2"></i>Ver Voucher');
                } else {
                    $(this).html('<i class="fas fa-eye-slash mr-2"></i>Ocultar Voucher');
                }
            });
        });
    </script>
</body>
</html>